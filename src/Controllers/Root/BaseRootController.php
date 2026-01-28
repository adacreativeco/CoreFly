<?php

declare(strict_types=1);

namespace CoreFly\Controllers\Root;

use CoreFly\Controllers\BaseController;

abstract class BaseRootController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
    }

    protected function requireRootAuth(): void
    {
        $this->requireAuth();

        if ($this->getCurrentUserRole() !== 'super-admin-role') {
            http_response_code(403);
            echo $this->errorResponse('Access Denied: Root privileges required.', 403);
            exit;
        }

        // CRITICAL: Ensure we are in "Global Mode"
        // We do this by ensuring the database context is NOT restricted to a tenant
        // However, standard BaseModel applies tenant_id filter if set in the model instance (which usually comes from DB context)
        // BUT, our BaseModel::query() checks for tenantId property on the model instance.
        // The Database class has currentTenant property.
        
        // For Root operations, we might want to temporarily clear the tenant context in the DB class
        // or ensure our queries explicitly use withoutTenant().
        // The safest way is to ensure all Root controllers use models with withoutTenant().
    }
}
