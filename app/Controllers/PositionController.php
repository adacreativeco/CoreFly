<?php

namespace App\Controllers;

use App\Models\Position;
use App\Services\AuthService;

class PositionController
{
    private $positionModel;
    private $authService;
    private $currentUser;

    public function __construct()
    {
        $this->positionModel = new Position();
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

        $positions = $this->positionModel->findAll(['tenant_id' => $tenantId]);
        $this->json(['data' => $positions]);
    }

    public function create($params)
    {
        $user = $this->authenticate();
        $tenantId = $params['tenantId'];
        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data['title'])) {
            $this->json(['error' => 'Title required'], 400);
            return;
        }

        $position = $this->positionModel->create([
            'tenant_id' => $tenantId,
            'title' => $data['title'],
            'code' => $data['code'] ?? null,
            'description' => $data['description'] ?? null,
            'department_id' => $data['department_id'] ?? null,
            'level' => $data['level'] ?? null,
            'min_salary' => $data['min_salary'] ?? null,
            'max_salary' => $data['max_salary'] ?? null,
            'currency' => $data['currency'] ?? 'USD',
            'reporting_to' => $data['reporting_to'] ?? null
        ]);

        $this->json(['data' => $position], 201);
    }

    private function json($data, $status = 200)
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
    }
}
