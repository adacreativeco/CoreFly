<?php

declare(strict_types=1);

namespace CoreFly\Models;

use Exception;

class PasswordReset extends BaseModel
{
    protected string $table = 'password_resets';
    protected string $primaryKey = 'id';
    
    // Model properties
    public string $id;
    public string $user_id;
    public string $token;
    public string $expires_at;
    public bool $used = false;
    public ?string $created_at = null;
    
    protected array $fillable = [
        'id', 'user_id', 'token', 'expires_at', 'used'
    ];
    
    protected array $casts = [
        'expires_at' => 'datetime',
        'used' => 'boolean',
        'created_at' => 'datetime'
    ];

    public function isExpired(): bool
    {
        $expiresAt = new \DateTime($this->expires_at);
        $now = new \DateTime();
        
        return $now > $expiresAt;
    }

    public function isUsed(): bool
    {
        return $this->used === true;
    }

    public function markAsUsed(): self
    {
        $this->used = true;
        return $this;
    }

    public function getUser(): ?User
    {
        return User::find($this->user_id);
    }

    public static function findByToken(string $token): ?self
    {
        $resets = static::where('token', $token);
        return $resets[0] ?? null;
    }

    public static function deleteExpiredTokens(): int
    {
        $now = date('Y-m-d H:i:s');
        $sql = "DELETE FROM password_resets WHERE expires_at < ?";
        $db = \CoreFly\Utils\Database::getInstance();
        $stmt = $db->prepare($sql);
        $stmt->execute([$now]);
        
        return $stmt->rowCount();
    }

    public static function deleteUserTokens(string $userId): int
    {
        $sql = "DELETE FROM password_resets WHERE user_id = ?";
        $db = \CoreFly\Utils\Database::getInstance();
        $stmt = $db->prepare($sql);
        $stmt->execute([$userId]);
        
        return $stmt->rowCount();
    }
}
