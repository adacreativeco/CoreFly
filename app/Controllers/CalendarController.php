<?php

namespace App\Controllers;

use App\Models\CalendarEvent;
use App\Services\AuthService;
use App\Core\Database;
use PDO;

class CalendarController
{
    private $eventModel;
    private $authService;
    private $currentUser;

    public function __construct()
    {
        $this->eventModel = new CalendarEvent();
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

    public function index($params)
    {
        $user = $this->authenticate();
        $tenantId = $params['tenantId'];

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM calendar_events WHERE tenant_id = ? ORDER BY start_date ASC");
        $stmt->execute([$tenantId]);
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->json(['data' => $events]);
    }

    public function create($params)
    {
        $user = $this->authenticate();
        $tenantId = $params['tenantId'];
        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data['title']) || empty($data['start_date'])) {
            $this->json(['error' => 'Title and start_date are required'], 400);
        }

        $id = $this->generateUuid();
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            INSERT INTO calendar_events (id, tenant_id, title, description, event_type, start_date, end_date, location, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $id,
            $tenantId,
            $data['title'],
            $data['description'] ?? null,
            $data['event_type'] ?? 'meeting',
            $data['start_date'],
            $data['end_date'] ?? null,
            $data['location'] ?? null,
            $user['sub'] ?? 'system'
        ]);

        $created = $this->eventModel->find(['id' => $id, 'tenant_id' => $tenantId]);
        $this->json(['data' => $created], 201);
    }

    public function delete($params)
    {
        $user = $this->authenticate();
        $tenantId = $params['tenantId'];
        $eventId = $params['eventId'];

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("DELETE FROM calendar_events WHERE id = ? AND tenant_id = ?");
        $stmt->execute([$eventId, $tenantId]);

        $this->json(['success' => true]);
    }
}
