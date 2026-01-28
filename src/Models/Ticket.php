<?php

declare(strict_types=1);

namespace CoreFly\Models;

class Ticket extends BaseModel
{
    protected string $table = 'tickets';
    protected string $primaryKey = 'id';

    public string $id;
    public string $tenant_id;
    public ?string $department_id = null;
    public string $user_id;
    public ?string $category = null; // Legacy string
    public ?string $category_id = null; // New relation
    public ?string $category_name = null; // Helper for API response
    public ?string $priority = null;
    public ?string $status = null;
    public string $title;
    public ?string $description = null;
    public ?string $assigned_to = null;
    public ?string $created_at = null;
    public ?string $updated_at = null;

    protected array $fillable = [
        'id','tenant_id','department_id','user_id','category','category_id','priority','status','title','description','assigned_to'
    ];

    public function getMessages(): array
    {
        $db = \CoreFly\Utils\Database::getInstance();
        $stmt = $db->prepare("
            SELECT tm.*, u.username as user_name, u.email as user_email, u.role_id as user_role
            FROM ticket_messages tm
            LEFT JOIN users u ON tm.user_id = u.id
            WHERE tm.ticket_id = :ticket_id
            ORDER BY tm.created_at ASC
        ");
        $stmt->bindParam(':ticket_id', $this->id);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getCategoryName(): string
    {
        if (!empty($this->category_id)) {
            $cat = TicketCategory::find($this->category_id);
            return $cat ? $cat->name : ($this->category ?? 'Genel');
        }
        return $this->category ?? 'Genel';
    }
}
