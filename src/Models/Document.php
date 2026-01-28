<?php

declare(strict_types=1);

namespace CoreFly\Models;

class Document extends BaseModel
{
    protected string $table = 'documents';
    protected string $primaryKey = 'id';

    public string $id;
    public string $tenant_id;
    public ?string $department_id = null;
    public string $title;
    public ?string $file_path = null; // Can be null for folders
    public string $version = '1.0';
    public string $uploaded_by;
    public ?string $mime_type = null;
    public ?int $size = null;
    public ?string $parent_id = null;
    public int $is_folder = 0;
    public ?string $created_at = null;
    public ?string $updated_at = null;

    protected array $fillable = [
        'id','tenant_id','department_id','title','file_path','version','uploaded_by','mime_type','size', 'parent_id', 'is_folder'
    ];

    protected array $casts = [
        'is_folder' => 'integer',
        'size' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];
}

