import { apiCall } from '../api.js';
import { formatDate, showToast, showConfirm, showModal, closeModal } from '../utils.js';

let tenants = [];

export async function init() {
    console.log('Tenant Management Module Initialized');
    
    // Bind global functions
    window.editTenant = editTenant;
    window.deleteTenant = deleteTenant;
    window.impersonateTenant = impersonateTenant;

    // Load Data
    await loadTenants();

    // Event Listeners
    document.getElementById('tenant-form').addEventListener('submit', handleTenantSubmit);
    document.getElementById('tenant-search').addEventListener('input', filterTenants);
}

async function loadTenants() {
    const table = document.getElementById('tenants-table');
    
    try {
        const res = await apiCall('/tenants');
        if (res && res.data) {
            tenants = res.data.items || res.data;
            renderTenants(tenants);
        }
    } catch (error) {
        console.error('Failed to load tenants:', error);
        table.innerHTML = '<tr><td colspan="5" class="px-6 py-4 text-center text-red-500">Kiracılar yüklenirken hata oluştu.</td></tr>';
    }
}

function renderTenants(items) {
    const table = document.getElementById('tenants-table');
    
    if (!items || items.length === 0) {
        table.innerHTML = '<tr><td colspan="5" class="px-6 py-4 text-center text-gray-500">Kayıtlı kiracı yok.</td></tr>';
        return;
    }

    table.innerHTML = items.map(tenant => `
        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
            <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center">
                    <div class="h-10 w-10 flex-shrink-0 bg-gray-200 dark:bg-gray-700 rounded-lg flex items-center justify-center text-gray-500">
                        ${tenant.logo ? `<img src="${tenant.logo}" class="h-10 w-10 rounded-lg object-cover">` : '<i class="fas fa-building"></i>'}
                    </div>
                    <div class="ml-4">
                        <div class="text-sm font-medium text-gray-900 dark:text-white">${tenant.name}</div>
                        <div class="text-xs text-gray-500">ID: ${tenant.id}</div>
                    </div>
                </div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                <a href="https://${tenant.domain}" target="_blank" class="hover:text-indigo-600">${tenant.domain}</a>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <span class="px-2 py-1 text-xs rounded-full ${getStatusColor(tenant.status)}">
                    ${getStatusLabel(tenant.status)}
                </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                ${formatDate(tenant.created_at)}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                <button onclick="impersonateTenant(${tenant.id})" class="text-gray-500 hover:text-indigo-600 mr-3" title="Yönetici Olarak Giriş Yap">
                    <i class="fas fa-user-secret"></i>
                </button>
                <button onclick="editTenant(${tenant.id})" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 mr-3">
                    <i class="fas fa-edit"></i>
                </button>
                <button onclick="deleteTenant(${tenant.id})" class="text-red-600 hover:text-red-900 dark:text-red-400">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        </tr>
    `).join('');
}

function filterTenants() {
    const search = document.getElementById('tenant-search').value.toLowerCase();
    
    const filtered = tenants.filter(t => 
        t.name.toLowerCase().includes(search) || 
        t.domain.toLowerCase().includes(search)
    );
    
    renderTenants(filtered);
}

async function handleTenantSubmit(e) {
    e.preventDefault();
    
    const id = document.getElementById('tenant-id').value;
    
    // Collect selected modules
    const modules = [];
    document.querySelectorAll('input[name="modules"]:checked').forEach(cb => {
        modules.push(cb.value);
    });

    const data = {
        name: document.getElementById('tenant-name').value,
        domain: document.getElementById('tenant-domain').value,
        status: document.getElementById('tenant-status').value,
        admin_email: document.getElementById('tenant-admin-email').value,
        active_modules: JSON.stringify(modules)
    };

    const password = document.getElementById('tenant-admin-password').value;
    if (password) data.admin_password = password;

    try {
        const url = id ? `/tenants/${id}` : '/tenants';
        const method = id ? 'PUT' : 'POST';
        
        await apiCall(url, method, data);
        showToast(id ? 'Kiracı güncellendi' : 'Kiracı oluşturuldu', 'success');
        closeModal('tenant-modal');
        await loadTenants();
    } catch (error) {
        showToast('İşlem başarısız', 'error');
        console.error(error);
    }
}

async function editTenant(id) {
    const tenant = tenants.find(t => t.id === id);
    if (!tenant) return;

    document.getElementById('tenant-id').value = tenant.id;
    document.getElementById('tenant-name').value = tenant.name;
    document.getElementById('tenant-domain').value = tenant.domain;
    document.getElementById('tenant-status').value = tenant.status;
    
    // Reset admin fields (security)
    document.getElementById('tenant-admin-email').value = ''; 
    document.getElementById('tenant-admin-password').value = '';

    // Check modules
    const activeModules = tenant.active_modules ? JSON.parse(tenant.active_modules) : [];
    document.querySelectorAll('input[name="modules"]').forEach(cb => {
        cb.checked = activeModules.includes(cb.value);
    });
    
    showModal('tenant-modal');
}

async function deleteTenant(id) {
    if (!await showConfirm('Bu kiracıyı ve tüm verilerini silmek istediğinize emin misiniz? BU İŞLEM GERİ ALINAMAZ!')) return;

    try {
        await apiCall(`/tenants/${id}`, 'DELETE');
        showToast('Kiracı silindi', 'success');
        await loadTenants();
    } catch (error) {
        showToast('Silme işlemi başarısız', 'error');
    }
}

async function impersonateTenant(id) {
    if (!await showConfirm('Bu kiracının paneline geçiş yapmak istiyor musunuz?')) return;
    
    try {
        const res = await apiCall('/admin/switch-tenant', 'POST', { tenant_id: id });
        if (res && res.data && res.data.token) {
            localStorage.setItem('token', res.data.token);
            showToast('Kiracı paneline geçiş yapıldı', 'success');
            setTimeout(() => {
                window.location.href = '#dashboard';
                window.location.reload();
            }, 500);
        }
    } catch (error) {
        showToast('Geçiş yapılamadı: ' + (error.message || 'Bilinmeyen hata'), 'error');
    }
}

function getStatusColor(status) {
    switch(status) {
        case 'active': return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300';
        case 'inactive': return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
        case 'suspended': return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300';
        default: return 'bg-gray-100 text-gray-800';
    }
}

function getStatusLabel(status) {
    const labels = {
        'active': 'Aktif',
        'inactive': 'Pasif',
        'suspended': 'Askıya Alındı'
    };
    return labels[status] || status;
}


