<?php

use CoreFly\Core\Router;
use CoreFly\Middleware\CheckModuleEnabled;
use CoreFly\Controllers\AuthController;
use CoreFly\Controllers\UserController;
use CoreFly\Controllers\RoleController;
use CoreFly\Controllers\TenantController;
use CoreFly\Controllers\DashboardController;
use CoreFly\Controllers\AnnouncementController;
use CoreFly\Controllers\DocumentController;
use CoreFly\Controllers\TaskController;
use CoreFly\Controllers\ProjectController;
use CoreFly\Controllers\EventController;
use CoreFly\Controllers\MessagingController;
use CoreFly\Controllers\CallController;
use CoreFly\Controllers\HelpdeskController;
use CoreFly\Controllers\SettingsController;
use CoreFly\Controllers\SystemSettingsController;
use CoreFly\Controllers\HrController;
use CoreFly\Controllers\CrmController;
use CoreFly\Controllers\AccountingController;
use CoreFly\Controllers\InventoryController;
use CoreFly\Controllers\DonationController;
use CoreFly\Controllers\FieldController;
use CoreFly\Controllers\PoliticsController;
use CoreFly\Controllers\NotificationController;

use CoreFly\Controllers\Root\RootDashboardController;
use CoreFly\Controllers\Root\RootTenantController;
use CoreFly\Controllers\Root\RootUserController;
use CoreFly\Controllers\Root\RootSettingsController;
use CoreFly\Controllers\Root\RootLogsController;
use CoreFly\Controllers\Root\RootBillingController;
use CoreFly\Controllers\Root\RootLogStreamController;
use CoreFly\Controllers\Root\RootFeatureFlagController;
use CoreFly\Controllers\Root\RootAnnouncementController;
use CoreFly\Controllers\Root\RootAIController;

/** @var Router $router */

// Public Routes
$router->post('/api/auth/login', [AuthController::class, 'login']);
$router->post('/api/auth/verify-2fa', [AuthController::class, 'verifyTwoFactor']);
$router->post('/api/auth/refresh', [AuthController::class, 'refreshToken']);
$router->post('/api/auth/logout', [AuthController::class, 'logout']);
$router->post('/api/auth/reset-password', [AuthController::class, 'resetPassword']);
$router->get('/api/auth/csrf', [AuthController::class, 'csrfToken']);

