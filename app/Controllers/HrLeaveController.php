<?php

namespace App\Controllers;

use App\Models\HrLeaveRequest;
use App\Services\AuthService;
use App\Core\Database;
use PDO;

class HrLeaveController
{
    private $leaveModel;
    private $authService;
    private $currentUser;

    public function __construct()
    {
        $this->leaveModel = new HrLeaveRequest();
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
            SELECT l.*, e.first_name, e.last_name, e.email as employee_email, d.name as department_name
            FROM hr_leave_requests l
            LEFT JOIN employees e ON l.employee_id = e.id
            LEFT JOIN departments d ON e.department_id = d.id
            WHERE l.tenant_id = ?
            ORDER BY l.created_at DESC
        ");
        $stmt->execute([$tenantId]);
        $leaves = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->json(['data' => $leaves]);
    }

    public function create($params)
    {
        $this->authenticate();
        $tenantId = $params['tenantId'];
        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data['employee_id']) || empty($data['start_date']) || empty($data['end_date'])) {
            $this->json(['error' => 'Çalışan ve tarih alanları zorunludur.'], 400);
            return;
        }

        $id = $this->generateUuid();
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            INSERT INTO hr_leave_requests (id, tenant_id, employee_id, leave_type, start_date, end_date, days, reason, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $id,
            $tenantId,
            $data['employee_id'],
            $data['leave_type'] ?? 'Yıllık İzin',
            $data['start_date'],
            $data['end_date'],
            $data['days'] ?? 1,
            $data['reason'] ?? '',
            'pending'
        ]);

        $this->json(['data' => ['id' => $id, 'message' => 'İzin talebi oluşturuldu']], 201);
    }

    public function updateStatus($params)
    {
        $this->authenticate();
        $tenantId = $params['tenantId'];
        $leaveId = $params['leaveId'];
        $data = json_decode(file_get_contents('php://input'), true);

        $status = $data['status'] ?? 'approved';

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("UPDATE hr_leave_requests SET status = ? WHERE id = ? AND tenant_id = ?");
        $stmt->execute([$status, $leaveId, $tenantId]);

        $this->json(['message' => 'İzin durumu güncellendi']);
    }

    public function delete($params)
    {
        $this->authenticate();
        $tenantId = $params['tenantId'];
        $leaveId = $params['leaveId'];

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("DELETE FROM hr_leave_requests WHERE id = ? AND tenant_id = ?");
        $stmt->execute([$leaveId, $tenantId]);

        $this->json(['message' => 'İzin talebi silindi']);
    }
}
