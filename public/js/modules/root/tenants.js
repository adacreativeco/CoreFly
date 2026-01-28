import { apiCall } from '../../api.js';
import { formatDate, showToast, showModal, closeModal, showConfirm } from '../../utils.js';

let currentPage = 1;
let currentLimit = 10;
let totalPages = 1;
let tenants = [];
let sortField = 'created_at';
let sortOrder = 'desc';

export async function init() {
    loadTenants();

    // Event Listeners
    document.getElementById('tenant-search')?.addEventListener('input', debounce(() => {
        currentPage = 1;
        loadTenants();
    }, 500));
    
    document.getElementById('tenant-status-filter')?.addEventListener('change', () => {
        currentPage = 1;
        loadTenants();
    });

    document.getElementById('create-tenant-form')?.addEventListener('submit', (e) => e.preventDefault());
    
    document.getElementById('prev-page')?.addEventListener('click', () => changePage(currentPage - 1));
    document.getElementById('next-page')?.addEventListener('click', () => changePage(currentPage + 1));

    // Sort Listeners
    document.querySelectorAll('th[cursor-pointer]').forEach(th => {
        th.addEventListener('click', () => {
            const text = th.textContent.trim().toLowerCase();
            let field = 'created_at';
            if (text.includes('tenant')) field = 'name';
            else if (text.includes('domain')) field = 'domain';
            else if (text.includes('status')) field = 'status';
            else if (text.includes('created')) field = 'created_at';
            
            if (sortField === field) {
                sortOrder = sortOrder === 'asc' ? 'desc' : 'asc';
            } else {
                sortField = field;
                sortOrder = 'asc';
            }
            loadTenants();
        });
    });

    // Expose global functions
    window.openModal = showModal;
    window.closeModal = closeModal;
    window.submitCreateTenant = submitCreateTenant;
    window.rootImpersonate = impersonateTenant;
    window.deleteRootTenant = deleteTenant;
    
    // New Ops functions
    window.freezeTenant = freezeTenant;
    window.unfreezeTenant = unfreezeTenant;
    window.toggleMaintenance = toggleMaintenance;
    window.clearTenantCache = clearTenantCache;
}

async function loadTenants() {
    const search = document.getElementById('tenant-search')?.value || '';
    const status = document.getElementById('tenant-status-filter')?.value || '';
    const tbody = document.getElementById('tenants-table-body');
    
    if (tbody) tbody.innerHTML = `
        <tr>
            <td colspan="6" class="px-6 py-4 text-center">
                <div class="flex justify-center items-center">
                    <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-indigo-600"></div>
                    <span class="ml-2 text-gray-500">Loading tenants...</span>
                </div>
            </td>
        </tr>
    `;

    try {
        const res = await apiCall(`/root/tenants?page=${currentPage}&limit=${currentLimit}&search=${search}&status=${status}&sort=${sortField}&order=${sortOrder}`);
        if (res && res.data) {
            tenants = res.data.items || [];
            renderTable(tenants);
            updatePagination(res.data.pagination);
        }
    } catch (error) {
        if (tbody) tbody.innerHTML = '<tr><td colspan="6" class="px-6 py-4 text-center text-red-500">Failed to load tenants</td></tr>';
        console.error(error);
    }
}

