<?php

require_once __DIR__ . '/../app/Core/Database.php';
require_once __DIR__ . '/../app/Core/Router.php';
require_once __DIR__ . '/../app/Middleware/TenantMiddleware.php';

// Security & CORS Headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Tenant-ID");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS, PATCH");
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: SAMEORIGIN");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Permissions-Policy: camera=(self), microphone=(self), geolocation=()");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Simple autoloader
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base_dir = __DIR__ . '/../app/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

// Load Env (Simplified)
if (file_exists(__DIR__ . '/../.env')) {
    $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && substr($line, 0, 1) !== '#') {
            list($key, $value) = explode('=', $line, 2);
            putenv(trim($key) . '=' . trim($value));
        }
    }
}

// Global Exception Handler
set_exception_handler(function ($e) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'error' => 'Internal Server Error',
        'message' => $e->getMessage(),
        // 'trace' => $e->getTraceAsString() // Disable in production
    ]);
});

// Run Tenant Middleware
$tenantMiddleware = new \App\Middleware\TenantMiddleware();
$tenantMiddleware->handle();

// Init Router
$router = new \App\Core\Router();

// Define Routes
$router->get('/', function() {
    echo json_encode([
        'status' => 'success',
        'message' => 'CoreFly API v1 is running',
        'tenant' => defined('CURRENT_TENANT_ID') ? CURRENT_TENANT_ID : 'unknown'
    ]);
});

$router->get('/health', function() {
    echo json_encode(['status' => 'healthy', 'timestamp' => time()]);
});

// Auth Routes
$router->post('/api/auth/register', [\App\Controllers\AuthController::class, 'register']);
$router->post('/api/auth/login', [\App\Controllers\AuthController::class, 'login']);
$router->get('/api/auth/me', [\App\Controllers\AuthController::class, 'me']);
$router->post('/api/auth/change-password', [\App\Controllers\AuthController::class, 'changePassword']);
$router->post('/api/auth/profile', [\App\Controllers\AuthController::class, 'updateProfile']);
$router->get('/api/auth/tenant', [\App\Controllers\AuthController::class, 'getTenant']);
$router->put('/api/auth/tenant', [\App\Controllers\AuthController::class, 'updateTenant']);

// Workspace Routes
$router->get('/api/workspace/{tenantId}/feed', [\App\Controllers\WorkspaceController::class, 'getFeed']);
$router->get('/api/workspace/{tenantId}/posts/{postId}', [\App\Controllers\WorkspaceController::class, 'getPost']);
$router->post('/api/workspace/{tenantId}/posts', [\App\Controllers\WorkspaceController::class, 'createPost']);
$router->post('/api/workspace/{tenantId}/posts/{postId}/comments', [\App\Controllers\WorkspaceController::class, 'addComment']);

// HR Routes
$router->get('/api/hr/{tenantId}/departments', [\App\Controllers\DepartmentController::class, 'index']);
$router->post('/api/hr/{tenantId}/departments', [\App\Controllers\DepartmentController::class, 'create']);

$router->get('/api/hr/{tenantId}/positions', [\App\Controllers\PositionController::class, 'index']);
$router->post('/api/hr/{tenantId}/positions', [\App\Controllers\PositionController::class, 'create']);

$router->get('/api/hr/{tenantId}/employees', [\App\Controllers\EmployeeController::class, 'index']);
$router->post('/api/hr/{tenantId}/employees', [\App\Controllers\EmployeeController::class, 'create']);
$router->get('/api/hr/{tenantId}/employees/{employeeId}', [\App\Controllers\EmployeeController::class, 'show']);

// HR Leave Requests
$router->get('/api/hr/{tenantId}/leave-requests', [\App\Controllers\HrLeaveController::class, 'index']);
$router->post('/api/hr/{tenantId}/leave-requests', [\App\Controllers\HrLeaveController::class, 'create']);
$router->patch('/api/hr/{tenantId}/leave-requests/{leaveId}/status', [\App\Controllers\HrLeaveController::class, 'updateStatus']);
$router->delete('/api/hr/{tenantId}/leave-requests/{leaveId}', [\App\Controllers\HrLeaveController::class, 'delete']);

