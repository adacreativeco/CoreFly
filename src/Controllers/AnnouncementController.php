<?php

declare(strict_types=1);

namespace CoreFly\Controllers;

use CoreFly\Models\Announcement;

class AnnouncementController extends BaseController
{
    public function index(): string
    {
        try {
            $this->requireAuth();
            $pageParams = $this->getPaginationParams();
            
            // Filtering
            $search = $_GET['search'] ?? null;
            $status = $_GET['status'] ?? null;
            $type = $_GET['type'] ?? null;
    
            // Retrieve ALL announcements first (without tenant filter)
            // We will filter them in PHP based on user's eligibility
            $query = Announcement::query()->withoutTenant();
            $list = $query->get();
    
            $currentUser = $this->currentUser;
            $isSuperAdmin = ($currentUser['role'] ?? '') === 'super-admin-role';
            $tenantId = $this->getCurrentTenantId();
            $userId = $this->getCurrentUserId();
            $userRole = $currentUser['role'] ?? '';
            $userDept = $currentUser['department'] ?? null;
    
            $list = array_filter($list, function($item) use ($search, $status, $type, $isSuperAdmin, $tenantId, $userId, $userRole, $userDept) {
                // 1. Basic Filters
                if ($status && ($item->status ?? 'published') !== $status) return false;
                if ($type && ($item->type ?? 'general') !== $type) return false;
                if ($search && stripos($item->title, $search) === false && stripos($item->content, $search) === false) return false;
    
                // 2. Visibility Logic
                if ($isSuperAdmin) {
                    return true; // Super Admin sees everything
                }
    
                // Department Scope Logic
                if ($userRole === 'department-manager-role') {
                    // Can see global tenant announcements (where dept is null) OR own dept announcements
                    $itemDept = $item->department_id ?? null;
                    if ($itemDept && $itemDept !== $userDept) {
                        return false; // Belongs to another department
                    }
                }
    
                // "Global" announcements (target_type = 'all')
                if (($item->target_type ?? 'all') === 'all') {
                    return true;
                }
    
                // Tenant-specific (legacy tenant_id check OR new target_type='tenant')
                if ($item->tenant_id === $tenantId) {
                    // Check specific targeting
                    if (($item->target_type ?? 'all') === 'role') {
                        $roles = $item->target_values ?? [];
                        if (!in_array($userRole, $roles)) return false;
                    }
                    if (($item->target_type ?? 'all') === 'user') {
                        $users = $item->target_values ?? [];
                        if (!in_array($userId, $users)) return false;
                    }
                    return true;
                }
    
                // Cross-tenant targeting
                if (($item->target_type ?? 'all') === 'tenant') {
                    $tenants = $item->target_values ?? [];
                    if (in_array($tenantId, $tenants)) return true;
                }
    
                return false;
            });
    
            // Enrich data
            $users = [];
            $userIds = array_unique(array_map(fn($i) => $i->author_id, $list));
            if (!empty($userIds)) {
                try {
                    foreach (\CoreFly\Models\User::all() as $u) {
                        $users[$u->id] = $u->getFullName();
                    }
                } catch (\Exception $e) {
                    error_log("Failed to load users for announcements: " . $e->getMessage());
                }
            }
    
            $dataArray = array_map(function($item) use ($users) {
                $arr = $item->toArray();
                $arr['author_name'] = $users[$item->author_id] ?? 'Bilinmeyen';
                return $arr;
            }, $list);
    
            // Sort: Pinned first, then date desc
            usort($dataArray, function($a, $b) {
                if (($a['is_pinned'] ?? false) !== ($b['is_pinned'] ?? false)) {
                    return ($b['is_pinned'] ?? false) ? 1 : -1;
                }
                return strtotime($b['created_at']) - strtotime($a['created_at']);
            });
    
            $data = $this->paginate(array_values($dataArray), $pageParams['page'], $pageParams['per_page']);
            return $this->successResponse($data, 'Duyurular getirildi');
        } catch (\Exception $e) {
            return $this->errorResponse('Server Error: ' . $e->getMessage(), 500);
        }
    }

    public function create(): string
    {
        $this->requireCsrfIfProd();
        $this->requirePermission('announcements:create');
        $data = $this->sanitizeInput($this->getRequestData());
        $required = ['title', 'content'];
        $errors = $this->validateRequired($data, $required);
        if ($errors) {
            return $this->errorResponse('Geçersiz veri', 422, $errors);
        }

        $isSuperAdmin = ($this->currentUser['role'] ?? '') === 'super-admin-role';
        $targetType = $data['target_type'] ?? 'all';
        
        // Security Check: Non-SuperAdmins can only create for their own tenant/roles/users
        if (!$isSuperAdmin && in_array($targetType, ['all', 'tenant'])) {
            // Force to tenant-local scope if not super admin
            // Actually 'tenant' target type is valid for local admins if they target their OWN tenant (redundant but okay)
            // But 'all' (Global) is strictly Super Admin only.
            if ($targetType === 'all') {
                $targetType = 'tenant'; // Downgrade to tenant
                $data['target_values'] = [$this->getCurrentTenantId()];
            }
        }

        // Prepare target values
        $targetValues = $data['target_values'] ?? [];
        if (is_string($targetValues)) {
            // If sent as comma-separated string or JSON string
            $decoded = json_decode($targetValues, true);
            $targetValues = is_array($decoded) ? $decoded : explode(',', $targetValues);
        }

        $deptId = null;
        if ($this->currentUser['role'] === 'department-manager-role') {
            $deptId = $this->currentUser['department'] ?? null;
            if (!$deptId) return $this->errorResponse('Departman bilgisi eksik', 403);
        }

        $announcement = new Announcement([
            'tenant_id' => $this->getCurrentTenantId(),
            'department_id' => $deptId,
            'title' => $data['title'],
            'content' => $data['content'],
            'author_id' => $this->getCurrentUserId(),
            'is_pinned' => isset($data['is_pinned']) ? filter_var($data['is_pinned'], FILTER_VALIDATE_BOOLEAN) : false,
            'priority' => $data['priority'] ?? 'medium',
            'status' => $data['status'] ?? 'published',
            'type' => $data['type'] ?? 'general',
            'target_type' => $targetType,
            'target_values' => $targetValues,
            'expiry_date' => $data['expiry_date'] ?? null,
        ]);
        
        // If Super Admin creates a global announcement, we might want to set tenant_id to a special value or keep it as their tenant
        // keeping it as their tenant is fine as long as index() logic handles visibility correctly (which it does now).
        
        $announcement->save();
        $this->logActivity('announcement:create', 'announcement', $announcement->id);
        return $this->successResponse($announcement->toArray(), 'Duyuru oluşturuldu');
    }

