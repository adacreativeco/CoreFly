<?php

declare(strict_types=1);

namespace CoreFly\Models;

class CrmActivity extends BaseModel
{
    protected string $table = 'crm_activities';
    
    protected array $fillable = [
        'id',
        'tenant_id',
        'related_to_type', // customer, deal
        'related_to_id',
        'type', // call, meeting, email, note
        'subject',
        'description',
        'date',
        'status', // planned, completed
        'created_by',
        'created_at'
    ];
    
    public ?string $id = null;
    public ?string $tenant_id = null;
    public ?string $related_to_type = null;
    public ?string $related_to_id = null;
    public ?string $type = null;
    public ?string $subject = null;
    public ?string $description = null;
    public ?string $date = null;
    public ?string $status = 'completed';
    public ?string $created_by = null;
}
