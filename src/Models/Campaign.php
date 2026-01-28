<?php

declare(strict_types=1);

namespace CoreFly\Models;

use CoreFly\Models\BaseModel;

class Campaign extends BaseModel
{
    protected string $table = 'campaigns';
    
    public string $id;
    public string $tenant_id;
    public string $name;
    public string $description;
    public string $type;
    public float $target_amount = 0.0;
    public float $raised_amount = 0.0;
    public string $currency = 'TRY';
    public string $start_date;
    public string $end_date;
    public string $status;
    public bool $is_active;
    public string $image_url;
    public string $video_url;
    public string $contact_person;
    public string $contact_email;
    public string $contact_phone;
    public string $location;
    public int $beneficiary_count;
    public string $category;
    public string $urgency_level;
    public string $visibility;
    public bool $allow_anonymous;
    public bool $allow_recurring;
    public string $created_by;
    public string $updated_by;


    public function __construct(array $data = [])
    {
        parent::__construct($data);
    }


    public function getTotalDonations(): float
    {
        $db = \CoreFly\Utils\Database::getInstance();
        $stmt = $db->prepare("
            SELECT SUM(amount) as total
            FROM donations
            WHERE campaign_id = :campaign_id AND tenant_id = :tenant_id AND payment_status = 'completed'
        ");

        $stmt->bindParam(':campaign_id', $this->id);
        $stmt->bindParam(':tenant_id', $this->tenant_id);
        $stmt->execute();
        
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return (float)($result['total'] ?? 0);
    }

    public function getDonations(): array
    {
        return Donation::where('campaign_id', $this->id)
            ->where('tenant_id', $this->tenant_id)
            ->where('payment_status', 'completed')
            ->orderBy('donation_date', 'DESC')
            ->get();
    }

    public function getUniqueDonors(): int
    {
        $db = \CoreFly\Utils\Database::getInstance();
        $stmt = $db->prepare("
            SELECT COUNT(DISTINCT donor_email) as count
            FROM donations
            WHERE campaign_id = :campaign_id AND tenant_id = :tenant_id AND payment_status = 'completed'
        ");

        $stmt->bindParam(':campaign_id', $this->id);
        $stmt->bindParam(':tenant_id', $this->tenant_id);
        $stmt->execute();
        
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return (int)($result['count'] ?? 0);
    }

    public function isOver(): bool
    {
        if (empty($this->end_date)) {
            return false;
        }
        return strtotime($this->end_date) < time();
    }

    public function isUpcoming(): bool
    {
        if (empty($this->start_date)) {
            return false;
        }
        return strtotime($this->start_date) > time();
    }

    public function isActive(): bool
    {
        return $this->is_active && 
               !$this->isOver() && 
               !$this->isUpcoming() &&
               $this->status === 'active';
    }

    public function getProgressPercentage(): int
    {
        if ($this->target_amount <= 0) {
            return 0;
        }
        
        $progress = (int)round(($this->raised_amount / $this->target_amount) * 100);
        return min($progress, 100);
    }

    public function getActiveCampaigns(string $tenantId): array
    {
        return self::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->where('status', 'active')
            ->where('start_date', '<=', date('Y-m-d'))
            ->where('end_date', '>=', date('Y-m-d'))
            ->orderBy('created_at', 'DESC')
            ->get();
    }

    public function getUpcomingCampaigns(string $tenantId): array
    {
        return self::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->where('start_date', '>', date('Y-m-d'))
            ->orderBy('start_date', 'ASC')
            ->get();
    }
}