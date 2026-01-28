<?php

declare(strict_types=1);

namespace CoreFly\Middleware;

use CoreFly\Models\Tenant;
use CoreFly\Services\JwtService;
use CoreFly\Utils\Database;

class CheckModuleEnabled
{
    private static ?array $modulesConfig = null;

    public static function handle(string $path): void
    {
        // 1. Load Module Config
        if (self::$modulesConfig === null) {
            self::$modulesConfig = require __DIR__ . '/../../config/modules.php';
        }

        // 2. Identify Module from Path
        $module = self::identifyModule($path);
        if (!$module) {
            return; // Not a module-specific route or core module that is always active
        }

        // 3. Check if Module is Optional/Enterprise
        // If it's a core module (required=true), we don't need to check tenant settings usually,
        // unless we want to support disabling core modules (which the doc says "Standard as active").
        // The doc says: "Her intranet kurulumunda varsayılan olarak aktif olan modüller... Opsiyonel... İsteğe göre aktif/pasif"
        
        // So we only check if it's in 'optional' or 'enterprise' lists.
        $isOptional = isset(self::$modulesConfig['optional'][$module]);
        $isEnterprise = isset(self::$modulesConfig['enterprise'][$module]);

        if (!$isOptional && !$isEnterprise) {
            return; // Core module, always enabled
        }

        // 4. Authenticate to get Tenant
        // Since we are in a middleware-like execution flow in index.php, we might not have the user yet.
        // We need to decode the token here or rely on a previous auth step.
        // For simplicity, we will duplicate a lightweight auth check here just to get the tenant_id.
        $tenantId = self::getTenantIdFromToken();

        if (!$tenantId) {
            // If not authenticated, we can't check tenant settings. 
            // But usually, if the route requires a module, it requires auth.
            // If it's a public route, maybe we skip?
            // Let's assume 401 if no token.
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }

        // 5. Check Tenant Settings
        if (!self::isModuleEnabledForTenant($tenantId, $module)) {
            http_response_code(403);
            echo json_encode([
                'error' => 'module_disabled',
                'message' => 'Bu modül kurumunuz için aktif değildir.'
            ]);
            exit;
        }
    }

    private static function identifyModule(string $path): ?string
    {
        // Simple regex mapping
        // api/hr/... -> hr
        // api/crm/... -> crm
        if (preg_match('#^api/([a-z0-9_]+)#', $path, $matches)) {
            $prefix = $matches[1];
            // Check if this prefix maps to a module key
            // We can iterate over the config to match
            
            // Optional
            if (isset(self::$modulesConfig['optional'][$prefix])) return $prefix;
            // Enterprise
            if (isset(self::$modulesConfig['enterprise'][$prefix])) return $prefix;
            // Core (if we needed to check)
             if (isset(self::$modulesConfig['core'][$prefix])) return $prefix;
        }
        return null;
    }

    private static function getTenantIdFromToken(): ?string
    {
        $token = null;
        $headers = null;
        
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
        }
        
        // Try getallheaders first
        if ($headers && isset($headers['Authorization']) && preg_match('/Bearer\s+(.*)$/i', $headers['Authorization'], $matches)) {
            $token = $matches[1];
        } 
        // Fallback to $_SERVER
        elseif (isset($_SERVER['Authorization']) && preg_match('/Bearer\s+(.*)$/i', $_SERVER['Authorization'], $matches)) {
            $token = $matches[1];
        }
        elseif (isset($_SERVER['HTTP_AUTHORIZATION']) && preg_match('/Bearer\s+(.*)$/i', $_SERVER['HTTP_AUTHORIZATION'], $matches)) {
            $token = $matches[1];
        }

        if (!$token) {
            error_log("CheckModuleEnabled: No token found. Headers: " . print_r($headers, true) . " SERVER: " . print_r($_SERVER, true));
            return null;
        }

        try {
            $jwt = new JwtService();
            $payload = $jwt->validateToken($token);
            // Prioritize active_tenant_id for Super Admin impersonation or context switching
            return $payload['active_tenant_id'] ?? ($payload['tenant_id'] ?? null);
        } catch (\Exception $e) {
            return null;
        }
    }

    private static function isModuleEnabledForTenant(string $tenantId, string $module): bool
    {
        // Fetch tenant active_modules
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT active_modules FROM tenants WHERE id = ?");
        $stmt->execute([$tenantId]);
        $row = $stmt->fetch();

        if (!$row) return false;

        $activeModules = json_decode($row['active_modules'] ?? '[]', true);
        if (!is_array($activeModules)) return false;

        return in_array($module, $activeModules);
    }
}
