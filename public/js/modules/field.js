import { apiCall } from '../api.js';
import { formatDate, showToast, showConfirm, showModal, closeModal } from '../utils.js';

let zones = [];
let teams = [];

export async function init() {
    console.log('Field Module Initialized');
    
    window.editZone = editZone;
    window.deleteZone = deleteZone;

    await Promise.all([loadZones(), loadTeams()]);

    const form = document.getElementById('zone-form');
    if (form) form.addEventListener('submit', handleZoneSubmit);
}

async function loadZones() {
    const table = document.getElementById('zones-table');
    if (!table) return;
    
    try {
        const res = await apiCall('/field/zones');
        if (res && res.data) {
            zones = res.data.items || res.data;
            renderZones(zones);
        }
    } catch (error) {
        console.error('Failed to load zones:', error);
        table.innerHTML = '<tr><td colspan="4" class="px-6 py-4 text-center text-red-500">Veriler yüklenirken hata oluştu.</td></tr>';
    }
}

async function loadTeams() {
    const list = document.getElementById('team-list');
    if (!list) return;
    
    try {
        const res = await apiCall('/field/teams');
        if (res && res.data) {
            teams = res.data;
            document.getElementById('team-count').textContent = teams.length;
            renderTeams(teams);
        }
    } catch (error) {
        console.error('Failed to load teams:', error);
        list.innerHTML = '<p class="text-center text-red-500 text-sm">Ekipler yüklenemedi.</p>';
    }
}

function renderZones(items) {
    const table = document.getElementById('zones-table');
    
    if (!items || items.length === 0) {
        table.innerHTML = '<tr><td colspan="4" class="px-6 py-4 text-center text-gray-500">Bölge tanımlanmamış.</td></tr>';
        return;
    }

    table.innerHTML = items.map(zone => `
        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
            <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-gray-900 dark:text-white">${zone.name}</div>
                <div class="text-xs text-gray-500">Hedef: ${zone.target || '-'}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                ${zone.manager || 'Atanmamış'}
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <span class="px-2 py-1 text-xs rounded-full ${getStatusColor(zone.status)}">
                    ${getStatusLabel(zone.status)}
                </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                <button onclick="editZone('${zone.id}')" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 mr-3">
                    <i class="fas fa-edit"></i>
                </button>
                <button onclick="deleteZone('${zone.id}')" class="text-red-600 hover:text-red-900 dark:text-red-400">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        </tr>
    `).join('');
}

function renderTeams(items) {
    const list = document.getElementById('team-list');
    
    if (!items || items.length === 0) {
        list.innerHTML = '<p class="text-center text-gray-500 text-sm">Kayıtlı ekip yok.</p>';
        return;
    }

    list.innerHTML = items.map(team => `
        <div class="flex items-center space-x-3 p-2 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg transition-colors">
            <div class="h-10 w-10 rounded-full bg-indigo-100 dark:bg-indigo-900 flex items-center justify-center text-indigo-600 dark:text-indigo-300 font-bold">
                ${team.name.charAt(0).toUpperCase()}
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                    ${team.name}
                </p>
                <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                    ${team.member_count || 0} Üye • ${team.active_task_count || 0} Görev
                </p>
            </div>
            <button class="text-gray-400 hover:text-indigo-600">
                <i class="fas fa-chevron-right"></i>
            </button>
        </div>
    `).join('');
}

async function handleZoneSubmit(e) {
    e.preventDefault();
    
    const id = document.getElementById('zone-id').value;
    const data = {
        name: document.getElementById('zone-name').value,
        manager: document.getElementById('zone-manager').value,
        target: document.getElementById('zone-target').value,
        status: document.getElementById('zone-status').value
    };

    try {
        const url = id ? `/field/zones/${id}` : '/field/zones';
        const method = id ? 'PUT' : 'POST';
        
        await apiCall(url, method, data);
        showToast(id ? 'Bölge güncellendi' : 'Bölge oluşturuldu', 'success');
        closeModal('zone-modal');
        await loadZones();
    } catch (error) {
        showToast('İşlem başarısız', 'error');
        console.error(error);
    }
}

async function editZone(id) {
    const zone = zones.find(z => z.id === id);
    if (!zone) return;

    document.getElementById('zone-id').value = zone.id;
    document.getElementById('zone-name').value = zone.name;
    document.getElementById('zone-manager').value = zone.manager || '';
    document.getElementById('zone-target').value = zone.target || '';
    document.getElementById('zone-status').value = zone.status;
    
    showModal('zone-modal');
}

async function deleteZone(id) {
    if (!await showConfirm('Bu bölgeyi silmek istediğinize emin misiniz?')) return;

    try {
        await apiCall(`/field/zones/${id}`, 'DELETE');
        showToast('Bölge silindi', 'success');
        await loadZones();
    } catch (error) {
        showToast('Silme işlemi başarısız', 'error');
    }
}

function getStatusColor(status) {
    switch(status) {
        case 'active': return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300';
        case 'completed': return 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300';
        default: return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
    }
}

function getStatusLabel(status) {
    const labels = {
        'active': 'Aktif',
        'completed': 'Tamamlandı',
        'pending': 'Beklemede'
    };
    return labels[status] || status;
}


