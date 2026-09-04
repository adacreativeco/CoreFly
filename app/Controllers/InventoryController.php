<?php

namespace App\Controllers;

use App\Models\InventoryProduct;
use App\Models\InventoryMovement;
use App\Models\InventoryCategory;
use App\Services\AuthService;
use App\Core\Database;
use PDO;

class InventoryController
{
    private $productModel;
    private $movementModel;
    private $categoryModel;
    private $authService;
    private $currentUser;

    public function __construct()
    {
        $this->productModel = new InventoryProduct();
        $this->movementModel = new InventoryMovement();
        $this->categoryModel = new InventoryCategory();
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

    // --- PRODUCTS ---

    public function indexProducts($params)
    {
        $user = $this->authenticate();
        $tenantId = $params['tenantId'];

        $db = Database::getInstance()->getConnection();
        $search = $_GET['search'] ?? '';
        $lowStock = $_GET['low_stock'] ?? '';
        $categoryId = $_GET['category_id'] ?? '';

        $sql = "
            SELECT p.*, c.name as category_name 
            FROM inventory_products p 
            LEFT JOIN inventory_categories c ON p.category_id = c.id 
            WHERE p.tenant_id = ?
        ";
        $binds = [$tenantId];

        if (!empty($search)) {
            $sql .= " AND (p.name LIKE ? OR p.sku LIKE ? OR p.barcode LIKE ?)";
            $s = "%$search%";
            $binds[] = $s; $binds[] = $s; $binds[] = $s;
        }

        if (!empty($categoryId)) {
            $sql .= " AND p.category_id = ?";
            $binds[] = $categoryId;
        }

        if ($lowStock === 'true') {
            $sql .= " AND p.quantity <= p.min_quantity";
        }

        $sql .= " ORDER BY p.name ASC";

        $stmt = $db->prepare($sql);
        $stmt->execute($binds);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->json(['data' => $products]);
    }

    public function createProduct($params)
    {
        $user = $this->authenticate();
        $tenantId = $params['tenantId'];
        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data['name'])) {
            $this->json(['error' => 'Product name is required'], 400);
        }

