<?php

namespace App\Services;

use CoreFly\Models\FeatureFlag;

class FeatureFlagService
{
    /**
     * Evaluate if a feature is enabled for a given context
     * 
     * @param string $key Feature flag key
     * @param array $context ['tenant_id' => ..., 'user_id' => ..., 'email' => ...]
     * @return bool
     */
    public function isEnabled(string $key, array $context = []): bool
    {
        // Using findGlobal since flags are system-wide
        $flag = FeatureFlag::query()->withoutTenant()->where('key', $key)->first();

        if (!$flag) {
            return false;
        }

        if ($flag->is_global) {
            return true;
        }

        $rules = json_decode($flag->rules ?? '{}', true);
        if (!$rules) {
            return (bool)$flag->default_value;
        }

        // 1. Check Tenant Allowlist
        if (isset($rules['allowed_tenants']) && is_array($rules['allowed_tenants'])) {
            if (isset($context['tenant_id']) && in_array($context['tenant_id'], $rules['allowed_tenants'])) {
                return true;
            }
        }

        // 2. Check User Allowlist (Email based)
        if (isset($rules['allowed_emails']) && is_array($rules['allowed_emails'])) {
            if (isset($context['email']) && in_array($context['email'], $rules['allowed_emails'])) {
                return true;
            }
        }

        // 3. Percentage Rollout (Deterministic based on tenant_id or user_id)
        if (isset($rules['percentage_rollout']) && is_numeric($rules['percentage_rollout'])) {
            $percentage = (int)$rules['percentage_rollout'];
            $identifier = $context['tenant_id'] ?? $context['user_id'] ?? null;
            
            if ($identifier) {
                // Simple hash-based bucketing
                $hash = crc32($key . $identifier);
                $bucket = $hash % 100; // 0-99
                if ($bucket < $percentage) {
                    return true;
                }
            }
        }

        return (bool)$flag->default_value;
    }
}
