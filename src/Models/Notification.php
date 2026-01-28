<?php

declare(strict_types=1);

namespace CoreFly\Models;

use Exception;

class Notification extends BaseModel
{
    protected string $table = 'notifications';
    protected string $primaryKey = 'id';
    
    // Model properties
    public string $id;
    public string $tenant_id;
    public string $user_id;
    public string $type;
    public string $title;
    public string $message;
    public ?string $data = null;
    public bool $is_read = false;
    public ?string $read_at = null;
    public ?string $created_at = null;
    public ?string $updated_at = null;
    
    protected array $fillable = [
        'id', 'tenant_id', 'user_id', 'type', 'title', 'message', 'data', 'is_read', 'read_at'
    ];
    
    protected array $casts = [
        'data' => 'array',
        'is_read' => 'boolean',
        'read_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function markAsRead(): self
    {
        $this->is_read = true;
        $this->read_at = date('Y-m-d H:i:s');
        return $this;
    }

    public function markAsUnread(): self
    {
        $this->is_read = false;
        $this->read_at = null;
        return $this;
    }

    public function isRead(): bool
    {
        return $this->is_read === true;
    }

    public function getUser(): ?User
    {
        return User::find($this->user_id);
    }

    public static function createForUser(string $userId, string $type, string $title, string $message, array $data = []): self
    {
        $db = \CoreFly\Utils\Database::getInstance();
        $tenantId = $db->getCurrentTenant();

        $notification = new self([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => $data,
            'is_read' => false
        ]);

        $notification->save();
        return $notification;
    }

    public static function createForMultipleUsers(array $userIds, string $type, string $title, string $message, array $data = []): array
    {
        $notifications = [];
        
        foreach ($userIds as $userId) {
            $notifications[] = self::createForUser($userId, $type, $title, $message, $data);
        }

        return $notifications;
    }

    public static function getUnreadForUser(string $userId): array
    {
        $notifications = self::where('user_id', $userId)->get();
        return array_values(array_filter($notifications, fn($notification) => !$notification->is_read));
    }

    public static function markAllAsReadForUser(string $userId): int
    {
        $unreadNotifications = self::getUnreadForUser($userId);
        $count = 0;

        foreach ($unreadNotifications as $notification) {
            $notification->markAsRead()->save();
            $count++;
        }

        return $count;
    }

    public static function deleteOldNotifications(int $days = 30): int
    {
        $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        $sql = "DELETE FROM notifications WHERE created_at < ? AND is_read = 1";
        $db = \CoreFly\Utils\Database::getInstance();
        $stmt = $db->prepare($sql);
        $stmt->execute([$cutoffDate]);
        
        return $stmt->rowCount();
    }

    public function getTypeIcon(): string
    {
        $icons = [
            'announcement' => '📢',
            'task' => '✅',
            'project' => '📊',
            'event' => '📅',
            'message' => '💬',
            'document' => '📄',
            'helpdesk' => '🎫',
            'system' => '⚙️',
            'warning' => '⚠️',
            'error' => '❌',
            'success' => '✅',
            'info' => 'ℹ️'
        ];

        return $icons[$this->type] ?? '📢';
    }

    public function getTimeAgo(): string
    {
        $created = new \DateTime($this->created_at);
        $now = new \DateTime();
        $diff = $now->diff($created);

        if ($diff->y > 0) {
            return $diff->y . ' yıl önce';
        } elseif ($diff->m > 0) {
            return $diff->m . ' ay önce';
        } elseif ($diff->d > 0) {
            return $diff->d . ' gün önce';
        } elseif ($diff->h > 0) {
            return $diff->h . ' saat önce';
        } elseif ($diff->i > 0) {
            return $diff->i . ' dakika önce';
        } else {
            return 'şimdi';
        }
    }
}
