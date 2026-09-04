<?php

namespace App\Models;

use App\Core\Model;

class FileAttachment extends Model
{
    protected $table = 'file_attachments';
    protected $fillable = [
        'tenant_id', 
        'uploaded_by', 
        'entity_type', 
        'entity_id', 
        'file_name', 
        'original_name', 
        'file_path', 
        'file_size', 
        'mime_type', 
        'is_public'
    ];
}
