<?php

declare(strict_types=1);

namespace CoreFly\Models;

class CrmCustomer extends BaseModel
{
    protected string $table = 'crm_customers';
    
    protected array $fillable = [
        'id',
        'tenant_id',
        'name',
        'email',
        'phone',
        'company',
        'address',
        'city',
        'country',
        'tax_id',
        'industry',
        'website',
        'lead_source',
        'status', // active, inactive, lead
        'notes',
        'created_by',
        'updated_by',
        'created_at',
        'updated_at'
    ];
    
    public ?string $id = null;
    public ?string $tenant_id = null;
    public ?string $name = null;
    public ?string $email = null;
    public ?string $phone = null;
    public ?string $company = null;
    public ?string $address = null;
    public ?string $city = null;
    public ?string $country = null;
    public ?string $tax_id = null;
    public ?string $industry = null;
    public ?string $website = null;
    public ?string $lead_source = null;
    public ?string $status = 'lead';
    public ?string $notes = null;
    public ?string $created_by = null;
    public ?string $updated_by = null;
    
    public function getDeals()
    {
        return CrmDeal::where('customer_id', $this->id)->get();
    }
}
