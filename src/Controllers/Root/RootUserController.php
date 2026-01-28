<?php

declare(strict_types=1);

namespace CoreFly\Controllers\Root;

use CoreFly\Models\User;
use CoreFly\Utils\Validator;

class RootUserController extends BaseRootController
{
    public function index()
    {
        $this->requireRootAuth();

        $page = (int) ($this->getQueryParam('page', 1));
        $limit = (int) ($this->getQueryParam('limit', 20));
        $search = $this->getQueryParam('search', '');
        $tenantId = $this->getQueryParam('tenant_id', '');

        // CRITICAL: Use withoutTenant() to query global user table
        $query = User::query()->withoutTenant();

        if ($search) {
            $query->where('username', 'LIKE', "%$search%")
                  ->orWhere('email', 'LIKE', "%$search%")
                  ->orWhere('first_name', 'LIKE', "%$search%")
                  ->orWhere('last_name', 'LIKE', "%$search%");
        }

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        $total = $query->count();
        $users = $query->limit($limit)
                       ->offset(($page - 1) * $limit)
                       ->orderBy('created_at', 'DESC')
                       ->get();

        // Map to add Tenant Name
        $items = array_map(function($user) {
            $u = $user->toArray();
            $tenant = \CoreFly\Models\Tenant::find($user->tenant_id);
            $u['tenant_name'] = $tenant ? $tenant->name : 'Unknown';
            return $u;
        }, $users);

        return $this->successResponse([
            'items' => $items,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $limit,
                'total' => $total,
                'last_page' => ceil($total / $limit)
            ]
        ]);
    }

    public function resetPassword(string $id)
    {
        $this->requireRootAuth();
        
        // Global find
        $user = User::query()->withoutTenant()->where('id', $id)->first();

        if (!$user) {
            return $this->errorResponse('User not found', 404);
        }

        $data = $this->getJsonInput();
        if (empty($data['password'])) {
            return $this->errorResponse('Password is required', 400);
        }

        $user->setPassword($data['password']);
        $user->save();

        return $this->successResponse(null, 'Password reset successfully');
    }
    
    public function toggleStatus(string $id)
    {
        $this->requireRootAuth();
        $user = User::query()->withoutTenant()->where('id', $id)->first();
        
        if (!$user) {
            return $this->errorResponse('User not found', 404);
        }
        
        $user->status = $user->status === 'active' ? 'suspended' : 'active';
        $user->save();
        
        return $this->successResponse(['status' => $user->status], 'User status updated');
    }
}
