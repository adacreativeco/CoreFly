<?php

declare(strict_types=1);

namespace CoreFly\Models;

use CoreFly\Models\BaseModel;

class TicketMessage extends BaseModel
{
    protected string $table = 'ticket_messages';
    
    public string $id;
    public string $tenant_id;
    public string $ticket_id;
    public string $user_id;
    public string $message;
    public string $attachments; // JSON string
    public bool $is_internal;

    public function getUser(): ?array
    {
        $db = \CoreFly\Utils\Database::getInstance();
        $stmt = $db->prepare("SELECT id, username, email, role FROM users WHERE id = :user_id");
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->execute();
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }
}
