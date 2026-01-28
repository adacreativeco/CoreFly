import { apiCall } from '../api.js';
import { showToast, showModal, closeModal, showConfirm, formatDate } from '../utils.js';

let projects = [];

export async function init(subview) {
    console.log('Projects Module Initialized', subview);
    
    try {
        // Check if subview is an ID (detail view)
        if (subview) {
            await loadProjectDetail(subview);
            return;
        }

        // List view logic
        window.filterProjects = filterProjects;
        window.handleProjectSubmit = handleProjectSubmit;
        window.editProject = editProject;
        window.deleteProject = deleteProject;
        window.viewProjectDetails = viewProjectDetails;

        injectModals();
        await loadProjects();
        await loadStats();
    } catch (e) {
        console.error('Projects module init error:', e);
        showToast('Projeler yüklenirken hata oluştu', 'error');
    }
}

async function loadProjectDetail(id) {
    const container = document.getElementById('app');
    
    const res = await apiCall(`/projects/${id}`);
    if (!res || !res.data) {
        container.innerHTML = '<div class="p-8 text-center">Proje bulunamadı</div>';
        return;
    }
    
    const p = res.data;

    container.innerHTML = `
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="mb-6">
                <a href="#projects" class="text-indigo-600 hover:text-indigo-800 flex items-center mb-4">
                    <i class="fas fa-arrow-left mr-2"></i> Projelere Dön
                </a>
                <div class="flex justify-between items-start">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">${p.title}</h1>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${getStatusColor(p.status)} mr-2">${getStatusLabel(p.status)}</span>
                            ${p.project_type || 'Genel Proje'}
                        </p>
                    </div>
                    <div class="flex space-x-3">
                         <button onclick="editProject('${p.id}')" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700">
                            <i class="fas fa-edit mr-2"></i> Düzenle
                        </button>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Main Info -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Description -->
                    <div class="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg">
                        <div class="px-4 py-5 sm:px-6 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">Proje Detayları</h3>
                        </div>
                        <div class="px-4 py-5 sm:p-6 text-gray-700 dark:text-gray-300">
                            ${p.description || 'Açıklama yok.'}
                        </div>
                    </div>

                    <!-- Tasks -->
                    <div class="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg">
                         <div class="px-4 py-5 sm:px-6 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">Görevler</h3>
                        </div>
                        <div class="px-4 py-5 sm:p-6" id="project-tasks-container">
                            Yükleniyor...
                        </div>
                    </div>
                </div>

                <!-- Sidebar Info -->
                <div class="space-y-6">
                    <div class="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg">
                        <div class="px-4 py-5 sm:px-6 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">Bilgiler</h3>
                        </div>
                        <dl class="divide-y divide-gray-200 dark:divide-gray-700">
                            <div class="px-4 py-4 sm:px-6">
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Başlangıç</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-white">${p.start_date ? formatDate(p.start_date).split(' ')[0] : '-'}</dd>
                            </div>
                            <div class="px-4 py-4 sm:px-6">
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Bitiş</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-white">${p.end_date ? formatDate(p.end_date).split(' ')[0] : '-'}</dd>
                            </div>
                            <div class="px-4 py-4 sm:px-6">
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Bütçe</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-white">${p.budget ? parseFloat(p.budget).toLocaleString('tr-TR', {style: 'currency', currency: 'TRY'}) : '-'}</dd>
                            </div>
                            <div class="px-4 py-4 sm:px-6">
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">İlerleme</dt>
                                <dd class="mt-1 flex items-center">
                                    <div class="flex-1 bg-gray-200 rounded-full h-2 mr-2">
                                        <div class="bg-indigo-600 h-2 rounded-full" style="width: ${p.progress || 0}%"></div>
                                    </div>
                                    <span class="text-sm text-gray-900 dark:text-white">%${p.progress || 0}</span>
                                </dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    `;

    // Load Project Tasks
    const tasksRes = await apiCall('/tasks');
    if (tasksRes && tasksRes.data) {
        let allTasks = tasksRes.data.data || tasksRes.data;
        if (!Array.isArray(allTasks)) allTasks = [];
        
        const projectTasks = allTasks.filter(t => t.project_id === id);
        renderProjectTasks(projectTasks);
    } else {
        document.getElementById('project-tasks-container').innerHTML = 'Görevler yüklenemedi.';
    }
    
    // Inject Modals for Edit
    window.editProject = (pid) => {
        projects = [p];
        injectModals();
        const project = projects.find(item => item.id === pid);
        if (project) {
             const form = document.querySelector('#project-create-modal form');
             if(form) {
                form.reset();
                form.querySelector('[name=id]').value = project.id;
                form.querySelector('[name=title]').value = project.title;
                form.querySelector('[name=project_type]').value = project.project_type || '';
                form.querySelector('[name=status]').value = project.status || 'planning';
                form.querySelector('[name=start_date]').value = project.start_date ? project.start_date.split('T')[0] : '';
                form.querySelector('[name=end_date]').value = project.end_date ? project.end_date.split('T')[0] : '';
                form.querySelector('[name=priority]').value = project.priority || 'medium';
                form.querySelector('[name=budget]').value = project.budget || '';
                form.querySelector('[name=description]').value = project.description || '';
                document.getElementById('modal-title-project').textContent = 'Proje Düzenle';
                showModal('project-create-modal');
             }
        }
    };

    window.handleProjectSubmit = async (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        const data = Object.fromEntries(formData.entries());
        const pid = data.id;
        const url = pid ? `/projects/${pid}` : '/projects';
        const method = pid ? 'PUT' : 'POST';
        const r = await apiCall(url, method, data);
        if (r && (r.success || r.data)) {
            showToast('Proje güncellendi', 'success');
            closeModal('project-create-modal');
            loadProjectDetail(pid);
        } else {
            showToast(r.error || 'Hata', 'error');
        }
    };
    
    injectModals();
}

