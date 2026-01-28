<?php

namespace CoreFly\Models;

class AccountingInvoice extends BaseModel {
    protected string $table = 'accounting_invoices';
    
    public string $id;
    public string $tenant_id;
    public string $account_id;
    public string $number;
    public string $date;
    public ?string $due_date = null;
    public float $subtotal = 0.00;
    public float $tax_total = 0.00;
    public float $total = 0.00;
    public string $status = 'draft'; // draft, sent, paid, cancelled
    public ?string $notes = null;
    public ?string $created_at = null;
    public ?string $updated_at = null;

    protected array $fillable = [
        'id', 'tenant_id', 'account_id', 'number', 'date', 'due_date',
        'subtotal', 'tax_total', 'total', 'status', 'notes'
    ];

    public function getItems() {
        return AccountingInvoiceItem::where('invoice_id', $this->id)->get();
    }
}
