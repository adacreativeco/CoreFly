<?php

namespace App\Controllers;

use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Services\AuthService;
use App\Core\Database;

class ConversationController
{
    private $conversationModel;
    private $participantModel;
    private $authService;
    private $currentUser;

    public function __construct()
    {
        $this->conversationModel = new Conversation();
        $this->participantModel = new ConversationParticipant();
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

        // TODO: Filter conversations where user is a participant
        // For now, list all public/group conversations for tenant
        $conversations = $this->conversationModel->findAll(['tenant_id' => $tenantId]);
        $this->json(['data' => $conversations]);
    }

    public function create($params)
    {
        $user = $this->authenticate();
        $tenantId = $params['tenantId'];
        $data = json_decode(file_get_contents('php://input'), true);

        // For direct messages, check if conversation already exists
        if (($data['type'] ?? 'direct') === 'direct' && !empty($data['recipient_id'])) {
             // Logic to find existing DM would go here
        }

        $conversation = $this->conversationModel->create([
            'tenant_id' => $tenantId,
            'title' => $data['title'] ?? null,
            'type' => $data['type'] ?? 'direct',
            'description' => $data['description'] ?? null,
            'created_by' => $user['id'],
            'last_message_at' => date('Y-m-d H:i:s')
        ]);

        // Add creator as participant
        $this->participantModel->create([
            'conversation_id' => $conversation['id'],
            'user_id' => $user['id'],
            'role' => 'admin'
        ]);

        // Add recipient(s) if provided
        if (!empty($data['recipient_id'])) {
            $this->participantModel->create([
                'conversation_id' => $conversation['id'],
                'user_id' => $data['recipient_id'],
                'role' => 'member'
            ]);
        } elseif (!empty($data['participant_ids']) && is_array($data['participant_ids'])) {
            foreach ($data['participant_ids'] as $pid) {
                $this->participantModel->create([
                    'conversation_id' => $conversation['id'],
                    'user_id' => $pid,
                    'role' => 'member'
                ]);
            }
        }

        $this->json(['data' => $conversation], 201);
    }

    public function show($params)
    {
        $user = $this->authenticate();
        $tenantId = $params['tenantId'];
        $conversationId = $params['conversationId'];

        if ($tenantId !== $user['tenant_id']) {
            $this->json(['error' => 'Forbidden'], 403);
            return;
        }

        $conversation = $this->conversationModel->find($conversationId);
        if (!$conversation || $conversation['tenant_id'] !== $tenantId) {
            $this->json(['error' => 'Conversation not found'], 404);
            return;
        }

        $this->json(['data' => $conversation]);
    }

    public function delete($params)
    {
        $user = $this->authenticate();
        $tenantId = $params['tenantId'];
        $conversationId = $params['conversationId'];

        if ($tenantId !== $user['tenant_id']) {
            $this->json(['error' => 'Forbidden'], 403);
            return;
        }

        $conversation = $this->conversationModel->find($conversationId);
        if (!$conversation || $conversation['tenant_id'] !== $tenantId) {
            $this->json(['error' => 'Conversation not found'], 404);
            return;
        }

        $db = Database::getInstance()->getConnection();
        // İlgili mesajları sil
        $stmtMsg = $db->prepare("DELETE FROM messages WHERE conversation_id = ?");
        $stmtMsg->execute([$conversationId]);

        // Katılımcıları sil
        $stmtPart = $db->prepare("DELETE FROM conversation_participants WHERE conversation_id = ?");
        $stmtPart->execute([$conversationId]);

        // Sohbeti sil
        $this->conversationModel->delete($conversationId);

        $this->json(['message' => 'Conversation deleted successfully']);
    }

    private function json($data, $status = 200)
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
    }
}
