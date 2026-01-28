<?php

declare(strict_types=1);

namespace CoreFly\Controllers;

use CoreFly\Models\User;

class UserController extends BaseController
{
    public function index(): string
    {
        try {
            $this->requirePermission('users:read');
            $tenantId = $this->getCurrentTenantId();
            
            $page = (int)($this->getQueryParam('page', 1));
            $limit = (int)($this->getQueryParam('limit', 20));
            $offset = ($page - 1) * $limit;
            
            $search = $this->getQueryParam('search', '');
            
            $query = User::where('tenant_id', $tenantId);

            // Department Manager Scope
            if ($this->getCurrentUserRole() === 'department-manager-role') {
                $deptId = $this->currentUser['department'] ?? null;
                if ($deptId) {
                    $query->where('department_id', $deptId);
                }
            }
            
            if (!empty($search)) {
                $query->where('(first_name LIKE :search OR last_name LIKE :search OR email LIKE :search OR username LIKE :search)');
                $query->setBindings(['search' => "%{$search}%"]);
            }
            
            $total = $query->count();
            $users = $query->orderBy('first_name', 'ASC')->limit($limit)->offset($offset)->get();
            
            return $this->successResponse([
                'users' => array_map(function($user) {
                    return [
                        'id' => $user->id,
                        'full_name' => $user->getFullName(),
                        'first_name' => $user->first_name,
                        'last_name' => $user->last_name,
                        'username' => $user->username,
                        'email' => $user->email,
                        'phone' => $user->phone,
                        'role' => $user->role_id,
                        'department_id' => $user->department_id,
                        'status' => $user->status,
                        'avatar' => $user->getAvatarUrl(),
                        'preferences' => $user->preferences,
                        'created_at' => $user->created_at
                    ];
                }, $users),
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'pages' => ceil($total / $limit)
                ]
            ], 'Kullanıcı listesi getirildi');
        } catch (\Throwable $e) {
            return $this->errorResponse('User Error: ' . $e->getMessage(), 500);
        }
    }

    public function store(): string
    {
        try {
            $this->requirePermission('users:create');
            $tenantId = $this->getCurrentTenantId();
            
            $data = $this->getJsonInput();
            
            $requiredFields = ['username', 'email', 'password', 'first_name', 'last_name', 'role_id'];
            foreach ($requiredFields as $field) {
                if (empty($data[$field])) {
                    return $this->errorResponse("{$field} alanı zorunludur", 400);
                }
            }
            
            // Check if email or username exists
            if (User::where('email', $data['email'])->count() > 0) {
                return $this->errorResponse('Bu email adresi zaten kullanımda', 400);
            }
            if (User::where('username', $data['username'])->count() > 0) {
                return $this->errorResponse('Bu kullanıcı adı zaten kullanımda', 400);
            }

            // Department Manager Scope
            if ($this->getCurrentUserRole() === 'department-manager-role') {
                $deptId = $this->currentUser['department'] ?? null;
                if (!$deptId) {
                    return $this->errorResponse('Kendi departmanınız tanımlı değil', 403);
                }
                // Force department to current user's department
                $data['department_id'] = $deptId;
            }
            
            $user = new User([
                'id' => bin2hex(random_bytes(16)), 
                'tenant_id' => $tenantId,
                'username' => $data['username'],
                'email' => $data['email'],
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'phone' => $data['phone'] ?? null,
                'department_id' => $data['department_id'] ?? null,
                'role_id' => $data['role_id'],
                'status' => $data['status'] ?? 'active',
                'preferences' => $data['preferences'] ?? [],
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            
            $user->setPassword($data['password']);
            
            if ($user->save()) {
                $this->logActivity('user_created', 'user', $user->id);
                return $this->successResponse($user, 'Kullanıcı oluşturuldu', 201);
            }
            
            return $this->errorResponse('Kullanıcı oluşturulamadı', 500);
        } catch (\Throwable $e) {
            return $this->errorResponse('User Error: ' . $e->getMessage(), 500);
        }
    }

    public function show(string $id): string
    {
        try {
            $this->requirePermission('users:read');
            $tenantId = $this->getCurrentTenantId();
            
            $user = User::find($id);
            
            if (!$user || $user->tenant_id !== $tenantId) {
                return $this->errorResponse('Kullanıcı bulunamadı', 404);
            }

            // Department Manager Scope
            if ($this->getCurrentUserRole() === 'department-manager-role') {
                $deptId = $this->currentUser['department'] ?? null;
                if ($user->department_id !== $deptId) {
                    return $this->errorResponse('Bu kullanıcıyı görüntüleme yetkiniz yok', 403);
                }
            }
            
            return $this->successResponse($user, 'Kullanıcı detayı getirildi');
        } catch (\Throwable $e) {
            return $this->errorResponse('User Error: ' . $e->getMessage(), 500);
        }
    }

    public function update(?string $id = null): string
    {
        try {
            $this->requirePermission('users:update');
            $tenantId = $this->getCurrentTenantId();
            
            $user = User::find($id);
            
            if (!$user || $user->tenant_id !== $tenantId) {
                return $this->errorResponse('Kullanıcı bulunamadı', 404);
            }

            // Department Manager Scope
            if ($this->getCurrentUserRole() === 'department-manager-role') {
                $deptId = $this->currentUser['department'] ?? null;
                if ($user->department_id !== $deptId) {
                    return $this->errorResponse('Bu kullanıcıyı düzenleme yetkiniz yok', 403);
                }
                // Prevent changing department
                if (isset($data['department_id']) && $data['department_id'] !== $deptId) {
                     return $this->errorResponse('Kullanıcıyı başka departmana taşıyamazsınız', 403);
                }
            }
            
            $data = $this->getJsonInput();
            
            $fillable = ['first_name', 'last_name', 'phone', 'department_id', 'role_id', 'status', 'preferences'];
            foreach ($fillable as $field) {
                if (isset($data[$field])) {
                    $user->$field = $data[$field];
                }
            }
            
            if (!empty($data['password'])) {
                $user->setPassword($data['password']);
            }
            
            $user->updated_at = date('Y-m-d H:i:s');
            
            if ($user->save()) {
                $this->logActivity('user_updated', 'user', $user->id);
                return $this->successResponse($user, 'Kullanıcı güncellendi');
            }
            
            return $this->errorResponse('Kullanıcı güncellenemedi', 500);
        } catch (\Throwable $e) {
            return $this->errorResponse('User Error: ' . $e->getMessage(), 500);
        }
    }

    public function destroy(?string $id = null): string
    {
        try {
            $this->requirePermission('users:delete');
            $tenantId = $this->getCurrentTenantId();
            
            $user = User::find($id);
            
            if (!$user || $user->tenant_id !== $tenantId) {
                return $this->errorResponse('Kullanıcı bulunamadı', 404);
            }

            // Department Manager Scope
            if ($this->getCurrentUserRole() === 'department-manager-role') {
                $deptId = $this->currentUser['department'] ?? null;
                if ($user->department_id !== $deptId) {
                    return $this->errorResponse('Bu kullanıcıyı silme yetkiniz yok', 403);
                }
            }
            
            if ($user->id === $this->getCurrentUserId()) {
                return $this->errorResponse('Kendinizi silemezsiniz', 400);
            }
            
            if ($user->delete()) {
                $this->logActivity('user_deleted', 'user', $id);
                return $this->successResponse(null, 'Kullanıcı silindi');
            }
            
            return $this->errorResponse('Kullanıcı silinemedi', 500);
        } catch (\Throwable $e) {
            return $this->errorResponse('User Error: ' . $e->getMessage(), 500);
        }
    }
}
