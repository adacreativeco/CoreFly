<?php

namespace App\Controllers;

use App\Services\AuthService;
use App\Core\Database;
use PDO;

class AuditLogController
{
    private $authService;

    public function __construct()
    {
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
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }

        $payload = $this->authService->verifyToken($token);
        if (!$payload) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }
        return $payload;
    }

    private function json($data, $status = 200)
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    public function index($params)
    {
        $this->authenticate();
        $tenantId = $params['tenantId'];

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            SELECT a.*, u.email as user_email, u.full_name as user_name
            FROM audit_logs a
            LEFT JOIN users u ON a.user_id = u.id
            WHERE a.tenant_id = ?
            ORDER BY a.created_at DESC
            LIMIT 100
        ");
        $stmt->execute([$tenantId]);
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->json(['data' => $logs]);
    }

    public function createTest($params)
    {
        $user = $this->authenticate();
        $tenantId = $params['tenantId'];
        $data = json_decode(file_get_contents('php://input'), true);

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            INSERT INTO audit_logs (id, tenant_id, user_id, action, entity_type, entity_id, details, ip_address, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
        ");

        $id = uniqid('audit_', true);
        $action = $data['action'] ?? 'test_action';
        $entityType = $data['entity_type'] ?? 'system';
        $entityId = $data['entity_id'] ?? uniqid();
        $details = isset($data['details']) ? json_encode($data['details']) : json_encode(['test' => true]);
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

        $stmt->execute([$id, $tenantId, $user['id'], $action, $entityType, $entityId, $details, $ip]);

        $this->json(['status' => 'success', 'id' => $id], 201);
    }
}
