<?php

declare(strict_types=1);

namespace CoreFly\Models;

use CoreFly\Models\BaseModel;

class FieldProduction extends BaseModel
{
    protected string $table = 'field_productions';
    
    public string $id;
    public string $tenant_id;
    public string $field_id;
    public string $season_year;
    public string $crop_type;
    public string $variety_name;
    public float $planted_area;
    public string $area_unit;
    public float $seed_quantity;
    public string $seed_unit;
    public string $planting_date;
    public string $expected_harvest_date;
    public string $actual_harvest_date;
    public float $expected_yield;
    public float $actual_yield;
    public string $yield_unit;
    public float $total_revenue;
    public string $currency;
    public float $total_cost;
    public float $net_profit;
    public string $quality_grade;
    public string $market_price;
    public string $notes;
    public string $created_by;
    public string $updated_by;


    public function getField(): ?Field
    {
        return Field::find($this->field_id);
    }

    public function calculateProfit(): void
    {
        $this->net_profit = $this->total_revenue - $this->total_cost;
    }

    public function getYieldPerArea(): float
    {
        if ($this->planted_area <= 0) {
            return 0;
        }
        return $this->actual_yield / $this->planted_area;
    }

    public function getRevenuePerArea(): float
    {
        if ($this->planted_area <= 0) {
            return 0;
        }
        return $this->total_revenue / $this->planted_area;
    }

    public function getCostPerArea(): float
    {
        if ($this->planted_area <= 0) {
            return 0;
        }
        return $this->total_cost / $this->planted_area;
    }

    public function getProfitPerArea(): float
    {
        if ($this->planted_area <= 0) {
            return 0;
        }
        return $this->net_profit / $this->planted_area;
    }

    public function isHarvestOverdue(): bool
    {
        if (empty($this->expected_harvest_date) || !empty($this->actual_harvest_date)) {
            return false;
        }
        return strtotime($this->expected_harvest_date) < time();
    }

    public function getProductionBySeason(string $tenantId, string $seasonYear): array
    {
        return self::where('tenant_id', $tenantId)
            ->where('season_year', $seasonYear)
            ->orderBy('created_at', 'DESC')
            ->get();
    }

    public function getProductionByCrop(string $tenantId, string $cropType): array
    {
        return self::where('tenant_id', $tenantId)
            ->where('crop_type', $cropType)
            ->orderBy('season_year', 'DESC')
            ->get();
    }

    public function getProductionStats(string $tenantId, string $fieldId): array
    {
        $db = \CoreFly\Utils\Database::getInstance();
        
        $stmt = $db->prepare("
            SELECT 
                COUNT(*) as total_seasons,
                SUM(planted_area) as total_planted_area,
                SUM(actual_yield) as total_yield,
                SUM(total_revenue) as total_revenue,
                SUM(total_cost) as total_cost,
                AVG(actual_yield) as avg_yield,
                AVG(total_revenue) as avg_revenue,
                AVG(total_cost) as avg_cost,
                AVG(net_profit) as avg_profit
            FROM field_productions
            WHERE tenant_id = :tenant_id AND field_id = :field_id
        ");

        $stmt->bindParam(':tenant_id', $tenantId);
        $stmt->bindParam(':field_id', $fieldId);
        $stmt->execute();
        
        $stats = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        $stmt = $db->prepare("
            SELECT 
                crop_type,
                COUNT(*) as season_count,
                SUM(actual_yield) as total_yield,
                AVG(actual_yield) as avg_yield,
                AVG(total_revenue) as avg_revenue
            FROM field_productions
            WHERE tenant_id = :tenant_id AND field_id = :field_id
            GROUP BY crop_type
            ORDER BY season_count DESC
        ");

        $stmt->bindParam(':tenant_id', $tenantId);
        $stmt->bindParam(':field_id', $fieldId);
        $stmt->execute();
        
        $cropStats = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        return [
            'overall' => $stats,
            'by_crop' => $cropStats
        ];
    }

    public function getTopProducingFields(string $tenantId, int $limit = 10): array
    {
        $db = \CoreFly\Utils\Database::getInstance();
        $stmt = $db->prepare("
            SELECT 
                f.name as field_name,
                fp.field_id,
                SUM(fp.actual_yield) as total_yield,
                AVG(fp.actual_yield) as avg_yield,
                SUM(fp.net_profit) as total_profit,
                AVG(fp.net_profit) as avg_profit
            FROM field_productions fp
            JOIN fields f ON fp.field_id = f.id
            WHERE fp.tenant_id = :tenant_id AND f.is_active = 1
            GROUP BY fp.field_id, f.name
            ORDER BY total_profit DESC
            LIMIT :limit
        ");

        $stmt->bindParam(':tenant_id', $tenantId);
        $stmt->bindParam(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}