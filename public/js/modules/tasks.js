import { apiCall, getCurrentUser } from '../api.js';
import { showToast, showModal, closeModal, showConfirm, formatDate } from '../utils.js';

let tasks = [];
let projects = [];

export async function init() {
    console.log('Tasks Module Initialized');
    
    window.switchTaskView = switchTaskView;
    window.filterTasks = filterTasks;
    window.handleTaskSubmit = handleTaskSubmit;
    window.editTask = editTask;
    window.deleteTask = deleteTask;
    window.allowDrop = allowDrop;
    window.dropTask = dropTask;
    window.dragTask = dragTask;
    window.updateTaskStatus = updateTaskStatus;
    window.handleProjectSubmit = handleProjectSubmit;
    window.addSubtask = addSubtask;
    window.toggleSubtask = toggleSubtask;
    window.openCreateTaskModal = openCreateTaskModal;

    injectModals();
    await loadProjects(); // For select options
    await loadTasks();
}

function injectModals() {
    const modalContainer = document.getElementById('modal-container');
    
    // Check if task modal exists
    if (!document.getElementById('task-create-modal')) {
        const taskModalHTML = `
        <!-- Create Task Modal -->
        <div id="task-create-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center">
            <div class="bg-white dark:bg-gray-800 rounded-lg w-full max-w-lg mx-4 overflow-hidden shadow-xl transform transition-all">
                <div class="flex justify-between items-center px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white" id="modal-title-task">Yeni Görev</h3>
                    <button onclick="closeModal('task-create-modal')" class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <form onsubmit="handleTaskSubmit(event)" class="p-6 space-y-4">
                    <input type="hidden" name="id">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Başlık</label>
                        <input type="text" name="title" required class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white sm:text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Proje</label>
                        <select name="project_id" id="task-project-select" class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white sm:text-sm">
                            <option value="">Genel</option>
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Durum</label>
                            <select name="status" class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white sm:text-sm">
                                <option value="todo" selected>Yapılacak</option>
                                <option value="in_progress">Devam Eden</option>
                                <option value="completed">Tamamlandı</option>
                                <option value="blocked">Bloke</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Öncelik</label>
                            <select name="priority" class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white sm:text-sm">
                                <option value="low">Düşük</option>
                                <option value="medium" selected>Orta</option>
                                <option value="high">Yüksek</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Bitiş Tarihi</label>
                            <input type="date" name="due_date" class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white sm:text-sm">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Açıklama</label>
                        <textarea name="description" rows="3" class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white sm:text-sm"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Atanan Kişi</label>
                        <select name="assigned_to" id="task-assignee-select" class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white sm:text-sm">
                            <option value="">Kendim</option>
                            <!-- Users will be loaded here if needed, for now self or open -->
                        </select>
                    </div>
                    <div class="flex justify-end pt-4">
                        <button type="button" onclick="closeModal('task-create-modal')" class="bg-white dark:bg-gray-700 py-2 px-4 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-200 mr-3">İptal</button>
                        <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">Kaydet</button>
                    </div>
                </form>
            </div>
        </div>
        `;
        modalContainer.innerHTML += taskModalHTML;
    }

    // Check if project modal exists (inject it for 'New Project' button)
    if (!document.getElementById('project-create-modal')) {
        const projectModalHTML = `
        <!-- Create Project Modal -->
        <div id="project-create-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center">
            <div class="bg-white dark:bg-gray-800 rounded-lg w-full max-w-lg mx-4 overflow-hidden shadow-xl transform transition-all">
                <div class="flex justify-between items-center px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Yeni Proje</h3>
                    <button onclick="closeModal('project-create-modal')" class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <form onsubmit="handleProjectSubmit(event)" class="p-6 space-y-4">
                    <input type="hidden" name="id">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Proje Adı</label>
                        <input type="text" name="title" required class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white sm:text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Proje Tipi</label>
                        <input type="text" name="project_type" class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white sm:text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Durum</label>
                        <select name="status" class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white sm:text-sm">
                            <option value="planning">Planlama</option>
                            <option value="active">Aktif</option>
                        </select>
                    </div>
                    <div class="flex justify-end pt-4">
                        <button type="button" onclick="closeModal('project-create-modal')" class="bg-white dark:bg-gray-700 py-2 px-4 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-200 mr-3">İptal</button>
                        <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700">Oluştur</button>
                    </div>
                </form>
            </div>
        </div>
        `;
        modalContainer.innerHTML += projectModalHTML;
    }
}

