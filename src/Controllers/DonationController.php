<?php

declare(strict_types=1);

namespace CoreFly\Controllers;

use CoreFly\Models\Donation;
use CoreFly\Models\Campaign;
use CoreFly\Models\Donor;
use CoreFly\Utils\Database;

class DonationController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index(): string
    {
        try {
            $this->requirePermission('donations:read');
            $tenantId = $this->getCurrentTenantId();
            
            $page = (int)($this->getQueryParam('page', 1));
            $limit = (int)($this->getQueryParam('limit', 20));
            $offset = ($page - 1) * $limit;
            
            $search = $this->getQueryParam('search', '');
            $campaignId = $this->getQueryParam('campaign_id', '');
            $paymentStatus = $this->getQueryParam('payment_status', '');
            $dateFrom = $this->getQueryParam('date_from', '');
            $dateTo = $this->getQueryParam('date_to', '');
            
            $query = Donation::where('tenant_id', $tenantId);
            
            if (!empty($search)) {
                $query->where('(donor_name LIKE :search OR donor_email LIKE :search OR transaction_id LIKE :search)');
                $query->setBindings(['search' => "%{$search}%"]);
            }
            
            if (!empty($campaignId)) {
                $query->where('campaign_id', $campaignId);
            }
            
            if (!empty($paymentStatus)) {
                $query->where('payment_status', $paymentStatus);
            }
            
            if (!empty($dateFrom)) {
                $query->where('created_at', '>=', $dateFrom);
            }
            
            if (!empty($dateTo)) {
                $query->where('created_at', '<=', $dateTo);
            }
            
            $total = $query->count();
            $donations = $query->orderBy('created_at', 'DESC')->limit($limit)->offset($offset)->get();
            
            return $this->successResponse([
                'donations' => $donations,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'pages' => ceil($total / $limit)
                ]
            ], 'Bağış listesi getirildi');
        } catch (\Throwable $e) {
            return $this->errorResponse('Donation Error: ' . $e->getMessage(), 500);
        }
    }

    public function show(string $id): string
    {
        try {
            $this->requirePermission('donations:read');
            $tenantId = $this->getCurrentTenantId();
            
            $donation = Donation::find($id);
            
            if (!$donation || $donation->tenant_id !== $tenantId) {
                return $this->errorResponse('Bağış bulunamadı', 404);
            }
            
            $campaign = $donation->getCampaign();
            
            return $this->successResponse([
                'donation' => $donation,
                'campaign' => $campaign
            ], 'Bağış detayı getirildi');
        } catch (\Throwable $e) {
            return $this->errorResponse('Donation Error: ' . $e->getMessage(), 500);
        }
    }

    public function store(): string
    {
        try {
            $this->requirePermission('donations:create');
            $tenantId = $this->getCurrentTenantId();
            $userId = $this->getCurrentUserId();
            
            $data = $this->getJsonInput();
            
            // Auto-fill from Donor ID
            if (!empty($data['donor_id'])) {
                $donor = Donor::find($data['donor_id']);
                if ($donor) {
                    $data['donor_name'] = $donor->first_name . ' ' . $donor->last_name;
                    $data['donor_email'] = $donor->email;
                    $data['donor_phone'] = $donor->phone;
                    $data['donor_type'] = $donor->type;
                }
            }

            // Auto-fill from Campaign ID
            if (!empty($data['campaign_id'])) {
                $campaign = Campaign::find($data['campaign_id']);
                if ($campaign) {
                    $data['campaign_name'] = $campaign->name;
                }
            }

            $requiredFields = ['donor_name', 'donor_email', 'amount', 'payment_method'];
            foreach ($requiredFields as $field) {
                if (empty($data[$field])) {
                    return $this->errorResponse("{$field} alanı zorunludur", 400);
                }
            }
            
            $donation = new Donation([
                'id' => $this->generateUUID(),
                'tenant_id' => $tenantId,
                'donor_name' => $data['donor_name'],
                'donor_email' => $data['donor_email'],
                'donor_id' => $data['donor_id'] ?? '',
                'donor_phone' => $data['donor_phone'] ?? '',
                'donor_address' => $data['donor_address'] ?? '',
                'donor_type' => $data['donor_type'] ?? 'individual',
                'campaign_id' => $data['campaign_id'] ?? '',
                'campaign_name' => $data['campaign_name'] ?? '',
                'amount' => (float)$data['amount'],
                'currency' => $data['currency'] ?? 'TRY',
                'payment_method' => $data['payment_method'],
                'payment_status' => 'pending',
                'donation_type' => $data['donation_type'] ?? 'one-time',
                'purpose' => $data['purpose'] ?? '',
                'notes' => $data['notes'] ?? '',
                'is_anonymous' => (bool)($data['is_anonymous'] ?? false),
                'is_recurring' => (bool)($data['is_recurring'] ?? false),
                'recurring_frequency' => $data['recurring_frequency'] ?? '',
                'recurring_end_date' => $data['recurring_end_date'] ?? '',
                'status' => 'pending',
                'created_by' => $userId,
                'updated_by' => $userId,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            
            if ($donation->save()) {
                $this->logActivity('donation_created', 'donation', $donation->id, [
                    'donor_name' => $donation->donor_name,
                    'amount' => $donation->amount
                ]);
                return $this->successResponse($donation, 'Bağış oluşturuldu', 201);
            }
            
            return $this->errorResponse('Bağış oluşturulamadı', 500);
        } catch (\Throwable $e) {
            return $this->errorResponse('Donation Error: ' . $e->getMessage(), 500);
        }
    }

    public function update(string $id): string
    {
        try {
            $this->requirePermission('donations:update');
            $tenantId = $this->getCurrentTenantId();
            $userId = $this->getCurrentUserId();
            
            $donation = Donation::find($id);
            
            if (!$donation || $donation->tenant_id !== $tenantId) {
                return $this->errorResponse('Bağış bulunamadı', 404);
            }
            
            $data = $this->getJsonInput();
            
            // Allow updating basic fields
            $fillable = ['donor_name', 'donor_email', 'donor_phone', 'amount', 'currency', 'payment_method', 'donation_type', 'purpose', 'notes', 'is_anonymous', 'campaign_id'];
            
            foreach ($fillable as $field) {
                if (isset($data[$field])) {
                    $donation->$field = $data[$field];
                }
            }
            
            // Handle Campaign Name update if campaign_id changed
            if (isset($data['campaign_id']) && $data['campaign_id'] !== $donation->campaign_id) {
                 $campaign = Campaign::find($data['campaign_id']);
                 if ($campaign) {
                     $donation->campaign_name = $campaign->name;
                 }
            }

            $donation->updated_by = $userId;
            $donation->updated_at = date('Y-m-d H:i:s');
            
            if ($donation->save()) {
                $this->logActivity('donation_updated', 'donation', $donation->id, [
                    'amount' => $donation->amount
                ]);
                return $this->successResponse($donation, 'Bağış güncellendi');
            }
            
            return $this->errorResponse('Bağış güncellenemedi', 500);
        } catch (\Throwable $e) {
            return $this->errorResponse('Donation Error: ' . $e->getMessage(), 500);
        }
    }

    public function destroy(string $id): string
    {
        try {
            $this->requirePermission('donations:delete');
            $tenantId = $this->getCurrentTenantId();
            
            $donation = Donation::find($id);
            
            if (!$donation || $donation->tenant_id !== $tenantId) {
                return $this->errorResponse('Bağış bulunamadı', 404);
            }
            
            if ($donation->delete()) {
                $this->logActivity('donation_deleted', 'donation', $donation->id, [
                    'donor_name' => $donation->donor_name
                ]);
                return $this->successResponse(null, 'Bağış silindi');
            }
            
            return $this->errorResponse('Bağış silinemedi', 500);
        } catch (\Throwable $e) {
            return $this->errorResponse('Donation Error: ' . $e->getMessage(), 500);
        }
    }

    public function updatePaymentStatus(string $id): string
    {
        try {
            $this->requirePermission('donations:update');
            $tenantId = $this->getCurrentTenantId();
            $userId = $this->getCurrentUserId();
            
            $donation = Donation::find($id);
            
            if (!$donation || $donation->tenant_id !== $tenantId) {
                return $this->errorResponse('Bağış bulunamadı', 404);
            }
            
            $data = $this->getJsonInput();
            
            if (!isset($data['payment_status'])) {
                return $this->errorResponse('Ödeme durumu zorunludur', 400);
            }
            
            $oldStatus = $donation->payment_status;
            $donation->payment_status = $data['payment_status'];
            
            if (isset($data['transaction_id'])) {
                $donation->transaction_id = $data['transaction_id'];
            }
            
            if ($data['payment_status'] === 'completed') {
                $donation->status = 'completed';
                $donation->processed_by = $userId;
                
                if (!empty($donation->campaign_id)) {
                    $campaign = Campaign::find($donation->campaign_id);
                    if ($campaign) {
                        $campaign->raised_amount += $donation->amount;
                        $campaign->save();
                    }
                }
            }
            
            $donation->updated_by = $userId;
            $donation->updated_at = date('Y-m-d H:i:s');
            
            if ($donation->save()) {
                $this->logActivity('donation_payment_status_updated', 'donation', $donation->id, [
                    'old_status' => $oldStatus,
                    'new_status' => $donation->payment_status
                ]);
                return $this->successResponse($donation, 'Bağış ödeme durumu güncellendi');
            }
            
            return $this->errorResponse('Bağış güncellenemedi', 500);
        } catch (\Throwable $e) {
            return $this->errorResponse('Donation Error: ' . $e->getMessage(), 500);
        }
    }

    public function sendAcknowledgment(string $id): string
    {
        try {
            $this->requirePermission('donations:update');
            $tenantId = $this->getCurrentTenantId();
            
            $donation = Donation::find($id);
            
            if (!$donation || $donation->tenant_id !== $tenantId) {
                return $this->errorResponse('Bağış bulunamadı', 404);
            }
            
            if ($donation->acknowledgment_sent) {
                return $this->errorResponse('Teşekkür mesajı zaten gönderildi', 400);
            }
            
            if ($donation->sendAcknowledgment()) {
                $this->logActivity('donation_acknowledgment_sent', 'donation', $donation->id, [
                    'donor_name' => $donation->donor_name
                ]);
                return $this->successResponse(null, 'Teşekkür mesajı gönderildi');
            }
            
            return $this->errorResponse('Teşekkür mesajı gönderilemedi', 500);
        } catch (\Throwable $e) {
            return $this->errorResponse('Donation Error: ' . $e->getMessage(), 500);
        }
    }

    public function sendTaxReceipt(string $id): string
    {
        try {
            $this->requirePermission('donations:update');
            $tenantId = $this->getCurrentTenantId();
            
            $donation = Donation::find($id);
            
            if (!$donation || $donation->tenant_id !== $tenantId) {
                return $this->errorResponse('Bağış bulunamadı', 404);
            }
            
            if ($donation->tax_receipt_sent) {
                return $this->errorResponse('Vergi makbuzu zaten gönderildi', 400);
            }
            
            if ($donation->sendTaxReceipt()) {
                $this->logActivity('donation_tax_receipt_sent', 'donation', $donation->id, [
                    'donor_name' => $donation->donor_name,
                    'receipt_number' => $donation->tax_receipt_number
                ]);
                return $this->successResponse(null, 'Vergi makbuzu gönderildi');
            }
            
            return $this->errorResponse('Vergi makbuzu gönderilemedi', 500);
        } catch (\Throwable $e) {
            return $this->errorResponse('Donation Error: ' . $e->getMessage(), 500);
        }
    }

    public function getDonorStats(): string
    {
        try {
            $this->requirePermission('donations:read');
            $tenantId = $this->getCurrentTenantId();
            
            $email = $this->getQueryParam('email', '');
            
            if (empty($email)) {
                return $this->errorResponse('Email adresi zorunludur', 400);
            }
            
            $donation = new Donation();
            $stats = $donation->getDonorStats($tenantId, $email);
            
            return $this->successResponse($stats, 'Bağışçı istatistikleri getirildi');
        } catch (\Throwable $e) {
            return $this->errorResponse('Donation Error: ' . $e->getMessage(), 500);
        }
    }

    public function getCampaignStats(string $campaignId): string
    {
        try {
            $this->requirePermission('donations:read');
            $tenantId = $this->getCurrentTenantId();
            
            $campaign = Campaign::find($campaignId);
            
            if (!$campaign || $campaign->tenant_id !== $tenantId) {
                return $this->errorResponse('Kampanya bulunamadı', 404);
            }
            
            $donation = new Donation();
            $stats = $donation->getCampaignStats($tenantId, $campaignId);
            
            return $this->successResponse($stats, 'Kampanya bağış istatistikleri getirildi');
        } catch (\Throwable $e) {
            return $this->errorResponse('Donation Error: ' . $e->getMessage(), 500);
        }
    }

    public function getMonthlyStats(): string
    {
        try {
            $this->requirePermission('donations:read');
            $tenantId = $this->getCurrentTenantId();
            
            $year = (int)($this->getQueryParam('year', date('Y')));
            
            $donation = new Donation();
            $stats = $donation->getMonthlyStats($tenantId, $year);
            
            return $this->successResponse($stats, 'Aylık bağış istatistikleri getirildi');
        } catch (\Throwable $e) {
            return $this->errorResponse('Donation Error: ' . $e->getMessage(), 500);
        }
    }

    public function getStatistics(): string
    {
        try {
            $this->requirePermission('donations:read');
            $tenantId = $this->getCurrentTenantId();
            
            $db = \CoreFly\Utils\Database::getInstance();
            
            $stmt = $db->prepare("
                SELECT 
                    COUNT(*) as total_donations,
                    COUNT(DISTINCT donor_email) as unique_donors,
                    SUM(CASE WHEN payment_status = 'completed' THEN amount ELSE 0 END) as total_amount,
                    AVG(CASE WHEN payment_status = 'completed' THEN amount ELSE NULL END) as avg_amount,
                    SUM(CASE WHEN is_recurring = 1 THEN amount ELSE 0 END) as recurring_amount,
                    COUNT(CASE WHEN is_recurring = 1 THEN 1 ELSE NULL END) as recurring_count
                FROM donations
                WHERE tenant_id = :tenant_id
            ");
            
            $stmt->bindParam(':tenant_id', $tenantId);
            $stmt->execute();
            
            $overallStats = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            $stmt = $db->prepare("
                SELECT 
                    payment_status,
                    COUNT(*) as count,
                    SUM(amount) as total_amount
                FROM donations
                WHERE tenant_id = :tenant_id
                GROUP BY payment_status
            ");
            
            $stmt->bindParam(':tenant_id', $tenantId);
            $stmt->execute();
            
            $statusStats = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            $currentMonth = date('Y-m');
            $stmt = $db->prepare("
                SELECT 
                    SUM(amount) as monthly_total,
                    COUNT(*) as monthly_count
                FROM donations
                WHERE tenant_id = :tenant_id AND strftime('%Y-%m', created_at) = :month AND payment_status = 'completed'
            ");
            
            $stmt->bindParam(':tenant_id', $tenantId);
            $stmt->bindParam(':month', $currentMonth);
            $stmt->execute();
            
            $monthlyStats = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            return $this->successResponse([
                'overall' => $overallStats,
                'by_status' => $statusStats,
                'current_month' => $monthlyStats
            ], 'Bağış istatistikleri getirildi');
        } catch (\Throwable $e) {
            return $this->errorResponse('Donation Error: ' . $e->getMessage(), 500);
        }
    }

    // --- Campaign Management ---

    public function getCampaigns(): string
    {
        try {
            $this->requirePermission('donations:read');
            $tenantId = $this->getCurrentTenantId();
            $campaigns = Campaign::where('tenant_id', $tenantId)->orderBy('created_at', 'DESC')->get();
            return $this->successResponse($campaigns, 'Kampanyalar getirildi');
        } catch (\Throwable $e) {
            return $this->errorResponse('Campaign Error: ' . $e->getMessage(), 500);
        }
    }

    public function storeCampaign(): string
    {
        $this->requirePermission('donations:create');
        $tenantId = $this->getCurrentTenantId();
        $userId = $this->getCurrentUserId();
        $data = $this->getJsonInput();

        $this->validateRequired($data, ['name', 'target_amount']);

        $campaign = new Campaign([
            'id' => $this->generateUUID(),
            'tenant_id' => $tenantId,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'type' => $data['type'] ?? 'general',
            'target_amount' => (float)$data['target_amount'],
            'start_date' => $data['start_date'] ?? null,
            'end_date' => $data['end_date'] ?? null,
            'status' => $data['status'] ?? 'active',
            'created_by' => $userId,
            'updated_by' => $userId
        ]);

        if ($campaign->save()) {
            $this->logActivity('campaign_created', 'donation', $campaign->id, ['name' => $campaign->name]);
            return $this->successResponse($campaign, 'Kampanya oluşturuldu', 201);
        }

        return $this->errorResponse('Kampanya oluşturulamadı', 500);
    }

    public function updateCampaign(string $id): string
    {
        $this->requirePermission('donations:update');
        $tenantId = $this->getCurrentTenantId();
        $campaign = Campaign::find($id);

        if (!$campaign || $campaign->tenant_id !== $tenantId) {
            return $this->errorResponse('Kampanya bulunamadı', 404);
        }

        $data = $this->getJsonInput();
        $campaign->fill($data);
        $campaign->updated_by = $this->getCurrentUserId();

        if ($campaign->save()) {
            return $this->successResponse($campaign, 'Kampanya güncellendi');
        }

        return $this->errorResponse('Kampanya güncellenemedi', 500);
    }

    public function destroyCampaign(string $id): string
    {
        $this->requirePermission('donations:delete');
        $tenantId = $this->getCurrentTenantId();
        $campaign = Campaign::find($id);

        if (!$campaign || $campaign->tenant_id !== $tenantId) {
            return $this->errorResponse('Kampanya bulunamadı', 404);
        }

        if ($campaign->delete()) {
            return $this->successResponse(null, 'Kampanya silindi');
        }

        return $this->errorResponse('Kampanya silinemedi', 500);
    }

    // --- Donor Management ---

    public function getDonors(): string
    {
        $this->requirePermission('donations:read');
        $tenantId = $this->getCurrentTenantId();
        $donors = Donor::where('tenant_id', $tenantId)->orderBy('created_at', 'DESC')->get();
        return $this->successResponse($donors, 'Bağışçılar getirildi');
    }

    public function storeDonor(): string
    {
        $this->requirePermission('donations:create');
        $tenantId = $this->getCurrentTenantId();
        $userId = $this->getCurrentUserId();
        $data = $this->getJsonInput();

        $this->validateRequired($data, ['first_name', 'last_name']);

        $donor = new Donor([
            'id' => $this->generateUUID(),
            'tenant_id' => $tenantId,
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'address' => $data['address'] ?? null,
            'city' => $data['city'] ?? null,
            'country' => $data['country'] ?? null,
            'type' => $data['type'] ?? 'individual',
            'tax_id' => $data['tax_id'] ?? null,
            'company_name' => $data['company_name'] ?? null,
            'notes' => $data['notes'] ?? null,
            'created_by' => $userId,
            'updated_by' => $userId
        ]);

        if ($donor->save()) {
            return $this->successResponse($donor, 'Bağışçı oluşturuldu', 201);
        }

        return $this->errorResponse('Bağışçı oluşturulamadı', 500);
    }

    public function updateDonor(string $id): string
    {
        $this->requirePermission('donations:update');
        $tenantId = $this->getCurrentTenantId();
        $donor = Donor::find($id);

        if (!$donor || $donor->tenant_id !== $tenantId) {
            return $this->errorResponse('Bağışçı bulunamadı', 404);
        }

        $data = $this->getJsonInput();
        $donor->fill($data);
        $donor->updated_by = $this->getCurrentUserId();

        if ($donor->save()) {
            return $this->successResponse($donor, 'Bağışçı güncellendi');
        }

        return $this->errorResponse('Bağışçı güncellenemedi', 500);
    }

    public function destroyDonor(string $id): string
    {
        $this->requirePermission('donations:delete');
        $tenantId = $this->getCurrentTenantId();
        $donor = Donor::find($id);

        if (!$donor || $donor->tenant_id !== $tenantId) {
            return $this->errorResponse('Bağışçı bulunamadı', 404);
        }

        if ($donor->delete()) {
            return $this->successResponse(null, 'Bağışçı silindi');
        }

        return $this->errorResponse('Bağışçı silinemedi', 500);
    }
}
