<?php

declare(strict_types=1);

namespace CoreFly\Models;

use CoreFly\Models\BaseModel;

class TicketCategory extends BaseModel
{
    protected string $table = 'ticket_categories';
    
    public string $id;
    public string $tenant_id;
    public string $name;
    public string $description;
    public string $color;
    public bool $is_active;
    public string $created_by;
    public string $updated_by;

    public function getTicketCount(): int
    {
        $db = \CoreFly\Utils\Database::getInstance();
        $stmt = $db->prepare("
            SELECT COUNT(*) as count 
            FROM tickets 
            WHERE category_id = :category_id
        ");
        
        $stmt->bindParam(':category_id', $this->id);
        $stmt->execute();
        
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return (int)($result['count'] ?? 0);
    }
}
