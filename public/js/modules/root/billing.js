import { apiCall } from '../../api.js';
import { formatDate, showToast } from '../../utils.js';

export async function init() {
    window.exportBillingReport = exportBillingReport;
    window.generateMockInvoice = generateMockInvoice;
    
    try {
        const res = await apiCall('/root/billing');
        if (res && res.data) {
            renderStats(res.data.stats);
            renderInvoices(res.data.invoices || []);
        }
    } catch (error) {
        console.error('Failed to load billing data', error);
    }
}

function renderStats(stats) {
    if (!stats) return;
    document.getElementById('mrr-stat').textContent = stats.mrr ? `$${stats.mrr.toLocaleString()}` : '$0';
    document.getElementById('revenue-stat').textContent = stats.new_revenue ? `$${stats.new_revenue.toLocaleString()}` : '$0';
    document.getElementById('overdue-stat').textContent = stats.overdue_count || '0';
    
    // Add churn rate if element exists
    const churnEl = document.getElementById('churn-stat');
    if (churnEl && stats.churn_rate) churnEl.textContent = stats.churn_rate + '%';
}

async function generateMockInvoice() {
    const tenantId = prompt('Enter Tenant ID to generate invoice for:');
    if (!tenantId) return;

    try {
        await apiCall(`/root/billing/invoices/generate`, 'POST', { tenant_id: tenantId });
        showToast('Invoice generated', 'success');
        // Reload
        const res = await apiCall('/root/billing');
        if (res.data) renderInvoices(res.data.invoices);
    } catch (e) {
        showToast('Failed to generate', 'error');
    }
}

function renderInvoices(invoices) {
    const tbody = document.getElementById('billing-table-body');
    if (!tbody) return;

    if (!invoices.length) {
        tbody.innerHTML = '<tr><td colspan="6" class="px-6 py-4 text-center text-gray-500">No invoices found</td></tr>';
        return;
    }

    tbody.innerHTML = invoices.map(i => `
        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
            <td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-gray-500 dark:text-gray-400">
                ${i.id}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                ${i.tenant_name}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                $${i.amount}
            </td>
             <td class="px-6 py-4 whitespace-nowrap">
                <span class="px-2 py-1 text-xs font-medium rounded-full ${getStatusColor(i.status)}">
                    ${i.status}
                </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                ${formatDate(i.date)}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                <button class="text-indigo-600 hover:text-indigo-900 dark:hover:text-indigo-400" title="Download Invoice">
                    <i class="fas fa-download"></i>
                </button>
            </td>
        </tr>
    `).join('');
}

function getStatusColor(status) {
    switch (status) {
        case 'paid': return 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400';
        case 'pending': return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400';
        case 'overdue': return 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400';
        default: return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
    }
}

async function exportBillingReport() {
    showToast('Preparing report...', 'info');
    // Mock export
    setTimeout(() => {
        showToast('Report downloaded successfully', 'success');
    }, 1500);
}
