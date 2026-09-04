<?php

namespace App\Middleware;

use App\Core\Database;

class TenantMiddleware
{
    public function handle()
    {
        // Skip middleware for public paths
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        if ($path === '/' || $path === '/health' || strpos($path, '/api/auth/') === 0) {
            return;
        }

        $tenantId = $this->resolveTenantId();

        if (!$tenantId) {
            $this->sendError('Tenant not identified', 400);
            exit;
        }

        if (!$this->isValidTenant($tenantId)) {
            $this->sendError('Invalid or inactive tenant', 403);
            exit;
        }

        // Set tenant context in Database
        Database::getInstance()->setTenantId($tenantId);
        
        // You might also want to set a global constant or put it in a container
        define('CURRENT_TENANT_ID', $tenantId);
    }

    private function resolveTenantId()
    {
        // 1. Check Header
        $headers = [];
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
        }
        
        if (isset($headers['X-Tenant-ID'])) {
            return $headers['X-Tenant-ID'];
        }
        if (isset($headers['x-tenant-id'])) {
            return $headers['x-tenant-id'];
        }

        // 2. Check Subdomain (simplified)
        $host = $_SERVER['HTTP_HOST'] ?? '';
        $parts = explode('.', $host);
        if (count($parts) > 1 && $parts[0] !== 'www' && $parts[0] !== 'api') {
             // In a real app, we would map subdomain to tenant_id
             // For now, let's assume subdomain IS the tenant_id or code
             return $parts[0];
        }

        // 3. Check Query Param (for dev/testing)
        if (isset($_GET['tenant_id'])) {
            return $_GET['tenant_id'];
        }

        // 4. Check URL Path (e.g. /api/workspace/{tenantId}/...)
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $segments = explode('/', trim($path, '/'));
        // /api/workspace/{id} -> [api, workspace, id]
        // /api/hr/{id} -> [api, hr, id]
        // /api/projects/{id} -> [api, projects, id]
        // /api/conversations/{id} -> [api, conversations, id]
        // /api/storage/{id} -> [api, storage, id]
        // /api/notifications/{id} -> [api, notifications, id]
        // /api/audit/{id} -> [api, audit, id]
        if (count($segments) >= 3 && $segments[0] === 'api' && in_array($segments[1], ['workspace', 'hr', 'projects', 'conversations', 'storage', 'notifications', 'audit'])) {
             return $segments[2];
        }

        return null;
    }

    private function isValidTenant($tenantId)
    {
        // In a real application, query the 'tenants' table
        // For bootstrapping, we'll allow a 'default' tenant
        if ($tenantId === 'default' || $tenantId === 'demo') {
            return true;
        }
        
        $db = Database::getInstance();
        try {
            // Assuming a 'tenants' table exists. 
            // Since we haven't run migrations yet, this might fail if we try to query.
            // For Sprint 0 bootstrapping, we will just return true if it looks like a UUID or valid string
            // and maybe log a warning.
            
            // UNCOMMENT when table exists:
            // $stmt = $db->query("SELECT id FROM tenants WHERE id = ? AND is_active = 1", [$tenantId]);
            // return $stmt->fetch() !== false;
            
            return true; 
        } catch (\Exception $e) {
            // Fallback for initial setup
            return true;
        }
    }

    private function sendError($message, $code)
    {
        header('Content-Type: application/json');
        http_response_code($code);
        echo json_encode(['error' => $message]);
    }
}
