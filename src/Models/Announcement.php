<?php

declare(strict_types=1);

namespace CoreFly\Models;

/**
 * @property int|string $id
 * @property int|string $tenant_id
 * @property string $title
 * @property string $body
 * @property string $content
 * @property string $type
 * @property string $status
 * @property string $priority
 * @property int|null $department_id
 * @property int|null $author_id
 * @property bool $is_pinned
 * @property string|null $target_type
 * @property string|array|null $target_audience
 * @property string|array|null $target_values
 * @property bool $is_active
 * @property string $published_at
 * @property string|null $expires_at
 * @property string|null $expiry_date
 * @property string $created_at
 * @property string $updated_at
 */
class Announcement extends BaseModel
{
    protected string $table = 'announcements';
}