function renderProjectTasks(tasks) {
    const container = document.getElementById('project-tasks-container');
    if (tasks.length === 0) {
        container.innerHTML = '<p class="text-gray-500 text-sm">Bu projeye ait görev bulunmuyor.</p>';
        return;
    }
    
    container.innerHTML = `
        <ul class="divide-y divide-gray-200 dark:divide-gray-700">
            ${tasks.map(t => `
                <li class="py-3 flex justify-between items-center">
                    <div>
                        <p class="text-sm font-medium text-gray-900 dark:text-white">${t.title}</p>
                        <p class="text-xs text-gray-500">${t.assigned_user_name || 'Atanmamış'} - ${t.due_date ? formatDate(t.due_date) : ''}</p>
                    </div>
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${t.status === 'completed' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'}">
                        ${t.status}
                    </span>
                </li>
            `).join('')}
        </ul>
    `;
}

function injectModals() {
    const modalContainer = document.getElementById('modal-container');
    if (document.getElementById('project-create-modal')) return;

    const modalsHTML = `
    <!-- Create/Edit Project Modal -->
    <div id="project-create-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center">
        <div class="bg-white dark:bg-gray-800 rounded-lg w-full max-w-2xl mx-4 overflow-hidden shadow-xl transform transition-all">
            <div class="flex justify-between items-center px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white" id="modal-title-project">Yeni Proje</h3>
                <button onclick="closeModal('project-create-modal')" class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form onsubmit="handleProjectSubmit(event)" class="p-6 space-y-4">
                <input type="hidden" name="id">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="col-span-2">
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
                            <option value="on_hold">Beklemede</option>
                            <option value="completed">Tamamlandı</option>
                            <option value="cancelled">İptal</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Başlangıç Tarihi</label>
                        <input type="date" name="start_date" class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white sm:text-sm">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Bitiş Tarihi</label>
                        <input type="date" name="end_date" class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white sm:text-sm">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Öncelik</label>
                        <select name="priority" class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white sm:text-sm">
                            <option value="low">Düşük</option>
                            <option value="medium" selected>Orta</option>
                            <option value="high">Yüksek</option>
                            <option value="urgent">Acil</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Bütçe</label>
                        <input type="number" step="0.01" name="budget" class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white sm:text-sm">
                    </div>
                    
                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Açıklama</label>
                        <textarea name="description" rows="3" class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white sm:text-sm"></textarea>
                    </div>
                </div>

                <div class="flex justify-end pt-4">
                    <button type="button" onclick="closeModal('project-create-modal')" class="bg-white dark:bg-gray-700 py-2 px-4 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-200 mr-3">İptal</button>
                    <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">Kaydet</button>
                </div>
            </form>
        </div>
    </div>
    `;
    
    modalContainer.innerHTML += modalsHTML;
}

async function loadProjects() {
    const res = await apiCall('/projects');
    if (res && res.data) {
        projects = res.data.data || res.data.items || res.data;
        
        if (!Array.isArray(projects)) {
             console.error('Projects data is not an array:', res.data);
             projects = [];
        }
        
        filterProjects();
    }
}

async function loadStats() {
    let stats = { total: 0, active: 0, completed: 0, overdue: 0 };
    
    try {
        const res = await apiCall('/projects/stats');
        if (res && res.data) {
            stats = res.data;
        } else {
            throw new Error('No data');
        }
    } catch (e) {
        console.warn('Stats endpoint failed, calculating locally', e);
        stats.total = projects.length;
        stats.active = projects.filter(p => p.status === 'active').length;
        stats.completed = projects.filter(p => p.status === 'completed').length;
        stats.overdue = projects.filter(p => {
             if (!p.end_date || p.status === 'completed') return false;
             return new Date(p.end_date) < new Date();
        }).length;
    }

    const container = document.getElementById('projects-stats-container');
    if (!container) return;

    container.innerHTML = `
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">Toplam Proje</dt>
                <dd class="mt-1 text-3xl font-semibold text-gray-900 dark:text-white">${stats.total}</dd>
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">Aktif</dt>
                <dd class="mt-1 text-3xl font-semibold text-green-600 dark:text-green-400">${stats.active}</dd>
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">Tamamlanan</dt>
                <dd class="mt-1 text-3xl font-semibold text-blue-600 dark:text-blue-400">${stats.completed}</dd>
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">Geciken</dt>
                <dd class="mt-1 text-3xl font-semibold text-red-600 dark:text-red-400">${stats.overdue || 0}</dd>
            </div>
        </div>
    `;
}

function filterProjects() {
    const search = document.getElementById('project-search').value.toLowerCase();
    const status = document.getElementById('project-filter-status').value;
    const priority = document.getElementById('project-filter-priority').value;

    const filtered = projects.filter(p => {
        const matchesSearch = p.title.toLowerCase().includes(search) || (p.description && p.description.toLowerCase().includes(search));
        const matchesStatus = status === 'all' || p.status === status;
        const matchesPriority = priority === 'all' || p.priority === priority;
        return matchesSearch && matchesStatus && matchesPriority;
    });

    renderProjectsList(filtered);
}

function renderProjectsList(list) {
    const container = document.getElementById('projects-list-container');
    if (!container) return;

    if (list.length === 0) {
        container.innerHTML = '<li class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">Proje bulunamadı.</li>';
        return;
    }

    container.innerHTML = list.map(p => `
        <li class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
            <div class="px-4 py-4 sm:px-6">
                <div class="flex items-center justify-between">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="text-lg font-medium text-indigo-600 dark:text-indigo-400 truncate cursor-pointer hover:underline" onclick="viewProjectDetails('${p.id}')">${p.title}</h3>
                            <div class="flex space-x-2">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${getStatusColor(p.status)}">
                                    ${getStatusLabel(p.status)}
                                </span>
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${getPriorityColor(p.priority)}">
                                    ${getPriorityLabel(p.priority)}
                                </span>
                            </div>
                        </div>
                        <div class="mt-2 flex items-center text-sm text-gray-500 dark:text-gray-400 sm:mt-0">
                            <div class="flex-1 flex flex-col sm:flex-row sm:space-x-6">
                                <div class="flex items-center mt-1 sm:mt-0">
                                    <i class="far fa-calendar-alt flex-shrink-0 mr-1.5 text-gray-400"></i>
                                    <p>${p.start_date ? formatDate(p.start_date) : 'Tarih yok'} - ${p.end_date ? formatDate(p.end_date) : '?'}</p>
                                </div>
                                <div class="flex items-center mt-1 sm:mt-0">
                                    <i class="fas fa-chart-line flex-shrink-0 mr-1.5 text-gray-400"></i>
                                    <p>%${p.progress || 0}</p>
                                </div>
                                <div class="flex items-center mt-1 sm:mt-0">
                                    <i class="fas fa-coins flex-shrink-0 mr-1.5 text-gray-400"></i>
                                    <p>${p.budget ? parseFloat(p.budget).toLocaleString('tr-TR', {style: 'currency', currency: 'TRY'}) : '-'}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="ml-4 flex-shrink-0 flex items-center space-x-3">
                        <button onclick="editProject('${p.id}')" class="text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400">
                            <i class="fas fa-edit text-lg"></i>
                        </button>
                        <button onclick="deleteProject('${p.id}')" class="text-gray-400 hover:text-red-600 dark:hover:text-red-400">
                            <i class="fas fa-trash text-lg"></i>
                        </button>
                    </div>
                </div>
                <!-- Progress Bar -->
                <div class="mt-4 w-full bg-gray-200 dark:bg-gray-600 rounded-full h-2.5">
                    <div class="bg-indigo-600 h-2.5 rounded-full" style="width: ${p.progress || 0}%"></div>
                </div>
            </div>
        </li>
    `).join('');
}

async function handleProjectSubmit(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData.entries());
    const id = data.id;
    const url = id ? `/projects/${id}` : '/projects';
    const method = id ? 'PUT' : 'POST';

    const res = await apiCall(url, method, data);
    if (res && (res.success || res.data)) {
        showToast(id ? 'Proje güncellendi' : 'Proje oluşturuldu', 'success');
        closeModal('project-create-modal');
        loadProjects();
        loadStats();
    } else {
        showToast(res.error || 'Hata oluştu', 'error');
    }
}

