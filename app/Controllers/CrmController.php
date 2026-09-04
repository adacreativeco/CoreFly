<?php

namespace App\Controllers;

use App\Models\CrmCustomer;
use App\Models\CrmDeal;
use App\Models\CrmActivity;
use App\Services\AuthService;
use App\Core\Database;
use PDO;

class CrmController
{
    private $customerModel;
    private $dealModel;
    private $activityModel;
    private $authService;
    private $currentUser;

    public function __construct()
    {
        $this->customerModel = new CrmCustomer();
        $this->dealModel = new CrmDeal();
        $this->activityModel = new CrmActivity();
        $this->authService = new AuthService();
    }

    private function authenticate()
    {
        $headers = [];
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
        }

        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        $token = str_replace('Bearer ', '', $authHeader);

        if (empty($token)) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized: No token']);
            exit;
        }

        $payload = $this->authService->verifyToken($token);
        if (!$payload) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized: Invalid token']);
            exit;
        }
        $this->currentUser = $payload;
        return $payload;
    }

    private function json($data, $status = 200)
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    private function generateUuid()
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    // --- CUSTOMERS ---

    public function indexCustomers($params)
    {
        $user = $this->authenticate();
        $tenantId = $params['tenantId'];

        if ($tenantId !== $user['tenant_id']) {
            $this->json(['error' => 'Forbidden'], 403);
        }

        $db = Database::getInstance()->getConnection();
        $search = $_GET['search'] ?? '';

        if (!empty($search)) {
            $stmt = $db->prepare("SELECT * FROM crm_customers WHERE tenant_id = ? AND (name LIKE ? OR company LIKE ? OR email LIKE ?) ORDER BY created_at DESC");
            $s = "%$search%";
            $stmt->execute([$tenantId, $s, $s, $s]);
        } else {
            $stmt = $db->prepare("SELECT * FROM crm_customers WHERE tenant_id = ? ORDER BY created_at DESC");
            $stmt->execute([$tenantId]);
        }

        $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $this->json(['data' => $customers]);
    }

    public function createCustomer($params)
    {
        $user = $this->authenticate();
        $tenantId = $params['tenantId'];
        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data['name'])) {
            $this->json(['error' => 'Name is required'], 400);
        }

        $id = $this->generateUuid();
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("INSERT INTO crm_customers (id, tenant_id, name, email, phone, company, address, city, country, status, notes, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $id,
            $tenantId,
            $data['name'],
            $data['email'] ?? null,
            $data['phone'] ?? null,
            $data['company'] ?? null,
            $data['address'] ?? null,
            $data['city'] ?? null,
            $data['country'] ?? 'Türkiye',
            $data['status'] ?? 'lead',
            $data['notes'] ?? null,
            $user['sub'] ?? null
        ]);

        $created = $this->customerModel->find(['id' => $id, 'tenant_id' => $tenantId]);
        $this->json(['data' => $created], 201);
    }

    public function deleteCustomer($params)
    {
        $user = $this->authenticate();
        $tenantId = $params['tenantId'];
        $customerId = $params['customerId'];

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("DELETE FROM crm_customers WHERE id = ? AND tenant_id = ?");
        $stmt->execute([$customerId, $tenantId]);

        $this->json(['success' => true]);
    }

    // --- DEALS ---

    public function indexDeals($params)
    {
        $user = $this->authenticate();
        $tenantId = $params['tenantId'];

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            SELECT d.*, c.name as customer_name, c.company as customer_company 
            FROM crm_deals d 
            LEFT JOIN crm_customers c ON d.customer_id = c.id 
            WHERE d.tenant_id = ? 
            ORDER BY d.created_at DESC
        ");
        $stmt->execute([$tenantId]);
        $deals = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->json(['data' => $deals]);
    }

    public function createDeal($params)
    {
        $user = $this->authenticate();
        $tenantId = $params['tenantId'];
        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data['title']) || empty($data['customer_id'])) {
            $this->json(['error' => 'Title and customer_id are required'], 400);
        }

        $id = $this->generateUuid();
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            INSERT INTO crm_deals (id, tenant_id, customer_id, title, value, currency, stage, probability, expected_close_date, notes, created_by) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $id,
            $tenantId,
            $data['customer_id'],
            $data['title'],
            $data['value'] ?? 0,
            $data['currency'] ?? 'TRY',
            $data['stage'] ?? 'lead',
            $data['probability'] ?? 20,
            $data['expected_close_date'] ?? null,
            $data['notes'] ?? null,
            $user['sub'] ?? null
        ]);

        $created = $this->dealModel->find(['id' => $id, 'tenant_id' => $tenantId]);
        $this->json(['data' => $created], 201);
    }

    public function updateDealStage($params)
    {
        $user = $this->authenticate();
        $tenantId = $params['tenantId'];
        $dealId = $params['dealId'];
        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data['stage'])) {
            $this->json(['error' => 'Stage is required'], 400);
        }

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("UPDATE crm_deals SET stage = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ? AND tenant_id = ?");
        $stmt->execute([$data['stage'], $dealId, $tenantId]);

        $this->json(['success' => true]);
    }

    public function deleteDeal($params)
    {
        $user = $this->authenticate();
        $tenantId = $params['tenantId'];
        $dealId = $params['dealId'];

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("DELETE FROM crm_deals WHERE id = ? AND tenant_id = ?");
        $stmt->execute([$dealId, $tenantId]);

        $this->json(['success' => true]);
    }

    // --- ACTIVITIES ---

    public function indexActivities($params)
    {
        $user = $this->authenticate();
        $tenantId = $params['tenantId'];

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM crm_activities WHERE tenant_id = ? ORDER BY created_at DESC LIMIT 50");
        $stmt->execute([$tenantId]);
        $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->json(['data' => $activities]);
    }

    public function createActivity($params)
    {
        $user = $this->authenticate();
        $tenantId = $params['tenantId'];
        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data['subject']) || empty($data['type'])) {
            $this->json(['error' => 'Subject and type are required'], 400);
        }

        $id = $this->generateUuid();
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            INSERT INTO crm_activities (id, tenant_id, related_to_type, related_to_id, type, subject, description, date, status, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $id,
            $tenantId,
            $data['related_to_type'] ?? 'customer',
            $data['related_to_id'] ?? '',
            $data['type'],
            $data['subject'],
            $data['description'] ?? null,
            $data['date'] ?? date('Y-m-d H:i:s'),
            $data['status'] ?? 'completed',
            $user['sub'] ?? null
        ]);

        $this->json(['data' => ['id' => $id]], 201);
    }

    // --- STATS ---

    public function stats($params)
    {
        $user = $this->authenticate();
        $tenantId = $params['tenantId'];

        $db = Database::getInstance()->getConnection();

        $custCount = $db->query("SELECT COUNT(*) FROM crm_customers WHERE tenant_id = '$tenantId'")->fetchColumn();
        $dealsCount = $db->query("SELECT COUNT(*) FROM crm_deals WHERE tenant_id = '$tenantId'")->fetchColumn();
        $totalPipelineValue = $db->query("SELECT SUM(value) FROM crm_deals WHERE tenant_id = '$tenantId' AND stage NOT IN ('lost')")->fetchColumn() ?: 0;
        $wonValue = $db->query("SELECT SUM(value) FROM crm_deals WHERE tenant_id = '$tenantId' AND stage = 'won'")->fetchColumn() ?: 0;

        $this->json([
            'data' => [
                'total_customers' => (int)$custCount,
                'total_deals' => (int)$dealsCount,
                'pipeline_value' => (float)$totalPipelineValue,
                'won_value' => (float)$wonValue
            ]
        ]);
    }
}
