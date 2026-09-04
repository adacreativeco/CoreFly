# Sprint 6: Notifications & System Logs Plan

## Goal
Implement a notification system for user alerts and an audit logging system to track critical actions within the tenant.

## Scope

### 1. Database Schema
- **Notifications Table** (`notifications`): Stores user-specific notifications with read status.
- **Audit Logs Table** (`audit_logs`): Stores system events (who did what, when, and where).

### 2. Backend API
- **Notifications**:
  - `GET /api/notifications/{tenantId}`: List user's notifications.
  - `PATCH /api/notifications/{tenantId}/{notificationId}/read`: Mark as read.
  - `POST /api/notifications/{tenantId}/test`: (Dev only) Trigger a test notification.

- **Audit Logs**:
  - `GET /api/audit/{tenantId}`: List tenant audit logs (Admin only).
  - Internal Service: `AuditService::log($tenantId, $userId, $action, $entity, $details)` to be used by other controllers.

### 3. Verification
- Create `scripts/test_notifications.php` to verify:
  1. Create a notification.
  2. List notifications.
  3. Mark as read.
  4. Create an audit log entry.
  5. Retrieve audit logs.

## Tasks
1. [ ] Create migration SQL files.
2. [ ] Run migrations.
3. [ ] Implement `Notification`, `AuditLog` models.
4. [ ] Implement `NotificationController`, `AuditController`.
5. [ ] Register routes in `public/index.php`.
6. [ ] Create and run verification script.