async function loadProjects() {
    const res = await apiCall('/projects');
    if (res && res.data) {
        // Handle variations: {data: [...]}, {items: [...], pagination: ...}, or [...]
        projects = res.data.data || res.data.items || res.data; 
        
        if (!Array.isArray(projects)) {
             console.error('Projects data is not an array:', res.data);
             projects = [];
        }
        
        const select = document.getElementById('task-project-select');
        if (select) {
            select.innerHTML = '<option value="">Genel</option>' + 
                projects.map(p => `<option value="${p.id}">${p.title || p.name}</option>`).join('');
        }
    }
}

async function loadTasks() {
    const container = document.getElementById('tasks-list-container');
    
    try {
        const res = await apiCall('/tasks');

        if (res && res.data) {
             // Handle variations: {data: [...]}, {items: [...], pagination: ...}, or [...]
            tasks = res.data.data || res.data.items || res.data;
            
            if (!Array.isArray(tasks)) {
                 console.error('Tasks data is not an array:', res.data);
                 tasks = [];
            }

            filterTasks();
        } else {
            console.error('Invalid response structure:', res);
            if (container) {
                container.innerHTML = `<li class="px-4 py-8 text-center text-red-500 dark:text-red-400">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    Hata: ${res?.message || 'Görevler yüklenirken bir sorun oluştu.'}
                </li>`;
            }
        }
    } catch (e) {
        console.error('Load tasks error:', e);
        if (container) {
            container.innerHTML = `<li class="px-4 py-8 text-center text-red-500">Beklenmeyen bir hata oluştu.</li>`;
        }
    }
}

function filterTasks() {
    const search = document.getElementById('task-search').value.toLowerCase();
    const status = document.getElementById('task-filter-status').value;
    const priority = document.getElementById('task-filter-priority').value;

    const filtered = tasks.filter(t => {
        const matchesSearch = t.title.toLowerCase().includes(search) || (t.description && t.description.toLowerCase().includes(search));
        const matchesStatus = status === 'all' || t.status === status;
        const matchesPriority = priority === 'all' || t.priority === priority;
        return matchesSearch && matchesStatus && matchesPriority;
    });

    renderTaskList(filtered);
    renderKanbanBoard(filtered);
}

function renderTaskList(taskList) {
    const container = document.getElementById('tasks-list-container');
    if (!container) return;

    if (taskList.length === 0) {
        container.innerHTML = '<li class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">Görev bulunamadı.</li>';
        return;
    }

    container.innerHTML = taskList.map(task => `
        <li class="hover:bg-gray-50 dark:hover:bg-gray-700">
            <div class="px-4 py-4 sm:px-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center min-w-0 flex-1">
                        <div class="min-w-0 flex-1 px-4 md:grid md:grid-cols-2 md:gap-4">
                            <div>
                                <p class="text-sm font-medium text-indigo-600 dark:text-indigo-400 truncate">${task.title}</p>
                                <p class="mt-2 flex items-center text-sm text-gray-500 dark:text-gray-400">
                                    <i class="fas fa-project-diagram flex-shrink-0 mr-1.5 text-gray-400"></i>
                                    <span class="truncate">${task.project_name || 'Genel'}</span>
                                </p>
                            </div>
                            <div class="hidden md:block">
                                <div>
                                    <p class="text-sm text-gray-900 dark:text-white">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${getStatusColor(task.status)}">
                                            ${getStatusLabel(task.status)}
                                        </span>
                                    </p>
                                    <p class="mt-2 flex items-center text-sm text-gray-500 dark:text-gray-400">
                                        <i class="far fa-calendar flex-shrink-0 mr-1.5 text-gray-400"></i>
                                        ${task.due_date ? formatDate(task.due_date) : 'Tarih yok'}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="flex space-x-2">
                        <button onclick="editTask('${task.id}')" class="text-gray-400 hover:text-gray-500"><i class="fas fa-edit"></i></button>
                        <button onclick="deleteTask('${task.id}')" class="text-red-400 hover:text-red-500"><i class="fas fa-trash"></i></button>
                    </div>
                </div>
                
                <!-- Subtasks Section -->
                <div class="mt-4 border-t border-gray-100 dark:border-gray-600 pt-4 px-4">
                    <div class="flex justify-between items-center mb-2">
                         <h5 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Alt Görevler (${task.completed_subtasks_count || 0}/${task.subtasks_count || 0})</h5>
                         <button onclick="addSubtask('${task.id}')" class="text-xs text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 font-medium">+ Ekle</button>
                    </div>
                    <ul class="space-y-2">
                        ${(task.subtasks || []).map(sub => `
                            <li class="flex items-center text-sm">
                                <input type="checkbox" ${sub.is_completed ? 'checked' : ''} 
                                    onchange="toggleSubtask('${sub.id}')"
                                    class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                                <span class="ml-2 ${sub.is_completed ? 'line-through text-gray-400' : 'text-gray-700 dark:text-gray-300'}">${sub.title}</span>
                            </li>
                        `).join('')}
                    </ul>
                </div>
            </div>
        </li>
    `).join('');
}

