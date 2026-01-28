<?php

declare(strict_types=1);

namespace CoreFly\Controllers;

use CoreFly\Utils\Database;

class RoleController extends BaseController
{
    public function index(): string
    {
        $this->requirePermission('users:read'); // Changed from reports:read to users:read as roles are related to users
        
        // Use Model to ensure tenant scoping
        $roles = \CoreFly\Models\Role::all();
        
        // Transform if needed, or return as is
        return $this->successResponse(['items' => $roles], 'Roller getirildi');
    }

    public function permissions(): string
    {
        $this->requireCsrfIfProd();
        $this->requirePermission('users:update');
        $data = $this->getRequestData();
        $roleId = $this->sanitizeInput($data['role_id'] ?? '');
        $permission = $this->sanitizeInput($data['permission'] ?? '');
        $action = $this->sanitizeInput($data['action'] ?? 'add');
        
        if (!$roleId || !$permission) {
            return $this->errorResponse('role_id ve permission gerekli', 422);
        }

        // Verify role belongs to tenant
        $role = \CoreFly\Models\Role::find($roleId);
        if (!$role) {
             return $this->errorResponse('Rol bulunamadı', 404);
        }

        // Check if system role (optional: can tenant admin edit system roles? maybe not permissions of system roles?)
        // For now, assume they can edit permissions of custom roles, maybe not system ones like 'tenant-admin' itself to avoid lockout.
        // But requirements say "Yeni rol oluşturma... Her rol için yetkileri belirleme".
        
        $db = Database::getInstance();
        try {
            if ($action === 'remove') {
                $role->removePermission($permission);
                $role->save();
                $this->logActivity('role_permission:remove', 'role', $roleId, ['permission' => $permission]);
                return $this->successResponse(null, 'İzin kaldırıldı');
            }
            
            $role->addPermission($permission);
            $role->save();
            $this->logActivity('role_permission:add', 'role', $roleId, ['permission' => $permission]);
            return $this->successResponse(null, 'İzin eklendi');
        } catch (\Exception $e) {
            return $this->errorResponse('İzin güncelleme başarısız', 500);
        }
    }
    
    public function store(): string
    {
        $this->requirePermission('users:create'); // Need a permission for roles? users:create is close enough or new 'roles:create'
        $data = $this->getJsonInput();
        
        if (empty($data['name'])) {
            return $this->errorResponse('Rol adı gerekli', 422);
        }
        
        $role = new \CoreFly\Models\Role();
        $role->name = $data['name'];
        $role->description = $data['description'] ?? '';
        $role->tenant_id = $this->getCurrentTenantId();
        $role->status = 'active';

        if (!empty($data['permissions']) && is_array($data['permissions'])) {
            foreach ($data['permissions'] as $perm) {
                if (str_contains($perm, ':')) {
                    $role->addPermission($perm);
                }
            }
        }

        $role->save();
        
        return $this->successResponse($role, 'Rol oluşturuldu', 201);
    }
    
    public function destroy(string $id): string
    {
        $this->requirePermission('users:delete');
        $role = \CoreFly\Models\Role::find($id);
        
        if (!$role) {
            return $this->errorResponse('Rol bulunamadı', 404);
        }
        
        if ($role->isSystemRole()) {
            return $this->errorResponse('Sistem rolleri silinemez', 403);
        }
        
        // Check if assigned to users?
        // $role->canBeDeleted() check is in model
        if (!$role->canBeDeleted()) {
             return $this->errorResponse('Kullanıcısı olan rol silinemez', 400);
        }
        
        $role->delete();
        return $this->successResponse(null, 'Rol silindi');
    }
}

