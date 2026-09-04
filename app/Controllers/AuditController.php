<?php

namespace App\Controllers;

use App\Models\AuditLog;
use App\Services\AuthService;

class AuditController
{
    private $auditModel;
    private $authService;
    private $currentUser;

    public function __construct()
    {
        $this->auditModel = new AuditLog();
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

    public function index($params)
    {
        $user = $this->authenticate();
        $tenantId = $params['tenantId'];

        if ($tenantId !== $user['tenant_id']) {
            $this->json(['error' => 'Forbidden'], 403);
            return;
        }

        // In real app, check for 'admin' role
        
        $logs = $this->auditModel->findAll(['tenant_id' => $tenantId]);
        $this->json(['data' => $logs]);
    }
    
    // Internal helper (static) or service method would be better, but for now:
    public function logAction($tenantId, $userId, $action, $entityType, $entityId, $details = [])
    {
        $this->auditModel->create([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'details' => json_encode($details),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null
        ]);
    }
    
    // Test endpoint to trigger a log
    public function createTest($params) {
        $user = $this->authenticate();
        $tenantId = $params['tenantId'];
        
        $this->logAction($tenantId, $user['id'], 'test_action', 'system', '0', ['msg' => 'Test Log']);
        
        $this->json(['message' => 'Log created'], 201);
    }

    private function json($data, $status = 200)
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
    }
}
