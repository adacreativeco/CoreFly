<?php

namespace CoreFly\Models;

class AccountingInvoiceItem extends BaseModel {
    protected string $table = 'accounting_invoice_items';
    
    public string $id;
    public string $invoice_id;
    public string $description;
    public float $quantity = 1.00;
    public float $unit_price = 0.00;
    public float $tax_rate = 0.00; // Percent
    public float $total = 0.00;
    public ?string $created_at = null;
    public ?string $updated_at = null;

    protected array $fillable = [
        'id', 'invoice_id', 'description', 'quantity', 'unit_price', 'tax_rate', 'total'
    ];
}
