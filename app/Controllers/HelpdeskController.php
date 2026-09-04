<?php

namespace App\Controllers;

use App\Models\HelpdeskTicket;
use App\Models\HelpdeskMessage;
use App\Services\AuthService;
use App\Core\Database;
use PDO;

class HelpdeskController
{
    private $ticketModel;
    private $messageModel;
    private $authService;
    private $currentUser;

    public function __construct()
    {
        $this->ticketModel = new HelpdeskTicket();
        $this->messageModel = new HelpdeskMessage();
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

    public function indexTickets($params)
    {
        $user = $this->authenticate();
        $tenantId = $params['tenantId'];

        $db = Database::getInstance()->getConnection();
        $search = $_GET['search'] ?? '';
        $status = $_GET['status'] ?? '';
        $priority = $_GET['priority'] ?? '';

        $sql = "SELECT * FROM helpdesk_tickets WHERE tenant_id = ?";
        $binds = [$tenantId];

        if (!empty($search)) {
            $sql .= " AND (title LIKE ? OR description LIKE ? OR category LIKE ?)";
            $s = "%$search%";
            $binds[] = $s; $binds[] = $s; $binds[] = $s;
        }

        if (!empty($status)) {
            $sql .= " AND status = ?";
            $binds[] = $status;
        }

        if (!empty($priority)) {
            $sql .= " AND priority = ?";
            $binds[] = $priority;
        }

        $sql .= " ORDER BY created_at DESC";

        $stmt = $db->prepare($sql);
        $stmt->execute($binds);
        $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->json(['data' => $tickets]);
    }

    public function createTicket($params)
    {
        $user = $this->authenticate();
        $tenantId = $params['tenantId'];
        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data['title']) || empty($data['description'])) {
            $this->json(['error' => 'Title and description are required'], 400);
        }

        $id = $this->generateUuid();
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            INSERT INTO helpdesk_tickets (id, tenant_id, title, description, priority, status, category, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $id,
            $tenantId,
            $data['title'],
            $data['description'],
            $data['priority'] ?? 'medium',
            'open',
            $data['category'] ?? 'Genel Destek',
            $user['sub'] ?? 'system'
        ]);

        $created = $this->ticketModel->find(['id' => $id, 'tenant_id' => $tenantId]);
        $this->json(['data' => $created], 201);
    }

    public function showTicket($params)
    {
        $user = $this->authenticate();
        $tenantId = $params['tenantId'];
        $ticketId = $params['ticketId'];

        $ticket = $this->ticketModel->find(['id' => $ticketId, 'tenant_id' => $tenantId]);
        if (!$ticket) {
            $this->json(['error' => 'Ticket not found'], 404);
        }

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM helpdesk_messages WHERE ticket_id = ? AND tenant_id = ? ORDER BY created_at ASC");
        $stmt->execute([$ticketId, $tenantId]);
        $ticket['messages'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->json(['data' => $ticket]);
    }

    public function addMessage($params)
    {
        $user = $this->authenticate();
        $tenantId = $params['tenantId'];
        $ticketId = $params['ticketId'];
        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data['message'])) {
            $this->json(['error' => 'Message is required'], 400);
        }

        $id = $this->generateUuid();
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            INSERT INTO helpdesk_messages (id, tenant_id, ticket_id, user_id, user_name, message)
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $id,
            $tenantId,
            $ticketId,
            $user['sub'] ?? 'user',
            $data['user_name'] ?? 'Destek Kullanıcısı',
            $data['message']
        ]);

        // Update ticket updated_at
        $db->prepare("UPDATE helpdesk_tickets SET updated_at = CURRENT_TIMESTAMP WHERE id = ?")->execute([$ticketId]);

        $this->json(['data' => ['id' => $id, 'message' => $data['message']]], 201);
    }

    public function updateStatus($params)
    {
        $user = $this->authenticate();
        $tenantId = $params['tenantId'];
        $ticketId = $params['ticketId'];
        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data['status'])) {
            $this->json(['error' => 'Status is required'], 400);
        }

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("UPDATE helpdesk_tickets SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ? AND tenant_id = ?");
        $stmt->execute([$data['status'], $ticketId, $tenantId]);

        $this->json(['success' => true]);
    }

    public function stats($params)
    {
        $user = $this->authenticate();
        $tenantId = $params['tenantId'];

        $db = Database::getInstance()->getConnection();
        $total = $db->query("SELECT COUNT(*) FROM helpdesk_tickets WHERE tenant_id = '$tenantId'")->fetchColumn();
        $open = $db->query("SELECT COUNT(*) FROM helpdesk_tickets WHERE tenant_id = '$tenantId' AND status = 'open'")->fetchColumn();
        $inProgress = $db->query("SELECT COUNT(*) FROM helpdesk_tickets WHERE tenant_id = '$tenantId' AND status = 'in_progress'")->fetchColumn();
        $resolved = $db->query("SELECT COUNT(*) FROM helpdesk_tickets WHERE tenant_id = '$tenantId' AND status IN ('resolved', 'closed')")->fetchColumn();

        $this->json([
            'data' => [
                'total_tickets' => (int)$total,
                'open_tickets' => (int)$open,
                'in_progress_tickets' => (int)$inProgress,
                'resolved_tickets' => (int)$resolved
            ]
        ]);
    }
}
