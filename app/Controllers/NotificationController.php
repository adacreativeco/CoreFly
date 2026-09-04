<?php

namespace App\Controllers;

use App\Models\Notification;
use App\Services\AuthService;

class NotificationController
{
    private $notificationModel;
    private $authService;
    private $currentUser;

    public function __construct()
    {
        $this->notificationModel = new Notification();
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

        $notifications = $this->notificationModel->findAll([
            'tenant_id' => $tenantId,
            'user_id' => $user['id']
        ]);
        
        $this->json(['data' => $notifications]);
    }

    public function markAsRead($params)
    {
        $user = $this->authenticate();
        $tenantId = $params['tenantId'];
        $notificationId = $params['notificationId'];

        $notification = $this->notificationModel->find($notificationId);
        
        if (!$notification || $notification['user_id'] !== $user['id']) {
            $this->json(['error' => 'Notification not found'], 404);
            return;
        }

        $this->notificationModel->update($notificationId, [
            'is_read' => 1,
            'read_at' => date('Y-m-d H:i:s')
        ]);

        $this->json(['message' => 'Marked as read']);
    }

    // Dev/Test endpoint
    public function createTest($params)
    {
        $user = $this->authenticate();
        $tenantId = $params['tenantId'];
        $data = json_decode(file_get_contents('php://input'), true);

        $notification = $this->notificationModel->create([
            'tenant_id' => $tenantId,
            'user_id' => $user['id'], // Self notification
            'type' => $data['type'] ?? 'info',
            'title' => $data['title'] ?? 'Test Notification',
            'content' => $data['content'] ?? 'This is a test.',
            'action_url' => $data['action_url'] ?? null
        ]);

        $this->json(['data' => $notification], 201);
    }

    private function json($data, $status = 200)
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
    }
}
