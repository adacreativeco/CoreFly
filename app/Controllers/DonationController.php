<?php

namespace App\Controllers;

use App\Models\Donation;
use App\Services\AuthService;
use App\Core\Database;
use PDO;

class DonationController
{
    private $donationModel;
    private $authService;
    private $currentUser;

    public function __construct()
    {
        $this->donationModel = new Donation();
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

        $sql = "SELECT * FROM donations WHERE tenant_id = ?";
        $binds = [$tenantId];

        if (!empty($search)) {
            $sql .= " AND (donor_name LIKE ? OR campaign_name LIKE ? OR donor_phone LIKE ?)";
            $s = "%$search%";
            $binds[] = $s; $binds[] = $s; $binds[] = $s;
        }

        $sql .= " ORDER BY created_at DESC";

        $stmt = $db->prepare($sql);
        $stmt->execute($binds);
        $donations = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->json(['data' => $donations]);
    }

    public function create($params)
    {
        $user = $this->authenticate();
        $tenantId = $params['tenantId'];
        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data['donor_name']) || empty($data['amount'])) {
            $this->json(['error' => 'Donor name and amount are required'], 400);
        }

        $id = $this->generateUuid();
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            INSERT INTO donations (id, tenant_id, donor_name, donor_email, donor_phone, amount, currency, campaign_name, payment_method, notes, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $id,
            $tenantId,
            $data['donor_name'],
            $data['donor_email'] ?? null,
            $data['donor_phone'] ?? null,
            (float)$data['amount'],
            $data['currency'] ?? 'TRY',
            $data['campaign_name'] ?? 'Genel Bağış',
            $data['payment_method'] ?? 'bank',
            $data['notes'] ?? null,
            $user['sub'] ?? 'system'
        ]);

        $created = $this->donationModel->find(['id' => $id, 'tenant_id' => $tenantId]);
        $this->json(['data' => $created], 201);
    }

    public function stats($params)
    {
        $user = $this->authenticate();
        $tenantId = $params['tenantId'];

        $db = Database::getInstance()->getConnection();
        $totalAmount = $db->query("SELECT SUM(amount) FROM donations WHERE tenant_id = '$tenantId'")->fetchColumn() ?: 0;
        $totalDonors = $db->query("SELECT COUNT(DISTINCT donor_name) FROM donations WHERE tenant_id = '$tenantId'")->fetchColumn();
        $totalCount = $db->query("SELECT COUNT(*) FROM donations WHERE tenant_id = '$tenantId'")->fetchColumn();

        $this->json([
            'data' => [
                'total_amount' => (float)$totalAmount,
                'total_donors' => (int)$totalDonors,
                'total_donations' => (int)$totalCount
            ]
        ]);
    }
}
