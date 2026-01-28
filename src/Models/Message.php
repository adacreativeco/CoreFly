<?php

declare(strict_types=1);

namespace CoreFly\Models;

class Message extends BaseModel
{
    protected string $table = 'messages';
    protected string $primaryKey = 'id';

    public ?string $id = null;
    public ?string $tenant_id = null;
    public ?string $sender_id = null;
    public ?string $receiver_id = null;
    public ?string $group_id = null;
    public ?string $message = null;
    public ?string $file_path = null;
    public ?string $file_type = null;
    public int $is_read = 0;
    public ?string $read_at = null;
    public ?string $created_at = null;
    public ?string $updated_at = null;

    protected array $fillable = [
        'id', 'tenant_id', 'sender_id', 'receiver_id', 'group_id', 
        'message', 'file_path', 'file_type', 'is_read', 'read_at'
    ];

    protected array $casts = [
        'is_read' => 'integer',
        'read_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];
}