function renderTable(items) {
    const tbody = document.getElementById('tenants-table-body');
    if (!tbody) return;

    if (!items.length) {
        tbody.innerHTML = '<tr><td colspan="6" class="px-6 py-4 text-center text-gray-500">No tenants found</td></tr>';
        return;
    }

    tbody.innerHTML = items.map(t => `
        <tr class="group hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
            <td class="px-6 py-4">
                <div class="flex items-center">
                    <div class="h-10 w-10 rounded-lg bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white font-bold text-lg shadow-sm relative">
                        ${t.name.charAt(0).toUpperCase()}
                        ${t.status === 'suspended' ? '<div class="absolute -top-1 -right-1 w-3 h-3 bg-red-500 rounded-full border-2 border-white"></div>' : ''}
                    </div>
                    <div class="ml-4">
                        <div class="text-sm font-medium text-gray-900 dark:text-white group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">${t.name}</div>
                        <div class="text-xs text-gray-500">ID: ${t.id}</div>
                    </div>
                </div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 font-mono">
                ${t.domain}.corfly.com
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <span class="px-2.5 py-0.5 text-xs font-medium rounded-full ${getStatusColor(t.status)} border border-opacity-20">
                    ${t.status}
                </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                <div class="flex items-center">
                    <i class="fas fa-database mr-2 text-gray-400"></i>
                    ${t.db_name || 'Shared'}
                </div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                ${formatDate(t.created_at)}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                <div class="flex justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                    <!-- Ops Actions -->
                    ${t.status === 'suspended' 
                        ? `<button onclick="unfreezeTenant('${t.id}')" class="p-2 text-green-600 hover:bg-green-50 rounded-lg dark:hover:bg-green-900/30 transition-colors" title="Unfreeze Tenant"><i class="fas fa-play"></i></button>`
                        : `<button onclick="freezeTenant('${t.id}')" class="p-2 text-orange-600 hover:bg-orange-50 rounded-lg dark:hover:bg-orange-900/30 transition-colors" title="Freeze Tenant"><i class="fas fa-pause"></i></button>`
                    }
                    <button onclick="clearTenantCache('${t.id}')" class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg dark:hover:bg-blue-900/30 transition-colors" title="Clear Cache">
                        <i class="fas fa-broom"></i>
                    </button>
                    <button onclick="rootImpersonate('${t.id}')" class="p-2 text-indigo-600 hover:bg-indigo-50 rounded-lg dark:hover:bg-indigo-900/30 transition-colors" title="Log in as Admin">
                        <i class="fas fa-sign-in-alt"></i>
                    </button>
                    <button onclick="deleteRootTenant('${t.id}')" class="p-2 text-red-600 hover:bg-red-50 rounded-lg dark:hover:bg-red-900/30 transition-colors" title="Delete">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
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
    loadTenants();
}

function getStatusColor(status) {
    switch (status) {
        case 'active': return 'bg-green-100 text-green-800 border-green-200 dark:bg-green-900/30 dark:text-green-400 dark:border-green-800';
        case 'suspended': return 'bg-red-100 text-red-800 border-red-200 dark:bg-red-900/30 dark:text-red-400 dark:border-red-800';
        case 'pending': return 'bg-yellow-100 text-yellow-800 border-yellow-200 dark:bg-yellow-900/30 dark:text-yellow-400 dark:border-yellow-800';
        default: return 'bg-gray-100 text-gray-800 border-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600';
    }
}

function debounce(func, wait) {
    let timeout;
    return function(...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(this, args), wait);
    };
}

async function submitCreateTenant() {
    const form = document.getElementById('create-tenant-form');
    const formData = new FormData(form);
    const data = Object.fromEntries(formData.entries());
    
    // Basic validation
    if (!data.name || !data.domain || !data.email) {
        showToast('Please fill all fields', 'error');
        return;
    }

    const btn = form.closest('.bg-white').querySelector('button[onclick="submitCreateTenant()"]');
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Creating...';

    try {
        await apiCall('/root/tenants', 'POST', data);
        showToast('Tenant created successfully', 'success');
        closeModal('create-tenant-modal');
        form.reset();
        loadTenants();
    } catch (error) {
        console.error(error);
    } finally {
        btn.disabled = false;
        btn.innerHTML = originalText;
    }
}

async function deleteTenant(id) {
    if (!await showConfirm('Are you sure you want to delete this tenant? This action cannot be undone.')) return;
    try {
        await apiCall(`/root/tenants/${id}`, 'DELETE');
        showToast('Tenant deleted', 'success');
        loadTenants();
    } catch (error) {
        console.error(error);
    }
}

async function impersonateTenant(id) {
    if (!await showConfirm('Switch context to this tenant?')) return;
    try {
        const res = await apiCall(`/root/tenants/${id}/impersonate`, 'POST');
        if (res && res.data && res.data.token) {
            localStorage.setItem('root_token_backup', localStorage.getItem('token'));
            localStorage.setItem('token', res.data.token);
            window.location.href = '/'; 
        }
    } catch (error) {
        showToast('Switch failed', 'error');
    }
}

// --- New Ops Actions ---

async function freezeTenant(id) {
    if (!await showConfirm('Are you sure you want to freeze this tenant? All access will be blocked immediately.')) return;
    try {
        await apiCall(`/root/tenants/${id}/freeze`, 'POST');
        showToast('Tenant frozen successfully', 'success');
        loadTenants();
    } catch (error) {
        showToast('Action failed', 'error');
    }
}

async function unfreezeTenant(id) {
    if (!await showConfirm('Re-activate this tenant?')) return;
    try {
        await apiCall(`/root/tenants/${id}/unfreeze`, 'POST');
        showToast('Tenant reactivated successfully', 'success');
        loadTenants();
    } catch (error) {
        showToast('Action failed', 'error');
    }
}

async function clearTenantCache(id) {
    if (!await showConfirm('Flush cache for this tenant?')) return;
    try {
        await apiCall(`/root/tenants/${id}/clear-cache`, 'POST');
        showToast('Cache cleared successfully', 'success');
    } catch (error) {
        showToast('Action failed', 'error');
    }
}

async function toggleMaintenance(id) {
    try {
        const res = await apiCall(`/root/tenants/${id}/maintenance`, 'POST');
        showToast(res.message || 'Maintenance mode updated', 'success');
        // Ideally reload or update UI state locally
    } catch (error) {
        showToast('Action failed', 'error');
    }
}
