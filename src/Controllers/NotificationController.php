<?php

namespace CoreFly\Controllers;

use CoreFly\Models\Notification;
use CoreFly\Utils\Logger;

class NotificationController extends BaseController
{
    /**
     * Get all notifications for the current user
     */
    public function index(): string
    {
        try {
            // Permission check can be strict or open to all authenticated users
            // $this->requirePermission('notifications:read');
            
            $userId = $this->getCurrentUserId();
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
            $unreadOnly = isset($_GET['unread']) && $_GET['unread'] === 'true';
            
            $query = "SELECT * FROM notifications WHERE tenant_id = :tenant_id AND user_id = :user_id";
            $params = [
                'tenant_id' => $this->getCurrentTenantId(),
                'user_id' => $userId
            ];
            
            if ($unreadOnly) {
                $query .= " AND is_read = 0";
            }
            
            $query .= " ORDER BY created_at DESC LIMIT :limit";
            $params['limit'] = $limit;
            
            $stmt = $this->db->prepare($query);
            
            // Bind params manually for limit to work properly in some drivers
            $stmt->bindValue(':tenant_id', $this->getCurrentTenantId());
            $stmt->bindValue(':user_id', $userId);
            if (isset($params['limit'])) $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
            
            $stmt->execute();
            $notifications = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            // Get unread count
            $countStmt = $this->db->prepare("SELECT COUNT(*) FROM notifications WHERE tenant_id = ? AND user_id = ? AND is_read = 0");
            $countStmt->execute([$this->getCurrentTenantId(), $userId]);
            $unreadCount = $countStmt->fetchColumn();

            return $this->successResponse([
                'notifications' => $notifications,
                'unread_count' => $unreadCount
            ]);

        } catch (\Exception $e) {
            Logger::error("Notification Fetch Error: " . $e->getMessage());
            return $this->errorResponse('Bildirimler alınamadı', 500);
        }
    }

    /**
     * Mark a notification as read
     */
    public function markAsRead(string $id): string
    {
        try {
            $userId = $this->getCurrentUserId();
            
            $stmt = $this->db->prepare("UPDATE notifications SET is_read = 1, read_at = CURRENT_TIMESTAMP WHERE id = ? AND user_id = ? AND tenant_id = ?");
            $stmt->execute([$id, $userId, $this->getCurrentTenantId()]);
            
            if ($stmt->rowCount() > 0) {
                return $this->successResponse(null, 'Bildirim okundu olarak işaretlendi');
            }
            
            return $this->errorResponse('Bildirim bulunamadı', 404);

        } catch (\Exception $e) {
            Logger::error("Notification Update Error: " . $e->getMessage());
            return $this->errorResponse('İşlem başarısız', 500);
        }
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead(): string
    {
        try {
            $userId = $this->getCurrentUserId();
            
            $stmt = $this->db->prepare("UPDATE notifications SET is_read = 1, read_at = CURRENT_TIMESTAMP WHERE user_id = ? AND tenant_id = ? AND is_read = 0");
            $stmt->execute([$userId, $this->getCurrentTenantId()]);
            
            return $this->successResponse(['updated' => $stmt->rowCount()], 'Tüm bildirimler okundu olarak işaretlendi');

        } catch (\Exception $e) {
            Logger::error("Notification Mark All Error: " . $e->getMessage());
            return $this->errorResponse('İşlem başarısız', 500);
        }
    }
    
    /**
     * Delete a notification
     */
    public function destroy(string $id): string
    {
        try {
            $userId = $this->getCurrentUserId();
            
            $stmt = $this->db->prepare("DELETE FROM notifications WHERE id = ? AND user_id = ? AND tenant_id = ?");
            $stmt->execute([$id, $userId, $this->getCurrentTenantId()]);
            
            return $this->successResponse(null, 'Bildirim silindi');

        } catch (\Exception $e) {
            Logger::error("Notification Delete Error: " . $e->getMessage());
            return $this->errorResponse('Silme işlemi başarısız', 500);
        }
    }
}
