<?php

namespace App\Controllers;

use App\Models\Tenant;
use App\Services\AuthService;
use App\Core\Database;
use PDO;

class RootTenantController
{
    private $tenantModel;
    private $authService;
    private $currentUser;

    public function __construct()
    {
        $this->tenantModel = new Tenant();
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

    public function index()
    {
        $this->authenticate();
        $db = Database::getInstance()->getConnection();
        
        $sql = "
            SELECT t.*, COUNT(u.id) as user_count 
            FROM tenants t 
            LEFT JOIN users u ON t.id = u.tenant_id 
            GROUP BY t.id 
            ORDER BY t.created_at DESC
        ";
        $tenants = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);

        $this->json(['data' => $tenants]);
    }

    public function create()
    {
        $this->authenticate();
        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data['name'])) {
            $this->json(['error' => 'Tenant name is required'], 400);
        }

        $id = $this->generateUuid();
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("INSERT INTO tenants (id, name, status) VALUES (?, ?, 'active')");
        $stmt->execute([$id, $data['name']]);

        $this->json(['data' => ['id' => $id, 'name' => $data['name']]], 201);
    }

    public function stats()
    {
        $this->authenticate();
        $db = Database::getInstance()->getConnection();

        $tenantCount = $db->query("SELECT COUNT(*) FROM tenants")->fetchColumn();
        $activeTenantCount = $db->query("SELECT COUNT(*) FROM tenants WHERE status = 'active'")->fetchColumn();
        $userCount = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();

        $this->json([
            'data' => [
                'total_tenants' => (int)$tenantCount,
                'active_tenants' => (int)$activeTenantCount,
                'total_users' => (int)$userCount,
            ]
        ]);
    }
}
