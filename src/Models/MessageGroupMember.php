<?php

declare(strict_types=1);

namespace CoreFly\Models;

class MessageGroupMember extends BaseModel
{
    protected string $table = 'message_group_members';
    protected bool $timestamps = false; 

    public ?string $id = null;
    public string $group_id;
    public string $user_id;
    public string $joined_at;
    public int $is_admin = 0;

    protected array $fillable = [
        'id', 'group_id', 'user_id', 'joined_at', 'is_admin'
    ];
}
