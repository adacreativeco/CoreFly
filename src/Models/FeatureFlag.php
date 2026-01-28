<?php

namespace CoreFly\Models;

/**
 * @property string $key
 * @property string $description
 * @property bool $is_global
 * @property bool $default_value
 * @property string|null $rules
 */
class FeatureFlag extends BaseModel
{
    protected string $table = 'feature_flags';
    
    // Explicitly define that rules is a JSON column if your ORM supports casting
    // For this custom lightweight ORM, we might need to handle decode manually in controller/service
}
