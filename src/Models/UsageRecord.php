<?php

namespace CoreFly\Models;

/**
 * @property int $tenant_id
 * @property string $metric_key
 * @property float $value
 * @property string $recorded_at
 */
class UsageRecord extends BaseModel
{
    protected string $table = 'usage_records';
}
