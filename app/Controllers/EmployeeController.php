<?php

namespace App\Controllers;

use App\Models\Employee;
use App\Services\AuthService;

class EmployeeController
{
    private $employeeModel;
    private $authService;
    private $currentUser;

    public function __construct()
    {
        $this->employeeModel = new Employee();
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

        $db = \App\Core\Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            SELECT e.*, 
                   COALESCE(u.full_name, 'İsimsiz Personel') as full_name, 
                   u.email as email, 
                   u.avatar_url,
                   COALESCE(d.name, 'Genel Departman') as department_name, 
                   COALESCE(p.title, 'Uzman Personel') as position_title 
            FROM employees e 
            LEFT JOIN users u ON e.user_id = u.id 
            LEFT JOIN departments d ON e.department_id = d.id 
            LEFT JOIN positions p ON e.position_id = p.id 
            WHERE e.tenant_id = ?
            ORDER BY e.created_at DESC
        ");
        $stmt->execute([$tenantId]);
        $employees = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $this->json(['data' => $employees]);
    }

    public function create($params)
    {
        $user = $this->authenticate();
        $tenantId = $params['tenantId'];
        $data = json_decode(file_get_contents('php://input'), true);

        $db = \App\Core\Database::getInstance()->getConnection();
        $userId = $data['user_id'] ?? null;

        // Eğer user_id verilmemişse ama full_name ve email varsa kullanıcı oluştur
        if (empty($userId) && !empty($data['email'])) {
            $userModel = new \App\Models\User();
            $existing = $userModel->findOne(['email' => $data['email']]);
            if ($existing) {
                $userId = $existing['id'];
            } else {
                $newUser = $userModel->create([
                    'email' => $data['email'],
                    'password_hash' => password_hash('CoreFly123!', PASSWORD_DEFAULT),
                    'full_name' => $data['full_name'] ?? 'Yeni Personel',
                    'tenant_id' => $tenantId,
                    'role' => 'member'
                ]);
                $userId = $newUser['id'];
            }
        }

        if (empty($userId)) {
            $this->json(['error' => 'Geçerli bir kullanıcı veya e-posta adresi gereklidir.'], 400);
            return;
        }

        $empNumber = $data['employee_number'] ?? ('EMP-' . rand(1000, 9999));
        $employee = $this->employeeModel->create([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'employee_number' => $empNumber,
            'hire_date' => $data['hire_date'] ?? date('Y-m-d'),
            'termination_date' => $data['termination_date'] ?? null,
            'department_id' => $data['department_id'] ?? null,
            'position_id' => $data['position_id'] ?? null,
            'manager_id' => $data['manager_id'] ?? null,
            'employment_type' => $data['employment_type'] ?? 'full_time',
            'salary' => $data['salary'] ?? 35000,
            'currency' => $data['currency'] ?? 'TRY',
            'status' => $data['status'] ?? 'active'
        ]);

        $this->json(['data' => $employee, 'message' => 'Personel başarıyla sisteme kaydedildi.'], 201);
    }

    public function show($params)
    {
        $user = $this->authenticate();
        $tenantId = $params['tenantId'];
        $employeeId = $params['employeeId'];

        if ($tenantId !== $user['tenant_id']) {
            $this->json(['error' => 'Forbidden'], 403);
            return;
        }

        $employee = $this->employeeModel->find($employeeId);
        if (!$employee || $employee['tenant_id'] !== $tenantId) {
            $this->json(['error' => 'Employee not found'], 404);
            return;
        }

        $this->json(['data' => $employee]);
    }

    private function json($data, $status = 200)
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
    }
}
