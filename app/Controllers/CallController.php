<?php

namespace App\Controllers;

use App\Models\CallHistory;
use App\Models\CallSignal;
use App\Models\User;
use App\Services\AuthService;
use App\Core\Database;
use PDO;

class CallController
{
    private $callHistoryModel;
    private $callSignalModel;
    private $userModel;
    private $authService;
    private $currentUser;

    public function __construct()
    {
        $this->callHistoryModel = new CallHistory();
        $this->callSignalModel = new CallSignal();
        $this->userModel = new User();
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
            $this->json(['error' => 'Unauthorized: No token'], 401);
            exit;
        }

        $payload = $this->authService->verifyToken($token);
        if (!$payload) {
            $this->json(['error' => 'Unauthorized: Invalid token'], 401);
            exit;
        }

        $this->currentUser = $payload;
        return $payload;
    }

    /**
     * Start an audio or video call
     * POST /api/calls/{tenantId}/start
     */
    public function startCall($params)
    {
        $user = $this->authenticate();
        $tenantId = $params['tenantId'];

        if ($tenantId !== $user['tenant_id']) {
            $this->json(['error' => 'Forbidden'], 403);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $targetId = $data['target_id'] ?? null;
        $callType = $data['call_type'] ?? 'audio'; // 'audio' or 'video'

        if (empty($targetId)) {
            $this->json(['error' => 'Target user ID is required'], 400);
            return;
        }

        if ($targetId === $user['id']) {
            $this->json(['error' => 'You cannot call yourself'], 400);
            return;
        }

        // Verify target user exists in this tenant
        $target = $this->userModel->find($targetId);
        if (!$target || $target['tenant_id'] !== $tenantId) {
            $this->json(['error' => 'Target user not found in tenant'], 404);
            return;
        }

        $callId = uniqid('call_', true);
        $now = date('Y-m-d H:i:s');

        $this->callHistoryModel->create([
            'id' => $callId,
            'tenant_id' => $tenantId,
            'caller_id' => $user['id'],
            'callee_id' => $targetId,
            'call_type' => in_array($callType, ['audio', 'video']) ? $callType : 'audio',
            'status' => 'initiated',
            'started_at' => $now,
            'duration_seconds' => 0,
            'created_at' => $now
        ]);

        $this->json([
            'status' => 'success',
            'message' => 'Call initiated',
            'data' => [
                'call_id' => $callId,
                'caller_id' => $user['id'],
                'callee_id' => $targetId,
                'callee_name' => $target['full_name'] ?? $target['email'] ?? 'Kullanıcı',
                'call_type' => $callType,
                'status' => 'initiated'
            ]
        ], 201);
    }

    /**
     * Send WebRTC signal (offer, answer, ice-candidate)
     * POST /api/calls/{tenantId}/signal
     */
    public function handleSignaling($params)
    {
        $user = $this->authenticate();
        $tenantId = $params['tenantId'];

        if ($tenantId !== $user['tenant_id']) {
            $this->json(['error' => 'Forbidden'], 403);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $callId = $data['call_id'] ?? null;
        $type = $data['type'] ?? null;
        $payload = $data['payload'] ?? null;

        if (!$callId || !$type || !$payload) {
            $this->json(['error' => 'Call ID, type, and payload are required'], 400);
            return;
        }

        $call = $this->callHistoryModel->find($callId);
        if (!$call || $call['tenant_id'] !== $tenantId) {
            $this->json(['error' => 'Call not found or expired'], 404);
            return;
        }

        $signalId = uniqid('sig_', true);
        $this->callSignalModel->create([
            'id' => $signalId,
            'tenant_id' => $tenantId,
            'call_id' => $callId,
            'sender_id' => $user['id'],
            'type' => $type,
            'payload' => is_array($payload) ? json_encode($payload, JSON_UNESCAPED_UNICODE) : (string)$payload,
            'is_processed' => 0,
            'created_at' => date('Y-m-d H:i:s')
        ]);

        $this->json(['status' => 'forwarded', 'signal_id' => $signalId]);
    }

    /**
     * Poll incoming signals from the peer
     * GET /api/calls/{tenantId}/signals?call_id=xyz
     */
    public function pollSignals($params)
    {
        $user = $this->authenticate();
        $tenantId = $params['tenantId'];
        $callId = $_GET['call_id'] ?? null;

        if (!$callId) {
            $this->json(['error' => 'Call ID is required'], 400);
            return;
        }

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            SELECT * FROM call_signals 
            WHERE call_id = :callId 
              AND sender_id != :userId 
              AND is_processed = 0 
            ORDER BY created_at ASC
        ");
        $stmt->execute([
            ':callId' => $callId,
            ':userId' => $user['id']
        ]);
        $signals = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($signals)) {
            $ids = array_column($signals, 'id');
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $updateStmt = $db->prepare("UPDATE call_signals SET is_processed = 1 WHERE id IN ($placeholders)");
            $updateStmt->execute($ids);
        }

        foreach ($signals as &$sig) {
            $decoded = json_decode($sig['payload'], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $sig['payload'] = $decoded;
            }
        }

        $this->json(['signals' => $signals]);
    }

    /**
     * Check if someone is currently calling the logged-in user
     * GET /api/calls/{tenantId}/incoming
     */
    public function pollIncoming($params)
    {
        $user = $this->authenticate();
        $tenantId = $params['tenantId'];

        if ($tenantId !== $user['tenant_id']) {
            $this->json(['error' => 'Forbidden'], 403);
            return;
        }

        $db = Database::getInstance()->getConnection();
        
        // Find calls where I am callee, status is initiated or ringing, created in last 45 seconds
        // Portable query using timestamp difference
        $threshold = date('Y-m-d H:i:s', time() - 45);
        $stmt = $db->prepare("
            SELECT c.*, 
                   u.full_name, u.email, u.avatar_url
            FROM call_history c
            JOIN users u ON c.caller_id = u.id
            WHERE c.tenant_id = :tenantId
              AND c.callee_id = :userId
              AND c.status IN ('initiated', 'ringing')
              AND c.created_at >= :threshold
            ORDER BY c.created_at DESC 
            LIMIT 1
        ");
        $stmt->execute([
            ':tenantId' => $tenantId,
            ':userId' => $user['id'],
            ':threshold' => $threshold
        ]);
        $call = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->json(['incoming_call' => $call ?: null]);
    }

    /**
     * Answer incoming call
     * POST /api/calls/{tenantId}/answer
     */
    public function answerCall($params)
    {
        $user = $this->authenticate();
        $tenantId = $params['tenantId'];
        $data = json_decode(file_get_contents('php://input'), true);
        $callId = $data['call_id'] ?? null;

        if (!$callId) {
            $this->json(['error' => 'Call ID is required'], 400);
            return;
        }

        $call = $this->callHistoryModel->find($callId);
        if (!$call || $call['tenant_id'] !== $tenantId) {
            $this->json(['error' => 'Call not found'], 404);
            return;
        }

        $this->callHistoryModel->update($callId, [
            'status' => 'answered',
            'started_at' => date('Y-m-d H:i:s')
        ]);

        $this->json(['status' => 'answered', 'call_id' => $callId]);
    }

    /**
     * End or reject call
     * POST /api/calls/{tenantId}/end
     */
    public function endCall($params)
    {
        $user = $this->authenticate();
        $tenantId = $params['tenantId'];
        $data = json_decode(file_get_contents('php://input'), true);
        $callId = $data['call_id'] ?? null;
        $reason = $data['reason'] ?? 'ended'; // ended, rejected, missed

        if (!$callId) {
            $this->json(['error' => 'Call ID is required'], 400);
            return;
        }

        $call = $this->callHistoryModel->find($callId);
        if (!$call || $call['tenant_id'] !== $tenantId) {
            $this->json(['error' => 'Call not found'], 404);
            return;
        }

        $now = time();
        $duration = 0;
        if (!empty($call['started_at']) && $call['status'] === 'answered') {
            $start = strtotime($call['started_at']);
            if ($start && $now >= $start) {
                $duration = $now - $start;
            }
        }

        $finalStatus = in_array($reason, ['ended', 'rejected', 'missed']) ? $reason : 'ended';
        $this->callHistoryModel->update($callId, [
            'status' => $finalStatus,
            'ended_at' => date('Y-m-d H:i:s', $now),
            'duration_seconds' => $duration
        ]);

        // Forward end signal
        $this->callSignalModel->create([
            'id' => uniqid('sig_', true),
            'tenant_id' => $tenantId,
            'call_id' => $callId,
            'sender_id' => $user['id'],
            'type' => 'end',
            'payload' => json_encode(['reason' => $finalStatus]),
            'is_processed' => 0,
            'created_at' => date('Y-m-d H:i:s')
        ]);

        $this->json([
            'status' => $finalStatus,
            'duration_seconds' => $duration
        ]);
    }

    /**
     * Get call history for logged-in user
     * GET /api/calls/{tenantId}/history
     */
    public function getHistory($params)
    {
        $user = $this->authenticate();
        $tenantId = $params['tenantId'];

        if ($tenantId !== $user['tenant_id']) {
            $this->json(['error' => 'Forbidden'], 403);
            return;
        }

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            SELECT c.*,
                   u_caller.full_name as caller_name, u_caller.avatar_url as caller_avatar,
                   u_callee.full_name as callee_name, u_callee.avatar_url as callee_avatar
            FROM call_history c
            JOIN users u_caller ON c.caller_id = u_caller.id
            JOIN users u_callee ON c.callee_id = u_callee.id
            WHERE c.tenant_id = :tenantId
              AND (c.caller_id = :userId OR c.callee_id = :userId)
            ORDER BY c.created_at DESC
            LIMIT 50
        ");
        $stmt->execute([
            ':tenantId' => $tenantId,
            ':userId' => $user['id']
        ]);
        $history = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->json(['data' => $history]);
    }

    private function json($data, $status = 200)
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
    }
}
