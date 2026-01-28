<?php

declare(strict_types=1);

namespace CoreFly\Models;

class FieldActivity extends BaseModel
{
    protected string $table = 'field_activities';
    
    public string $id;
    public string $tenant_id;
    public string $title;
    public ?string $description = null;
    public string $location; // Adres veya koordinat
    public string $type; // Ziyaret, Toplantı, Stand, Anket
    public string $status; // Planlandı, Tamamlandı, İptal
    public string $date;
    public ?string $assigned_team_id = null;
    public ?string $organizer_id = null;
    public ?array $metadata = null; // Anket sonuçları, fotoğraflar vb.

    protected array $fillable = [
        'id', 'tenant_id', 'title', 'description', 'location', 
        'type', 'status', 'date', 'assigned_team_id', 'organizer_id', 'metadata'
    ];

    protected array $casts = [
        'metadata' => 'array',
        'date' => 'datetime'
    ];
}
