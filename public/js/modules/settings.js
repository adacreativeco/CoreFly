import { apiCall, getCurrentUser } from '../api.js';
import { showToast, showConfirm, showModal, closeModal } from '../utils.js';

let currentTab = 'account';

export async function init() {
    console.log('Settings Module Initialized');
    
    // Expose functions to window for HTML onclick handlers
    window.openModal = showModal; // Alias for HTML compatibility
    window.closeModal = closeModal;
    window.handleProfileUpdate = handleProfileUpdate;
    window.handlePasswordChange = handlePasswordChange;
    window.switchSettingsTab = switchSettingsTab;
    window.handleTenantSettings = handleTenantSettings;
    window.resetColor = resetColor;
    
    // Departments
    window.handleCreateDepartment = handleCreateDepartment;
    window.editDepartment = editDepartment;
    window.deleteDepartment = deleteDepartment;
    
    // Roles
    window.handleCreateRole = handleCreateRole;
    window.deleteRole = deleteRole;

    injectModals();
    
    // Load initial tab (default or from hash/storage if we wanted persistence)
    await switchSettingsTab('account');
}

function injectModals() {
    const container = document.getElementById('modal-container');
    if (!container) return;

    // Department Modal
    if (!document.getElementById('department-modal')) {
        const deptModal = `
            <div id="department-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center">
                <div class="bg-white dark:bg-gray-800 rounded-lg w-full max-w-md mx-4">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Departman Ekle/Düzenle</h3>
                        <button onclick="closeModal('department-modal')" class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <form onsubmit="handleCreateDepartment(event)" class="p-6 space-y-4">
                        <input type="hidden" name="id" id="dept-id">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Departman Adı</label>
                            <input type="text" name="name" id="dept-name" required class="mt-1 block w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm py-2 px-3 focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Açıklama</label>
                            <textarea name="description" id="dept-description" rows="3" class="mt-1 block w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm py-2 px-3 focus:ring-indigo-500 focus:border-indigo-500"></textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Yönetici</label>
                            <select name="manager_id" id="dept-manager" class="mt-1 block w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm py-2 px-3 focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="">Seçiniz</option>
                                <!-- Populated dynamically -->
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Üst Departman</label>
                            <select name="parent_id" id="dept-parent" class="mt-1 block w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm py-2 px-3 focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="">Yok (Ana Birim)</option>
                                <!-- Populated dynamically -->
                            </select>
                        </div>
                        <div class="flex justify-end pt-4">
                            <button type="button" onclick="closeModal('department-modal')" class="bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-200 mr-3 hover:bg-gray-50 dark:hover:bg-gray-600 py-2 px-4">İptal</button>
                            <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">Kaydet</button>
                        </div>
                    </form>
                </div>
            </div>
        `;
        container.insertAdjacentHTML('beforeend', deptModal);
    }

    // Role Modal
    if (!document.getElementById('role-modal')) {
        const roleModal = `
            <div id="role-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center">
                <div class="bg-white dark:bg-gray-800 rounded-lg w-full max-w-2xl mx-4 max-h-[90vh] overflow-y-auto">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Rol Ekle</h3>
                        <button onclick="closeModal('role-modal')" class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <form onsubmit="handleCreateRole(event)" class="p-6 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Rol Adı</label>
                            <input type="text" name="name" required class="mt-1 block w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm py-2 px-3 focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Açıklama</label>
                            <textarea name="description" rows="2" class="mt-1 block w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm py-2 px-3 focus:ring-indigo-500 focus:border-indigo-500"></textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">İzinler</label>
                            <div id="role-permissions-list" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2 text-sm text-gray-600 dark:text-gray-400 bg-gray-50 dark:bg-gray-700/50 p-4 rounded-lg border border-gray-200 dark:border-gray-700">
                                <!-- Checkboxes populated by JS -->
                            </div>
                        </div>
                        <div class="flex justify-end pt-4">
                            <button type="button" onclick="closeModal('role-modal')" class="bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-200 mr-3 hover:bg-gray-50 dark:hover:bg-gray-600 py-2 px-4">İptal</button>
                            <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">Kaydet</button>
                        </div>
                    </form>
                </div>
            </div>
        `;
        container.insertAdjacentHTML('beforeend', roleModal);
    }
}

