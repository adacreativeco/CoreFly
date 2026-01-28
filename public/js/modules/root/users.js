import { apiCall } from '../../api.js';
import { formatDate, showToast, showConfirm, showModal, closeModal } from '../../utils.js';

let currentPage = 1;
let currentLimit = 20;
let totalPages = 1;
let sortField = 'created_at';
let sortOrder = 'desc';

export async function init() {
    loadTenantsForFilter();
    loadUsers();

    // Event Listeners
    const debouncedLoad = debounce(() => {
        currentPage = 1;
        loadUsers();
    }, 500);

    document.getElementById('user-search')?.addEventListener('input', debouncedLoad);
    document.getElementById('user-role-filter')?.addEventListener('change', () => {
        currentPage = 1;
        loadUsers();
    });
    document.getElementById('user-tenant-filter')?.addEventListener('change', () => {
        currentPage = 1;
        loadUsers();
    });

    document.getElementById('prev-page')?.addEventListener('click', () => changePage(currentPage - 1));
    document.getElementById('next-page')?.addEventListener('click', () => changePage(currentPage + 1));

    // Sort Listeners
    document.querySelectorAll('th[cursor-pointer]').forEach(th => {
        th.addEventListener('click', () => {
            const text = th.textContent.trim().toLowerCase();
            let field = 'created_at';
            if (text.includes('user')) field = 'username';
            else if (text.includes('role')) field = 'role_id';
            else if (text.includes('status')) field = 'status';
            else if (text.includes('joined')) field = 'created_at';
            
            if (sortField === field) {
                sortOrder = sortOrder === 'asc' ? 'desc' : 'asc';
            } else {
                sortField = field;
                sortOrder = 'asc';
            }
            loadUsers();
        });
    });

    // Expose global functions
    window.openModal = showModal;
    window.closeModal = closeModal;
    window.toggleRootUserStatus = toggleStatus;
    window.resetRootUserPassword = resetPassword;
}

async function loadTenantsForFilter() {
    try {
        const res = await apiCall('/root/tenants?limit=100'); // Get first 100 tenants for filter
        if (res && res.data && res.data.items) {
            const select = document.getElementById('user-tenant-filter');
            if (!select) return;
            
            // Keep the first "All Tenants" option
            select.innerHTML = '<option value="">All Tenants</option>' + 
                res.data.items.map(t => `<option value="${t.id}">${t.name}</option>`).join('');
        }
    } catch (error) {
        console.error('Failed to load tenants for filter', error);
    }
}

async function loadUsers() {
    const search = document.getElementById('user-search')?.value || '';
    const tenantId = document.getElementById('user-tenant-filter')?.value || '';
    const role = document.getElementById('user-role-filter')?.value || '';
    const tbody = document.getElementById('users-table-body');
    
    if (tbody) tbody.innerHTML = `
        <tr>
            <td colspan="6" class="px-6 py-4 text-center">
                <div class="flex justify-center items-center">
                    <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-indigo-600"></div>
                    <span class="ml-2 text-gray-500">Loading users...</span>
                </div>
            </td>
        </tr>
    `;

    try {
        const res = await apiCall(`/root/users?page=${currentPage}&limit=${currentLimit}&search=${search}&tenant_id=${tenantId}&role=${role}&sort=${sortField}&order=${sortOrder}`);
        if (res && res.data) {
            renderTable(res.data.items || []);
            updatePagination(res.data.pagination);
        }
    } catch (error) {
        if (tbody) tbody.innerHTML = '<tr><td colspan="6" class="px-6 py-4 text-center text-red-500">Failed to load users</td></tr>';
        console.error(error);
    }
}

function renderTable(users) {
    const tbody = document.getElementById('users-table-body');
    if (!tbody) return;

    if (!users.length) {
        tbody.innerHTML = '<tr><td colspan="6" class="px-6 py-4 text-center text-gray-500">No users found</td></tr>';
        return;
    }

    tbody.innerHTML = users.map(u => `
        <tr class="group hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
            <td class="px-6 py-4">
                <div class="flex items-center">
                    <div class="h-9 w-9 rounded-full bg-gradient-to-r from-blue-500 to-indigo-600 flex items-center justify-center text-white font-bold text-sm shadow-sm">
                        ${u.username.charAt(0).toUpperCase()}
                    </div>
                    <div class="ml-4">
                        <div class="text-sm font-medium text-gray-900 dark:text-white group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">${u.username}</div>
                        <div class="text-xs text-gray-500">${u.email}</div>
                    </div>
                </div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${getRoleBadgeClass(u.role_id)} border border-opacity-20">
                    ${u.role_id || 'user'}
                </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm text-gray-900 dark:text-white">${u.tenant_name || 'N/A'}</div>
                <div class="text-xs text-gray-500 font-mono">ID: ${u.tenant_id ? u.tenant_id.substring(0,8) + '...' : 'N/A'}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <span class="px-2 py-1 text-xs font-medium rounded-full ${u.status === 'active' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400 border-green-200 dark:border-green-800' : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400 border-red-200 dark:border-red-800'} border border-opacity-20">
                    ${u.status || 'active'}
                </span>
            </td>
             <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                ${formatDate(u.created_at)}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                <div class="flex justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                    <button onclick="toggleRootUserStatus('${u.id}')" class="p-2 text-indigo-600 hover:bg-indigo-50 rounded-lg dark:hover:bg-indigo-900/30 transition-colors" title="${u.status === 'active' ? 'Suspend User' : 'Activate User'}">
                        <i class="fas ${u.status === 'active' ? 'fa-ban' : 'fa-check'}"></i>
                    </button>
                    <button onclick="resetRootUserPassword('${u.id}')" class="p-2 text-gray-600 hover:bg-gray-100 rounded-lg dark:text-gray-400 dark:hover:bg-gray-700 transition-colors" title="Reset Password">
                        <i class="fas fa-key"></i>
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
    loadUsers();
}

function getRoleBadgeClass(role) {
    switch (role) {
        case 'super-admin': return 'bg-purple-100 text-purple-800 border-purple-200 dark:bg-purple-900/30 dark:text-purple-400 dark:border-purple-800';
        case 'admin': return 'bg-blue-100 text-blue-800 border-blue-200 dark:bg-blue-900/30 dark:text-blue-400 dark:border-blue-800';
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

async function toggleStatus(id) {
    if (!await showConfirm('Change user status?')) return;
    try {
        await apiCall(`/root/users/${id}/toggle-status`, 'POST');
        showToast('Status updated', 'success');
        loadUsers();
    } catch (error) {
        showToast('Update failed', 'error');
    }
}

async function resetPassword(id) {
    const newPass = prompt('Enter new password for user (leave empty to generate random):');
    if (newPass === null) return; 

    try {
        await apiCall(`/root/users/${id}/reset-password`, 'POST', { password: newPass });
        showToast('Password reset successfully. Check email for details.', 'success');
    } catch (error) {
        showToast('Reset failed', 'error');
    }
}
