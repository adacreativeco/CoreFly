<?php

namespace App\Controllers;

use App\Models\Project;
use App\Models\ProjectMember;
use App\Services\AuthService;

class ProjectController
{
    private $projectModel;
    private $projectMemberModel;
    private $authService;
    private $currentUser;

    public function __construct()
    {
        $this->projectModel = new Project();
        $this->projectMemberModel = new ProjectMember();
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

        $projects = $this->projectModel->findAll(['tenant_id' => $tenantId]);
        $this->json(['data' => $projects]);
    }

    public function create($params)
    {
        $user = $this->authenticate();
        $tenantId = $params['tenantId'];
        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data['name'])) {
            $this->json(['error' => 'Name required'], 400);
            return;
        }

        $project = $this->projectModel->create([
            'tenant_id' => $tenantId,
            'code' => $data['code'] ?? null,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'type' => $data['type'] ?? 'internal',
            'status' => $data['status'] ?? 'planning',
            'priority' => $data['priority'] ?? 'medium',
            'start_date' => $data['start_date'] ?? null,
            'end_date' => $data['end_date'] ?? null,
            'budget' => $data['budget'] ?? null,
            'currency' => $data['currency'] ?? 'USD',
            'created_by' => $user['id']
        ]);

        // Add creator as project manager/member
        $this->projectMemberModel->create([
            'project_id' => $project['id'],
            'user_id' => $user['id'],
            'role' => 'manager',
            'responsibilities' => 'Project Creator'
        ]);

        $this->json(['data' => $project], 201);
    }

    public function show($params)
    {
        $user = $this->authenticate();
        $tenantId = $params['tenantId'];
        $projectId = $params['projectId'];

        if ($tenantId !== $user['tenant_id']) {
            $this->json(['error' => 'Forbidden'], 403);
            return;
        }

        $project = $this->projectModel->find($projectId);
        if (!$project || $project['tenant_id'] !== $tenantId) {
            $this->json(['error' => 'Project not found'], 404);
            return;
        }

        $this->json(['data' => $project]);
    }

    public function addMember($params)
    {
        $user = $this->authenticate();
        $tenantId = $params['tenantId'];
        $projectId = $params['projectId'];
        $data = json_decode(file_get_contents('php://input'), true);

        // Verify project ownership/access (simplified)
        $project = $this->projectModel->find($projectId);
        if (!$project || $project['tenant_id'] !== $tenantId) {
            $this->json(['error' => 'Project not found'], 404);
            return;
        }

        if (empty($data['user_id'])) {
            $this->json(['error' => 'User ID required'], 400);
            return;
        }

        $member = $this->projectMemberModel->create([
            'project_id' => $projectId,
            'user_id' => $data['user_id'],
            'role' => $data['role'] ?? 'member',
            'responsibilities' => $data['responsibilities'] ?? null
        ]);

        $this->json(['data' => $member], 201);
    }

    private function json($data, $status = 200)
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
    }
}