        $id = $this->generateUuid();
        $sku = !empty($data['sku']) ? $data['sku'] : 'SKU-' . strtoupper(substr(uniqid(), -6));
        $initialQty = (int)($data['quantity'] ?? 0);

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            INSERT INTO inventory_products (
                id, tenant_id, category_id, name, sku, barcode, description, 
                quantity, min_quantity, unit, unit_cost, unit_price, location, status, created_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $id,
            $tenantId,
            $data['category_id'] ?? null,
            $data['name'],
            $sku,
            $data['barcode'] ?? null,
            $data['description'] ?? null,
            $initialQty,
            (int)($data['min_quantity'] ?? 5),
            $data['unit'] ?? 'Adet',
            (float)($data['unit_cost'] ?? 0),
            (float)($data['unit_price'] ?? 0),
            $data['location'] ?? null,
            $data['status'] ?? 'active',
            $user['sub'] ?? null
        ]);

        // If initial quantity > 0, record movement
        if ($initialQty > 0) {
            $movementId = $this->generateUuid();
            $mStmt = $db->prepare("
                INSERT INTO inventory_movements (id, tenant_id, product_id, movement_type, quantity, previous_quantity, new_quantity, reason, created_by)
                VALUES (?, ?, ?, 'in', ?, 0, ?, 'İlk stok açılışı', ?)
            ");
            $mStmt->execute([$movementId, $tenantId, $id, $initialQty, $initialQty, $user['sub'] ?? null]);
        }

        $created = $this->productModel->find(['id' => $id, 'tenant_id' => $tenantId]);
        $this->json(['data' => $created], 201);
    }

    public function adjustStock($params)
    {
        $user = $this->authenticate();
        $tenantId = $params['tenantId'];
        $productId = $params['productId'];
        $data = json_decode(file_get_contents('php://input'), true);

        $type = $data['type'] ?? 'in'; // 'in', 'out', 'adjustment'
        $qty = (int)($data['quantity'] ?? 0);
        $reason = $data['reason'] ?? 'Stok güncelleme';

        if ($qty <= 0 && $type !== 'adjustment') {
            $this->json(['error' => 'Quantity must be greater than 0'], 400);
        }

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT quantity FROM inventory_products WHERE id = ? AND tenant_id = ?");
        $stmt->execute([$productId, $tenantId]);
        $current = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$current) {
            $this->json(['error' => 'Product not found'], 404);
        }

        $oldQty = (int)$current['quantity'];
        if ($type === 'in') {
            $newQty = $oldQty + $qty;
        } elseif ($type === 'out') {
            $newQty = max(0, $oldQty - $qty);
        } else {
            $newQty = max(0, $qty);
        }

        // Update product quantity
        $uStmt = $db->prepare("UPDATE inventory_products SET quantity = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $uStmt->execute([$newQty, $productId]);

        // Insert movement
        $movementId = $this->generateUuid();
        $mStmt = $db->prepare("
            INSERT INTO inventory_movements (id, tenant_id, product_id, movement_type, quantity, previous_quantity, new_quantity, reason, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $mStmt->execute([
            $movementId,
            $tenantId,
            $productId,
            $type,
            abs($newQty - $oldQty),
            $oldQty,
            $newQty,
            $reason,
            $user['sub'] ?? null
        ]);

        $this->json(['data' => ['previous_quantity' => $oldQty, 'new_quantity' => $newQty]]);
    }

    public function deleteProduct($params)
    {
        $user = $this->authenticate();
        $tenantId = $params['tenantId'];
        $productId = $params['productId'];

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("DELETE FROM inventory_products WHERE id = ? AND tenant_id = ?");
        $stmt->execute([$productId, $tenantId]);

        $this->json(['success' => true]);
    }

    // --- MOVEMENTS ---

    public function indexMovements($params)
    {
        $user = $this->authenticate();
        $tenantId = $params['tenantId'];

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            SELECT m.*, p.name as product_name, p.sku as product_sku, p.unit as product_unit 
            FROM inventory_movements m 
            JOIN inventory_products p ON m.product_id = p.id 
            WHERE m.tenant_id = ? 
            ORDER BY m.created_at DESC 
            LIMIT 100
        ");
        $stmt->execute([$tenantId]);
        $movements = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->json(['data' => $movements]);
    }

    // --- CATEGORIES ---

    public function indexCategories($params)
    {
        $user = $this->authenticate();
        $tenantId = $params['tenantId'];

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM inventory_categories WHERE tenant_id = ? ORDER BY name ASC");
        $stmt->execute([$tenantId]);
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->json(['data' => $categories]);
    }

    public function createCategory($params)
    {
        $user = $this->authenticate();
        $tenantId = $params['tenantId'];
        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data['name'])) {
            $this->json(['error' => 'Category name is required'], 400);
        }

        $id = $this->generateUuid();
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("INSERT INTO inventory_categories (id, tenant_id, name, description) VALUES (?, ?, ?, ?)");
        $stmt->execute([$id, $tenantId, $data['name'], $data['description'] ?? null]);

        $this->json(['data' => ['id' => $id, 'name' => $data['name']]], 201);
    }

    // --- STATS ---

    public function stats($params)
    {
        $user = $this->authenticate();
        $tenantId = $params['tenantId'];

        $db = Database::getInstance()->getConnection();

        $totalItems = $db->query("SELECT COUNT(*) FROM inventory_products WHERE tenant_id = '$tenantId'")->fetchColumn();
        $totalQuantity = $db->query("SELECT SUM(quantity) FROM inventory_products WHERE tenant_id = '$tenantId'")->fetchColumn() ?: 0;
        $totalValue = $db->query("SELECT SUM(quantity * unit_cost) FROM inventory_products WHERE tenant_id = '$tenantId'")->fetchColumn() ?: 0;
        $lowStockCount = $db->query("SELECT COUNT(*) FROM inventory_products WHERE tenant_id = '$tenantId' AND quantity <= min_quantity")->fetchColumn();

        $this->json([
            'data' => [
                'total_products' => (int)$totalItems,
                'total_quantity' => (int)$totalQuantity,
                'total_value' => (float)$totalValue,
                'low_stock_count' => (int)$lowStockCount
            ]
        ]);
    }
}
