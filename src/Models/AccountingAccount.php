<?php

namespace CoreFly\Models;

class AccountingAccount extends BaseModel {
    protected string $table = 'accounting_accounts';
    
    public ?string $id = null;
    public ?string $tenant_id = null;
    public ?string $code = null;
    public ?string $name = null;
    public ?string $type = null;
    public ?float $balance = 0.0;
    public ?string $tax_number = null;
    public ?string $tax_office = null;
    public ?string $address = null;
    public ?string $phone = null;
    public ?string $email = null;
    public ?string $created_at = null;
    public ?string $updated_at = null;

    protected array $fillable = ['id', 'tenant_id', 'code', 'name', 'type', 'balance', 'tax_number', 'tax_office', 'address', 'phone', 'email', 'created_at', 'updated_at'];

    protected array $casts = [
        'balance' => 'float',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];
}