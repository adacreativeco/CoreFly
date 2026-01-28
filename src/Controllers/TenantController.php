<?php

declare(strict_types=1);

namespace CoreFly\Controllers;

use CoreFly\Models\Tenant;
use CoreFly\Utils\Validator;

class TenantController extends BaseController
{
    /**
     * Update current tenant settings
     */
    public function updateSettings()
    {
        // Ensure user is authenticated to populate tenant context
        $this->requireAuth();

        // Get current tenant ID from session/token
        $tenantId = $this->getCurrentTenantId();
        
        if (!$tenantId) {
            return $this->errorResponse('Tenant context not found', 400);
        }

        // Use the existing update logic but force the ID
        return $this->update($tenantId);
    }

    /**
     * List all tenants
     */
    public function index()
    {
        $this->requirePermission('tenants:read');

        $page = (int) ($this->getQueryParam('page', 1));
        $limit = (int) ($this->getQueryParam('limit', 10));
        $search = $this->getQueryParam('search', '');

        $query = Tenant::query();

        if ($search) {
            $query->where('name', 'LIKE', "%$search%")
                  ->orWhere('domain', 'LIKE', "%$search%");
        }

        $total = $query->count();
        $tenants = $query->limit($limit)
                         ->offset(($page - 1) * $limit)
                         ->orderBy('created_at', 'DESC')
                         ->get();

        return $this->successResponse([
            'items' => $tenants,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $limit,
                'total' => $total,
                'last_page' => ceil($total / $limit)
            ]
        ], 'Tenants retrieved successfully');
    }

    /**
     * Create a new tenant
     */
    public function store()
    {
        $this->requirePermission('tenants:create');
        $data = $this->getJsonInput();

        $validator = new Validator($data);
        $validator->required('name', 'domain', 'status');
        $validator->email('email'); // Optional contact email if we had one, but for now just basic fields

        if (!$validator->isValid()) {
            return $this->errorResponse('Validation failed', 422, $validator->getErrors());
        }

        // Check for duplicate domain
        if (Tenant::where('domain', $data['domain'])->exists()) {
            return $this->errorResponse('Domain already exists', 422, ['domain' => 'Domain already exists']);
        }

        $tenant = new Tenant();
        $tenant->id = uniqid('ten_', true);
        $tenant->name = $data['name'];
        $tenant->domain = $data['domain'];
        $tenant->status = $data['status'];
        $tenant->settings = $data['settings'] ?? [];
        $tenant->save();

        return $this->successResponse($tenant, 'Tenant created successfully');
    }

    /**
     * Show a specific tenant
     */
    public function show(string $id)
    {
        $this->requirePermission('tenants:read');

        $tenant = Tenant::find($id);

        if (!$tenant) {
            return $this->errorResponse('Tenant not found', 404);
        }

        return $this->successResponse($tenant, 'Tenant details retrieved');
    }

    /**
     * Update a tenant
     */
    public function update(string $id)
    {
        $currentUserRole = $this->getCurrentUserRole();
        $currentTenantId = $this->getCurrentTenantId();

        // If Super Admin, allow update
        if ($currentUserRole === 'super-admin-role') {
            $this->requirePermission('tenants:update');
        } 
        // If Tenant Admin, allow ONLY if updating OWN tenant
        else if ($currentUserRole === 'tenant-admin-role' && $id === $currentTenantId) {
            // Tenant Admin permission check (maybe a specific one like 'tenant:settings')
            // For now, assume if they are tenant-admin, they can manage their settings.
        } else {
            return $this->errorResponse('Unauthorized', 403);
        }

        $tenant = Tenant::find($id);

        if (!$tenant) {
            return $this->errorResponse('Tenant not found', 404);
        }

        $data = $this->getJsonInput();
        
        // Super Admin can update everything
        if ($currentUserRole === 'super-admin-role') {
            if (isset($data['name'])) $tenant->name = $data['name'];
            if (isset($data['domain'])) {
                if ($data['domain'] !== $tenant->domain && Tenant::where('domain', $data['domain'])->exists()) {
                    return $this->errorResponse('Domain already exists', 422, ['domain' => 'Domain already exists']);
                }
                $tenant->domain = $data['domain'];
            }
            if (isset($data['status'])) $tenant->status = $data['status'];
            if (isset($data['settings']) && is_array($data['settings'])) {
                $currentSettings = $tenant->settings;
                $tenant->settings = array_merge($currentSettings, $data['settings']);
            }
        } 
        // Tenant Admin can ONLY update specific settings
        else {
            // Allowed fields for Tenant Admin
            // They typically update settings like theme, modules (local toggle), notification rules
            if (isset($data['settings']) && is_array($data['settings'])) {
                $currentSettings = $tenant->settings;
                
                // Prevent Tenant Admin from enabling modules not allowed by Super Admin? 
                // The requirement says: "Süper Admin modülleri “platform genelinde” açar. Kurum Admini kendi kurumunda kullanılıp kullanılmayacağını seçer."
                // This implies Super Admin enables it globally (maybe via a license or 'allowed_modules' list in settings), and Tenant Admin toggles 'active_modules'.
                // For simplicity now, let's allow merging settings, but maybe we should blacklist critical settings later.
                
                $tenant->settings = array_merge($currentSettings, $data['settings']);
            }
            // Name/Domain usually locked? Let's allow name update if needed, but maybe keep domain locked.
            if (isset($data['name'])) $tenant->name = $data['name'];
        }

        $tenant->save();

        return $this->successResponse($tenant, 'Tenant updated successfully');
    }

    /**
     * Delete a tenant
     */
    public function destroy(string $id)
    {
        $this->requirePermission('tenants:delete');

        $tenant = Tenant::find($id);

        if (!$tenant) {
            return $this->errorResponse('Tenant not found', 404);
        }

        $tenant->delete();

        return $this->successResponse(null, 'Tenant deleted successfully');
    }
}