function renderKanbanBoard(taskList) {
    const columns = ['todo', 'in_progress', 'completed', 'blocked'];
    
    columns.forEach(col => {
        const colTasks = taskList.filter(t => t.status === col);
        const container = document.getElementById(`kanban-${col}`);
        const counter = document.getElementById(`count-${col}`);
        
        if (counter) counter.textContent = colTasks.length;
        if (container) {
            container.innerHTML = colTasks.map(task => `
                <div class="bg-white dark:bg-gray-700 p-3 rounded shadow-sm border border-gray-200 dark:border-gray-600 cursor-move hover:shadow-md transition-shadow" 
                     draggable="true" 
                     ondragstart="dragTask(event, '${task.id}')">
                    <div class="flex justify-between items-start mb-2">
                        <span class="text-xs font-medium px-2 py-0.5 rounded ${getPriorityBg(task.priority)}">${getPriorityLabel(task.priority)}</span>
                        <button onclick="editTask('${task.id}')" class="text-gray-400 hover:text-gray-600"><i class="fas fa-ellipsis-h"></i></button>
                    </div>
                    <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-2">${task.title}</h4>
                    <div class="flex justify-between items-center text-xs text-gray-500 dark:text-gray-400">
                        <span><i class="far fa-calendar mr-1"></i>${task.due_date ? formatDate(task.due_date).slice(0,5) : '-'}</span>
                        ${task.assigned_to_avatar ? `<img src="${task.assigned_to_avatar}" class="w-5 h-5 rounded-full">` : ''}
                    </div>
                </div>
            `).join('');
        }
    });
}

// View Switcher
function switchTaskView(view) {
    const listView = document.getElementById('task-view-list');
    const kanbanView = document.getElementById('task-view-kanban');
    const btnList = document.getElementById('btn-task-view-list');
    const btnKanban = document.getElementById('btn-task-view-kanban');

    if (view === 'list') {
        listView.classList.remove('hidden');
        kanbanView.classList.add('hidden');
        btnList.classList.replace('bg-white', 'bg-gray-200');
        btnList.classList.replace('dark:bg-gray-800', 'dark:bg-gray-700');
        btnKanban.classList.replace('bg-gray-200', 'bg-white');
        btnKanban.classList.replace('dark:bg-gray-700', 'dark:bg-gray-800');
    } else {
        listView.classList.add('hidden');
        kanbanView.classList.remove('hidden');
        // Swap styles logic...
        btnKanban.classList.add('bg-gray-200', 'dark:bg-gray-700');
        btnList.classList.remove('bg-gray-200', 'dark:bg-gray-700');
    }
}

// Drag & Drop
function allowDrop(ev) {
    ev.preventDefault();
}

function dragTask(ev, id) {
    ev.dataTransfer.setData("text/plain", id);
}

async function dropTask(ev, status) {
    ev.preventDefault();
    const id = ev.dataTransfer.getData("text");
    
    // Optimistic Update
    const task = tasks.find(t => t.id === id);
    if (task && task.status !== status) {
        const oldStatus = task.status;
        task.status = status;
        filterTasks(); // Re-render

        // API Call
        const res = await apiCall(`/tasks/status`, 'PUT', { id, status });
        if (!res || !res.success) {
            // Revert
            task.status = oldStatus;
            filterTasks();
            showToast('Durum güncellenemedi', 'error');
        }
    }
}