// HR Payrolls
$router->get('/api/hr/{tenantId}/payrolls', [\App\Controllers\HrPayrollController::class, 'index']);
$router->post('/api/hr/{tenantId}/payrolls', [\App\Controllers\HrPayrollController::class, 'create']);
$router->patch('/api/hr/{tenantId}/payrolls/{payrollId}/status', [\App\Controllers\HrPayrollController::class, 'updateStatus']);
$router->delete('/api/hr/{tenantId}/payrolls/{payrollId}', [\App\Controllers\HrPayrollController::class, 'delete']);

// Company Tasks
$router->get('/api/tasks/{tenantId}', [\App\Controllers\CompanyTaskController::class, 'index']);
$router->post('/api/tasks/{tenantId}', [\App\Controllers\CompanyTaskController::class, 'create']);
$router->patch('/api/tasks/{tenantId}/{taskId}', [\App\Controllers\CompanyTaskController::class, 'update']);
$router->delete('/api/tasks/{tenantId}/{taskId}', [\App\Controllers\CompanyTaskController::class, 'delete']);

// Inventory Suppliers
$router->get('/api/inventory/{tenantId}/suppliers', [\App\Controllers\InventorySupplierController::class, 'index']);
$router->post('/api/inventory/{tenantId}/suppliers', [\App\Controllers\InventorySupplierController::class, 'create']);
$router->delete('/api/inventory/{tenantId}/suppliers/{supplierId}', [\App\Controllers\InventorySupplierController::class, 'delete']);

// Project Routes
$router->get('/api/projects/{tenantId}', [\App\Controllers\ProjectController::class, 'index']);
$router->post('/api/projects/{tenantId}', [\App\Controllers\ProjectController::class, 'create']);
$router->get('/api/projects/{tenantId}/{projectId}', [\App\Controllers\ProjectController::class, 'show']);
$router->post('/api/projects/{tenantId}/{projectId}/members', [\App\Controllers\ProjectController::class, 'addMember']);

// Project Tasks Routes
$router->get('/api/projects/{tenantId}/{projectId}/tasks', [\App\Controllers\ProjectTaskController::class, 'index']);
$router->post('/api/projects/{tenantId}/{projectId}/tasks', [\App\Controllers\ProjectTaskController::class, 'create']);
$router->patch('/api/projects/{tenantId}/{projectId}/tasks/{taskId}', [\App\Controllers\ProjectTaskController::class, 'update']);

// Messaging Routes
$router->get('/api/conversations/{tenantId}', [\App\Controllers\ConversationController::class, 'index']);
$router->post('/api/conversations/{tenantId}', [\App\Controllers\ConversationController::class, 'create']);
$router->get('/api/conversations/{tenantId}/{conversationId}', [\App\Controllers\ConversationController::class, 'show']);
$router->delete('/api/conversations/{tenantId}/{conversationId}', [\App\Controllers\ConversationController::class, 'delete']);

$router->get('/api/conversations/{tenantId}/{conversationId}/messages', [\App\Controllers\MessageController::class, 'index']);
$router->post('/api/conversations/{tenantId}/{conversationId}/messages', [\App\Controllers\MessageController::class, 'create']);
$router->patch('/api/conversations/{tenantId}/{conversationId}/messages/{messageId}', [\App\Controllers\MessageController::class, 'update']);

// VoIP & Video Call Routes
$router->post('/api/calls/{tenantId}/start', [\App\Controllers\CallController::class, 'startCall']);
$router->post('/api/calls/{tenantId}/signal', [\App\Controllers\CallController::class, 'handleSignaling']);
$router->get('/api/calls/{tenantId}/signals', [\App\Controllers\CallController::class, 'pollSignals']);
$router->get('/api/calls/{tenantId}/incoming', [\App\Controllers\CallController::class, 'pollIncoming']);
$router->post('/api/calls/{tenantId}/answer', [\App\Controllers\CallController::class, 'answerCall']);
$router->post('/api/calls/{tenantId}/end', [\App\Controllers\CallController::class, 'endCall']);
$router->get('/api/calls/{tenantId}/history', [\App\Controllers\CallController::class, 'getHistory']);

// Storage Routes
$router->post('/api/storage/{tenantId}/upload', [\App\Controllers\StorageController::class, 'upload']);
$router->get('/api/storage/{tenantId}/files', [\App\Controllers\StorageController::class, 'listFiles']);
$router->get('/api/storage/{tenantId}/files/{fileId}', [\App\Controllers\StorageController::class, 'download']);
$router->delete('/api/storage/{tenantId}/files/{fileId}', [\App\Controllers\StorageController::class, 'delete']);

