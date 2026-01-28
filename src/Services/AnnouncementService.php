<?php

namespace CoreFly\Services;

use CoreFly\Models\Announcement;

class AnnouncementService
{
    /**
     * Get active announcements for a specific tenant context
     */
    public function getActiveAnnouncements(?int $tenantId = null): array
    {
        // Fetch all active announcements that are published and not expired
        // Note: Raw SQL or whereRaw needed for complex JSON querying usually, 
        // but for this MVP we'll fetch active ones and filter in PHP.
        
        $now = date('Y-m-d H:i:s');
        
        // This query assumes BaseModel handles basic where clauses correctly
        $candidates = Announcement::query()
            ->withoutTenant()
            ->where('is_active', 1)
            ->where('published_at', '<=', $now)
            ->get();
            
        $filtered = [];
        
        foreach ($candidates as $announcement) {
            if ($announcement->expires_at && $announcement->expires_at < $now) {
                continue;
            }

            $target = json_decode($announcement->target_audience ?? '{}', true);
            
            // Global announcement if no target specified
            if (empty($target) || (empty($target['tenants']) && empty($target['plans']))) {
                $filtered[] = $announcement;
                continue;
            }

            // Tenant specific check
            if ($tenantId && isset($target['tenants']) && is_array($target['tenants'])) {
                if (in_array($tenantId, $target['tenants'])) {
                    $filtered[] = $announcement;
                }
            }
        }
        
        return $filtered;
    }
}