function editProject(id) {
    const project = projects.find(p => p.id === id);
    if (project) {
        const form = document.querySelector('#project-create-modal form');
        form.reset();
        form.querySelector('[name=id]').value = project.id;
        form.querySelector('[name=title]').value = project.title;
        form.querySelector('[name=project_type]').value = project.project_type || '';
        form.querySelector('[name=status]').value = project.status || 'planning';
        form.querySelector('[name=start_date]').value = project.start_date ? project.start_date.split('T')[0] : '';
        form.querySelector('[name=end_date]').value = project.end_date ? project.end_date.split('T')[0] : '';
        form.querySelector('[name=priority]').value = project.priority || 'medium';
        form.querySelector('[name=budget]').value = project.budget || '';
        form.querySelector('[name=description]').value = project.description || '';
        
        document.getElementById('modal-title-project').textContent = 'Proje Düzenle';
        showModal('project-create-modal');
    }
}

async function deleteProject(id) {
    if(await showConfirm('Bu projeyi silmek istediğinize emin misiniz?')) {
        const res = await apiCall(`/projects/${id}`, 'DELETE');
        if (res && res.success) {
            showToast('Proje silindi', 'success');
            loadProjects();
            loadStats();
        } else {
            showToast('Silme başarısız', 'error');
        }
    }
}

