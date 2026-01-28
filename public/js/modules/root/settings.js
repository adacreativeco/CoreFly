import { apiCall } from '../../api.js';
import { showToast, showModal, closeModal, showConfirm, formatDate } from '../../utils.js';

export async function init() {
    // Tab Switching Logic
    window.switchTab = (tabName) => {
        // Update sidebar links
        document.querySelectorAll('nav a').forEach(link => {
            link.classList.remove('bg-indigo-50', 'text-indigo-600', 'dark:bg-indigo-900/20', 'dark:text-indigo-400', 'translate-x-1');
            link.classList.add('text-gray-600', 'dark:text-gray-400', 'hover:bg-gray-50', 'dark:hover:bg-gray-700');
        });
        
        const activeLink = document.getElementById(`tab-link-${tabName}`);
        if (activeLink) {
            activeLink.classList.remove('text-gray-600', 'dark:text-gray-400', 'hover:bg-gray-50', 'dark:hover:bg-gray-700');
            activeLink.classList.add('bg-indigo-50', 'text-indigo-600', 'dark:bg-indigo-900/20', 'dark:text-indigo-400', 'translate-x-1');
        }

        // Show/Hide Content with transition
        document.querySelectorAll('[id^="tab-content-"]').forEach(content => {
            content.classList.add('hidden', 'opacity-0', 'translate-y-2');
            content.classList.remove('opacity-100', 'translate-y-0');
        });
        
        const targetContent = document.getElementById(`tab-content-${tabName}`);
        targetContent.classList.remove('hidden');
        
        // Slight delay to allow display:block to apply before opacity transition
        requestAnimationFrame(() => {
            targetContent.classList.remove('opacity-0', 'translate-y-2');
            targetContent.classList.add('opacity-100', 'translate-y-0', 'transition-all', 'duration-300');
        });
        
        // Load content if needed
        if (tabName === 'feature-flags') loadFeatureFlags();
        if (tabName === 'announcements') loadAnnouncements();
    };

    window.saveSettings = saveSettings;
    window.openModal = showModal;
    window.closeModal = closeModal;
    
    // Feature Flag Actions
    window.submitCreateFlag = submitCreateFlag;
    window.deleteFeatureFlag = deleteFeatureFlag;
    
    // Announcement Actions
    window.submitCreateAnnouncement = submitCreateAnnouncement;
    window.deleteAnnouncement = deleteAnnouncement;

    loadSettings();
}

async function loadSettings() {
    try {
        const res = await apiCall('/root/settings');
        if (res && res.data) {
            console.log('Settings loaded:', res.data);
        }
    } catch (error) {
        console.error('Failed to load settings', error);
    }
}

