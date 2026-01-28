<?php

declare(strict_types=1);

namespace CoreFly\Controllers;

use CoreFly\Models\Ticket;
use CoreFly\Models\TicketCategory;
use CoreFly\Models\TicketMessage;

class HelpdeskController extends BaseController
{
    public function __construct()
    {
        try {
            parent::__construct();
        } catch (\Throwable $e) {
            error_log("HelpdeskController Init Error: " . $e->getMessage());
        }
    }

    public function index(): string
    {
        $this->requirePermission('helpdesk:read');
        $tenantId = $this->getCurrentTenantId();
        $userId = $this->getCurrentUserId();
        $role = $this->getCurrentUserRole();
        
        // Define staff roles that can see all tickets
        $staffRoles = ['super-admin-role', 'tenant-admin-role', 'department-manager-role'];
        $isStaff = in_array($role, $staffRoles);
        $isSuperAdmin = $role === 'super-admin-role';
        
        $page = (int)($this->getQueryParam('page', 1));
        $limit = (int)($this->getQueryParam('limit', 20));
        $offset = ($page - 1) * $limit;
        
        $status = $this->getQueryParam('status', '');
        $priority = $this->getQueryParam('priority', '');
        
        if ($isSuperAdmin) {
            // Super Admin sees ALL tickets from ALL tenants (Platform Support)
            $query = Ticket::query()->withoutTenant();
        } else {
            // Others only see their tenant's tickets
            $query = Ticket::where('tenant_id', $tenantId);
        }
        
        // If not staff, only show own tickets
        if (!$isStaff) {
            $query->where('user_id', $userId);
        } else if ($role === 'department-manager-role') {
            // Department Manager sees all tickets in their department
            $deptId = $this->currentUser['department'] ?? null;
            if ($deptId) {
                $query->where('department_id', $deptId);
            }
        }
        
        if (!empty($status)) {
            $query->where('status', $status);
        }
        
        if (!empty($priority)) {
            $query->where('priority', $priority);
        }
        
        $total = $query->count();
        $items = $query->orderBy('created_at', 'DESC')->limit($limit)->offset($offset)->get();
        
        // Enrich
        foreach ($items as $item) {
            $item->category_name = $item->getCategoryName();
            // Add tenant info for Super Admin
            if ($isSuperAdmin) {
                $tenant = \CoreFly\Models\Tenant::find($item->tenant_id);
                $item->tenant_name = $tenant ? $tenant->name : 'Bilinmiyor';
            }
        }
        
        return $this->successResponse([
            'items' => $items,
            'is_staff' => $isStaff, // Send this to frontend
            'is_super_admin' => $isSuperAdmin,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => ceil($total / $limit)
            ]
        ], 'Talepler getirildi');
    }

    public function show(string $id): string
    {
        $this->requirePermission('helpdesk:read');
        $tenantId = $this->getCurrentTenantId();
        $userId = $this->getCurrentUserId();
        $role = $this->getCurrentUserRole();
        $staffRoles = ['super-admin-role', 'tenant-admin-role', 'department-manager-role'];
        $isStaff = in_array($role, $staffRoles);
        $isSuperAdmin = $role === 'super-admin-role';
        
        // Find ticket (globally if super admin, scoped otherwise)
        if ($isSuperAdmin) {
             $ticket = Ticket::query()->withoutTenant()->where('id', $id)->first();
        } else {
             $ticket = Ticket::find($id);
        }
        
        if (!$ticket) {
            return $this->errorResponse('Talep bulunamadı', 404);
        }

        // Tenant Check (skip for Super Admin)
        if (!$isSuperAdmin && $ticket->tenant_id !== $tenantId) {
             return $this->errorResponse('Talep bulunamadı', 404);
        }
        
        // Access Control: Staff can see all, User can only see own
        if (!$isStaff && $ticket->user_id !== $userId) {
            return $this->errorResponse('Bu talebi görüntüleme yetkiniz yok', 403);
        }

        // Department Scope
        if ($role === 'department-manager-role') {
            $deptId = $this->currentUser['department'] ?? null;
            if ($ticket->department_id !== $deptId) {
                return $this->errorResponse('Bu talebi görüntüleme yetkiniz yok', 403);
            }
        }
        
        $ticket->category_name = $ticket->getCategoryName();
        if ($isSuperAdmin) {
            $tenant = \CoreFly\Models\Tenant::find($ticket->tenant_id);
            $ticket->tenant_name = $tenant ? $tenant->name : 'Bilinmiyor';
        }

        $messages = $ticket->getMessages();
        
        // Filter internal messages if not staff
        if (!$isStaff) {
            $messages = array_filter($messages, function($msg) {
                return empty($msg['is_internal']) || $msg['is_internal'] == 0;
            });
            $messages = array_values($messages); // Re-index
        }
        
        return $this->successResponse([
            'ticket' => $ticket,
            'messages' => $messages,
            'is_staff' => $isStaff,
            'is_super_admin' => $isSuperAdmin
        ], 'Talep detayı getirildi');
    }

    public function create(): string
    {
        $this->requireCsrfIfProd();
        $this->requirePermission('helpdesk:create');
        $tenantId = $this->getCurrentTenantId();
        $userId = $this->getCurrentUserId();
        
        $data = $this->getJsonInput();
        
        if (empty($data['title']) || empty($data['message'])) {
            return $this->errorResponse('Başlık ve mesaj zorunludur', 400);
        }
        
        // 1. Create Ticket
        $ticketId = $this->generateUUID();
        $ticket = new Ticket([
            'id' => $ticketId,
            'tenant_id' => $tenantId,
            'department_id' => $this->currentUser['department'] ?? null,
            'user_id' => $userId,
            'title' => $data['title'],
            'description' => substr($data['message'], 0, 200) . '...', // Preview
            'priority' => $data['priority'] ?? 'medium',
            'status' => 'open',
            'category_id' => $data['category_id'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        
        if ($ticket->save()) {
            // 2. Create Initial Message
            $message = new TicketMessage([
                'id' => $this->generateUUID(),
                'tenant_id' => $tenantId,
                'ticket_id' => $ticketId,
                'user_id' => $userId,
                'message' => $data['message'],
                'is_internal' => false,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            $message->save();
            
            $this->logActivity('ticket:create', 'ticket', $ticketId);
            return $this->successResponse($ticket, 'Talep oluşturuldu', 201);
        }
        
        return $this->errorResponse('Talep oluşturulamadı', 500);
    }

    public function addMessage(string $id): string
    {
        // Use read permission initially, then verify ownership or update permission
        $this->requirePermission('helpdesk:read');
        $tenantId = $this->getCurrentTenantId();
        $userId = $this->getCurrentUserId();
        $role = $this->getCurrentUserRole();
        $staffRoles = ['super-admin-role', 'tenant-admin-role', 'department-manager-role'];
        $isStaff = in_array($role, $staffRoles);
        $isSuperAdmin = $role === 'super-admin-role';
        
        if ($isSuperAdmin) {
            $ticket = Ticket::query()->withoutTenant()->where('id', $id)->first();
        } else {
            $ticket = Ticket::find($id);
        }

        if (!$ticket) {
            return $this->errorResponse('Talep bulunamadı', 404);
        }

        if (!$isSuperAdmin && $ticket->tenant_id !== $tenantId) {
            return $this->errorResponse('Talep bulunamadı', 404);
        }
        
        // Authorization check: Must be staff OR ticket owner
        if (!$isStaff && $ticket->user_id !== $userId) {
             return $this->errorResponse('Bu talebe yanıt verme yetkiniz yok', 403);
        }
        
        $data = $this->getJsonInput();
        if (empty($data['message'])) {
            return $this->errorResponse('Mesaj boş olamaz', 400);
        }
        
        // Only staff can send internal messages
        $isInternal = $isStaff ? ($data['is_internal'] ?? false) : false;
        
        $message = new TicketMessage([
            'id' => $this->generateUUID(),
            'tenant_id' => $ticket->tenant_id, // Use ticket's tenant_id, not current user's (important for Super Admin)
            'ticket_id' => $ticket->id,
            'user_id' => $userId,
            'message' => $data['message'],
            'is_internal' => $isInternal,
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        if ($message->save()) {
            // Update ticket timestamp and status logic
            $ticket->updated_at = date('Y-m-d H:i:s');
            
            // Auto-update status based on who replied
            if ($isStaff) {
                // If staff replies, and it was open/customer-reply, mark as answered
                if (in_array($ticket->status, ['open', 'customer_reply'])) {
                    $ticket->status = 'answered';
                }
            } else {
                // If customer replies, mark as customer_reply (unless it was resolved/closed? maybe reopen?)
                if ($ticket->status !== 'closed') {
                    $ticket->status = 'customer_reply';
                }
            }
            
            $ticket->save();
            
            return $this->successResponse($message, 'Mesaj eklendi', 201);
        }
        
        return $this->errorResponse('Mesaj eklenemedi', 500);
    }

    public function updateStatus(string $id): string
    {
        $this->requirePermission('helpdesk:update');
        $tenantId = $this->getCurrentTenantId();
        $role = $this->getCurrentUserRole();
        $isSuperAdmin = $role === 'super-admin-role';
        
        if ($isSuperAdmin) {
            $ticket = Ticket::query()->withoutTenant()->where('id', $id)->first();
        } else {
            $ticket = Ticket::find($id);
        }

        if (!$ticket) {
             return $this->errorResponse('Talep bulunamadı', 404);
        }

        if (!$isSuperAdmin && $ticket->tenant_id !== $tenantId) {
            return $this->errorResponse('Talep bulunamadı', 404);
        }
        
        $data = $this->getJsonInput();
        if (!empty($data['status'])) $ticket->status = $data['status'];
        if (!empty($data['priority'])) $ticket->priority = $data['priority'];
        if (!empty($data['assigned_to'])) $ticket->assigned_to = $data['assigned_to'];
        
        $ticket->updated_at = date('Y-m-d H:i:s');
        $ticket->save();
        
        return $this->successResponse($ticket, 'Durum güncellendi');
    }

    public function delete(): string
    {
        $this->requirePermission('helpdesk:delete');
        $id = $_GET['id'] ?? null; // Legacy support
        // In new routing, ID might come from URL param in index.php, handled by $id arg in delete($id)
        // But since this method signature is delete(), we assume query param or legacy call.
        
        // Let's support an argument if index.php passes it
        $args = func_get_args();
        if (!empty($args[0])) $id = $args[0];
        
        if (!$id) return $this->errorResponse('ID gerekli', 422);
        
        $ticket = Ticket::find($id);
        if (!$ticket) return $this->errorResponse('Talep bulunamadı', 404);
        
        // Delete messages first? Or soft delete ticket?
        // Let's just delete ticket for now.
        $ticket->delete();
        
        return $this->successResponse(['id' => $id], 'Talep silindi');
    }

    // --- Category Methods ---

    public function getCategories(): string
    {
        $this->requirePermission('helpdesk:read');
        $tenantId = $this->getCurrentTenantId();
        
        $categories = TicketCategory::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->get();
            
        return $this->successResponse($categories, 'Kategoriler getirildi');
    }

    public function storeCategory(): string
    {
        $this->requirePermission('helpdesk:create');
        $tenantId = $this->getCurrentTenantId();
        $userId = $this->getCurrentUserId();
        
        $data = $this->getJsonInput();
        if (empty($data['name'])) return $this->errorResponse('İsim zorunludur', 400);
        
        $cat = new TicketCategory([
            'id' => $this->generateUUID(),
            'tenant_id' => $tenantId,
            'name' => $data['name'],
            'description' => $data['description'] ?? '',
            'color' => $data['color'] ?? '#3b82f6',
            'is_active' => true,
            'created_by' => $userId,
            'updated_by' => $userId,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        
        $cat->save();
        return $this->successResponse($cat, 'Kategori oluşturuldu', 201);
    }
    
    public function deleteCategory(string $id): string
    {
        $this->requirePermission('helpdesk:delete');
        $tenantId = $this->getCurrentTenantId();
        
        $cat = TicketCategory::find($id);
        if (!$cat || $cat->tenant_id !== $tenantId) return $this->errorResponse('Kategori bulunamadı', 404);
        
        $cat->is_active = false; // Soft delete
        $cat->save();
        
        return $this->successResponse(null, 'Kategori silindi');
    }
}
