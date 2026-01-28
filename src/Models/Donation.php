<?php

declare(strict_types=1);

namespace CoreFly\Models;

use CoreFly\Models\BaseModel;

class Donation extends BaseModel
{
    protected string $table = 'donations';
    
    public string $id;
    public string $tenant_id;
    public ?string $donor_id;
    public string $donor_name;
    public ?string $donor_email = null;
    public ?string $donor_phone = null;
    public ?string $donor_address = null;
    public ?string $donor_type = null;
    public ?string $campaign_id = null;
    public ?string $campaign_name = null;
    public float $amount = 0.0;
    public ?string $currency = null;
    public ?string $payment_method = null;
    public ?string $payment_status = null;
    public ?string $transaction_id = null;
    public ?string $donation_type = null;
    public ?string $purpose = null;
    public ?string $notes = null;
    public $is_anonymous = false; // Removed strict bool type
    public $is_recurring = false; // Removed strict bool type
    public ?string $recurring_frequency = null;
    public ?string $recurring_end_date = null;
    public ?string $tax_receipt_number = null;
    public $tax_receipt_sent = false; // Removed strict bool type
    public ?string $acknowledgment_sent = null;
    public ?string $status = null;
    public ?string $processed_by = null;
    public ?string $created_by = null;
    public ?string $updated_by = null;

    public function getCampaign(): ?Campaign
    {
        if (empty($this->campaign_id)) {
            return null;
        }
        return Campaign::find($this->campaign_id);
    }

    public static function getRecurringDonations(string $tenantId): array
    {
        return self::where('tenant_id', $tenantId)
            ->where('is_recurring', true)
            ->where('status', 'active')
            ->orderBy('created_at', 'DESC')
            ->get();
    }

    public static function getDonationsByDateRange(string $tenantId, string $startDate, string $endDate): array
    {
        return self::where('tenant_id', $tenantId)
            ->where('created_at', '>=', $startDate)
            ->where('created_at', '<=', $endDate)
            ->where('payment_status', 'completed')
            ->orderBy('created_at', 'DESC')
            ->get();
    }

    public function getDonorStats(string $tenantId, string $donorEmail): array
    {
        $db = \CoreFly\Utils\Database::getInstance();
        $stmt = $db->prepare("
            SELECT 
                COUNT(*) as total_donations,
                SUM(amount) as total_amount,
                MIN(created_at) as first_donation,
                MAX(created_at) as last_donation,
                AVG(amount) as avg_amount
            FROM donations
            WHERE tenant_id = :tenant_id AND donor_email = :donor_email AND payment_status = 'completed'
        ");

        $stmt->bindParam(':tenant_id', $tenantId);
        $stmt->bindParam(':donor_email', $donorEmail);
        $stmt->execute();
        
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function getCampaignStats(string $tenantId, string $campaignId): array
    {
        $db = \CoreFly\Utils\Database::getInstance();
        $stmt = $db->prepare("
            SELECT 
                COUNT(*) as total_donations,
                COUNT(DISTINCT donor_email) as unique_donors,
                SUM(amount) as total_amount,
                AVG(amount) as avg_amount,
                MAX(amount) as max_amount,
                MIN(amount) as min_amount
            FROM donations
            WHERE tenant_id = :tenant_id AND campaign_id = :campaign_id AND payment_status = 'completed'
        ");

        $stmt->bindParam(':tenant_id', $tenantId);
        $stmt->bindParam(':campaign_id', $campaignId);
        $stmt->execute();
        
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function getMonthlyStats(string $tenantId, int $year): array
    {
        $db = \CoreFly\Utils\Database::getInstance();
        // SQLite does not support MONTH() or YEAR(). Use strftime.
        // %m returns 01-12. %Y returns YYYY.
        // For month name, SQLite doesn't have MONTHNAME. We'll select the month number and handle naming in application if needed, or just return the number.
        // We can group by strftime('%m', created_at).
        
        $stmt = $db->prepare("
            SELECT 
                strftime('%m', created_at) as month,
                COUNT(*) as donation_count,
                SUM(amount) as total_amount,
                AVG(amount) as avg_amount
            FROM donations
            WHERE tenant_id = :tenant_id AND strftime('%Y', created_at) = :year AND payment_status = 'completed'
            GROUP BY strftime('%m', created_at)
            ORDER BY month
        ");
        
        // Need to bind :year as string because strftime returns string
        $yearStr = (string)$year;

        $stmt->bindParam(':tenant_id', $tenantId);
        $stmt->bindParam(':year', $yearStr);
        $stmt->execute();
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function sendAcknowledgment(): bool
    {
        if ($this->acknowledgment_sent) {
            return true;
        }

        $this->acknowledgment_sent = date('Y-m-d H:i:s');
        return $this->save();
    }

    public function sendTaxReceipt(): bool
    {
        if ($this->tax_receipt_sent) {
            return true;
        }

        $this->tax_receipt_sent = true;
        $this->tax_receipt_number = $this->generateTaxReceiptNumber();
        return $this->save();
    }

    private function generateTaxReceiptNumber(): string
    {
        $year = date('Y');
        $sequence = substr(str_pad((string)rand(1, 99999), 5, '0', STR_PAD_LEFT), -5);
        return "TR{$year}{$sequence}";
    }

    public function processRecurringPayment(): bool
    {
        if (!$this->is_recurring || $this->payment_status !== 'completed') {
            return false;
        }

        if (!empty($this->recurring_end_date) && strtotime($this->recurring_end_date) < time()) {
            $this->status = 'completed';
            return $this->save();
        }

        $newDonation = new Donation([
            'id' => $this->generateUUID(),
            'tenant_id' => $this->tenant_id,
            'donor_name' => $this->donor_name,
            'donor_email' => $this->donor_email,
            'donor_phone' => $this->donor_phone,
            'donor_address' => $this->donor_address,
            'donor_type' => $this->donor_type,
            'campaign_id' => $this->campaign_id,
            'campaign_name' => $this->campaign_name,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'payment_method' => $this->payment_method,
            'payment_status' => 'pending',
            'donation_type' => $this->donation_type,
            'purpose' => $this->purpose,
            'notes' => "Recurring donation from {$this->donor_name}",
            'is_anonymous' => $this->is_anonymous,
            'is_recurring' => true,
            'recurring_frequency' => $this->recurring_frequency,
            'recurring_end_date' => $this->recurring_end_date,
            'status' => 'active',
            'created_by' => $this->created_by,
            'updated_by' => $this->created_by,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        return $newDonation->save();
    }
}