async function saveSettings() {
    const btn = document.querySelector('button[onclick="saveSettings()"]');
    const originalContent = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Saving...';

    try {
        await new Promise(resolve => setTimeout(resolve, 800)); // Mock network delay
        showToast('Settings saved successfully', 'success');
    } catch (error) {
        showToast('Failed to save settings', 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = originalContent;
    }
}

// --- Feature Flags ---

async function loadFeatureFlags() {
    const tbody = document.getElementById('feature-flags-table-body');
    tbody.innerHTML = '<tr><td colspan="4" class="px-6 py-4 text-center">Loading...</td></tr>';
    
    try {
        const res = await apiCall('/root/feature-flags');
        if (res && res.data && res.data.items) {
            renderFeatureFlags(res.data.items);
        }
    } catch (error) {
        tbody.innerHTML = '<tr><td colspan="4" class="px-6 py-4 text-center text-red-500">Failed to load flags</td></tr>';
    }
}

function renderFeatureFlags(flags) {
    const tbody = document.getElementById('feature-flags-table-body');
    if (!flags.length) {
        tbody.innerHTML = '<tr><td colspan="4" class="px-6 py-4 text-center text-gray-500">No feature flags defined</td></tr>';
        return;
    }
    
    tbody.innerHTML = flags.map(flag => `
        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
            <td class="px-4 py-3 whitespace-nowrap">
                <div class="text-sm font-medium text-gray-900 dark:text-white">${flag.key}</div>
                <div class="text-xs text-gray-500">${flag.description || '-'}</div>
            </td>
            <td class="px-4 py-3 whitespace-nowrap">
                <span class="px-2 py-1 text-xs font-medium rounded-full ${flag.default_value ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'}">
                    ${flag.default_value ? 'Enabled' : 'Disabled'}
                </span>
            </td>
            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                ${flag.is_global ? 'Global' : 'Targeted'}
            </td>
            <td class="px-4 py-3 whitespace-nowrap text-right text-sm font-medium">
                <button onclick="deleteFeatureFlag(${flag.id})" class="text-red-600 hover:text-red-900 dark:hover:text-red-400 ml-3">Delete</button>
            </td>
        </tr>
    `).join('');
}

async function submitCreateFlag() {
    const form = document.getElementById('create-flag-form');
    const formData = new FormData(form);
    const data = Object.fromEntries(formData.entries());
    
    // Checkbox handling
    data.is_global = data.is_global ? 1 : 0;
    
    if (!data.key) {
        showToast('Key is required', 'error');
        return;
    }

    try {
        await apiCall('/root/feature-flags', 'POST', data);
        showToast('Feature flag created', 'success');
        closeModal('create-flag-modal');
        form.reset();
        loadFeatureFlags();
    } catch (error) {
        console.error(error);
    }
}

async function deleteFeatureFlag(id) {
    if (!await showConfirm('Delete this feature flag?')) return;
    try {
        await apiCall(`/root/feature-flags/${id}`, 'DELETE');
        showToast('Feature flag deleted', 'success');
        loadFeatureFlags();
    } catch (error) {
        showToast('Failed to delete', 'error');
    }
}

// --- Announcements ---

async function loadAnnouncements() {
    const list = document.getElementById('announcements-list');
    list.innerHTML = '<div class="text-center py-4">Loading...</div>';
    
    try {
        const res = await apiCall('/root/announcements');
        if (res && res.data && res.data.items) {
            renderAnnouncements(res.data.items);
        }
    } catch (error) {
        list.innerHTML = '<div class="text-center py-4 text-red-500">Failed to load announcements</div>';
    }
}

function renderAnnouncements(items) {
    const list = document.getElementById('announcements-list');
    if (!items.length) {
        list.innerHTML = '<div class="text-center py-4 text-gray-500">No active announcements</div>';
        return;
    }
    
    list.innerHTML = items.map(item => `
        <div class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-4 border border-gray-100 dark:border-gray-600">
            <div class="flex justify-between items-start">
                <div class="flex items-center gap-3">
                    <span class="px-2 py-1 text-xs font-bold uppercase rounded ${getTypeClass(item.type)}">${item.type}</span>
                    <h4 class="text-sm font-bold text-gray-900 dark:text-white">${item.title}</h4>
                </div>
                <button onclick="deleteAnnouncement(${item.id})" class="text-gray-400 hover:text-red-500 transition-colors">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">${item.body}</p>
            <div class="mt-3 flex items-center gap-4 text-xs text-gray-500 dark:text-gray-400">
                <span><i class="far fa-clock mr-1"></i> Published: ${formatDate(item.published_at)}</span>
                ${item.expires_at ? `<span><i class="far fa-hourglass mr-1"></i> Expires: ${formatDate(item.expires_at)}</span>` : ''}
            </div>
        </div>
    `).join('');
}

function getTypeClass(type) {
    switch (type) {
        case 'critical': return 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400';
        case 'warning': return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400';
        default: return 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400';
    }
}

async function submitCreateAnnouncement() {
    const form = document.getElementById('create-announcement-form');
    const formData = new FormData(form);
    const data = Object.fromEntries(formData.entries());
    
    if (!data.title || !data.body) {
        showToast('Title and Body are required', 'error');
        return;
    }

    try {
        await apiCall('/root/announcements', 'POST', data);
        showToast('Announcement published', 'success');
        closeModal('create-announcement-modal');
        form.reset();
        loadAnnouncements();
    } catch (error) {
        console.error(error);
    }
}

async function deleteAnnouncement(id) {
    if (!await showConfirm('Delete this announcement?')) return;
    try {
        await apiCall(`/root/announcements/${id}`, 'DELETE');
        showToast('Announcement deleted', 'success');
        loadAnnouncements();
    } catch (error) {
        showToast('Failed to delete', 'error');
    }
}
