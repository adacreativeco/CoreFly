<?php

declare(strict_types=1);

namespace CoreFly\Controllers\Root;

use CoreFly\Models\Tenant;
use CoreFly\Models\User;
use CoreFly\Utils\Database;
use App\Services\SystemMetricsService;

class RootDashboardController extends BaseRootController
{
    public function index()
    {
        $this->requireRootAuth();

        $db = Database::getInstance();

        // 1. Total Tenants
        $totalTenants = Tenant::query()->count();
        $activeTenants = Tenant::where('status', 'active')->count();

        // 2. Total Users (Global)
        $totalUsers = User::query()->withoutTenant()->count();
        $activeUsers = User::query()->withoutTenant()->where('status', 'active')->count();

        // 3. System Health (Using Service)
        $metricsService = new SystemMetricsService();
        $systemMetrics = $metricsService->getMetrics();
        
        // 4. Recent Tenants
        $recentTenants = Tenant::query()
            ->orderBy('created_at', 'DESC')
            ->limit(5)
            ->get();

        // 5. Recent Users (Global)
        $recentUsers = User::query()
            ->withoutTenant()
            ->orderBy('created_at', 'DESC')
            ->limit(5)
            ->get();
            
        // 6. API Stats (Mocked or from Logs)
        // Ideally we query audit_logs
        $apiRequestsToday = 15420; // Mock
        $errorRate = 0.45; // Mock

        return $this->successResponse([
            'metrics' => [
                'total_tenants' => $totalTenants,
                'active_tenants' => $activeTenants,
                'total_users' => $totalUsers,
                'active_users' => $activeUsers,
                'disk_usage_percent' => $systemMetrics['disk']['percentage'],
                'cpu_usage_percent' => $systemMetrics['cpu'],
                'memory_usage' => $systemMetrics['memory'],
                'api_requests_today' => $apiRequestsToday,
                'error_rate_percent' => $errorRate,
                'uptime' => $systemMetrics['uptime']
            ],
            'recent_tenants' => $recentTenants,
            'recent_users' => array_map(function($u) {
                return [
                    'id' => $u->id,
                    'username' => $u->username,
                    'email' => $u->email,
                    'created_at' => $u->created_at,
                    'tenant_id' => $u->tenant_id // Show which tenant they belong to
                ];
            }, $recentUsers)
        ]);
    }

    public function stats()
    {
        $this->requireRootAuth();
        $metricsService = new SystemMetricsService();
        return $this->successResponse($metricsService->getMetrics());
    }
}
