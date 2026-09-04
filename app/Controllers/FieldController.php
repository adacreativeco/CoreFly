<?php

namespace App\Controllers;

use App\Models\FieldTask;
use App\Services\AuthService;
use App\Core\Database;
use PDO;

class FieldController
{
    private $taskModel;
    private $authService;
    private $currentUser;

    public function __construct()
    {
        $this->taskModel = new FieldTask();
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
        $search = $_GET['search'] ?? '';
        $status = $_GET['status'] ?? '';

        $sql = "SELECT * FROM field_tasks WHERE tenant_id = ?";
        $binds = [$tenantId];

        if (!empty($search)) {
            $sql .= " AND (title LIKE ? OR location LIKE ? OR team_name LIKE ?)";
            $s = "%$search%";
            $binds[] = $s; $binds[] = $s; $binds[] = $s;
        }

        if (!empty($status)) {
            $sql .= " AND status = ?";
            $binds[] = $status;
        }

        $sql .= " ORDER BY task_date DESC, created_at DESC";

        $stmt = $db->prepare($sql);
        $stmt->execute($binds);
        $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->json(['data' => $tasks]);
    }

    public function create($params)
    {
        $user = $this->authenticate();
        $tenantId = $params['tenantId'];
        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data['title'])) {
            $this->json(['error' => 'Title is required'], 400);
        }

        $id = $this->generateUuid();
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            INSERT INTO field_tasks (id, tenant_id, title, description, team_name, location, status, priority, assigned_to, task_date, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $id,
            $tenantId,
            $data['title'],
            $data['description'] ?? null,
            $data['team_name'] ?? 'Saha Ekibi A',
            $data['location'] ?? null,
            $data['status'] ?? 'planned',
            $data['priority'] ?? 'medium',
            $data['assigned_to'] ?? null,
            $data['task_date'] ?? date('Y-m-d'),
            $user['sub'] ?? 'system'
        ]);

        $created = $this->taskModel->find(['id' => $id, 'tenant_id' => $tenantId]);
        $this->json(['data' => $created], 201);
    }

    public function updateStatus($params)
    {
        $user = $this->authenticate();
        $tenantId = $params['tenantId'];
        $taskId = $params['taskId'];
        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data['status'])) {
            $this->json(['error' => 'Status is required'], 400);
        }

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("UPDATE field_tasks SET status = ? WHERE id = ? AND tenant_id = ?");
        $stmt->execute([$data['status'], $taskId, $tenantId]);

        $this->json(['success' => true]);
    }

    public function delete($params)
    {
        $user = $this->authenticate();
        $tenantId = $params['tenantId'];
        $taskId = $params['taskId'];

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("DELETE FROM field_tasks WHERE id = ? AND tenant_id = ?");
        $stmt->execute([$taskId, $tenantId]);

        $this->json(['success' => true]);
    }
}
