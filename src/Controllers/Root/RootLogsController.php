<?php

declare(strict_types=1);

namespace CoreFly\Controllers\Root;

use CoreFly\Models\AuditLog;
use CoreFly\Models\User;
use CoreFly\Models\Tenant;

class RootLogsController extends BaseRootController
{
    public function index()
    {
        $this->requireRootAuth();

        $page = (int) ($this->getQueryParam('page', 1));
        $limit = (int) ($this->getQueryParam('limit', 20));
        $search = $this->getQueryParam('search', '');
        $tenantId = $this->getQueryParam('tenant_id', '');
        $action = $this->getQueryParam('action', '');

        // Query Global Logs
        $query = AuditLog::query()->withoutTenant();

        if ($search) {
            $query->where('action', 'LIKE', "%$search%")
                  ->orWhere('resource_type', 'LIKE', "%$search%");
        }

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        if ($action) {
            $query->where('action', $action);
        }

        $total = $query->count();
        $logs = $query->limit($limit)
                      ->offset(($page - 1) * $limit)
                      ->orderBy('created_at', 'DESC')
                      ->get();

        // Enrich logs with names
        $items = array_map(function($log) {
            $l = $log->toArray();
            
            // Tenant Name
            $tenant = Tenant::find($log->tenant_id);
            $l['tenant_name'] = $tenant ? $tenant->name : 'Unknown';

            // User Name
            if ($log->user_id) {
                $user = User::query()->withoutTenant()->where('id', $log->user_id)->first();
                $l['user_name'] = $user ? $user->username : 'System';
            } else {
                $l['user_name'] = 'System';
            }

            return $l;
        }, $logs);

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
}
