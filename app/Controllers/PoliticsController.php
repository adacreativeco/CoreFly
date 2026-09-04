<?php

namespace App\Controllers;

use App\Models\PoliticsVolunteer;
use App\Services\AuthService;
use App\Core\Database;
use PDO;

class PoliticsController
{
    private $volunteerModel;
    private $authService;
    private $currentUser;

    public function __construct()
    {
        $this->volunteerModel = new PoliticsVolunteer();
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

        $sql = "SELECT * FROM politics_volunteers WHERE tenant_id = ?";
        $binds = [$tenantId];

        if (!empty($search)) {
            $sql .= " AND (full_name LIKE ? OR district LIKE ? OR neighborhood LIKE ? OR ballot_box_number LIKE ?)";
            $s = "%$search%";
            $binds[] = $s; $binds[] = $s; $binds[] = $s; $binds[] = $s;
        }

        $sql .= " ORDER BY full_name ASC";

        $stmt = $db->prepare($sql);
        $stmt->execute($binds);
        $volunteers = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->json(['data' => $volunteers]);
    }

    public function create($params)
    {
        $user = $this->authenticate();
        $tenantId = $params['tenantId'];
        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data['full_name'])) {
            $this->json(['error' => 'Full name is required'], 400);
        }

        $id = $this->generateUuid();
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            INSERT INTO politics_volunteers (id, tenant_id, full_name, phone, city, district, neighborhood, ballot_box_number, role, notes)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $id,
            $tenantId,
            $data['full_name'],
            $data['phone'] ?? null,
            $data['city'] ?? 'İstanbul',
            $data['district'] ?? null,
            $data['neighborhood'] ?? null,
            $data['ballot_box_number'] ?? null,
            $data['role'] ?? 'member',
            $data['notes'] ?? null
        ]);

        $created = $this->volunteerModel->find(['id' => $id, 'tenant_id' => $tenantId]);
        $this->json(['data' => $created], 201);
    }

    public function stats($params)
    {
        $user = $this->authenticate();
        $tenantId = $params['tenantId'];

        $db = Database::getInstance()->getConnection();
        $totalVolunteers = $db->query("SELECT COUNT(*) FROM politics_volunteers WHERE tenant_id = '$tenantId'")->fetchColumn();
        $ballotOfficers = $db->query("SELECT COUNT(*) FROM politics_volunteers WHERE tenant_id = '$tenantId' AND role = 'ballot_officer'")->fetchColumn();
        $coveredBoxes = $db->query("SELECT COUNT(DISTINCT ballot_box_number) FROM politics_volunteers WHERE tenant_id = '$tenantId' AND ballot_box_number IS NOT NULL AND ballot_box_number != ''")->fetchColumn();

        $this->json([
            'data' => [
                'total_members' => (int)$totalVolunteers,
                'ballot_officers' => (int)$ballotOfficers,
                'covered_boxes' => (int)$coveredBoxes
            ]
        ]);
    }
}