// Protected Routes
$router->group('/api', function (Router $router) {
    
    // Auth Profile
    $router->get('/auth/profile', [AuthController::class, 'getProfile']);
    $router->put('/auth/update-profile', [AuthController::class, 'updateProfile']);
    
    // Admin - Tenant Switching
    $router->post('/admin/switch-tenant', [AuthController::class, 'switchTenant']);

    // ==========================================
    // ROOT ADMIN CONSOLE (Global Scope)
    // ==========================================
    
    // Dashboard
    $router->get('/root/dashboard', [RootDashboardController::class, 'index']);
    $router->get('/root/stats', [RootDashboardController::class, 'stats']);
    $router->get('/root/log-stream', [RootLogStreamController::class, 'stream']);
    
    // Global Tenant Management
    $router->get('/root/tenants', [RootTenantController::class, 'index']);
    $router->post('/root/tenants', [RootTenantController::class, 'store']);
    $router->put('/root/tenants/{id}', [RootTenantController::class, 'update']);
    $router->delete('/root/tenants/{id}', [RootTenantController::class, 'destroy']);
    $router->post('/root/tenants/{id}/freeze', [RootTenantController::class, 'freeze']);
    $router->post('/root/tenants/{id}/unfreeze', [RootTenantController::class, 'unfreeze']);
    $router->post('/root/tenants/{id}/maintenance', [RootTenantController::class, 'maintenance']);
    $router->post('/root/tenants/{id}/clear-cache', [RootTenantController::class, 'clearCache']);
    $router->post('/root/tenants/{id}/impersonate', [RootTenantController::class, 'impersonate']);
    
    // Global User Management
    $router->get('/root/users', [RootUserController::class, 'index']);
    $router->post('/root/users/{id}/reset-password', [RootUserController::class, 'resetPassword']);
    $router->post('/root/users/{id}/toggle-status', [RootUserController::class, 'toggleStatus']);

    // Feature Flags (Governance)
    $router->get('/root/feature-flags', [RootFeatureFlagController::class, 'index']);
    $router->post('/root/feature-flags', [RootFeatureFlagController::class, 'store']);
    $router->put('/root/feature-flags/{id}', [RootFeatureFlagController::class, 'update']);
    $router->delete('/root/feature-flags/{id}', [RootFeatureFlagController::class, 'destroy']);

    // Announcements (Governance)
    $router->get('/root/announcements', [RootAnnouncementController::class, 'index']);
    $router->post('/root/announcements', [RootAnnouncementController::class, 'store']);
    $router->put('/root/announcements/{id}', [RootAnnouncementController::class, 'update']);
    $router->delete('/root/announcements/{id}', [RootAnnouncementController::class, 'destroy']);
    
    // Global Settings
    $router->get('/root/settings', [RootSettingsController::class, 'index']);
    $router->post('/root/settings', [RootSettingsController::class, 'update']);
    
    // Global Logs
    $router->get('/root/logs', [RootLogsController::class, 'index']);
    
    // Global Billing
    $router->get('/root/billing', [RootBillingController::class, 'index']);
    $router->post('/root/billing/plans', [RootBillingController::class, 'updatePlan']);
    $router->post('/root/billing/invoices/generate', [RootBillingController::class, 'generateInvoice']);

    // AI Assistant (Phase 5)
    $router->post('/root/ai/ask', [RootAIController::class, 'ask']);

    // Users
    $router->get('/users', [UserController::class, 'index']);
    $router->post('/users', [UserController::class, 'store']);
    $router->get('/users/{id}', [UserController::class, 'show']);
    $router->put('/users/{id}', [UserController::class, 'update']);
    $router->delete('/users/{id}', [UserController::class, 'destroy']);

    // Roles
    $router->get('/roles', [RoleController::class, 'index']);
    $router->post('/roles/permissions', [RoleController::class, 'permissions']);

    // Tenants
    $router->put('/tenant/settings', [TenantController::class, 'updateSettings']);
    $router->get('/tenants', [TenantController::class, 'index']);
    $router->post('/tenants', [TenantController::class, 'store']);
    $router->get('/tenants/{id}', [TenantController::class, 'show']);
    $router->put('/tenants/{id}', [TenantController::class, 'update']);
    $router->delete('/tenants/{id}', [TenantController::class, 'destroy']);

    // Dashboard
    $router->get('/dashboard', [DashboardController::class, 'index']);
    $router->get('/dashboard/widgets', [DashboardController::class, 'widgets']);
    $router->get('/dashboard/activity', [DashboardController::class, 'activityFeed']);

    // Announcements
    $router->get('/announcements', [AnnouncementController::class, 'index']);
    $router->post('/announcements', [AnnouncementController::class, 'create']);
    $router->put('/announcements/{id}', [AnnouncementController::class, 'update']);
    $router->delete('/announcements/{id}', [AnnouncementController::class, 'delete']);
    $router->post('/announcements/pin', [AnnouncementController::class, 'pin']);
    $router->post('/announcements/unpin', [AnnouncementController::class, 'unpin']);

    // Documents
    $router->get('/documents', [DocumentController::class, 'index']);
    $router->post('/documents', [DocumentController::class, 'upload']);
    $router->post('/documents/folder', [DocumentController::class, 'createFolder']);
    $router->post('/documents/version', [DocumentController::class, 'uploadVersion']);

    // Tasks
    $router->get('/tasks', [TaskController::class, 'index']);
    $router->post('/tasks', [TaskController::class, 'create']);
    $router->put('/tasks/{id}', [TaskController::class, 'update']);
    $router->delete('/tasks/{id}', [TaskController::class, 'delete']);
    $router->put('/tasks/status', [TaskController::class, 'updateStatus']);
    $router->post('/tasks/subtasks', [TaskController::class, 'addSubtask']);
    $router->post('/tasks/subtasks/toggle', [TaskController::class, 'toggleSubtask']);

    // Projects
    $router->get('/projects', [ProjectController::class, 'index']);
    $router->get('/projects/stats', [ProjectController::class, 'getStatistics']);
    $router->get('/projects/gantt', [ProjectController::class, 'getGanttData']);
    $router->post('/projects', [ProjectController::class, 'store']);
    $router->get('/projects/{id}', [ProjectController::class, 'show']);
    $router->put('/projects/{id}', [ProjectController::class, 'update']);
    $router->delete('/projects/{id}', [ProjectController::class, 'delete']);

    // Calendar
    $router->get('/events', [EventController::class, 'index']);
    $router->post('/events', [EventController::class, 'create']);
    $router->put('/events/{id}', [EventController::class, 'update']);
    $router->delete('/events/{id}', [EventController::class, 'delete']);

    // Messaging
    $router->get('/messages', [MessagingController::class, 'index']);
    $router->get('/messages/users', [MessagingController::class, 'getUsers']);
    $router->get('/messages/chat-info', [MessagingController::class, 'getChatInfo']);
    $router->post('/messages/groups', [MessagingController::class, 'createGroup']);
    $router->post('/messages/send', [MessagingController::class, 'send']);
    $router->post('/messages/read', [MessagingController::class, 'markRead']);
    
    // Calls
    $router->post('/calls/start', [CallController::class, 'startCall']);
    $router->post('/calls/signal', [CallController::class, 'handleSignaling']);
    $router->get('/calls/signals', [CallController::class, 'pollSignals']);
    $router->post('/calls/end', [CallController::class, 'endCall']);
    $router->get('/calls/poll', [CallController::class, 'pollIncoming']);
    $router->get('/calls/status', [CallController::class, 'getStatus']);

    // Helpdesk
    $router->get('/helpdesk/tickets', [HelpdeskController::class, 'index']);
    $router->post('/helpdesk/tickets', [HelpdeskController::class, 'create']);
    $router->put('/helpdesk/tickets', [HelpdeskController::class, 'update']);
    $router->delete('/helpdesk/tickets', [HelpdeskController::class, 'delete']);
    $router->get('/helpdesk/categories', [HelpdeskController::class, 'getCategories']);
    $router->post('/helpdesk/categories', [HelpdeskController::class, 'storeCategory']);
    $router->get('/helpdesk/tickets/{id}', [HelpdeskController::class, 'show']);
    $router->put('/helpdesk/tickets/{id}', [HelpdeskController::class, 'updateStatus']);
    $router->post('/helpdesk/tickets/{id}/messages', [HelpdeskController::class, 'addMessage']);
    $router->delete('/helpdesk/categories/{id}', [HelpdeskController::class, 'deleteCategory']);

    // Notifications
    $router->get('/notifications', [NotificationController::class, 'index']);
    $router->put('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    $router->post('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead']);
    $router->delete('/notifications/{id}', [NotificationController::class, 'destroy']);

    // Settings
    $router->put('/settings/profile', [SettingsController::class, 'updateProfile']);
    $router->put('/settings/password', [SettingsController::class, 'changePassword']);

    // System Settings
    $router->get('/system-settings', [SystemSettingsController::class, 'index']);
    $router->post('/system-settings', [SystemSettingsController::class, 'update']);
    $router->get('/system-settings/health', [SystemSettingsController::class, 'getHealth']);
    $router->get('/system-settings/logs', [SystemSettingsController::class, 'getLogs']);
    $router->post('/system-settings/cache/clear', [SystemSettingsController::class, 'clearCache']);
    $router->post('/system-settings/db/optimize', [SystemSettingsController::class, 'optimizeDb']);

    // HR
    $router->get('/hr/employees', [HrController::class, 'index']);
    $router->post('/hr/employees', [HrController::class, 'store']);
    $router->put('/hr/employees/{id}', [HrController::class, 'update']);
    $router->delete('/hr/employees/{id}', [HrController::class, 'destroy']);
    $router->get('/hr/departments', [HrController::class, 'indexDepartments']);
    $router->post('/hr/departments', [HrController::class, 'storeDepartment']);
    $router->put('/hr/departments/{id}', [HrController::class, 'updateDepartment']);
    $router->delete('/hr/departments/{id}', [HrController::class, 'destroyDepartment']);
    $router->get('/hr/stats', [HrController::class, 'getStatistics']);
    $router->get('/hr/leave-requests', [HrController::class, 'getLeaveRequests']);
    $router->post('/hr/leave-requests', [HrController::class, 'storeLeaveRequest']);
    $router->put('/hr/leave-requests/{id}/status', [HrController::class, 'updateLeaveStatus']);
    $router->get('/hr/payrolls', [HrController::class, 'indexPayrolls']);
    $router->post('/hr/payrolls', [HrController::class, 'storePayroll']);
    $router->put('/hr/payrolls/{id}/status', [HrController::class, 'updatePayrollStatus']);
    $router->delete('/hr/payrolls/{id}', [HrController::class, 'destroyPayroll']);

    // CRM
    $router->get('/crm/customers', [CrmController::class, 'index']);
    $router->post('/crm/customers', [CrmController::class, 'store']);
    $router->get('/crm/stats', [CrmController::class, 'getStatistics']);
    $router->get('/crm/deals', [CrmController::class, 'getDeals']);
    $router->get('/crm/activities', [CrmController::class, 'getActivities']);

    // Accounting
    $router->get('/accounting/stats', [AccountingController::class, 'stats']);
    $router->get('/accounting/accounts', [AccountingController::class, 'indexAccounts']);
    $router->post('/accounting/accounts', [AccountingController::class, 'storeAccount']);
    $router->put('/accounting/accounts/{id}', [AccountingController::class, 'updateAccount']);
    $router->get('/accounting/transactions', [AccountingController::class, 'indexTransactions']);
    $router->post('/accounting/transactions', [AccountingController::class, 'storeTransaction']);
    $router->delete('/accounting/transactions/{id}', [AccountingController::class, 'destroyTransaction']);
    $router->get('/accounting/invoices', [AccountingController::class, 'indexInvoices']);
    $router->post('/accounting/invoices', [AccountingController::class, 'storeInvoice']);
    $router->get('/accounting/invoices/{id}', [AccountingController::class, 'showInvoice']);
    $router->put('/accounting/invoices/{id}', [AccountingController::class, 'updateInvoice']);
    $router->delete('/accounting/invoices/{id}', [AccountingController::class, 'destroyInvoice']);

    // Inventory
    $router->get('/inventory', [InventoryController::class, 'index']);
    $router->post('/inventory', [InventoryController::class, 'store']);
    $router->put('/inventory/{id}', [InventoryController::class, 'update']);
    $router->delete('/inventory/{id}', [InventoryController::class, 'destroy']);
    $router->get('/inventory/categories', [InventoryController::class, 'getCategories']);
    $router->post('/inventory/categories', [InventoryController::class, 'storeCategory']);
    $router->delete('/inventory/categories/{id}', [InventoryController::class, 'destroyCategory']);
    $router->get('/inventory/suppliers', [InventoryController::class, 'getSuppliers']);
    $router->post('/inventory/suppliers', [InventoryController::class, 'storeSupplier']);
    $router->delete('/inventory/suppliers/{id}', [InventoryController::class, 'destroySupplier']);
    $router->get('/inventory/movements', [InventoryController::class, 'getAllMovements']);
    $router->get('/inventory/low-stock', [InventoryController::class, 'getLowStockItems']);
    $router->get('/inventory/expiring', [InventoryController::class, 'getExpiringItems']);
    $router->get('/inventory/statistics', [InventoryController::class, 'getStatistics']);
    $router->get('/inventory/{id}', [InventoryController::class, 'show']);
    $router->put('/inventory/{id}', [InventoryController::class, 'update']);
    $router->delete('/inventory/{id}', [InventoryController::class, 'destroy']);
    $router->post('/inventory/{id}/adjust-quantity', [InventoryController::class, 'adjustQuantity']);
    $router->get('/inventory/{id}/movements', [InventoryController::class, 'getMovements']);

    // Donations
    $router->get('/donations', [DonationController::class, 'index']);
    $router->post('/donations', [DonationController::class, 'store']);
    $router->put('/donations/{id}', [DonationController::class, 'update']);
    $router->delete('/donations/{id}', [DonationController::class, 'destroy']);
    $router->get('/donations/donor-stats', [DonationController::class, 'getDonorStats']);
    $router->get('/donations/monthly-stats', [DonationController::class, 'getMonthlyStats']);
    $router->get('/donations/statistics', [DonationController::class, 'getStatistics']);
    $router->get('/donations/{id}', [DonationController::class, 'show']);
    $router->put('/donations/{id}/payment-status', [DonationController::class, 'updatePaymentStatus']);
    $router->post('/donations/{id}/acknowledgment', [DonationController::class, 'sendAcknowledgment']);
    $router->post('/donations/{id}/tax-receipt', [DonationController::class, 'sendTaxReceipt']);
    $router->get('/donations/campaign/{id}/stats', [DonationController::class, 'getCampaignStats']);

    // Field Management
    $router->get('/field/activities', [FieldController::class, 'indexActivities']);
    $router->post('/field/activities', [FieldController::class, 'createActivity']);
    $router->get('/field/members', [FieldController::class, 'indexMembers']);
    $router->post('/field/members', [FieldController::class, 'createMember']);
    $router->get('/field/stats', [FieldController::class, 'getStats']);
    // Zones & Teams
    $router->get('/field/zones', [FieldController::class, 'indexZones']);
    $router->post('/field/zones', [FieldController::class, 'createZone']);
    $router->put('/field/zones/{id}', [FieldController::class, 'updateZone']);
    $router->delete('/field/zones/{id}', [FieldController::class, 'deleteZone']);
    $router->get('/field/teams', [FieldController::class, 'indexTeams']);

    // Politics Module
    $router->get('/politics/analytics', [PoliticsController::class, 'getAnalytics']);
    // Voters
    $router->get('/politics/voters', [PoliticsController::class, 'indexVoters']);
    $router->post('/politics/voters', [PoliticsController::class, 'storeVoter']);
    $router->put('/politics/voters/{id}', [PoliticsController::class, 'updateVoter']);
    $router->delete('/politics/voters/{id}', [PoliticsController::class, 'deleteVoter']);
    // Boxes
    $router->get('/politics/boxes', [PoliticsController::class, 'indexBoxes']);
    $router->post('/politics/boxes', [PoliticsController::class, 'storeBox']);
    $router->put('/politics/boxes/{id}', [PoliticsController::class, 'updateBox']);
    $router->delete('/politics/boxes/{id}', [PoliticsController::class, 'deleteBox']);
    $router->get('/politics/volunteers', [PoliticsController::class, 'getVolunteers']);
    $router->post('/politics/volunteers', [PoliticsController::class, 'storeVolunteer']);
    $router->put('/politics/volunteers/{id}', [PoliticsController::class, 'updateVolunteer']);
    $router->delete('/politics/volunteers/{id}', [PoliticsController::class, 'deleteVolunteer']);

}, [CheckModuleEnabled::class]);

return $router;
