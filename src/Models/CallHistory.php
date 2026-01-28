<?php

namespace CoreFly\Models;

class CallHistory extends BaseModel
{
    protected string $table = 'call_history';
    protected bool $timestamps = false;
    
    public $id;
    public $caller_id;
    public $callee_id;
    public $group_id;
    public $call_type;
    public $status;
    public $started_at;
    public $ended_at;
    public $duration_seconds;

    public function __construct(?array $attributes = null)
    {
        parent::__construct($attributes);
        $this->tenantId = null; // Disable multi-tenancy for this model
    }
}
