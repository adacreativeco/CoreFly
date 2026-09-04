<?php

namespace App\Controllers;

use App\Models\ProjectTask;
use App\Models\Project;
use App\Services\AuthService;

class ProjectTaskController
{
    private $projectTaskModel;
    private $projectModel;
    private $authService;
    private $currentUser;

    public function __construct()
    {
        $this->projectTaskModel = new ProjectTask();
        $this->projectModel = new Project();
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
        $projectId = $params['projectId'];

        if ($tenantId !== $user['tenant_id']) {
            $this->json(['error' => 'Forbidden'], 403);
            return;
        }

        $tasks = $this->projectTaskModel->findAll([
            'tenant_id' => $tenantId,
            'project_id' => $projectId
        ]);
        $this->json(['data' => $tasks]);
    }

    public function create($params)
    {
        $user = $this->authenticate();
        $tenantId = $params['tenantId'];
        $projectId = $params['projectId'];
        $data = json_decode(file_get_contents('php://input'), true);

        // Verify project exists
        $project = $this->projectModel->find($projectId);
        if (!$project || $project['tenant_id'] !== $tenantId) {
            $this->json(['error' => 'Project not found'], 404);
            return;
        }

        if (empty($data['title'])) {
            $this->json(['error' => 'Title required'], 400);
            return;
        }

        $task = $this->projectTaskModel->create([
            'tenant_id' => $tenantId,
            'project_id' => $projectId,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'parent_id' => $data['parent_id'] ?? null,
            'assignee_id' => $data['assignee_id'] ?? null,
            'created_by' => $user['id'],
            'type' => $data['type'] ?? 'task',
            'status' => $data['status'] ?? 'todo',
            'priority' => $data['priority'] ?? 'medium',
            'due_date' => $data['due_date'] ?? null
        ]);

        $this->json(['data' => $task], 201);
    }

    public function update($params)
    {
        $user = $this->authenticate();
        $tenantId = $params['tenantId'];
        $projectId = $params['projectId'];
        $taskId = $params['taskId'];
        $data = json_decode(file_get_contents('php://input'), true);

        // Verify task exists and belongs to tenant/project
        $task = $this->projectTaskModel->find($taskId);
        if (!$task || $task['tenant_id'] !== $tenantId || $task['project_id'] !== $projectId) {
            $this->json(['error' => 'Task not found'], 404);
            return;
        }

        $updated = $this->projectTaskModel->update($taskId, $data);
        
        // Fetch updated task
        $task = $this->projectTaskModel->find($taskId);
        $this->json(['data' => $task]);
    }

    private function json($data, $status = 200)
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
    }
}
