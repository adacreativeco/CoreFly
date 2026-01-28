<?php

declare(strict_types=1);

namespace CoreFly\Models;

use CoreFly\Models\BaseModel;

class Inventory extends BaseModel
{
    protected string $table = 'inventory';
    
    public string $id;
    public string $tenant_id;
    public ?string $name = null;
    public ?string $description = null;
    public ?string $category = null; // Legacy string
    public ?string $category_id = null; // New FK
    public ?string $sku = null;
    public ?string $barcode = null;
    public int $quantity = 0;
    public int $min_quantity = 0;
    public int $max_quantity = 1000;
    public string $unit = '';
    public float $unit_cost = 0.0;
    public float $total_value = 0.0;
    public ?string $location = null;
    public ?string $supplier_id = null;
    public ?string $supplier_name = null; // Legacy cache
    public ?string $status = null;
    public $is_active = true; // Removed strict bool type
    public ?string $last_restock_date = null;
    public ?string $expiry_date = null;
    public ?string $created_by = null;
    public ?string $updated_by = null;

    public function __construct(array $data = [])
    {
        parent::__construct($data);
        $this->calculateTotalValue();
    }

    public function calculateTotalValue(): void
    {
        $this->total_value = $this->quantity * $this->unit_cost;
    }

    public function isLowStock(): bool
    {
        return $this->quantity <= $this->min_quantity;
    }

    public function isOverStock(): bool
    {
        return $this->quantity >= $this->max_quantity;
    }

    public function isExpired(): bool
    {
        if (empty($this->expiry_date)) {
            return false;
        }
        return strtotime($this->expiry_date) < time();
    }

    public function needsRestock(): bool
    {
        return $this->isLowStock() && $this->is_active;
    }

    public function adjustQuantity(int $adjustment, string $reason = '', string $userId = ''): bool
    {
        $oldQuantity = $this->quantity;
        $newQuantity = $oldQuantity + $adjustment;

        if ($newQuantity < 0) {
            throw new \Exception('Yetersiz stok');
        }

        $this->quantity = $newQuantity;
        $this->calculateTotalValue();
        
        if ($adjustment > 0) {
            $this->last_restock_date = date('Y-m-d H:i:s');
        }

        $result = $this->save();

        if ($result && !empty($userId)) {
            $this->logMovement($oldQuantity, $newQuantity, $adjustment, $reason, $userId);
        }

        return $result;
    }

    public function logMovement(int $oldQuantity, int $newQuantity, int $adjustment, string $reason, string $userId): bool
    {
        $movementData = [
            'inventory_id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'old_quantity' => $oldQuantity,
            'new_quantity' => $newQuantity,
            'adjustment' => $adjustment,
            'reason' => $reason,
            'user_id' => $userId,
            'created_at' => date('Y-m-d H:i:s')
        ];

        $db = \CoreFly\Utils\Database::getInstance();
        $stmt = $db->prepare("
            INSERT INTO inventory_movements 
            (id, inventory_id, tenant_id, old_quantity, new_quantity, adjustment, reason, user_id, created_at)
            VALUES (:id, :inventory_id, :tenant_id, :old_quantity, :new_quantity, :adjustment, :reason, :user_id, :created_at)
        ");

        $movementId = $this->generateUUID();
        $stmt->bindParam(':id', $movementId);
        $stmt->bindParam(':inventory_id', $this->id);
        $stmt->bindParam(':tenant_id', $this->tenant_id);
        $stmt->bindParam(':old_quantity', $oldQuantity);
        $stmt->bindParam(':new_quantity', $newQuantity);
        $stmt->bindParam(':adjustment', $adjustment);
        $stmt->bindParam(':reason', $reason);
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':created_at', $movementData['created_at']);

        return $stmt->execute();
    }

    public function getMovements(int $limit = 50): array
    {
        $db = \CoreFly\Utils\Database::getInstance();
        $stmt = $db->prepare("
            SELECT im.*, u.username as user_name, u.email as user_email
            FROM inventory_movements im
            LEFT JOIN users u ON im.user_id = u.id
            WHERE im.inventory_id = :inventory_id AND im.tenant_id = :tenant_id
            ORDER BY im.created_at DESC
            LIMIT :limit
        ");

        $stmt->bindParam(':inventory_id', $this->id);
        $stmt->bindParam(':tenant_id', $this->tenant_id);
        $stmt->bindParam(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getSupplier(): ?Supplier
    {
        if (empty($this->supplier_id)) {
            return null;
        }
        return Supplier::find($this->supplier_id);
    }
    
    public function getCategory(): ?InventoryCategory
    {
        if (empty($this->category_id)) {
            return null;
        }
        return InventoryCategory::find($this->category_id);
    }

    public static function getLowStockItems(string $tenantId): array
    {
        // Use raw query because where() wrapper handles simple '=' or LIKE conditions
        // but complex conditions like 'quantity <= min_quantity' might need raw handling
        // if the BaseModel doesn't support it well.
        // However, BaseModel seems to support it if we pass raw string in first arg?
        // Let's check BaseModel. Actually, standard Eloquent-like way is where('col', '<=', val).
        // But our simple BaseModel might be limited.
        // Safe way: fetch all active items and filter in PHP, or use raw SQL.
        // Since we want robust solution, let's use raw SQL via Database instance.
        
        $db = \CoreFly\Utils\Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM inventory WHERE tenant_id = :tenant_id AND is_active = 1 AND quantity <= min_quantity");
        $stmt->execute([':tenant_id' => $tenantId]);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        $items = [];
        foreach ($rows as $row) {
            $items[] = new self($row);
        }
        return $items;
    }

    public static function getExpiringItems(string $tenantId, int $days = 30): array
    {
        $expiryDate = date('Y-m-d', strtotime("+{$days} days"));
        $today = date('Y-m-d');
        
        $db = \CoreFly\Utils\Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM inventory WHERE tenant_id = :tenant_id AND is_active = 1 AND expiry_date <= :expiry AND expiry_date >= :today");
        $stmt->execute([':tenant_id' => $tenantId, ':expiry' => $expiryDate, ':today' => $today]);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        $items = [];
        foreach ($rows as $row) {
            $items[] = new self($row);
        }
        return $items;
    }

    public function getStockValue(string $tenantId): float
    {
        $db = \CoreFly\Utils\Database::getInstance();
        $stmt = $db->prepare("
            SELECT SUM(total_value) as total_value
            FROM inventory
            WHERE tenant_id = :tenant_id AND is_active = 1
        ");

        $stmt->bindParam(':tenant_id', $tenantId);
        $stmt->execute();
        
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return (float)($result['total_value'] ?? 0);
    }

    public function getCategoryStats(string $tenantId): array
    {
        // Try to use category_id and join with categories table, fallback to grouping by string
        $db = \CoreFly\Utils\Database::getInstance();
        $stmt = $db->prepare("
            SELECT 
                COALESCE(c.name, i.category) as category_name,
                COUNT(*) as item_count,
                SUM(i.quantity) as total_quantity,
                SUM(i.total_value) as total_value
            FROM inventory i
            LEFT JOIN inventory_categories c ON i.category_id = c.id
            WHERE i.tenant_id = :tenant_id AND i.is_active = 1
            GROUP BY COALESCE(c.name, i.category)
            ORDER BY total_value DESC
        ");

        $stmt->bindParam(':tenant_id', $tenantId);
        $stmt->execute();
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