// Notification Routes
$router->get('/api/notifications/{tenantId}', [\App\Controllers\NotificationController::class, 'index']);
$router->patch('/api/notifications/{tenantId}/{notificationId}/read', [\App\Controllers\NotificationController::class, 'markAsRead']);
$router->post('/api/notifications/{tenantId}/test', [\App\Controllers\NotificationController::class, 'createTest']);

// Roles & Permissions
$router->get('/api/roles/{tenantId}', [\App\Controllers\RoleController::class, 'index']);
$router->post('/api/roles/{tenantId}', [\App\Controllers\RoleController::class, 'create']);
$router->post('/api/roles/{tenantId}/{roleId}/permissions', [\App\Controllers\RoleController::class, 'updatePermissions']);
$router->delete('/api/roles/{tenantId}/{roleId}', [\App\Controllers\RoleController::class, 'delete']);

// Audit Logs
$router->get('/api/audit-logs/{tenantId}', [\App\Controllers\AuditLogController::class, 'index']);
$router->post('/api/audit-logs/{tenantId}/test', [\App\Controllers\AuditLogController::class, 'createTest']);
$router->get('/api/audit/{tenantId}', [\App\Controllers\AuditLogController::class, 'index']);
$router->post('/api/audit/{tenantId}/test', [\App\Controllers\AuditLogController::class, 'createTest']);

// CRM Routes
$router->get('/api/crm/{tenantId}/stats', [\App\Controllers\CrmController::class, 'stats']);
$router->get('/api/crm/{tenantId}/customers', [\App\Controllers\CrmController::class, 'indexCustomers']);
$router->post('/api/crm/{tenantId}/customers', [\App\Controllers\CrmController::class, 'createCustomer']);
$router->delete('/api/crm/{tenantId}/customers/{customerId}', [\App\Controllers\CrmController::class, 'deleteCustomer']);

$router->get('/api/crm/{tenantId}/deals', [\App\Controllers\CrmController::class, 'indexDeals']);
$router->post('/api/crm/{tenantId}/deals', [\App\Controllers\CrmController::class, 'createDeal']);
$router->patch('/api/crm/{tenantId}/deals/{dealId}/stage', [\App\Controllers\CrmController::class, 'updateDealStage']);
$router->delete('/api/crm/{tenantId}/deals/{dealId}', [\App\Controllers\CrmController::class, 'deleteDeal']);

$router->get('/api/crm/{tenantId}/activities', [\App\Controllers\CrmController::class, 'indexActivities']);
$router->post('/api/crm/{tenantId}/activities', [\App\Controllers\CrmController::class, 'createActivity']);

// Inventory Routes
$router->get('/api/inventory/{tenantId}/stats', [\App\Controllers\InventoryController::class, 'stats']);
$router->get('/api/inventory/{tenantId}/products', [\App\Controllers\InventoryController::class, 'indexProducts']);
$router->post('/api/inventory/{tenantId}/products', [\App\Controllers\InventoryController::class, 'createProduct']);
$router->post('/api/inventory/{tenantId}/products/{productId}/adjust', [\App\Controllers\InventoryController::class, 'adjustStock']);
$router->delete('/api/inventory/{tenantId}/products/{productId}', [\App\Controllers\InventoryController::class, 'deleteProduct']);

$router->get('/api/inventory/{tenantId}/movements', [\App\Controllers\InventoryController::class, 'indexMovements']);
$router->get('/api/inventory/{tenantId}/categories', [\App\Controllers\InventoryController::class, 'indexCategories']);
$router->post('/api/inventory/{tenantId}/categories', [\App\Controllers\InventoryController::class, 'createCategory']);

// Accounting Routes
$router->get('/api/accounting/{tenantId}/stats', [\App\Controllers\AccountingController::class, 'stats']);
$router->get('/api/accounting/{tenantId}/invoices', [\App\Controllers\AccountingController::class, 'indexInvoices']);
$router->post('/api/accounting/{tenantId}/invoices', [\App\Controllers\AccountingController::class, 'createInvoice']);
$router->get('/api/accounting/{tenantId}/invoices/{invoiceId}', [\App\Controllers\AccountingController::class, 'showInvoice']);
$router->patch('/api/accounting/{tenantId}/invoices/{invoiceId}/status', [\App\Controllers\AccountingController::class, 'updateInvoiceStatus']);
$router->delete('/api/accounting/{tenantId}/invoices/{invoiceId}', [\App\Controllers\AccountingController::class, 'deleteInvoice']);

