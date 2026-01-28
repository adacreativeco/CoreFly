<?php

declare(strict_types=1);

namespace CoreFly\Models;

class HrEmployee extends BaseModel
{
    protected string $table = 'hr_employees';
    
    protected array $fillable = [
        'id',
        'tenant_id',
        'user_id', // Link to system user if they have login access
        'first_name',
        'last_name',
        'email',
        'phone',
        'department_id',
        'position',
        'salary',
        'hire_date',
        'birth_date',
        'gender',
        'address',
        'emergency_contact',
        'iban',
        'status', // active, terminated, on_leave
        'created_by',
        'updated_by',
        'created_at',
        'updated_at'
    ];
    
    protected array $casts = [
        'salary' => 'float'
    ];
    
    public ?string $id = null;
    public ?string $tenant_id = null;
    public ?string $user_id = null;
    public ?string $first_name = null;
    public ?string $last_name = null;
    public ?string $email = null;
    public ?string $phone = null;
    public ?string $department_id = null;
    public ?string $position = null;
    public float $salary = 0.0;
    public ?string $hire_date = null;
    public ?string $birth_date = null;
    public ?string $gender = null;
    public ?string $address = null;
    public ?string $emergency_contact = null;
    public ?string $iban = null;
    public ?string $status = 'active';
    public ?string $created_by = null;
    public ?string $updated_by = null;
    
    public function getDepartment()
    {
        return Department::find($this->department_id);
    }
    
    public function getFullName(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }
}
