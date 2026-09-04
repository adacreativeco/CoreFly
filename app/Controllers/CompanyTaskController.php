<?php

namespace App\Controllers;

use App\Models\CompanyTask;
use App\Services\AuthService;
use App\Core\Database;
use PDO;

class CompanyTaskController
{
    private $taskModel;
    private $authService;
    private $currentUser;

    public function __construct()
    {
        $this->taskModel = new CompanyTask();
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
        $this->authenticate();
        $tenantId = $params['tenantId'];

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            SELECT t.*, u.name as assigned_user_name
            FROM company_tasks t
            LEFT JOIN users u ON t.assigned_to = u.id
            WHERE t.tenant_id = ?
            ORDER BY t.created_at DESC
        ");
        $stmt->execute([$tenantId]);
        $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->json(['data' => $tasks]);
    }

    public function create($params)
    {
        $this->authenticate();
        $tenantId = $params['tenantId'];
        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data['title'])) {
            $this->json(['error' => 'Görev başlığı zorunludur.'], 400);
            return;
        }

        $id = $this->generateUuid();
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            INSERT INTO company_tasks (id, tenant_id, title, description, assigned_to, priority, status, due_date)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $id,
            $tenantId,
            $data['title'],
            $data['description'] ?? '',
            $data['assigned_to'] ?? null,
            $data['priority'] ?? 'medium',
            $data['status'] ?? 'todo',
            $data['due_date'] ?? null
        ]);

        $this->json(['data' => ['id' => $id, 'message' => 'Görev oluşturuldu']], 201);
    }

    public function update($params)
    {
        $this->authenticate();
        $tenantId = $params['tenantId'];
        $taskId = $params['taskId'];
        $data = json_decode(file_get_contents('php://input'), true);

        $db = Database::getInstance()->getConnection();
        $fields = [];
        $values = [];

        foreach (['title', 'description', 'assigned_to', 'priority', 'status', 'due_date'] as $key) {
            if (isset($data[$key])) {
                $fields[] = "$key = ?";
                $values[] = $data[$key];
            }
        }

        if (empty($fields)) {
            $this->json(['error' => 'Güncellenecek alan yok'], 400);
            return;
        }

        $values[] = $taskId;
        $values[] = $tenantId;
        $sql = "UPDATE company_tasks SET " . implode(', ', $fields) . " WHERE id = ? AND tenant_id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute($values);

        $this->json(['message' => 'Görev güncellendi']);
    }

    public function delete($params)
    {
        $this->authenticate();
        $tenantId = $params['tenantId'];
        $taskId = $params['taskId'];

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("DELETE FROM company_tasks WHERE id = ? AND tenant_id = ?");
        $stmt->execute([$taskId, $tenantId]);

        $this->json(['message' => 'Görev silindi']);
    }
}
