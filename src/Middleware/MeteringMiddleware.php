<?php

namespace CoreFly\Middleware;

use CoreFly\Services\BillingService;

class MeteringMiddleware
{
    public function handle()
    {
        // Only track tenant requests, ignore root/auth
        $uri = $_SERVER['REQUEST_URI'] ?? '/';

        // Skip if root, login, or public assets
        if (strpos($uri, '/api/root') === 0 || strpos($uri, '/login') !== false || strpos($uri, '/public') !== false) {
            return;
        }

        // Identify Tenant
// In a real app, this comes from the subdomain or JWT.
// For now, we assume tenant_id is in the session or JWT context if authenticated.

        // Mock Tenant ID detection for demo
        $tenantId = $_SESSION['tenant_id'] ?? null;

        if ($tenantId) {
            $billingService = new BillingService();
            // Fire and forget (in a real app, push to queue)
            $billingService->recordUsage($tenantId, 'api_calls', 1);
        }
    }
}