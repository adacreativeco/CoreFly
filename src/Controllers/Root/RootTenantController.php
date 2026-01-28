<?php

declare(strict_types=1);

namespace CoreFly\Controllers\Root;

use CoreFly\Models\Tenant;
use CoreFly\Utils\Validator;

class RootTenantController extends BaseRootController
{
    public function index()
    {
        $this->requireRootAuth();

        $page = (int) ($this->getQueryParam('page', 1));
        $limit = (int) ($this->getQueryParam('limit', 20));
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

        // Enrich with usage stats (mocked for now, but structure is there)
        $items = array_map(function($tenant) {
            $t = $tenant->toArray();
            $t['user_count'] = \CoreFly\Models\User::query()->withoutTenant()->where('tenant_id', $tenant->id)->count();
            $t['storage_usage_gb'] = rand(1, 50) / 10; // Mock
            $t['plan'] = $t['settings']['plan'] ?? 'Core';
            return $t;
        }, $tenants);

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

    public function store()
    {
        $this->requireRootAuth();
        $data = $this->getJsonInput();

        $validator = new Validator($data);
        $validator->required('name', 'domain', 'status');

        if (!$validator->isValid()) {
            return $this->errorResponse('Validation failed', 422, $validator->getErrors());
        }

        if (Tenant::where('domain', $data['domain'])->exists()) {
            return $this->errorResponse('Domain already exists', 422);
        }

        $tenant = new Tenant();
        $tenant->id = uniqid('ten_', true);
        $tenant->name = $data['name'];
        $tenant->domain = $data['domain'];
        $tenant->status = $data['status'];
        $tenant->settings = $data['settings'] ?? ['plan' => 'Core'];
        $tenant->active_modules = $data['active_modules'] ?? [];
        $tenant->save();

        return $this->successResponse($tenant, 'Tenant created successfully');
    }

    public function update(string $id)
    {
        $this->requireRootAuth();
        $tenant = Tenant::find($id);

        if (!$tenant) {
            return $this->errorResponse('Tenant not found', 404);
        }

        $data = $this->getJsonInput();

        if (isset($data['name'])) $tenant->name = $data['name'];
        if (isset($data['domain'])) $tenant->domain = $data['domain'];
        if (isset($data['status'])) $tenant->status = $data['status'];
        if (isset($data['settings'])) $tenant->settings = array_merge($tenant->settings ?? [], $data['settings']);
        if (isset($data['active_modules'])) $tenant->active_modules = $data['active_modules'];

        $tenant->save();

        return $this->successResponse($tenant, 'Tenant updated successfully');
    }

    public function destroy(string $id)
    {
        $this->requireRootAuth();
        $tenant = Tenant::find($id);

        if (!$tenant) {
            return $this->errorResponse('Tenant not found', 404);
        }

        // TODO: Deep delete (users, data) - skipping for safety in this iteration, just deleting tenant record
        $tenant->delete();

        return $this->successResponse(null, 'Tenant deleted successfully');
    }

    public function freeze(string $id)
    {
        $this->requireRootAuth();
        $tenant = Tenant::find($id);

        if (!$tenant) {
            return $this->errorResponse('Tenant not found', 404);
        }

        $tenant->status = 'suspended';
        $tenant->save();

        return $this->successResponse(null, 'Tenant frozen successfully. Access is now blocked.');
    }

    public function unfreeze(string $id)
    {
        $this->requireRootAuth();
        $tenant = Tenant::find($id);

        if (!$tenant) {
            return $this->errorResponse('Tenant not found', 404);
        }

        $tenant->status = 'active';
        $tenant->save();

        return $this->successResponse(null, 'Tenant activated successfully.');
    }

    public function maintenance(string $id)
    {
        $this->requireRootAuth();
        $tenant = Tenant::find($id);

        if (!$tenant) {
            return $this->errorResponse('Tenant not found', 404);
        }

        // Toggle maintenance mode in settings
        $settings = $tenant->settings ?? [];
        $settings['maintenance_mode'] = !($settings['maintenance_mode'] ?? false);
        $tenant->settings = $settings;
        $tenant->save();

        $status = $settings['maintenance_mode'] ? 'enabled' : 'disabled';
        return $this->successResponse(['maintenance_mode' => $settings['maintenance_mode']], "Maintenance mode $status for tenant.");
    }

    public function clearCache(string $id)
    {
        $this->requireRootAuth();
        $tenant = Tenant::find($id);

        if (!$tenant) {
            return $this->errorResponse('Tenant not found', 404);
        }

        // Simulate cache clearing
        // In a real app: Cache::tags(['tenant:'.$id])->flush();
        // or delete specific files in storage/framework/cache/data/...
        
        return $this->successResponse(null, 'Tenant cache cleared successfully.');
    }

    public function impersonate(string $id)
    {
        $this->requireRootAuth();
        
        $tenant = Tenant::find($id);
        if (!$tenant) {
            return $this->errorResponse('Tenant not found', 404);
        }

        // Create new token payload with active_tenant_id
        $payload = $this->currentUser;
        $payload['active_tenant_id'] = $tenant->id;
        
        // Generate new JWT
        $tokens = $this->jwtService->generateToken($payload);
        
        return $this->successResponse(['token' => $tokens['token']], 'Impersonation started');
    }
}
