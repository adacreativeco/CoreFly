<?php

namespace CoreFly\Services;

use CoreFly\Models\UsageRecord;
use CoreFly\Models\Invoice;
use CoreFly\Models\Tenant;

class BillingService
{
    /**
     * Record a usage event for a tenant
     */
    public function recordUsage(int $tenantId, string $metricKey, float $value = 1.0): void
    {
        $record = new UsageRecord();
        $record->tenant_id = $tenantId;
        $record->metric_key = $metricKey;
        $record->value = $value;
        $record->recorded_at = date('Y-m-d H:i:s');
        $record->save();
    }

    /**
     * Get aggregated usage for a tenant in the current month
     */
    public function getMonthlyUsage(int $tenantId): array
    {
        // This would typically use a SUM query
        // "SELECT metric_key, SUM(value) as total FROM usage_records WHERE ..."
        // For this simple ORM, we might need raw query or fetch all (inefficient but works for MVP)
        
        // Mock implementation for efficiency in MVP
        return [
            'api_calls' => 12500,
            'storage_mb' => 450.5,
            'active_users' => 12
        ];
    }

    /**
     * Generate an invoice for a tenant
     */
    public function generateInvoice(int $tenantId): Invoice
    {
        // 1. Calculate costs based on plan
        $amount = rand(50, 500); // Mock calculation

        // 2. Create Invoice
        $invoice = new Invoice();
        $invoice->tenant_id = $tenantId;
        $invoice->amount = $amount;
        $invoice->status = 'pending';
        $invoice->period_start = date('Y-m-01');
        $invoice->period_end = date('Y-m-t');
        $invoice->save();

        return $invoice;
    }

    /**
     * Get Global Revenue Stats
     */
    public function getRevenueStats(): array
    {
        // Mock data for dashboard
        return [
            'mrr' => 24500,
            'new_revenue' => 2850,
            'overdue_count' => 3,
            'churn_rate' => 1.2
        ];
    }
}
