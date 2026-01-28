<?php

declare(strict_types=1);

namespace CoreFly\Models;

class HrLeaveRequest extends BaseModel
{
    protected string $table = 'hr_leave_requests';
    
    protected array $fillable = [
        'id',
        'tenant_id',
        'employee_id',
        'type', // annual, sick, unpaid, other
        'start_date',
        'end_date',
        'days_count',
        'reason',
        'status', // pending, approved, rejected
        'rejection_reason',
        'approved_by',
        'created_by',
        'updated_by',
        'created_at',
        'updated_at'
    ];
    
    protected array $casts = [
        'days_count' => 'float'
    ];
    
    public ?string $id = null;
    public ?string $tenant_id = null;
    public ?string $employee_id = null;
    public ?string $type = 'annual';
    public ?string $start_date = null;
    public ?string $end_date = null;
    public float $days_count = 0;
    public ?string $reason = null;
    public ?string $status = 'pending';
    public ?string $rejection_reason = null;
    public ?string $approved_by = null;
    public ?string $created_by = null;
    public ?string $updated_by = null;
    
    public function getEmployee()
    {
        return HrEmployee::find($this->employee_id);
    }
}
