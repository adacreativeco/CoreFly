
import { apiCall } from '../api.js';
import { showToast } from '../utils.js';

export async function init() {
    console.log('Notifications Module Initialized');
    await loadNotifications();
    
    // Setup Mark All Read Button
    const markAllBtn = document.getElementById('mark-all-read-btn');
    if (markAllBtn) {
        markAllBtn.addEventListener('click', async () => {
            await App.markAllRead(); // Use App core method which updates UI globally
            await loadNotifications(); // Reload local list
            showToast('Tüm bildirimler okundu işaretlendi', 'success');
        });
    }
}

async function loadNotifications() {
    const list = document.getElementById('notifications-list');
    if (!list) return;
    
    list.innerHTML = `
        <div class="flex justify-center p-8">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
        </div>`;
    
    const res = await apiCall('/notifications?limit=50');
    
    if (!res || !res.data || !res.data.notifications || res.data.notifications.length === 0) {
        list.innerHTML = `
            <div class="text-center py-12">
                <i class="fas fa-bell-slash text-4xl text-gray-300 mb-4"></i>
                <p class="text-gray-500">Henüz bildiriminiz bulunmamaktadır.</p>
            </div>`;
        return;
    }

    const notifications = res.data.notifications;

    list.innerHTML = notifications.map(n => `
        <div class="flex items-start p-4 ${n.is_read ? 'bg-white dark:bg-gray-800' : 'bg-blue-50 dark:bg-blue-900/10'} border-b border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
            <div class="flex-shrink-0">
                <span class="inline-flex items-center justify-center h-8 w-8 rounded-full ${getIconColor(n.type)}">
                    <i class="fas ${getIcon(n.type)}"></i>
                </span>
            </div>
            <div class="ml-4 flex-1">
                <div class="flex justify-between">
                    <p class="text-sm font-medium text-gray-900 dark:text-white ${!n.is_read ? 'font-bold' : ''}">${n.title}</p>
                    <span class="text-xs text-gray-400 whitespace-nowrap ml-2">${App.formatDate(n.created_at)}</span>
                </div>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">${n.message}</p>
                ${!n.is_read ? `
                <div class="mt-2">
                    <button onclick="handleMarkRead('${n.id}')" class="text-xs text-indigo-600 hover:text-indigo-800 font-medium">
                        Okundu Olarak İşaretle
                    </button>
                </div>` : ''}
            </div>
            <button onclick="handleDelete('${n.id}')" class="ml-2 text-gray-400 hover:text-red-500 transition-colors" title="Sil">
                <i class="fas fa-trash-alt"></i>
            </button>
        </div>
    `).join('');
}

window.handleMarkRead = async (id) => {
    await App.markNotificationRead(id); // Use App core method
    await loadNotifications(); // Reload list
};

window.handleDelete = async (id) => {
    if (!confirm('Bu bildirimi silmek istediğinize emin misiniz?')) return;
    
    const res = await apiCall(`/notifications/${id}`, 'DELETE');
    if (res && res.success) {
        showToast('Bildirim silindi', 'success');
        await loadNotifications();
        // Also refresh global badge
        App.fetchNotifications();
    }
};

function getIcon(type) {
    switch(type) {
        case 'info': return 'fa-info';
        case 'success': return 'fa-check';
        case 'warning': return 'fa-exclamation-triangle';
        case 'error': return 'fa-times';
        case 'task': return 'fa-tasks';
        case 'announcement': return 'fa-bullhorn';
        default: return 'fa-bell';
    }
}

function getIconColor(type) {
    switch(type) {
        case 'info': return 'bg-blue-100 text-blue-600';
        case 'success': return 'bg-green-100 text-green-600';
        case 'warning': return 'bg-yellow-100 text-yellow-600';
        case 'error': return 'bg-red-100 text-red-600';
        case 'task': return 'bg-indigo-100 text-indigo-600';
        case 'announcement': return 'bg-purple-100 text-purple-600';
        default: return 'bg-gray-100 text-gray-600';
    }
}
