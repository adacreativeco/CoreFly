<?php

namespace App\Services;

class AIService
{
    /**
     * Mock AI query processing
     */
    public function ask(string $query): string
    {
        $query = strtolower($query);

        if (str_contains($query, 'error') || str_contains($query, 'fail')) {
            return "Analysis of recent logs indicates a spike in 500 errors from Tenant #3 around 14:00 UTC. Recommended action: Check 'BillingService' integration.";
        }

        if (str_contains($query, 'revenue') || str_contains($query, 'money')) {
            return "Current MRR is $24,500. Projected revenue for next month is $28,000 based on current growth trends.";
        }

        if (str_contains($query, 'tenant') || str_contains($query, 'user')) {
            return "We have 5 active tenants and 120 total users. Tenant #2 'Acme Corp' has the highest usage this week.";
        }

        return "I'm analyzing the platform data... All systems appear operational. CPU usage is nominal (12%).";
    }
}
