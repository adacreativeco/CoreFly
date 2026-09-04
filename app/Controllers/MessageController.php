<?php

namespace App\Controllers;

use App\Models\Message;
use App\Models\Conversation;
use App\Services\AuthService;

class MessageController
{
    private $messageModel;
    private $conversationModel;
    private $authService;
    private $currentUser;

    public function __construct()
    {
        $this->messageModel = new Message();
        $this->conversationModel = new Conversation();
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
        $conversationId = $params['conversationId'];

        if ($tenantId !== $user['tenant_id']) {
            $this->json(['error' => 'Forbidden'], 403);
            return;
        }

        // Verify conversation access
        $conversation = $this->conversationModel->find($conversationId);
        if (!$conversation || $conversation['tenant_id'] !== $tenantId) {
            $this->json(['error' => 'Conversation not found'], 404);
            return;
        }

        $messages = $this->messageModel->findAll([
            'tenant_id' => $tenantId,
            'conversation_id' => $conversationId
        ]);
        
        $this->json(['data' => $messages]);
    }

    public function create($params)
    {
        $user = $this->authenticate();
        $tenantId = $params['tenantId'];
        $conversationId = $params['conversationId'];
        $data = json_decode(file_get_contents('php://input'), true);

        // Verify conversation exists
        $conversation = $this->conversationModel->find($conversationId);
        if (!$conversation || $conversation['tenant_id'] !== $tenantId) {
            $this->json(['error' => 'Conversation not found'], 404);
            return;
        }

        if (empty($data['content'])) {
            $this->json(['error' => 'Content required'], 400);
            return;
        }

        $message = $this->messageModel->create([
            'tenant_id' => $tenantId,
            'conversation_id' => $conversationId,
            'sender_id' => $user['id'],
            'content' => $data['content'],
            'type' => $data['type'] ?? 'text',
            'attachments' => isset($data['attachments']) ? json_encode($data['attachments']) : null,
            'is_read' => 0
        ]);

        // Update conversation last_message_at
        $this->conversationModel->update($conversationId, [
            'last_message_at' => date('Y-m-d H:i:s')
        ]);

        $this->json(['data' => $message], 201);
    }

    public function update($params)
    {
        $user = $this->authenticate();
        $tenantId = $params['tenantId'];
        $conversationId = $params['conversationId'];
        $messageId = $params['messageId'];
        $data = json_decode(file_get_contents('php://input'), true);

        $message = $this->messageModel->find($messageId);
        if (!$message || $message['sender_id'] !== $user['id']) {
            $this->json(['error' => 'Message not found or forbidden'], 404);
            return;
        }

        // Soft delete or edit logic here
        // For now, just update content if provided
        if (isset($data['content'])) {
            $this->messageModel->update($messageId, ['content' => $data['content']]);
        }

        $updatedMessage = $this->messageModel->find($messageId);
        $this->json(['data' => $updatedMessage]);
    }

    private function json($data, $status = 200)
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
    }
}