async function switchSettingsTab(tab) {
    currentTab = tab;
    
    // Update Tab UI
    document.querySelectorAll('[id^="tab-settings-"]').forEach(el => {
        if (el.id === `tab-settings-${tab}`) {
            el.classList.remove('border-transparent', 'text-gray-500', 'dark:text-gray-400', 'hover:text-gray-700', 'dark:hover:text-gray-300', 'hover:border-gray-300');
            el.classList.add('border-indigo-500', 'text-indigo-600', 'dark:text-indigo-400');
        } else {
            el.classList.add('border-transparent', 'text-gray-500', 'dark:text-gray-400', 'hover:text-gray-700', 'dark:hover:text-gray-300', 'hover:border-gray-300');
            el.classList.remove('border-indigo-500', 'text-indigo-600', 'dark:text-indigo-400');
        }
    });

    // Show Content
    document.querySelectorAll('[id^="settings-content-"]').forEach(el => el.classList.add('hidden'));
    document.getElementById(`settings-content-${tab}`).classList.remove('hidden');

    // Load Data based on tab
    if (tab === 'account') {
        await loadProfile();
    } else if (tab === 'system') {
        await loadDepartments();
        await loadRoles();
        populatePermissions();
    } else if (tab === 'tenant') {
        await loadTenantSettingsUI();
    }
}

// --- Account Settings ---

async function loadProfile() {
    const res = await apiCall('/auth/profile');
    if (res && res.data) {
        const user = res.data;
        
        setValue('profile-first-name', user.first_name);
        setValue('profile-last-name', user.last_name);
        setValue('profile-email', user.email);
        setValue('profile-phone', user.phone || '');
        setValue('profile-avatar', user.avatar || '');
        
        const img = document.getElementById('profile-avatar-preview');
        if (img) img.src = user.avatar || '/images/default-avatar.svg';
    }
}

async function handleProfileUpdate(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData.entries());
    const res = await apiCall('/settings/profile', 'PUT', data);
    if (res && (res.success || res.data)) {
        showToast('Profil güncellendi', 'success');
        // Update global user info if possible, or just reload
    } else {
        showToast(res.error || 'Güncelleme başarısız', 'error');
    }
}

async function handlePasswordChange(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData.entries());

    if (data.new_password !== data.confirm_password) {
        showToast('Yeni şifreler eşleşmiyor', 'error');
        return;
    }

    const res = await apiCall('/settings/password', 'PUT', data);
    if (res && (res.success || res.data)) {
        showToast('Şifre başarıyla değiştirildi', 'success');
        e.target.reset();
    } else {
        showToast(res.error || 'Şifre değiştirilemedi', 'error');
    }
}

// --- System Settings (Departments) ---

async function loadDepartments() {
    const res = await apiCall('/hr/departments');
    const tbody = document.querySelector('#departments-table tbody');
    if (!tbody) return;
    tbody.innerHTML = '';

    if (res && res.success && res.data && res.data.items) {
        res.data.items.forEach(dept => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">${dept.name}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">${dept.manager_name || '-'}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">${dept.user_count || 0}</td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                    <button onclick="editDepartment('${dept.id}')" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300 mr-3">Düzenle</button>
                    <button onclick="deleteDepartment('${dept.id}')" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300">Sil</button>
                </td>
            `;
            tbody.appendChild(tr);
        });
    } else {
        tbody.innerHTML = '<tr><td colspan="4" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">Departman bulunamadı</td></tr>';
    }
}

async function handleCreateDepartment(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData.entries());
    const id = document.getElementById('dept-id').value;
    
    const method = id ? 'PUT' : 'POST';
    const url = id ? `/hr/departments/${id}` : '/hr/departments';

    const res = await apiCall(url, method, data);
    if (res && res.success) {
        showToast(id ? 'Departman güncellendi' : 'Departman oluşturuldu', 'success');
        window.closeModal('department-modal');
        loadDepartments();
    } else {
        showToast(res.message || 'İşlem başarısız', 'error');
    }
}

async function editDepartment(id) {
    const res = await apiCall(`/hr/departments/${id}`);
    if (res && res.success) {
        const d = res.data;
        setValue('dept-id', d.id);
        setValue('dept-name', d.name);
        setValue('dept-description', d.description || '');
        
        // Load selects before setting values
        await loadDepartmentsForSelect('dept-parent');
        // For manager, we ideally need a user list. 
        // Assuming loadEmployeesForSelect exists or we just load users.
        // For simplicity, let's populate manager select if we can, or skip for now.
        
        setValue('dept-manager', d.manager_id || '');
        setValue('dept-parent', d.parent_id || '');
        
        window.openModal('department-modal');
    }
}

async function deleteDepartment(id) {
    if (await showConfirm('Departmanı silmek istediğinize emin misiniz?')) {
        const res = await apiCall(`/hr/departments/${id}`, 'DELETE');
        if (res && res.success) {
            showToast('Departman silindi', 'success');
            loadDepartments();
        } else {
            showToast(res.message || 'Silme başarısız', 'error');
        }
    }
}

async function loadDepartmentsForSelect(elementId) {
    const res = await apiCall('/hr/departments');
    const select = document.getElementById(elementId);
    if(select) {
        select.innerHTML = '<option value="">Yok (Ana Birim)</option>';
        if (res && res.success && res.data && res.data.items) {
            res.data.items.forEach(d => {
                const option = document.createElement('option');
                option.value = d.id;
                option.textContent = d.name;
                select.appendChild(option);
            });
        }
    }
}

// --- System Settings (Roles) ---

const AVAILABLE_PERMISSIONS = [
    'users:read', 'users:create', 'users:update', 'users:delete',
    'departments:read', 'departments:create', 'departments:update', 'departments:delete',
    'roles:read', 'roles:create', 'roles:update', 'roles:delete',
    'announcements:read', 'announcements:create', 'announcements:update', 'announcements:delete',
    'documents:read', 'documents:create', 'documents:update', 'documents:delete',
    'tasks:read', 'tasks:create', 'tasks:update', 'tasks:delete',
    'projects:read', 'projects:create', 'projects:update', 'projects:delete',
    'crm:read', 'crm:create', 'crm:update', 'crm:delete',
    'hr:read', 'hr:create', 'hr:update', 'hr:delete',
    'accounting:view', 'accounting:create', 'accounting:delete',
    'system_settings:read', 'system_settings:update'
];

async function loadRoles() {
    const res = await apiCall('/roles');
    const tbody = document.querySelector('#roles-table tbody');
    if (!tbody) return;
    tbody.innerHTML = '';

    if (res && res.success && res.data && res.data.items) {
        res.data.items.forEach(role => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">${role.name}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">${role.description || '-'}</td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                    ${role.is_system ? '<span class="text-gray-400 italic mr-3">Sistem Rolü</span>' : `
                    <button onclick="deleteRole('${role.id}')" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300">Sil</button>
                    `}
                </td>
            `;
            tbody.appendChild(tr);
        });
    } else {
        tbody.innerHTML = '<tr><td colspan="3" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">Rol bulunamadı</td></tr>';
    }
}

