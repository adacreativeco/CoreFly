<?php

namespace App\Controllers;

use App\Models\InventorySupplier;
use App\Services\AuthService;
use App\Core\Database;
use PDO;

class InventorySupplierController
{
    private $supplierModel;
    private $authService;

    public function __construct()
    {
        $this->supplierModel = new InventorySupplier();
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
        $stmt = $db->prepare("SELECT * FROM inventory_suppliers WHERE tenant_id = ? ORDER BY created_at DESC");
        $stmt->execute([$tenantId]);
        $suppliers = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->json(['data' => $suppliers]);
    }

    public function create($params)
    {
        $this->authenticate();
        $tenantId = $params['tenantId'];
        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data['name'])) {
            $this->json(['error' => 'Tedarikçi adı zorunludur.'], 400);
            return;
        }

        $id = $this->generateUuid();
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            INSERT INTO inventory_suppliers (id, tenant_id, name, contact_person, phone, email, address, tax_number)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $id,
            $tenantId,
            $data['name'],
            $data['contact_person'] ?? '',
            $data['phone'] ?? '',
            $data['email'] ?? '',
            $data['address'] ?? '',
            $data['tax_number'] ?? ''
        ]);

        $this->json(['data' => ['id' => $id, 'message' => 'Tedarikçi eklendi']], 201);
    }

    public function delete($params)
    {
        $this->authenticate();
        $tenantId = $params['tenantId'];
        $supplierId = $params['supplierId'];

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("DELETE FROM inventory_suppliers WHERE id = ? AND tenant_id = ?");
        $stmt->execute([$supplierId, $tenantId]);

        $this->json(['message' => 'Tedarikçi silindi']);
    }
}
