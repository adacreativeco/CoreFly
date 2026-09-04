<?php

namespace App\Controllers;

use App\Models\HrPayroll;
use App\Services\AuthService;
use App\Core\Database;
use PDO;

class HrPayrollController
{
    private $payrollModel;
    private $authService;
    private $currentUser;

    public function __construct()
    {
        $this->payrollModel = new HrPayroll();
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
            SELECT p.*, e.first_name, e.last_name, e.email as employee_email, d.name as department_name
            FROM hr_payrolls p
            LEFT JOIN employees e ON p.employee_id = e.id
            LEFT JOIN departments d ON e.department_id = d.id
            WHERE p.tenant_id = ?
            ORDER BY p.period DESC, p.created_at DESC
        ");
        $stmt->execute([$tenantId]);
        $payrolls = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->json(['data' => $payrolls]);
    }

    public function create($params)
    {
        $this->authenticate();
        $tenantId = $params['tenantId'];
        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data['employee_id']) || empty($data['period'])) {
            $this->json(['error' => 'Çalışan ve bordro dönemi zorunludur.'], 400);
            return;
        }

        $base = floatval($data['base_salary'] ?? 0);
        $bonus = floatval($data['bonus'] ?? 0);
        $deductions = floatval($data['deductions'] ?? 0);
        $net = $base + $bonus - $deductions;

        $id = $this->generateUuid();
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            INSERT INTO hr_payrolls (id, tenant_id, employee_id, period, base_salary, bonus, deductions, net_salary, status, payment_date)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $id,
            $tenantId,
            $data['employee_id'],
            $data['period'],
            $base,
            $bonus,
            $deductions,
            $net,
            $data['status'] ?? 'pending',
            $data['payment_date'] ?? null
        ]);

        $this->json(['data' => ['id' => $id, 'net_salary' => $net, 'message' => 'Bordro kaydı oluşturuldu']], 201);
    }

    public function updateStatus($params)
    {
        $this->authenticate();
        $tenantId = $params['tenantId'];
        $payrollId = $params['payrollId'];
        $data = json_decode(file_get_contents('php://input'), true);

        $status = $data['status'] ?? 'paid';
        $paymentDate = $status === 'paid' ? date('Y-m-d') : null;

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("UPDATE hr_payrolls SET status = ?, payment_date = ? WHERE id = ? AND tenant_id = ?");
        $stmt->execute([$status, $paymentDate, $payrollId, $tenantId]);

        $this->json(['message' => 'Bordro durumu güncellendi']);
    }

    public function delete($params)
    {
        $this->authenticate();
        $tenantId = $params['tenantId'];
        $payrollId = $params['payrollId'];

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("DELETE FROM hr_payrolls WHERE id = ? AND tenant_id = ?");
        $stmt->execute([$payrollId, $tenantId]);

        $this->json(['message' => 'Bordro silindi']);
    }
}