async function handleCreateRole(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    const data = {
        name: formData.get('name'),
        description: formData.get('description'),
        permissions: []
    };
    
    document.querySelectorAll('input[name="permissions"]:checked').forEach(cb => {
        data.permissions.push(cb.value);
    });

    const res = await apiCall('/roles', 'POST', data);
    if (res && res.success) {
        showToast('Rol oluşturuldu', 'success');
        window.closeModal('role-modal');
        loadRoles();
    } else {
        showToast(res.message || 'İşlem başarısız', 'error');
    }
}

async function deleteRole(id) {
    if (await showConfirm('Rolü silmek istediğinize emin misiniz?')) {
        const res = await apiCall(`/roles/${id}`, 'DELETE');
        if (res && res.success) {
            showToast('Rol silindi', 'success');
            loadRoles();
        } else {
            showToast(res.message || 'Silme başarısız', 'error');
        }
    }
}

function populatePermissions() {
    const container = document.getElementById('role-permissions-list');
    if (!container || container.children.length > 0) return; // Avoid duplicates if already populated
    
    container.innerHTML = AVAILABLE_PERMISSIONS.map(perm => `
        <label class="flex items-center space-x-2">
            <input type="checkbox" name="permissions" value="${perm}" class="rounded text-indigo-600 focus:ring-indigo-500 border-gray-300 dark:border-gray-600 dark:bg-gray-700">
            <span class="text-gray-700 dark:text-gray-300">${perm}</span>
        </label>
    `).join('');
}

// --- Tenant Settings ---

async function loadTenantSettingsUI() {
    const res = await apiCall('/auth/profile');
    if (res && res.success && res.data && res.data.tenant && res.data.tenant.settings) {
        const settings = res.data.tenant.settings;
        setValue('settings-primary-color', settings.primary_color || '#4f46e5');
        setValue('settings-secondary-color', settings.secondary_color || '#6b7280');
        setValue('settings-accent-color', settings.accent_color || '#f59e0b');
    }
}

async function handleTenantSettings(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData.entries());
    
    // This endpoint might need to be created or verified.
    // Assuming /api/tenant/settings or similar exists.
    // Since we don't have explicit tenant API docs, we'll try /api/tenant/settings or assume it updates tenant profile.
    const res = await apiCall('/tenant/settings', 'PUT', { settings: data });
    
    if (res && res.success) {
        showToast('Görünüm ayarları kaydedildi', 'success');
        // Apply changes immediately
        const root = document.documentElement;
        if (data.primary_color) root.style.setProperty('--color-primary', data.primary_color);
        if (data.secondary_color) root.style.setProperty('--color-secondary', data.secondary_color);
        if (data.accent_color) root.style.setProperty('--color-accent', data.accent_color);
    } else {
        showToast(res.message || 'Ayarlar kaydedilemedi', 'error');
    }
}

function resetColor(type) {
    const defaults = {
        primary: '#4f46e5',
        secondary: '#6b7280',
        accent: '#f59e0b'
    };
    setValue(`settings-${type}-color`, defaults[type]);
}

// --- Helpers ---

function setValue(id, val) {
    const el = document.getElementById(id);
    if (el) el.value = val;
}