function viewProjectDetails(id) {
    window.location.hash = `projects/${id}`;
}

function getStatusColor(status) {
    switch(status) {
        case 'completed': return 'bg-green-100 text-green-800';
        case 'active': return 'bg-blue-100 text-blue-800';
        case 'planning': return 'bg-purple-100 text-purple-800';
        case 'on_hold': return 'bg-yellow-100 text-yellow-800';
        case 'cancelled': return 'bg-red-100 text-red-800';
        default: return 'bg-gray-100 text-gray-800';
    }
}

function getStatusLabel(status) {
    const labels = { 
        'completed': 'Tamamlandı', 
        'active': 'Aktif', 
        'planning': 'Planlama', 
        'on_hold': 'Beklemede', 
        'cancelled': 'İptal' 
    };
    return labels[status] || status;
}

function getPriorityColor(priority) {
    switch(priority) {
        case 'urgent': return 'bg-red-200 text-red-900';
        case 'high': return 'bg-red-100 text-red-800';
        case 'medium': return 'bg-yellow-100 text-yellow-800';
        case 'low': return 'bg-green-100 text-green-800';
        default: return 'bg-gray-100 text-gray-800';
    }
}

function getPriorityLabel(priority) {
    const labels = { 'urgent': 'Acil', 'high': 'Yüksek', 'medium': 'Orta', 'low': 'Düşük' };
    return labels[priority] || priority;
}
