<?php

declare(strict_types=1);

namespace CoreFly\Controllers\Root;

use CoreFly\Models\Invoice;
use CoreFly\Models\Tenant;
use CoreFly\Services\BillingService;

class RootBillingController extends BaseRootController
{
    public function index()
    {
        $this->requireRootAuth();
        
        $billingService = new BillingService();
        $stats = $billingService->getRevenueStats();

        // Get recent invoices (real data)
        // Note: Joining with tenants table would be ideal, but for MVP we fetch separately or assume name is cached
        $invoices = Invoice::query()
            ->withoutTenant()
            ->orderBy('created_at', 'DESC')
            ->limit(10)
            ->get();
            
        // Decorate with tenant names
        $invoicesData = [];
        foreach ($invoices as $inv) {
            $tenant = Tenant::find($inv->tenant_id);
            $invoicesData[] = [
                'id' => $inv->id,
                'tenant_name' => $tenant ? $tenant->name : 'Unknown Tenant',
                'amount' => $inv->amount,
                'status' => $inv->status,
                'date' => $inv->created_at
            ];
        }

        return $this->successResponse([
            'stats' => $stats,
            'invoices' => $invoicesData
        ]);
    }

    public function generateInvoice($tenantId)
    {
        $this->requireRootAuth();
        
        $billingService = new BillingService();
        $invoice = $billingService->generateInvoice((int)$tenantId);
        
        return $this->successResponse($invoice, 'Invoice generated successfully');
    }
}