async function handleTaskSubmit(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData.entries());
    const id = data.id;
    const url = id ? `/tasks/${id}` : '/tasks';
    const method = id ? 'PUT' : 'POST';
    
    // API Call wrapper already handles JSON stringify, but FormData conversion to object is correct
    // However, we need to ensure empty strings are handled if backend expects null
    if (data.due_date === '') data.due_date = null;
    if (data.project_id === '') data.project_id = null;
    if (data.assigned_to === '') data.assigned_to = null;

    const res = await apiCall(url, method, data);
    console.log('Task submit response:', res);
    
    if (res && (res.success || res.data)) {
        showToast(id ? 'Görev güncellendi' : 'Görev oluşturuldu', 'success');
        closeModal('task-create-modal');
        
        // Reset filters if new task created to ensure it's visible
        if (!id) {
             const statusFilter = document.getElementById('task-filter-status');
             const priorityFilter = document.getElementById('task-filter-priority');
             const searchFilter = document.getElementById('task-search');
             
             if (statusFilter) statusFilter.value = 'all';
             if (priorityFilter) priorityFilter.value = 'all';
             if (searchFilter) searchFilter.value = '';
        }

        loadTasks();
    } else {
        showToast(res.error || 'Hata oluştu', 'error');
    }
}

async function handleProjectSubmit(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData.entries());
    
    const res = await apiCall('/projects', 'POST', data);
    if (res && (res.success || res.data)) {
        showToast('Proje oluşturuldu', 'success');
        closeModal('project-create-modal');
        await loadProjects(); // Reload projects list for select dropdown
    } else {
        showToast(res.error || 'Proje oluşturulamadı', 'error');
    }
}

function openCreateTaskModal() {
    const form = document.querySelector('#task-create-modal form');
    if (form) {
        form.reset();
        // Manually clear hidden ID and ensure defaults
        const idInput = form.querySelector('[name=id]');
        if (idInput) idInput.value = '';
        
        // Reset title
        const titleEl = document.getElementById('modal-title-task');
        if (titleEl) titleEl.textContent = 'Yeni Görev';
        
        // Reset status/priority to defaults just in case
        const statusSelect = form.querySelector('[name=status]');
        if (statusSelect) statusSelect.value = 'todo';
        
        const prioritySelect = form.querySelector('[name=priority]');
        if (prioritySelect) prioritySelect.value = 'medium';
    }
    showModal('task-create-modal');
}

function editTask(id) {
    const task = tasks.find(t => t.id === id);
    if (task) {
        const form = document.querySelector('#task-create-modal form');
        form.reset();
        form.querySelector('[name=id]').value = task.id;
        form.querySelector('[name=title]').value = task.title;
        form.querySelector('[name=project_id]').value = task.project_id || '';
        form.querySelector('[name=status]').value = task.status || 'todo';
        form.querySelector('[name=priority]').value = task.priority || 'medium';
        form.querySelector('[name=due_date]').value = task.due_date ? task.due_date.split('T')[0] : '';
        form.querySelector('[name=description]').value = task.description || '';
        
        document.getElementById('modal-title-task').textContent = 'Görev Düzenle';
        showModal('task-create-modal');
    }
}

async function deleteTask(id) {
    if(await showConfirm('Bu görevi silmek istediğinize emin misiniz?')) {
        const res = await apiCall(`/tasks/${id}`, 'DELETE');
        
        if (res && res.success) {
            showToast('Görev silindi', 'success');
            loadTasks();
        } else {
             showToast('Silme başarısız', 'error');
        }
    }
}

async function addSubtask(taskId) {
    // Use a custom modal instead of prompt()
    const title = await showPromptModal('Alt Görev Ekle', 'Alt görev başlığını giriniz:');
    if (!title) return;

    const res = await apiCall('/tasks/subtasks', 'POST', { task_id: taskId, title });
    if (res && (res.success || res.data)) {
        showToast('Alt görev eklendi', 'success');
        loadTasks();
    } else {
        showToast(res.error || 'Hata oluştu', 'error');
    }
}

