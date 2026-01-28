<?php

declare(strict_types=1);

namespace CoreFly\Models;

use Exception;

class UserSession extends BaseModel
{
    protected string $table = 'user_sessions';
    protected string $primaryKey = 'id';
    
    // Model properties
    public string $id;
    public string $user_id;
    public string $token;
    public ?string $ip_address = null;
    public ?string $user_agent = null;
    public ?string $last_activity = null;
    public string $expires_at;
    public ?string $created_at = null;
    
    protected array $fillable = [
        'id', 'user_id', 'token', 'ip_address', 'user_agent', 'last_activity', 'expires_at'
    ];
    
    protected array $casts = [
        'last_activity' => 'datetime',
        'expires_at' => 'datetime',
        'created_at' => 'datetime'
    ];

    public function isExpired(): bool
    {
        $expiresAt = new \DateTime($this->expires_at);
        $now = new \DateTime();
        
        return $now > $expiresAt;
    }

    public function getUser(): ?User
    {
        return User::find($this->user_id);
    }

    public static function findByToken(string $token): ?self
    {
        $sessions = static::where('token', $token);
        return $sessions[0] ?? null;
    }

    public static function deleteExpiredSessions(): int
    {
        $now = date('Y-m-d H:i:s');
        $sql = "DELETE FROM user_sessions WHERE expires_at < ?";
        $db = \CoreFly\Utils\Database::getInstance();
        $stmt = $db->prepare($sql);
        $stmt->execute([$now]);
        
        return $stmt->rowCount();
    }

    public static function deleteUserSessions(string $userId): int
    {
        $sql = "DELETE FROM user_sessions WHERE user_id = ?";
        $db = \CoreFly\Utils\Database::getInstance();
        $stmt = $db->prepare($sql);
        $stmt->execute([$userId]);
        
        return $stmt->rowCount();
    }
}
