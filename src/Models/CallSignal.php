<?php

namespace CoreFly\Models;

class CallSignal extends BaseModel
{
    protected string $table = 'call_signals';
    protected bool $timestamps = false;

    public $call_id;
    public $sender_id;
    public $type;
    public $payload;
    public $is_processed;

    public function __construct(?array $attributes = null)
    {
        parent::__construct($attributes);
        $this->tenantId = null; // Disable multi-tenancy
    }
}