// Helper for custom prompt modal since window.prompt is blocked
function showPromptModal(title, message) {
    return new Promise((resolve) => {
        const modalId = 'custom-prompt-modal';
        let modal = document.getElementById(modalId);
        
        if (!modal) {
            const html = `
            <div id="${modalId}" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center">
                <div class="bg-white dark:bg-gray-800 rounded-lg w-full max-w-md mx-4 p-6 shadow-xl">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4" id="${modalId}-title"></h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4" id="${modalId}-message"></p>
                    <input type="text" id="${modalId}-input" class="w-full border border-gray-300 dark:border-gray-600 rounded-md p-2 mb-4 bg-white dark:bg-gray-700 text-gray-900 dark:text-white" autofocus>
                    <div class="flex justify-end space-x-3">
                        <button id="${modalId}-cancel" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 rounded hover:bg-gray-300 dark:hover:bg-gray-600">İptal</button>
                        <button id="${modalId}-confirm" class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">Tamam</button>
                    </div>
                </div>
            </div>`;
            document.body.insertAdjacentHTML('beforeend', html);
            modal = document.getElementById(modalId);
        }
        
        const titleEl = document.getElementById(`${modalId}-title`);
        const messageEl = document.getElementById(`${modalId}-message`);
        const inputEl = document.getElementById(`${modalId}-input`);
        const cancelBtn = document.getElementById(`${modalId}-cancel`);
        const confirmBtn = document.getElementById(`${modalId}-confirm`);
        
        titleEl.textContent = title;
        messageEl.textContent = message;
        inputEl.value = '';
        
        const close = (value) => {
            modal.classList.add('hidden');
            resolve(value);
            // Remove event listeners to prevent memory leaks if reused, or just clone
            cancelBtn.replaceWith(cancelBtn.cloneNode(true));
            confirmBtn.replaceWith(confirmBtn.cloneNode(true));
        };
        
        cancelBtn.onclick = () => close(null);
        confirmBtn.onclick = () => close(inputEl.value);
        
        inputEl.onkeydown = (e) => {
            if (e.key === 'Enter') close(inputEl.value);
            if (e.key === 'Escape') close(null);
        };
        
        modal.classList.remove('hidden');
        inputEl.focus();
    });
}

async function toggleSubtask(id) {
    // Optimistic Update can be tricky with list re-render, so we'll just reload or use partial update
    // For simplicity, let's call API and reload
    const res = await apiCall('/tasks/subtasks/toggle', 'POST', { id });
    if (res && (res.success || res.data)) {
        // showToast('Güncellendi', 'success'); // Too noisy for checkboxes
        loadTasks();
    } else {
        showToast('Hata oluştu', 'error');
        loadTasks(); // Revert
    }
}

async function updateTaskStatus(id, status) {
    // Optimistic Update
    const task = tasks.find(t => t.id === id);
    if (task && task.status !== status) {
        const oldStatus = task.status;
        task.status = status;
        filterTasks(); // Re-render

        // API Call
        const res = await apiCall(`/tasks/status`, 'PUT', { id, status });
        if (!res || !res.success) {
            // Revert
            task.status = oldStatus;
            filterTasks();
            showToast('Durum güncellenemedi', 'error');
        } else {
             showToast('Durum güncellendi', 'success');
        }
    }
}

function getStatusColor(status) {
    switch(status) {
        case 'completed': return 'bg-green-100 text-green-800';
        case 'in_progress': return 'bg-blue-100 text-blue-800';
        case 'blocked': return 'bg-red-100 text-red-800';
        default: return 'bg-gray-100 text-gray-800';
    }
}

function getStatusLabel(status) {
    const labels = { 'completed': 'Tamamlandı', 'in_progress': 'Devam Eden', 'blocked': 'Bloke', 'todo': 'Yapılacak' };
    return labels[status] || status;
}

function getPriorityBg(priority) {
    switch(priority) {
        case 'high': return 'bg-red-100 text-red-800';
        case 'medium': return 'bg-yellow-100 text-yellow-800';
        case 'low': return 'bg-green-100 text-green-800';
        default: return 'bg-gray-100 text-gray-800';
    }
}

function getPriorityLabel(priority) {
    const labels = { 'high': 'Yüksek', 'medium': 'Orta', 'low': 'Düşük' };
    return labels[priority] || priority;
}



