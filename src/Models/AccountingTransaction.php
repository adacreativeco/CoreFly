<?php

namespace CoreFly\Models;

class AccountingTransaction extends BaseModel {
    protected string $table = 'accounting_transactions';
    
    public ?string $id = null;
    public ?string $tenant_id = null;
    public ?string $account_id = null;
    public ?string $type = null;
    public ?float $amount = 0.0;
    public ?string $description = null;
    public ?string $date = null;
    public ?string $category = null;
    public ?string $invoice_id = null;
    public ?string $payment_method = null;
    public ?string $file_path = null;
    public ?string $created_at = null;
    public ?string $updated_at = null;

    protected array $fillable = ['id', 'tenant_id', 'account_id', 'type', 'amount', 'description', 'date', 'category', 'invoice_id', 'payment_method', 'file_path', 'created_at', 'updated_at'];

    protected array $casts = [
        'amount' => 'float',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];
}