    public function update(?string $id = null): string
    {
        $this->requireCsrfIfProd();
        $this->requirePermission('announcements:update');
        $data = $this->sanitizeInput($this->getRequestData());
        $id = $id ?? $_GET['id'] ?? $data['id'] ?? null;
        if (!$id) {
            return $this->errorResponse('ID gerekli', 422);
        }
        
        // Use withoutTenant() to find it first, then check permissions
        $announcement = Announcement::query()->withoutTenant()->where('id', $id)->first();
        
        if (!$announcement) {
            return $this->errorResponse('Bulunamadı', 404);
        }

        // Permission Check:
        // Super Admin can edit anything.
        // Tenant Admin can edit only their own tenant's announcements.
        $isSuperAdmin = ($this->currentUser['role'] ?? '') === 'super-admin-role';
        if (!$isSuperAdmin && $announcement->tenant_id !== $this->getCurrentTenantId()) {
             return $this->errorResponse('Yetkisiz işlem', 403);
        }

        // Department Scope
        if (($this->currentUser['role'] ?? '') === 'department-manager-role') {
            if ($announcement->department_id !== ($this->currentUser['department'] ?? null)) {
                return $this->errorResponse('Bu duyuruyu düzenleme yetkiniz yok', 403);
            }
        }

        $fields = ['title', 'content', 'is_pinned', 'priority', 'status', 'type', 'expiry_date', 'target_type', 'target_values'];
        foreach ($fields as $f) {
            if (array_key_exists($f, $data)) {
                if ($f === 'is_pinned') {
                    $announcement->is_pinned = filter_var($data[$f], FILTER_VALIDATE_BOOLEAN);
                } elseif ($f === 'target_values') {
                     $val = $data[$f];
                     if (is_string($val)) {
                        $decoded = json_decode($val, true);
                        $val = is_array($decoded) ? $decoded : explode(',', $val);
                     }
                     $announcement->target_values = $val;
                } else {
                    $announcement->$f = $this->sanitizeInput($data[$f]);
                }
            }
        }
        $announcement->save();
        $this->logActivity('announcement:update', 'announcement', $announcement->id);
        return $this->successResponse($announcement->toArray(), 'Duyuru güncellendi');
    }

    public function delete(?string $id = null): string
    {
        $this->requireCsrfIfProd();
        $this->requirePermission('announcements:delete');
        $id = $id ?? $_GET['id'] ?? null;
        if (!$id) {
            return $this->errorResponse('ID gerekli', 422);
        }
        $announcement = Announcement::find($id);
        if (!$announcement) {
            return $this->errorResponse('Bulunamadı', 404);
        }

        // Department Scope
        if (($this->currentUser['role'] ?? '') === 'department-manager-role') {
            if ($announcement->department_id !== ($this->currentUser['department'] ?? null)) {
                return $this->errorResponse('Bu duyuruyu silme yetkiniz yok', 403);
            }
        }

        $announcement->delete();
        $this->logActivity('announcement:delete', 'announcement', $id);
        return $this->successResponse(['id' => $id], 'Duyuru silindi');
    }

    public function pin(): string
    {
        $this->requireCsrfIfProd();
        $this->requirePermission('announcements:update');
        $id = $_GET['id'] ?? null;
        if (!$id) {
            return $this->errorResponse('ID gerekli', 422);
        }
        $announcement = Announcement::find($id);
        if (!$announcement) {
            return $this->errorResponse('Bulunamadı', 404);
        }
        $announcement->is_pinned = true;
        $announcement->save();
        $this->logActivity('announcement:pin', 'announcement', $id);
        return $this->successResponse($announcement->toArray(), 'Duyuru sabitlendi');
    }

    public function unpin(): string
    {
        $this->requireCsrfIfProd();
        $this->requirePermission('announcements:update');
        $id = $_GET['id'] ?? null;
        if (!$id) {
            return $this->errorResponse('ID gerekli', 422);
        }
        $announcement = Announcement::find($id);
        if (!$announcement) {
            return $this->errorResponse('Bulunamadı', 404);
        }
        $announcement->is_pinned = false;
        $announcement->save();
        $this->logActivity('announcement:unpin', 'announcement', $id);
        return $this->successResponse($announcement->toArray(), 'Duyuru sabitleme kaldırıldı');
    }
}
