<?php

declare(strict_types=1);

namespace CoreFly\Controllers;

use CoreFly\Models\CrmCustomer;
use CoreFly\Models\CrmDeal;
use CoreFly\Models\CrmActivity;
use CoreFly\Utils\Database;

class CrmController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
    }

    // --- Customers ---

    public function index(): string
    {
        $this->requirePermission('crm:read');
        $tenantId = $this->getCurrentTenantId();
        
        $page = (int)($this->getQueryParam('page', 1));
        $limit = (int)($this->getQueryParam('limit', 20));
        $offset = ($page - 1) * $limit;
        $search = $this->getQueryParam('search', '');
        $status = $this->getQueryParam('status', '');

        $query = CrmCustomer::where('tenant_id', $tenantId);

        if (!empty($search)) {
            $query->where('(name LIKE :search OR company LIKE :search OR email LIKE :search)');
            $query->setBindings(['search' => "%{$search}%"]);
        }

        if (!empty($status)) {
            $query->where('status', $status);
        }

        $total = $query->count();
        $customers = $query->orderBy('created_at', 'DESC')->limit($limit)->offset($offset)->get();

        return $this->successResponse([
            'items' => $customers,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => ceil($total / $limit)
            ]
        ], 'Müşteri listesi getirildi');
    }

    public function store(): string
    {
        $this->requirePermission('crm:create');
        $tenantId = $this->getCurrentTenantId();
        $userId = $this->getCurrentUserId();
        $data = $this->getJsonInput();

        $this->validateRequired($data, ['name']);

        $customer = new CrmCustomer([
            'id' => $this->generateUUID(),
            'tenant_id' => $tenantId,
            'name' => $data['name'],
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'company' => $data['company'] ?? null,
            'address' => $data['address'] ?? null,
            'city' => $data['city'] ?? null,
            'country' => $data['country'] ?? null,
            'tax_id' => $data['tax_id'] ?? null,
            'industry' => $data['industry'] ?? null,
            'website' => $data['website'] ?? null,
            'lead_source' => $data['lead_source'] ?? null,
            'status' => $data['status'] ?? 'lead',
            'notes' => $data['notes'] ?? null,
            'created_by' => $userId,
            'updated_by' => $userId
        ]);

        if ($customer->save()) {
            $this->logActivity('crm_customer_created', 'crm', $customer->id, ['name' => $customer->name]);
            return $this->successResponse($customer, 'Müşteri oluşturuldu', 201);
        }

        return $this->errorResponse('Müşteri oluşturulamadı', 500);
    }

    public function update(?string $id = null): string
    {
        $this->requirePermission('crm:update');
        $tenantId = $this->getCurrentTenantId();
        $customer = CrmCustomer::find($id);

        if (!$customer || $customer->tenant_id !== $tenantId) {
            return $this->errorResponse('Müşteri bulunamadı', 404);
        }

        $data = $this->getJsonInput();
        $customer->fill($data);
        $customer->updated_by = $this->getCurrentUserId();

        if ($customer->save()) {
            $this->logActivity('crm_customer_updated', 'crm', $customer->id, ['name' => $customer->name]);
            return $this->successResponse($customer, 'Müşteri güncellendi');
        }

        return $this->errorResponse('Müşteri güncellenemedi', 500);
    }

    public function destroy(?string $id = null): string
    {
        $this->requirePermission('crm:delete');
        $tenantId = $this->getCurrentTenantId();
        $customer = CrmCustomer::find($id);

        if (!$customer || $customer->tenant_id !== $tenantId) {
            return $this->errorResponse('Müşteri bulunamadı', 404);
        }

        if ($customer->delete()) {
            $this->logActivity('crm_customer_deleted', 'crm', $id, ['name' => $customer->name]);
            return $this->successResponse(null, 'Müşteri silindi');
        }

        return $this->errorResponse('Müşteri silinemedi', 500);
    }

    // --- Deals ---

    public function getDeals(): string
    {
        $this->requirePermission('crm:read');
        $tenantId = $this->getCurrentTenantId();
        
        $query = CrmDeal::where('tenant_id', $tenantId);
        
        $customerId = $this->getQueryParam('customer_id', '');
        if (!empty($customerId)) {
            $query->where('customer_id', $customerId);
        }

        $stage = $this->getQueryParam('stage', '');
        if (!empty($stage)) {
            $query->where('stage', $stage);
        }

        $deals = $query->orderBy('created_at', 'DESC')->get();
        
        // Load customer names manually since no ORM relation loader
        foreach ($deals as $deal) {
            $customer = CrmCustomer::find($deal->customer_id);
            $deal->customer_name = $customer ? $customer->name : 'Unknown';
        }

        return $this->successResponse($deals, 'Fırsatlar getirildi');
    }

    public function storeDeal(): string
    {
        $this->requirePermission('crm:create');
        $tenantId = $this->getCurrentTenantId();
        $userId = $this->getCurrentUserId();
        $data = $this->getJsonInput();

        $this->validateRequired($data, ['customer_id', 'title', 'value']);

        $deal = new CrmDeal([
            'id' => $this->generateUUID(),
            'tenant_id' => $tenantId,
            'customer_id' => $data['customer_id'],
            'title' => $data['title'],
            'value' => (float)$data['value'],
            'currency' => $data['currency'] ?? 'TRY',
            'stage' => $data['stage'] ?? 'new',
            'probability' => (int)($data['probability'] ?? 0),
            'expected_close_date' => $data['expected_close_date'] ?? null,
            'notes' => $data['notes'] ?? null,
            'assigned_to' => $data['assigned_to'] ?? $userId,
            'created_by' => $userId,
            'updated_by' => $userId
        ]);

        if ($deal->save()) {
            $this->logActivity('crm_deal_created', 'crm', $deal->id, ['title' => $deal->title]);
            return $this->successResponse($deal, 'Fırsat oluşturuldu', 201);
        }

        return $this->errorResponse('Fırsat oluşturulamadı', 500);
    }

    public function updateDeal(?string $id = null): string
    {
        $this->requirePermission('crm:update');
        $tenantId = $this->getCurrentTenantId();
        $deal = CrmDeal::find($id);

        if (!$deal || $deal->tenant_id !== $tenantId) {
            return $this->errorResponse('Fırsat bulunamadı', 404);
        }

        $data = $this->getJsonInput();
        $deal->fill($data);
        $deal->updated_by = $this->getCurrentUserId();

        if ($deal->save()) {
            return $this->successResponse($deal, 'Fırsat güncellendi');
        }

        return $this->errorResponse('Fırsat güncellenemedi', 500);
    }
    
    public function destroyDeal(?string $id = null): string
    {
        $this->requirePermission('crm:delete');
        $tenantId = $this->getCurrentTenantId();
        $deal = CrmDeal::find($id);

        if (!$deal || $deal->tenant_id !== $tenantId) {
            return $this->errorResponse('Fırsat bulunamadı', 404);
        }

        if ($deal->delete()) {
            return $this->successResponse(null, 'Fırsat silindi');
        }

        return $this->errorResponse('Fırsat silinemedi', 500);
    }

    // --- Activities ---

    public function getActivities(): string
    {
        $this->requirePermission('crm:read');
        $tenantId = $this->getCurrentTenantId();
        
        $query = CrmActivity::where('tenant_id', $tenantId);
        
        $relatedType = $this->getQueryParam('related_to_type', '');
        if (!empty($relatedType)) {
            $query->where('related_to_type', $relatedType);
        }

        $relatedId = $this->getQueryParam('related_to_id', '');
        if (!empty($relatedId)) {
            $query->where('related_to_id', $relatedId);
        }

        $activities = $query->orderBy('date', 'DESC')->orderBy('created_at', 'DESC')->get();
        
        return $this->successResponse($activities, 'Aktiviteler getirildi');
    }

    public function storeActivity(): string
    {
        $this->requirePermission('crm:create');
        $tenantId = $this->getCurrentTenantId();
        $userId = $this->getCurrentUserId();
        $data = $this->getJsonInput();

        $this->validateRequired($data, ['related_to_type', 'related_to_id', 'type', 'subject']);

        $activity = new CrmActivity([
            'id' => $this->generateUUID(),
            'tenant_id' => $tenantId,
            'related_to_type' => $data['related_to_type'],
            'related_to_id' => $data['related_to_id'],
            'type' => $data['type'],
            'subject' => $data['subject'],
            'description' => $data['description'] ?? null,
            'date' => $data['date'] ?? date('Y-m-d H:i:s'),
            'status' => $data['status'] ?? 'completed',
            'created_by' => $userId
        ]);

        if ($activity->save()) {
            $this->logActivity('crm_activity_created', 'crm', $activity->id, ['subject' => $activity->subject]);
            return $this->successResponse($activity, 'Aktivite oluşturuldu', 201);
        }

        return $this->errorResponse('Aktivite oluşturulamadı', 500);
    }
    
    public function updateActivity(?string $id = null): string
    {
        $this->requirePermission('crm:update');
        $tenantId = $this->getCurrentTenantId();
        $activity = CrmActivity::find($id);

        if (!$activity || $activity->tenant_id !== $tenantId) {
            return $this->errorResponse('Aktivite bulunamadı', 404);
        }

        $data = $this->getJsonInput();
        $activity->fill($data);

        if ($activity->save()) {
            return $this->successResponse($activity, 'Aktivite güncellendi');
        }

        return $this->errorResponse('Aktivite güncellenemedi', 500);
    }

    public function destroyActivity(?string $id = null): string
    {
        $this->requirePermission('crm:delete');
        $tenantId = $this->getCurrentTenantId();
        $activity = CrmActivity::find($id);

        if (!$activity || $activity->tenant_id !== $tenantId) {
            return $this->errorResponse('Aktivite bulunamadı', 404);
        }

        if ($activity->delete()) {
            $this->logActivity('crm_activity_deleted', 'crm', $id, ['subject' => $activity->subject]);
            return $this->successResponse(null, 'Aktivite silindi');
        }

        return $this->errorResponse('Aktivite silinemedi', 500);
    }

    public function getStatistics(): string
    {
        $this->requirePermission('crm:read');
        $tenantId = $this->getCurrentTenantId();
        $db = Database::getInstance();

        // Total Customers
        $stmt = $db->prepare("SELECT COUNT(*) FROM crm_customers WHERE tenant_id = ?");
        $stmt->execute([$tenantId]);
        $totalCustomers = $stmt->fetchColumn();

        // Deals by Stage
        $stmt = $db->prepare("SELECT stage, COUNT(*) as count, SUM(value) as total_value FROM crm_deals WHERE tenant_id = ? GROUP BY stage");
        $stmt->execute([$tenantId]);
        $pipeline = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Total Pipeline Value
        $totalPipelineValue = 0;
        foreach ($pipeline as $stage) {
            $totalPipelineValue += $stage['total_value'];
        }

        return $this->successResponse([
            'total_customers' => $totalCustomers,
            'pipeline' => $pipeline,
            'total_pipeline_value' => $totalPipelineValue
        ], 'İstatistikler getirildi');
    }
}
