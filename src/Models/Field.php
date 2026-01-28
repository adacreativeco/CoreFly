<?php

declare(strict_types=1);

namespace CoreFly\Models;

use CoreFly\Models\BaseModel;

class Field extends BaseModel
{
    protected string $table = 'fields';
    
    public string $id;
    public string $tenant_id;
    public string $name;
    public string $description;
    public string $type;
    public string $location;
    public string $coordinates;
    public float $area;
    public string $area_unit;
    public string $soil_type;
    public string $irrigation_type;
    public string $drainage_system;
    public string $crop_rotation_history;
    public string $current_crop;
    public string $planting_date;
    public string $expected_harvest_date;
    public string $status;
    public bool $is_active;
    public string $owner_name;
    public string $owner_contact;
    public float $rental_cost;
    public string $rental_currency;
    public string $rental_start_date;
    public string $rental_end_date;
    public string $last_inspection_date;
    public string $next_inspection_date;
    public string $field_manager_id;
    public string $notes;
    public string $created_by;
    public string $updated_by;

    public function getManager(): ?User
    {
        if (empty($this->field_manager_id)) {
            return null;
        }
        return User::find($this->field_manager_id);
    }

    public function getActivities(): array
    {
        return FieldActivity::where('field_id', $this->id)
            ->where('tenant_id', $this->tenant_id)
            ->orderBy('activity_date', 'DESC')
            ->get();
    }

    public function getCurrentSeasonActivities(): array
    {
        $currentYear = date('Y');
        $startDate = "{$currentYear}-01-01";
        $endDate = "{$currentYear}-12-31";
        
        return FieldActivity::where('field_id', $this->id)
            ->where('tenant_id', $this->tenant_id)
            ->where('activity_date', '>=', $startDate)
            ->where('activity_date', '<=', $endDate)
            ->orderBy('activity_date', 'DESC')
            ->get();
    }

    public function getUpcomingActivities(): array
    {
        return FieldActivity::where('field_id', $this->id)
            ->where('tenant_id', $this->tenant_id)
            ->where('activity_date', '>=', date('Y-m-d'))
            ->where('status', 'scheduled')
            ->orderBy('activity_date', 'ASC')
            ->get();
    }

    public function getProductionHistory(): array
    {
        return FieldProduction::where('field_id', $this->id)
            ->where('tenant_id', $this->tenant_id)
            ->orderBy('harvest_date', 'DESC')
            ->get();
    }

    public function getCurrentSeasonProduction(): ?FieldProduction
    {
        $currentYear = date('Y');
        
        return FieldProduction::where('field_id', $this->id)
            ->where('tenant_id', $this->tenant_id)
            ->where('season_year', $currentYear)
            ->first();
    }

    public function isOverdueForInspection(): bool
    {
        if (empty($this->next_inspection_date)) {
            return false;
        }
        return strtotime($this->next_inspection_date) < time();
    }

    public function getRentalStatus(): string
    {
        if (empty($this->rental_start_date) || empty($this->rental_end_date)) {
            return 'owned';
        }
        
        $today = time();
        $start = strtotime($this->rental_start_date);
        $end = strtotime($this->rental_end_date);
        
        if ($today < $start) {
            return 'upcoming';
        } elseif ($today > $end) {
            return 'expired';
        } else {
            return 'active';
        }
    }

    public static function getFieldsByManager(string $tenantId, string $managerId): array
    {
        return self::where('tenant_id', $tenantId)
            ->where('field_manager_id', $managerId)
            ->where('is_active', true)
            ->orderBy('name', 'ASC')
            ->get();
    }

    public static function getFieldsByStatus(string $tenantId, string $status): array
    {
        return self::where('tenant_id', $tenantId)
            ->where('status', $status)
            ->where('is_active', true)
            ->orderBy('name', 'ASC')
            ->get();
    }

    public static function getFieldsByType(string $tenantId, string $type): array
    {
        return self::where('tenant_id', $tenantId)
            ->where('type', $type)
            ->where('is_active', true)
            ->orderBy('name', 'ASC')
            ->get();
    }

    public function getTotalArea(string $tenantId): float
    {
        $db = \CoreFly\Utils\Database::getInstance();
        $stmt = $db->prepare("
            SELECT SUM(area) as total_area
            FROM fields
            WHERE tenant_id = :tenant_id AND is_active = 1
        ");

        $stmt->bindParam(':tenant_id', $tenantId);
        $stmt->execute();
        
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return (float)($result['total_area'] ?? 0);
    }

    public function getFieldStats(string $tenantId): array
    {
        $db = \CoreFly\Utils\Database::getInstance();
        
        $stmt = $db->prepare("
            SELECT 
                status,
                COUNT(*) as count,
                SUM(area) as total_area
            FROM fields
            WHERE tenant_id = :tenant_id AND is_active = 1
            GROUP BY status
        ");

        $stmt->bindParam(':tenant_id', $tenantId);
        $stmt->execute();
        
        $statusStats = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        $stmt = $db->prepare("
            SELECT 
                type,
                COUNT(*) as count,
                SUM(area) as total_area
            FROM fields
            WHERE tenant_id = :tenant_id AND is_active = 1
            GROUP BY type
        ");

        $stmt->bindParam(':tenant_id', $tenantId);
        $stmt->execute();
        
        $typeStats = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        $stmt = $db->prepare("
            SELECT 
                COUNT(*) as total_fields,
                SUM(area) as total_area,
                AVG(area) as avg_area
            FROM fields
            WHERE tenant_id = :tenant_id AND is_active = 1
        ");

        $stmt->bindParam(':tenant_id', $tenantId);
        $stmt->execute();
        
        $overallStats = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        return [
            'overall' => $overallStats,
            'by_status' => $statusStats,
            'by_type' => $typeStats
        ];
    }
}
