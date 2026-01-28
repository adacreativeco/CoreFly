import { apiCall } from '../../api.js';
import { formatDate, showConfirm, showToast } from '../../utils.js';

let currentPage = 1;
let currentLimit = 50;
let totalPages = 1;

export async function init() {
    loadTenantsForFilter();
    loadLogs();

    const debouncedLoad = debounce(() => {
        currentPage = 1;
        loadLogs();
    }, 500);

    document.getElementById('log-search')?.addEventListener('input', debouncedLoad);
    document.getElementById('log-tenant-filter')?.addEventListener('change', () => {
        currentPage = 1;
        loadLogs();
    });
    document.getElementById('log-level-filter')?.addEventListener('change', () => {
        currentPage = 1;
        loadLogs();
    });

    document.getElementById('prev-page')?.addEventListener('click', () => changePage(currentPage - 1));
    document.getElementById('next-page')?.addEventListener('click', () => changePage(currentPage + 1));

    window.loadLogs = () => {
        currentPage = 1;
        loadLogs();
    };
    window.clearLogs = clearLogs;
}

async function loadTenantsForFilter() {
    try {
        const res = await apiCall('/root/tenants?limit=100');
        if (res && res.data && res.data.items) {
            const select = document.getElementById('log-tenant-filter');
            if (!select) return;
            select.innerHTML = '<option value="">All Tenants</option>' + 
                res.data.items.map(t => `<option value="${t.id}">${t.name}</option>`).join('');
        }
    } catch (error) {
        console.error('Failed to load tenants for filter', error);
    }
}

async function loadLogs() {
    const search = document.getElementById('log-search')?.value || '';
    const tenantId = document.getElementById('log-tenant-filter')?.value || '';
    const level = document.getElementById('log-level-filter')?.value || '';
    const tbody = document.getElementById('logs-table-body');
    
    if (tbody) tbody.innerHTML = '<tr><td colspan="6" class="px-6 py-4 text-center">Loading...</td></tr>';

    try {
        // Construct query params
        const params = new URLSearchParams({
            page: currentPage,
            limit: currentLimit,
            search,
            tenant_id: tenantId,
            level
        });

        const res = await apiCall(`/root/logs?${params.toString()}`);
        if (res && res.data) {
            renderTable(res.data.items || []);
            updatePagination(res.data.pagination);
        }
    } catch (error) {
        if (tbody) tbody.innerHTML = '<tr><td colspan="6" class="px-6 py-4 text-center text-red-500">Failed to load logs</td></tr>';
        console.error(error);
    }
}

function renderTable(logs) {
    const tbody = document.getElementById('logs-table-body');
    if (!tbody) return;

    if (!logs.length) {
        tbody.innerHTML = '<tr><td colspan="6" class="px-6 py-4 text-center text-gray-500">No logs found</td></tr>';
        return;
    }

    tbody.innerHTML = logs.map(l => `
        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
            <td class="px-6 py-4 whitespace-nowrap">
                <span class="px-2 py-1 text-xs font-medium rounded-full ${getLevelBadgeClass(l.level || 'info')}">
                    ${(l.level || 'INFO').toUpperCase()}
                </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-gray-900 dark:text-white">${l.tenant_name || 'System'}</div>
                <div class="text-xs text-gray-500 font-mono">${l.tenant_id ? l.tenant_id.substring(0,8) + '...' : '-'}</div>
            </td>
            <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                <div class="font-medium text-gray-900 dark:text-white">${l.action || l.message}</div>
                <div class="text-xs mt-1 truncate max-w-md">${l.details || l.new_values ? JSON.stringify(l.new_values || l.details) : ''}</div>
            </td>
             <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                ${l.user_name || 'System'}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-500 dark:text-gray-400 font-mono">
                ${formatDate(l.created_at)}
            </td>
        </tr>
    `).join('');
}

function updatePagination(pagination) {
    if (!pagination) return;
    
    totalPages = pagination.last_page;
    currentPage = pagination.current_page;
    
    const start = ((currentPage - 1) * pagination.per_page) + 1;
    const end = Math.min(currentPage * pagination.per_page, pagination.total);
    
    document.getElementById('page-start').textContent = start;
    document.getElementById('page-end').textContent = end;
    document.getElementById('total-items').textContent = pagination.total;
    
    document.getElementById('prev-page').disabled = currentPage <= 1;
    document.getElementById('next-page').disabled = currentPage >= totalPages;
}

function changePage(newPage) {
    if (newPage < 1 || newPage > totalPages) return;
    currentPage = newPage;
    loadLogs();
}

function getLevelBadgeClass(level) {
    level = level.toLowerCase();
    switch (level) {
        case 'error': return 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400';
        case 'critical': return 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400 ring-1 ring-red-500';
        case 'warning': return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400';
        case 'info': return 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400';
        default: return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
    }
}

function debounce(func, wait) {
    let timeout;
    return function(...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(this, args), wait);
    };
}

async function clearLogs() {
    if (!await showConfirm('Are you sure you want to clear all logs? This action cannot be undone.')) return;
    try {
        await apiCall('/root/logs', 'DELETE');
        showToast('Logs cleared successfully', 'success');
        loadLogs();
    } catch (error) {
        showToast('Failed to clear logs', 'error');
    }
}
