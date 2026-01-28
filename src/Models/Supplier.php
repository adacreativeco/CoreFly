<?php

declare(strict_types=1);

namespace CoreFly\Models;

use CoreFly\Models\BaseModel;

class Supplier extends BaseModel
{
    protected string $table = 'suppliers';
    
    public string $id;
    public string $tenant_id;
    public string $name;
    public string $contact_person;
    public string $email;
    public string $phone;
    public string $address;
    public string $city;
    public string $country;
    public string $tax_number;
    public string $website;
    public string $payment_terms;
    public float $credit_limit;
    public string $currency;
    public string $status;
    public bool $is_active;
    public float $total_purchases;
    public string $last_order_date;
    public string $notes;
    public string $created_by;
    public string $updated_by;


    public function getInventoryItems(): array
    {
        return Inventory::where('supplier_id', $this->id)
            ->where('tenant_id', $this->tenant_id)
            ->where('is_active', true)
            ->get();
    }

    public function getTotalInventoryValue(): float
    {
        $db = \CoreFly\Utils\Database::getInstance();
        $stmt = $db->prepare("
            SELECT SUM(total_value) as total_value
            FROM inventory
            WHERE supplier_id = :supplier_id AND tenant_id = :tenant_id AND is_active = 1
        ");

        $stmt->bindParam(':supplier_id', $this->id);
        $stmt->bindParam(':tenant_id', $this->tenant_id);
        $stmt->execute();
        
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return (float)($result['total_value'] ?? 0);
    }

    public function getActiveSuppliers(string $tenantId): array
    {
        return self::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->where('status', 'active')
            ->orderBy('name', 'ASC')
            ->get();
    }
}