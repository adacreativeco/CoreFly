import { apiCall } from '../api.js';
import { formatDate, showToast, closeModal } from '../utils.js';

export async function init() {
    console.log('Dashboard Module Initialized');
    
    // Set Date
    const dateEl = document.getElementById('current-date');
    if (dateEl) {
        dateEl.textContent = new Date().toLocaleDateString('tr-TR', { 
            weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' 
        });
    }

    // Bind Modal Forms
    const taskForm = document.getElementById('dashboard-task-form');
    if (taskForm) {
        taskForm.addEventListener('submit', handleQuickTaskCreate);
    }

    const annForm = document.getElementById('dashboard-announcement-form');
    if (annForm) {
        annForm.addEventListener('submit', handleQuickAnnouncementCreate);
    }

    await loadDashboardData();
}

async function handleQuickTaskCreate(e) {
    e.preventDefault();
    
    const title = document.getElementById('task-title').value;
    const dueDate = document.getElementById('task-due-date').value;
    
    const res = await apiCall('/tasks', 'POST', {
        title: title,
        due_date: dueDate,
        status: 'todo',
        priority: 'medium'
    });
    
    if (res && (res.success || res.data)) {
        showToast('Görev oluşturuldu', 'success');
        closeModal('task-modal');
        document.getElementById('dashboard-task-form').reset();
        await loadDashboardData(); // Refresh dashboard
    } else {
        showToast(res.error || 'Görev oluşturulamadı', 'error');
    }
}

async function handleQuickAnnouncementCreate(e) {
    e.preventDefault();
    
    const title = document.getElementById('ann-title').value;
    const content = document.getElementById('ann-content').value;
    const priority = document.getElementById('ann-priority').value;
    
    const res = await apiCall('/announcements', 'POST', {
        title: title,
        content: content,
        priority: priority
    });
    
    if (res && (res.success || res.data)) {
        showToast('Duyuru yayınlandı', 'success');
        closeModal('announcement-modal');
        document.getElementById('dashboard-announcement-form').reset();
        await loadDashboardData(); // Refresh dashboard
    } else {
        showToast(res.error || 'Duyuru oluşturulamadı', 'error');
    }
}

async function loadDashboardData() {
    const res = await apiCall('/dashboard');
    if (!res || !res.data) return;

    const data = res.data;

    // 1. Update Stats
    if (data.stats && data.stats.tasks) {
        const stats = data.stats.tasks;
        setText('stat-tasks-total', stats.total);
        setText('stat-tasks-completed', stats.completed);
        setText('stat-tasks-inprogress', stats.in_progress);
        setText('stat-tasks-overdue', stats.overdue);
    }

    // 2. Recent Tasks
    const tasksTable = document.getElementById('dashboard-tasks-table');
    if (tasksTable && data.recent_tasks && data.recent_tasks.length > 0) {
        tasksTable.innerHTML = data.recent_tasks.map(task => `
            <tr>
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center">
                        <div class="text-sm font-medium text-gray-900 dark:text-white">${task.title}</div>
                    </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${getStatusColor(task.status)}">
                        ${getStatusLabel(task.status)}
                    </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-500 dark:text-gray-400">
                    ${formatDate(task.due_date)}
                </td>
            </tr>
        `).join('');
    } else if (tasksTable) {
        tasksTable.innerHTML = '<tr><td colspan="3" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">Henüz görev yok</td></tr>';
    }

    // 3. Upcoming Events
    const eventsContainer = document.getElementById('dashboard-events');
    if (eventsContainer && data.upcoming_events && data.upcoming_events.length > 0) {
        eventsContainer.innerHTML = data.upcoming_events.map(event => `
            <div class="flex items-start space-x-3 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                <div class="flex-shrink-0 w-10 text-center bg-white dark:bg-gray-800 rounded shadow-sm p-1">
                    <div class="text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase">${new Date(event.start_date).toLocaleString('tr-TR', { month: 'short' })}</div>
                    <div class="text-lg font-bold text-gray-900 dark:text-white">${new Date(event.start_date).getDate()}</div>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-900 dark:text-white truncate">${event.title}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 flex items-center mt-1">
                        <i class="far fa-clock mr-1"></i> ${new Date(event.start_date).toLocaleTimeString('tr-TR', {hour: '2-digit', minute:'2-digit'})}
                    </p>
                </div>
            </div>
        `).join('');
    } else if (eventsContainer) {
        eventsContainer.innerHTML = '<p class="text-center text-sm text-gray-500 dark:text-gray-400">Yaklaşan etkinlik yok</p>';
    }

    // 4. Announcements
    const announcementsContainer = document.getElementById('dashboard-announcements');
    if (announcementsContainer && data.announcements && data.announcements.length > 0) {
        announcementsContainer.innerHTML = data.announcements.map(ann => `
            <div class="border-l-4 ${ann.priority === 'high' ? 'border-red-500' : 'border-indigo-500'} pl-3 py-1">
                <p class="text-sm font-medium text-gray-900 dark:text-white">${ann.title}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 line-clamp-2">${ann.content}</p>
                <div class="mt-1 flex items-center text-xs text-gray-400">
                    <i class="far fa-clock mr-1"></i> ${formatDate(ann.created_at)}
                </div>
            </div>
        `).join('');
    } else if (announcementsContainer) {
        announcementsContainer.innerHTML = '<p class="text-center text-sm text-gray-500 dark:text-gray-400">Duyuru yok</p>';
    }

    // 5. Update Global Badges (if sidebar is present)
    if (data.sidebar_counts) {
        updateBadge('badge-announcements', data.sidebar_counts.announcements);
        updateBadge('badge-tasks', data.sidebar_counts.tasks);
        updateBadge('badge-messages', data.sidebar_counts.messages);
    }
}

function setText(id, text) {
    const el = document.getElementById(id);
    if (el) el.textContent = text;
}

function updateBadge(id, count) {
    // This assumes badges exist in the sidebar in index.html
    // If not present, this does nothing
    const badge = document.getElementById(id);
    if (badge) {
        badge.textContent = count;
        if (count > 0) badge.classList.remove('hidden');
        else badge.classList.add('hidden');
    }
}

function getStatusColor(status) {
    switch(status) {
        case 'completed': return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300';
        case 'in_progress': return 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300';
        case 'todo': return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
        case 'overdue': return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300';
        default: return 'bg-gray-100 text-gray-800';
    }
}

function getStatusLabel(status) {
    const labels = {
        'completed': 'Tamamlandı',
        'in_progress': 'Devam Ediyor',
        'todo': 'Yapılacak',
        'overdue': 'Gecikmiş'
    };
    return labels[status] || status;
}



