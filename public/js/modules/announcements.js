import { apiCall } from '../api.js';
import { formatDate, showToast, showModal, closeModal, showConfirm } from '../utils.js';

let announcements = [];

export async function init() {
    console.log('Announcements Module Initialized');
    
    // Bind global functions
    window.editAnnouncement = editAnnouncement;
    window.deleteAnnouncement = deleteAnnouncement;
    window.showModal = showModal;
    window.closeModal = closeModal;

    // Load Data
    await loadAnnouncements();

    // Event Listeners
    const form = document.getElementById('announcement-form');
    if (form) form.addEventListener('submit', handleFormSubmit);
    
    const search = document.getElementById('announcement-search');
    if (search) search.addEventListener('input', filterAnnouncements);
    
    const filter = document.getElementById('announcement-filter-priority');
    if (filter) filter.addEventListener('change', filterAnnouncements);
}

async function loadAnnouncements() {
    const grid = document.getElementById('announcements-grid');
    if (!grid) return;
    
    grid.innerHTML = '<div class="col-span-full text-center py-12"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600 mx-auto"></div></div>';

    try {
        const res = await apiCall('/announcements');
        if (res && res.data) {
            announcements = res.data.items || res.data;
            renderAnnouncements(announcements);
        }
    } catch (error) {
        console.error('Failed to load announcements:', error);
        if (grid) grid.innerHTML = '<div class="col-span-full text-center text-red-500">Duyurular yüklenirken bir hata oluştu.</div>';
    }
}

function renderAnnouncements(items) {
    const grid = document.getElementById('announcements-grid');
    if (!grid) return;
    
    if (!items || items.length === 0) {
        grid.innerHTML = `
            <div class="col-span-full text-center py-12 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                <div class="text-gray-400 mb-2"><i class="fas fa-bullhorn text-4xl"></i></div>
                <p class="text-gray-500 dark:text-gray-400">Henüz duyuru bulunmuyor.</p>
            </div>`;
        return;
    }

    grid.innerHTML = items.map(item => `
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden hover:shadow-md transition-shadow">
            <div class="p-6">
                <div class="flex justify-between items-start mb-4">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${
                        item.priority === 'high' 
                        ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300' 
                        : 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300'
                    }">
                        ${item.priority === 'high' ? 'Yüksek Öncelik' : 'Normal'}
                    </span>
                    <div class="flex items-center text-gray-400 text-sm">
                        <i class="far fa-clock mr-1"></i>
                        ${formatDate(item.created_at)}
                    </div>
                </div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">${item.title}</h3>
                <p class="text-gray-600 dark:text-gray-300 text-sm line-clamp-3 mb-4">${item.content}</p>
                
                <div class="flex justify-between items-center pt-4 border-t border-gray-100 dark:border-gray-700">
                    <div class="flex items-center text-xs text-gray-500 dark:text-gray-400">
                        <i class="far fa-user mr-1"></i> ${item.created_by_name || 'Sistem'}
                    </div>
                    <div class="flex gap-2">
                        <button onclick="editAnnouncement(${item.id})" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button onclick="deleteAnnouncement(${item.id})" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `).join('');
}

function filterAnnouncements() {
    const search = document.getElementById('announcement-search').value.toLowerCase();
    const priority = document.getElementById('announcement-filter-priority').value;

    const filtered = announcements.filter(item => {
        const matchesSearch = item.title.toLowerCase().includes(search) || item.content.toLowerCase().includes(search);
        const matchesPriority = priority ? item.priority === priority : true;
        return matchesSearch && matchesPriority;
    });

    renderAnnouncements(filtered);
}

async function handleFormSubmit(e) {
    e.preventDefault();
    
    const id = document.getElementById('ann-id').value;
    const data = {
        title: document.getElementById('ann-title').value,
        content: document.getElementById('ann-content').value,
        priority: document.getElementById('ann-priority').value
    };

    try {
        const url = id ? `/announcements/${id}` : '/announcements';
        const method = id ? 'PUT' : 'POST';
        
        await apiCall(url, method, data);
        showToast(id ? 'Duyuru güncellendi' : 'Duyuru oluşturuldu', 'success');
        closeModal('announcement-modal');
        await loadAnnouncements();
    } catch (error) {
        showToast('İşlem başarısız oldu', 'error');
        console.error(error);
    }
}

async function editAnnouncement(id) {
    const item = announcements.find(a => a.id === id);
    if (!item) return;

    document.getElementById('ann-id').value = item.id;
    document.getElementById('ann-title').value = item.title;
    document.getElementById('ann-content').value = item.content;
    document.getElementById('ann-priority').value = item.priority;
    
    showModal('announcement-modal');
}

async function deleteAnnouncement(id) {
    if (!await showConfirm('Bu duyuruyu silmek istediğinize emin misiniz?')) return;

    try {
        await apiCall(`/announcements/${id}`, 'DELETE');
        showToast('Duyuru silindi', 'success');
        await loadAnnouncements();
    } catch (error) {
        showToast('Silme işlemi başarısız', 'error');
    }
}

