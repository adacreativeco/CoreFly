<?php

declare(strict_types=1);

namespace CoreFly\Controllers\Root;

use CoreFly\Models\FeatureFlag;

class RootFeatureFlagController extends BaseRootController
{
    public function index()
    {
        $this->requireRootAuth();
        $flags = FeatureFlag::query()->withoutTenant()->orderBy('created_at', 'DESC')->get();
        return $this->successResponse(['items' => $flags]);
    }

    public function store()
    {
        $this->requireRootAuth();
        $data = $this->getJsonInput();

        if (empty($data['key'])) {
            return $this->errorResponse('Key is required', 400);
        }

        $existing = FeatureFlag::query()->withoutTenant()->where('key', $data['key'])->first();
        if ($existing) {
            return $this->errorResponse('Feature flag key already exists', 409);
        }

        $flag = new FeatureFlag();
        $flag->key = $data['key'];
        $flag->description = $data['description'] ?? '';
        $flag->is_global = (int)($data['is_global'] ?? 0);
        $flag->default_value = (int)($data['default_value'] ?? 0);
        $flag->rules = isset($data['rules']) ? json_encode($data['rules']) : null;
        $flag->save();

        return $this->successResponse($flag, 'Feature flag created');
    }

    public function update($id)
    {
        $this->requireRootAuth();
        $flag = FeatureFlag::query()->withoutTenant()->find($id);

        if (!$flag) {
            return $this->errorResponse('Feature flag not found', 404);
        }

        $data = $this->getJsonInput();
        
        if (isset($data['description'])) $flag->description = $data['description'];
        if (isset($data['is_global'])) $flag->is_global = (int)$data['is_global'];
        if (isset($data['default_value'])) $flag->default_value = (int)$data['default_value'];
        if (isset($data['rules'])) $flag->rules = json_encode($data['rules']);

        $flag->save();

        return $this->successResponse($flag, 'Feature flag updated');
    }

    public function destroy($id)
    {
        $this->requireRootAuth();
        $flag = FeatureFlag::query()->withoutTenant()->find($id);

        if (!$flag) {
            return $this->errorResponse('Feature flag not found', 404);
        }

        $flag->delete();
        return $this->successResponse(null, 'Feature flag deleted');
    }
}
