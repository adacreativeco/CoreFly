<?php

declare(strict_types=1);

namespace CoreFly\Models;

class CrmDeal extends BaseModel
{
    protected string $table = 'crm_deals';
    
    protected array $fillable = [
        'id',
        'tenant_id',
        'customer_id',
        'title',
        'value',
        'currency',
        'stage', // new, qualification, proposal, negotiation, won, lost
        'probability',
        'expected_close_date',
        'notes',
        'assigned_to',
        'created_by',
        'updated_by',
        'created_at',
        'updated_at'
    ];
    
    protected array $casts = [
        'value' => 'float',
        'probability' => 'int'
    ];
    
    public ?string $id = null;
    public ?string $tenant_id = null;
    public ?string $customer_id = null;
    public ?string $title = null;
    public float $value = 0.0;
    public string $currency = 'TRY';
    public string $stage = 'new';
    public int $probability = 0;
    public ?string $expected_close_date = null;
    public ?string $notes = null;
    public ?string $assigned_to = null;
    public ?string $created_by = null;
    public ?string $updated_by = null;
    
    public function getCustomer()
    {
        return CrmCustomer::find($this->customer_id);
    }
}
