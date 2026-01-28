<?php

declare(strict_types=1);

namespace CoreFly\Models;

class Member extends BaseModel
{
    protected string $table = 'members';
    
    public string $id;
    public string $tenant_id;
    public string $first_name;
    public string $last_name;
    public string $tc_no;
    public ?string $phone = null;
    public ?string $email = null;
    public ?string $address = null;
    public ?string $city = null;
    public ?string $district = null;
    public ?string $neighborhood = null; // Mahalle
    public ?string $ballot_box_no = null; // Sandık No
    public string $membership_status; // Aktif, Pasif, Aday
    public ?string $membership_date = null;
    public ?string $referrer_id = null; // Referans olan üye

    protected array $fillable = [
        'id', 'tenant_id', 'first_name', 'last_name', 'tc_no', 'phone', 'email',
        'address', 'city', 'district', 'neighborhood', 'ballot_box_no',
        'membership_status', 'membership_date', 'referrer_id'
    ];

    protected array $casts = [
        'membership_date' => 'date'
    ];

    public function getFullName(): string
    {
        return $this->first_name . ' ' . $this->last_name;
    }
}
