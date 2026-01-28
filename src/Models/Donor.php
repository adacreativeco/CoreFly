<?php

declare(strict_types=1);

namespace CoreFly\Models;

class Donor extends BaseModel
{
    protected string $table = 'donors';
    
    protected array $fillable = [
        'id',
        'tenant_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'address',
        'city',
        'country',
        'type', // individual, corporate
        'tax_id', // for corporate
        'company_name', // for corporate
        'notes',
        'status', // active, inactive
        'total_donated',
        'last_donation_date',
        'created_by',
        'updated_by',
        'created_at',
        'updated_at'
    ];
    
    public ?string $id = null;
    public ?string $tenant_id = null;
    public ?string $first_name = null;
    public ?string $last_name = null;
    public ?string $email = null;
    public ?string $phone = null;
    public ?string $address = null;
    public ?string $city = null;
    public ?string $country = null;
    public ?string $type = 'individual';
    public ?string $tax_id = null;
    public ?string $company_name = null;
    public ?string $notes = null;
    public ?string $status = 'active';
    public float $total_donated = 0.0;
    public ?string $last_donation_date = null;
    public ?string $created_by = null;
    public ?string $updated_by = null;
    
    public function getDonations()
    {
        return Donation::where('donor_email', $this->email)
            ->where('tenant_id', $this->tenant_id)
            ->orderBy('created_at', 'DESC')
            ->get();
    }
}