// GIB E-Invoice & E-Archive Routes
$router->post('/api/accounting/{tenantId}/invoices/{invoiceId}/send-einvoice', [\App\Controllers\AccountingController::class, 'sendEInvoice']);
$router->get('/api/accounting/{tenantId}/invoices/{invoiceId}/ubl-xml', [\App\Controllers\AccountingController::class, 'getUblXml']);
$router->get('/api/accounting/{tenantId}/invoices/{invoiceId}/preview-html', [\App\Controllers\AccountingController::class, 'getPreviewHtml']);
$router->post('/api/accounting/{tenantId}/invoices/{invoiceId}/check-einvoice-status', [\App\Controllers\AccountingController::class, 'checkEInvoiceStatus']);

$router->get('/api/accounting/{tenantId}/transactions', [\App\Controllers\AccountingController::class, 'indexTransactions']);
$router->post('/api/accounting/{tenantId}/transactions', [\App\Controllers\AccountingController::class, 'createTransaction']);

$router->get('/api/accounting/{tenantId}/accounts', [\App\Controllers\AccountingController::class, 'indexAccounts']);
$router->post('/api/accounting/{tenantId}/accounts', [\App\Controllers\AccountingController::class, 'createAccount']);

// Helpdesk Routes
$router->get('/api/helpdesk/{tenantId}/stats', [\App\Controllers\HelpdeskController::class, 'stats']);
$router->get('/api/helpdesk/{tenantId}/tickets', [\App\Controllers\HelpdeskController::class, 'indexTickets']);
$router->post('/api/helpdesk/{tenantId}/tickets', [\App\Controllers\HelpdeskController::class, 'createTicket']);
$router->get('/api/helpdesk/{tenantId}/tickets/{ticketId}', [\App\Controllers\HelpdeskController::class, 'showTicket']);
$router->post('/api/helpdesk/{tenantId}/tickets/{ticketId}/messages', [\App\Controllers\HelpdeskController::class, 'addMessage']);
$router->patch('/api/helpdesk/{tenantId}/tickets/{ticketId}/status', [\App\Controllers\HelpdeskController::class, 'updateStatus']);

// Announcement Routes
$router->get('/api/announcements/{tenantId}', [\App\Controllers\AnnouncementController::class, 'index']);
$router->post('/api/announcements/{tenantId}', [\App\Controllers\AnnouncementController::class, 'create']);
$router->delete('/api/announcements/{tenantId}/{announcementId}', [\App\Controllers\AnnouncementController::class, 'delete']);

// Calendar Routes
$router->get('/api/calendar/{tenantId}/events', [\App\Controllers\CalendarController::class, 'index']);
$router->post('/api/calendar/{tenantId}/events', [\App\Controllers\CalendarController::class, 'create']);
$router->delete('/api/calendar/{tenantId}/events/{eventId}', [\App\Controllers\CalendarController::class, 'delete']);

// Field Routes
$router->get('/api/field/{tenantId}/tasks', [\App\Controllers\FieldController::class, 'index']);
$router->post('/api/field/{tenantId}/tasks', [\App\Controllers\FieldController::class, 'create']);
$router->patch('/api/field/{tenantId}/tasks/{taskId}/status', [\App\Controllers\FieldController::class, 'updateStatus']);
$router->delete('/api/field/{tenantId}/tasks/{taskId}', [\App\Controllers\FieldController::class, 'delete']);

// Donation Routes
$router->get('/api/donations/{tenantId}/stats', [\App\Controllers\DonationController::class, 'stats']);
$router->get('/api/donations/{tenantId}', [\App\Controllers\DonationController::class, 'index']);
$router->post('/api/donations/{tenantId}', [\App\Controllers\DonationController::class, 'create']);

// Politics Routes
$router->get('/api/politics/{tenantId}/stats', [\App\Controllers\PoliticsController::class, 'stats']);
$router->get('/api/politics/{tenantId}/volunteers', [\App\Controllers\PoliticsController::class, 'index']);
$router->post('/api/politics/{tenantId}/volunteers', [\App\Controllers\PoliticsController::class, 'create']);

// Root SuperAdmin Routes
$router->get('/api/root/stats', [\App\Controllers\RootTenantController::class, 'stats']);
$router->get('/api/root/tenants', [\App\Controllers\RootTenantController::class, 'index']);
$router->post('/api/root/tenants', [\App\Controllers\RootTenantController::class, 'create']);

// Dispatch
$router->dispatch();





