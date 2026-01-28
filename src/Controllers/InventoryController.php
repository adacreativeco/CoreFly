<?php

declare(strict_types=1);

namespace CoreFly\Controllers;

use CoreFly\Models\Inventory;
use CoreFly\Models\InventoryCategory;
use CoreFly\Models\Supplier;

class InventoryController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        // Schema creation moved to migration files
    }

    public function index(): string
    {
        try {
            $this->requirePermission('inventory:read');
            $tenantId = $this->getCurrentTenantId();
            
            $page = (int)($this->getQueryParam('page', 1));
            $limit = (int)($this->getQueryParam('limit', 20));
            $offset = ($page - 1) * $limit;
            
            $search = $this->getQueryParam('search', '');
            $categoryId = $this->getQueryParam('category_id', '');
            $supplierId = $this->getQueryParam('supplier_id', '');
            $status = $this->getQueryParam('status', '');
            $lowStock = $this->getQueryParam('low_stock', '');
            
            $query = Inventory::where('tenant_id', $tenantId);
            
            if (!empty($search)) {
                $query->where('(name LIKE :search OR sku LIKE :search OR barcode LIKE :search)');
                $query->setBindings(['search' => "%{$search}%"]);
            }
            
            if (!empty($categoryId)) {
                $query->where('category_id', $categoryId);
            }

            if (!empty($supplierId)) {
                $query->where('supplier_id', $supplierId);
            }
            
            if (!empty($status)) {
                $query->where('status', $status);
            }
            
            if ($lowStock === 'true') {
                $query->where('quantity <= min_quantity');
            }
            
            $total = $query->count();
            $items = $query->orderBy('name', 'ASC')->limit($limit)->offset($offset)->get();
            
            // Enrich items with category and supplier names if needed
            foreach ($items as $item) {
                if (!empty($item->category_id)) {
                    $cat = InventoryCategory::find($item->category_id);
                    if ($cat) $item->category_name = $cat->name;
                }
                if (!empty($item->supplier_id)) {
                    $sup = Supplier::find($item->supplier_id);
                    if ($sup) $item->supplier_name = $sup->name;
                }
            }

            return $this->successResponse([
                'items' => $items,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'pages' => ceil($total / $limit)
                ]
            ], 'Envanter listesi getirildi');
        } catch (\Throwable $e) {
            return $this->errorResponse('Inventory Error: ' . $e->getMessage(), 500);
        }
    }

    public function show(?string $id = null): string
    {
        $this->requirePermission('inventory:read');
        $tenantId = $this->getCurrentTenantId();
        
        $item = Inventory::find($id);
        
        if (!$item || $item->tenant_id !== $tenantId) {
            return $this->errorResponse('Envanter bulunamadı', 404);
        }
        
        $movements = $item->getMovements(20);
        $supplier = $item->getSupplier();
        
        return $this->successResponse([
            'item' => $item,
            'movements' => $movements,
            'supplier' => $supplier
        ], 'Envanter detayı getirildi');
    }

    public function store(): string
    {
        $this->requirePermission('inventory:create');
        $tenantId = $this->getCurrentTenantId();
        $userId = $this->getCurrentUserId();
        
        $data = $this->getJsonInput();
        
        $requiredFields = ['name', 'quantity', 'unit', 'unit_cost'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                return $this->errorResponse("{$field} alanı zorunludur", 400);
            }
        }
        
        $inventory = new Inventory([
            'id' => $this->generateUUID(),
            'tenant_id' => $tenantId,
            'name' => $data['name'],
            'description' => $data['description'] ?? '',
            'category' => $data['category'] ?? '', // Legacy fallback
            'category_id' => $data['category_id'] ?? '',
            'sku' => $data['sku'] ?? '',
            'barcode' => $data['barcode'] ?? '',
            'quantity' => (int)$data['quantity'],
            'min_quantity' => (int)($data['min_quantity'] ?? 0),
            'max_quantity' => (int)($data['max_quantity'] ?? 1000),
            'unit' => $data['unit'],
            'unit_cost' => (float)$data['unit_cost'],
            'location' => $data['location'] ?? '',
            'supplier_id' => $data['supplier_id'] ?? '',
            'status' => $data['status'] ?? 'active',
            'is_active' => true,
            'expiry_date' => $data['expiry_date'] ?? '',
            'created_by' => $userId,
            'updated_by' => $userId,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        
        if ($inventory->save()) {
            $this->logActivity('inventory_created', 'inventory', $inventory->id, ['name' => $inventory->name]);
            return $this->successResponse($inventory, 'Envanter oluşturuldu', 201);
        }
        
        return $this->errorResponse('Envanter oluşturulamadı', 500);
    }

    public function update(?string $id = null): string
    {
        $this->requirePermission('inventory:update');
        $tenantId = $this->getCurrentTenantId();
        $userId = $this->getCurrentUserId();
        
        $item = Inventory::find($id);
        
        if (!$item || $item->tenant_id !== $tenantId) {
            return $this->errorResponse('Envanter bulunamadı', 404);
        }
        
        $data = $this->getJsonInput();
        
        $fields = ['name', 'description', 'category', 'category_id', 'sku', 'barcode', 'unit', 'location', 'supplier_id', 'status', 'expiry_date'];
        foreach ($fields as $field) {
            if (isset($data[$field])) {
                $item->$field = $data[$field];
            }
        }

        if (isset($data['unit_cost'])) {
            $item->unit_cost = (float)$data['unit_cost'];
            $item->calculateTotalValue();
        }
        
        $item->updated_by = $userId;
        $item->updated_at = date('Y-m-d H:i:s');
        
        if ($item->save()) {
            return $this->successResponse($item, 'Envanter güncellendi');
        }
        
        return $this->errorResponse('Envanter güncellenemedi', 500);
    }

    public function adjustQuantity(?string $id = null): string
    {
        $this->requirePermission('inventory:update');
        $tenantId = $this->getCurrentTenantId();
        $userId = $this->getCurrentUserId();
        
        $item = Inventory::find($id);
        
        if (!$item || $item->tenant_id !== $tenantId) {
            return $this->errorResponse('Envanter bulunamadı', 404);
        }
        
        $data = $this->getJsonInput();
        
        if (!isset($data['adjustment']) || !is_numeric($data['adjustment'])) {
            return $this->errorResponse('Geçerli bir miktar ayarlaması gereklidir', 400);
        }
        
        $adjustment = (int)$data['adjustment'];
        $reason = $data['reason'] ?? 'Manuel ayarlama';
        
        try {
            if ($item->adjustQuantity($adjustment, $reason, $userId)) {
                return $this->successResponse($item, 'Envanter miktarı güncellendi');
            }
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
        
        return $this->errorResponse('Envanter miktarı güncellenemedi', 500);
    }

    public function destroy(?string $id = null): string
    {
        $this->requirePermission('inventory:delete');
        $tenantId = $this->getCurrentTenantId();
        
        $item = Inventory::find($id);
        
        if (!$item || $item->tenant_id !== $tenantId) {
            return $this->errorResponse('Envanter bulunamadı', 404);
        }
        
        $item->is_active = false;
        $item->status = 'deleted';
        
        if ($item->save()) {
            return $this->successResponse(null, 'Envanter silindi');
        }
        
        return $this->errorResponse('Envanter silinemedi', 500);
    }

    // Category Methods

    public function getCategories(): string
    {
        $this->requirePermission('inventory:read');
        $tenantId = $this->getCurrentTenantId();
        
        $categories = InventoryCategory::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name', 'ASC')
            ->get();
            
        return $this->successResponse($categories, 'Kategoriler getirildi');
    }

    public function storeCategory(): string
    {
        $this->requirePermission('inventory:create');
        $tenantId = $this->getCurrentTenantId();
        $userId = $this->getCurrentUserId();
        
        $data = $this->getJsonInput();
        
        if (empty($data['name'])) {
            return $this->errorResponse('Kategori adı zorunludur', 400);
        }
        
        $category = new InventoryCategory([
            'id' => $this->generateUUID(),
            'tenant_id' => $tenantId,
            'name' => $data['name'],
            'description' => $data['description'] ?? '',
            'color' => $data['color'] ?? '#3b82f6',
            'status' => 'active',
            'is_active' => true,
            'created_by' => $userId,
            'updated_by' => $userId,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        
        if ($category->save()) {
            return $this->successResponse($category, 'Kategori oluşturuldu', 201);
        }
        
        return $this->errorResponse('Kategori oluşturulamadı', 500);
    }

    public function updateCategory(?string $id = null): string
    {
        $this->requirePermission('inventory:update');
        $tenantId = $this->getCurrentTenantId();
        
        $category = InventoryCategory::find($id);
        
        if (!$category || $category->tenant_id !== $tenantId) {
            return $this->errorResponse('Kategori bulunamadı', 404);
        }
        
        $data = $this->getJsonInput();
        
        if (isset($data['name'])) $category->name = $data['name'];
        if (isset($data['description'])) $category->description = $data['description'];
        if (isset($data['color'])) $category->color = $data['color'];
        
        $category->updated_at = date('Y-m-d H:i:s');
        
        if ($category->save()) {
            return $this->successResponse($category, 'Kategori güncellendi');
        }
        
        return $this->errorResponse('Kategori güncellenemedi', 500);
    }

    public function destroyCategory(?string $id = null): string
    {
        $this->requirePermission('inventory:delete');
        $tenantId = $this->getCurrentTenantId();
        
        $category = InventoryCategory::find($id);
        
        if (!$category || $category->tenant_id !== $tenantId) {
            return $this->errorResponse('Kategori bulunamadı', 404);
        }
        
        // Soft delete
        $category->is_active = false;
        
        if ($category->save()) {
            return $this->successResponse(null, 'Kategori silindi');
        }
        
        return $this->errorResponse('Kategori silinemedi', 500);
    }

    // Supplier Methods

    public function getSuppliers(): string
    {
        $this->requirePermission('inventory:read');
        $tenantId = $this->getCurrentTenantId();
        
        $suppliers = Supplier::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name', 'ASC')
            ->get();
            
        return $this->successResponse($suppliers, 'Tedarikçiler getirildi');
    }

    public function storeSupplier(): string
    {
        $this->requirePermission('inventory:create');
        $tenantId = $this->getCurrentTenantId();
        $userId = $this->getCurrentUserId();
        
        $data = $this->getJsonInput();
        
        if (empty($data['name'])) {
            return $this->errorResponse('Tedarikçi adı zorunludur', 400);
        }
        
        $supplier = new Supplier([
            'id' => $this->generateUUID(),
            'tenant_id' => $tenantId,
            'name' => $data['name'],
            'contact_person' => $data['contact_person'] ?? '',
            'email' => $data['email'] ?? '',
            'phone' => $data['phone'] ?? '',
            'address' => $data['address'] ?? '',
            'city' => $data['city'] ?? '',
            'country' => $data['country'] ?? '',
            'tax_number' => $data['tax_number'] ?? '',
            'status' => 'active',
            'is_active' => true,
            'created_by' => $userId,
            'updated_by' => $userId,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        
        if ($supplier->save()) {
            return $this->successResponse($supplier, 'Tedarikçi oluşturuldu', 201);
        }
        
        return $this->errorResponse('Tedarikçi oluşturulamadı', 500);
    }

    public function updateSupplier(?string $id = null): string
    {
        $this->requirePermission('inventory:update');
        $tenantId = $this->getCurrentTenantId();
        
        $supplier = Supplier::find($id);
        
        if (!$supplier || $supplier->tenant_id !== $tenantId) {
            return $this->errorResponse('Tedarikçi bulunamadı', 404);
        }
        
        $data = $this->getJsonInput();
        
        $fields = ['name', 'contact_person', 'email', 'phone', 'address', 'city', 'country', 'tax_number'];
        foreach ($fields as $field) {
            if (isset($data[$field])) {
                $supplier->$field = $data[$field];
            }
        }
        
        $supplier->updated_at = date('Y-m-d H:i:s');
        
        if ($supplier->save()) {
            return $this->successResponse($supplier, 'Tedarikçi güncellendi');
        }
        
        return $this->errorResponse('Tedarikçi güncellenemedi', 500);
    }

    public function destroySupplier(?string $id = null): string
    {
        $this->requirePermission('inventory:delete');
        $tenantId = $this->getCurrentTenantId();
        
        $supplier = Supplier::find($id);
        
        if (!$supplier || $supplier->tenant_id !== $tenantId) {
            return $this->errorResponse('Tedarikçi bulunamadı', 404);
        }
        
        $supplier->is_active = false;
        
        if ($supplier->save()) {
            return $this->successResponse(null, 'Tedarikçi silindi');
        }
        
        return $this->errorResponse('Tedarikçi silinemedi', 500);
    }

    public function getStatistics(): string
    {
        try {
            $this->requirePermission('inventory:read');
            $tenantId = $this->getCurrentTenantId();
            
            $inventory = new Inventory();
            
            try {
                $totalValue = $inventory->getStockValue($tenantId);
            } catch (\Throwable $e) {
                error_log("Error getting stock value: " . $e->getMessage());
                $totalValue = 0;
            }

            try {
                $categoryStats = $inventory->getCategoryStats($tenantId);
            } catch (\Throwable $e) {
                error_log("Error getting category stats: " . $e->getMessage());
                $categoryStats = [];
            }

            try {
                $lowStockItems = Inventory::getLowStockItems($tenantId);
            } catch (\Throwable $e) {
                error_log("Error getting low stock items: " . $e->getMessage());
                $lowStockItems = [];
            }

            try {
                $expiringItems = Inventory::getExpiringItems($tenantId, 30);
            } catch (\Throwable $e) {
                error_log("Error getting expiring items: " . $e->getMessage());
                $expiringItems = [];
            }
            
            $db = \CoreFly\Utils\Database::getInstance();
            $stmt = $db->prepare("
                SELECT 
                    COUNT(*) as total_items,
                    SUM(CASE WHEN quantity <= min_quantity THEN 1 ELSE 0 END) as low_stock_items,
                    SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_items,
                    AVG(unit_cost) as avg_unit_cost
                FROM inventory
                WHERE tenant_id = :tenant_id
            ");
            
            $stmt->bindParam(':tenant_id', $tenantId);
            $stmt->execute();
            
            $stats = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if ($stats === false) {
                $stats = [];
            }
            
            return $this->successResponse([
                'total_value' => $totalValue,
                'total_items' => (int)($stats['total_items'] ?? 0),
                'low_stock_items' => (int)($stats['low_stock_items'] ?? 0),
                'active_items' => (int)($stats['active_items'] ?? 0),
                'avg_unit_cost' => (float)($stats['avg_unit_cost'] ?? 0),
                'category_stats' => $categoryStats,
                'low_stock_list' => $lowStockItems,
                'expiring_items' => $expiringItems
            ], 'Envanter istatistikleri getirildi');
        } catch (\Throwable $e) {
            error_log("Inventory Statistics Error: " . $e->getMessage());
            return $this->errorResponse('Inventory Statistics Error: ' . $e->getMessage(), 500);
        }
    }

    public function getLowStockItems(): string
    {
        $this->requirePermission('inventory:read');
        $tenantId = $this->getCurrentTenantId();

        $items = Inventory::where('tenant_id', $tenantId)
            ->where('quantity <= min_quantity') // Raw condition for low stock
            ->where('is_active', true)
            ->get();

        return $this->successResponse($items, 'Düşük stoklu ürünler getirildi');
    }

    public function getExpiringItems(): string
    {
        $this->requirePermission('inventory:read');
        $tenantId = $this->getCurrentTenantId();
        
        // Items expiring in next 30 days
        $date = date('Y-m-d', strtotime('+30 days'));
        
        $items = Inventory::where('tenant_id', $tenantId)
            ->where("expiry_date IS NOT NULL AND expiry_date != '' AND expiry_date <= :date")
            ->setBindings(['date' => $date])
            ->where('is_active', true)
            ->get();

        return $this->successResponse($items, 'Son kullanma tarihi yaklaşan ürünler getirildi');
    }

    public function getAllMovements(): string
    {
        $this->requirePermission('inventory:read');
        $tenantId = $this->getCurrentTenantId();

        // Global movements
        $db = \CoreFly\Utils\Database::getInstance();
        $stmt = $db->prepare("
            SELECT im.*, i.name as item_name, u.username as user_name
            FROM inventory_movements im
            JOIN inventory i ON im.inventory_id = i.id
            LEFT JOIN users u ON im.user_id = u.id
            WHERE im.tenant_id = :tenant_id
            ORDER BY im.created_at DESC
            LIMIT 100
        ");
        
        $stmt->bindParam(':tenant_id', $tenantId);
        $stmt->execute();
        
        return $this->successResponse($stmt->fetchAll(\PDO::FETCH_ASSOC), 'Tüm stok hareketleri getirildi');
    }

    public function getMovements(?string $id = null): string
    {
        $this->requirePermission('inventory:read');
        $tenantId = $this->getCurrentTenantId();
        
        // If ID is provided, get movements for specific item
        if ($id) {
            $item = Inventory::find($id);
            if (!$item || $item->tenant_id !== $tenantId) {
                return $this->errorResponse('Envanter bulunamadı', 404);
            }
            return $this->successResponse($item->getMovements(50), 'Stok hareketleri getirildi');
        }

        // Global movements
        $db = \CoreFly\Utils\Database::getInstance();
        $stmt = $db->prepare("
            SELECT im.*, i.name as item_name, u.username as user_name
            FROM inventory_movements im
            JOIN inventory i ON im.inventory_id = i.id
            LEFT JOIN users u ON im.user_id = u.id
            WHERE im.tenant_id = :tenant_id
            ORDER BY im.created_at DESC
            LIMIT 100
        ");
        
        $stmt->bindParam(':tenant_id', $tenantId);
        $stmt->execute();
        
        return $this->successResponse($stmt->fetchAll(\PDO::FETCH_ASSOC), 'Tüm stok hareketleri getirildi');
    }
}
