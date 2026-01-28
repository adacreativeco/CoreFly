<?php

namespace CoreFly\Models;

/**
 * @property int $tenant_id
 * @property float $amount
 * @property string $status
 * @property string $period_start
 * @property string $period_end
 */
class Invoice extends BaseModel
{
    protected string $table = 'invoices';
}
