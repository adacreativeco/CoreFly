import { apiCall } from '../api.js';
import { showToast, showModal, closeModal, showConfirm, formatDate } from '../utils.js';

let helpdeskTickets = [];
let helpdeskCategories = [];

export async function init() {
    console.log('Helpdesk Module Initialized');

    // Make functions globally available
    window.filterHelpdeskTickets = filterHelpdeskTickets;
    window.handleHelpdeskTicketSubmit = handleHelpdeskTicketSubmit;
    window.viewHelpdeskTicket = viewHelpdeskTicket;
    window.handleHelpdeskMessageSubmit = handleHelpdeskMessageSubmit;
    window.updateHelpdeskTicketStatus = updateHelpdeskTicketStatus;

    injectModals();
    await loadHelpdeskCategories();
    await loadHelpdeskTickets();
}

function injectModals() {
    const modalContainer = document.getElementById('modal-container');
    if (document.getElementById('helpdesk-ticket-modal')) return;

    const modalsHTML = `
    <!-- Create Ticket Modal -->
    <div id="helpdesk-ticket-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center">
        <div class="bg-white dark:bg-gray-800 rounded-lg w-full max-w-lg mx-4 overflow-hidden shadow-xl transform transition-all">
            <div class="flex justify-between items-center px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Yeni Destek Talebi</h3>
                <button onclick="closeModal('helpdesk-ticket-modal')" class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form onsubmit="handleHelpdeskTicketSubmit(event)" class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Kategori</label>
                    <select name="category_id" id="helpdesk-category-select" required class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white sm:text-sm">
                        <option value="">Seçiniz...</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Konu</label>
                    <input type="text" name="subject" required class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white sm:text-sm">
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
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Açıklama</label>
                    <textarea name="description" rows="4" required class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white sm:text-sm"></textarea>
                </div>
                <div class="flex justify-end pt-4">
                    <button type="button" onclick="closeModal('helpdesk-ticket-modal')" class="bg-white dark:bg-gray-700 py-2 px-4 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-200 mr-3">İptal</button>
                    <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">Gönder</button>
                </div>
            </form>
        </div>
    </div>

    <!-- View Ticket Modal -->
    <div id="helpdesk-view-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center">
        <div class="bg-white dark:bg-gray-800 rounded-lg w-full max-w-3xl mx-4 overflow-hidden shadow-xl transform transition-all h-[90vh] flex flex-col">
            <div class="flex justify-between items-center px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <div>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white" id="view-ticket-subject">Konu</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400" id="view-ticket-meta">#ID - Durum</p>
                </div>
                <div class="flex items-center space-x-2">
                    <select id="view-ticket-status-select" onchange="updateHelpdeskTicketStatus(this.value)" class="text-sm border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white">
                        <option value="open">Açık</option>
                        <option value="in_progress">İşleniyor</option>
                        <option value="resolved">Çözüldü</option>
                        <option value="closed">Kapalı</option>
                    </select>
                    <button onclick="closeModal('helpdesk-view-modal')" class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300 ml-4">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            
            <div class="flex-1 overflow-y-auto p-6 space-y-6 bg-gray-50 dark:bg-gray-900" id="view-ticket-messages">
                <!-- Messages loaded here -->
            </div>

            <div class="p-4 border-t border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
                <form onsubmit="handleHelpdeskMessageSubmit(event)" class="flex space-x-4">
                    <input type="hidden" name="ticket_id" id="view-ticket-id">
                    <div class="flex-1">
                        <textarea name="message" rows="2" placeholder="Bir yanıt yazın..." required class="block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white sm:text-sm focus:ring-indigo-500 focus:border-indigo-500"></textarea>
                    </div>
                    <button type="submit" class="inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 self-end">
                        <i class="fas fa-paper-plane mr-2"></i> Gönder
                    </button>
                </form>
            </div>
        </div>
    </div>
    `;

    modalContainer.innerHTML += modalsHTML;
}

async function loadHelpdeskCategories() {
    const res = await apiCall('/helpdesk/categories');
    if (res && res.data) {
        helpdeskCategories = res.data;
        const select = document.getElementById('helpdesk-category-select');
        if (select) {
            select.innerHTML = '<option value="">Seçiniz...</option>' + 
                res.data.map(c => `<option value="${c.id}">${c.name}</option>`).join('');
        }
    }
}

async function loadHelpdeskTickets() {
    const res = await apiCall('/helpdesk/tickets');
    if (res && res.data) {
        helpdeskTickets = res.data; // Store for client-side filtering
        renderHelpdeskTickets(helpdeskTickets);
    }
}

function renderHelpdeskTickets(tickets) {
    const list = document.getElementById('helpdesk-ticket-list');
    if (!list) return;

    if (tickets.length === 0) {
        list.innerHTML = '<li class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">Kayıt bulunamadı.</li>';
        return;
    }

    list.innerHTML = tickets.map(ticket => `
        <li>
            <a href="#" onclick="viewHelpdeskTicket('${ticket.id}'); return false;" class="block hover:bg-gray-50 dark:hover:bg-gray-700 transition duration-150 ease-in-out">
                <div class="px-4 py-4 sm:px-6">
                    <div class="flex items-center justify-between">
                        <div class="text-sm font-medium text-indigo-600 dark:text-indigo-400 truncate">
                            ${ticket.subject}
                            <span class="ml-2 text-xs text-gray-500 dark:text-gray-400">#${ticket.id.slice(0, 8)}</span>
                        </div>
                        <div class="ml-2 flex-shrink-0 flex">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${getStatusColor(ticket.status)}">
                                ${getStatusLabel(ticket.status)}
                            </span>
                        </div>
                    </div>
                    <div class="mt-2 sm:flex sm:justify-between">
                        <div class="sm:flex">
                            <p class="flex items-center text-sm text-gray-500 dark:text-gray-400 mr-6">
                                <i class="fas fa-folder mr-1.5 text-gray-400"></i>
                                ${ticket.category_name || 'Genel'}
                            </p>
                            <p class="flex items-center text-sm text-gray-500 dark:text-gray-400">
                                <i class="fas fa-flag mr-1.5 ${getPriorityColor(ticket.priority)}"></i>
                                ${getPriorityLabel(ticket.priority)}
                            </p>
                        </div>
                        <div class="mt-2 flex items-center text-sm text-gray-500 dark:text-gray-400 sm:mt-0">
                            <i class="far fa-clock mr-1.5 text-gray-400"></i>
                            <p>Son güncelleme: ${formatDate(ticket.updated_at)}</p>
                        </div>
                    </div>
                </div>
            </a>
        </li>
    `).join('');
}

function filterHelpdeskTickets() {
    const search = document.getElementById('helpdesk-search').value.toLowerCase();
    const status = document.getElementById('helpdesk-status-filter').value;
    const priority = document.getElementById('helpdesk-priority-filter').value;

    const filtered = helpdeskTickets.filter(t => {
        const matchesSearch = t.subject.toLowerCase().includes(search) || t.id.includes(search);
        const matchesStatus = status === 'all' || t.status === status;
        const matchesPriority = priority === 'all' || t.priority === priority;
        return matchesSearch && matchesStatus && matchesPriority;
    });

    renderHelpdeskTickets(filtered);
}

async function handleHelpdeskTicketSubmit(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData.entries());
    
    const res = await apiCall('/helpdesk/tickets', 'POST', data);
    if (res && (res.success || res.data)) {
        showToast('Destek talebi oluşturuldu', 'success');
        closeModal('helpdesk-ticket-modal');
        loadHelpdeskTickets();
    } else {
        showToast(res.error || 'Hata oluştu', 'error');
    }
}

async function viewHelpdeskTicket(id) {
    const ticket = helpdeskTickets.find(t => t.id === id);
    if (!ticket) return;

    // Fill Modal Header
    document.getElementById('view-ticket-subject').textContent = ticket.subject;
    document.getElementById('view-ticket-meta').textContent = `#${ticket.id} - ${formatDate(ticket.created_at)}`;
    document.getElementById('view-ticket-id').value = ticket.id;
    document.getElementById('view-ticket-status-select').value = ticket.status;

    // Load Messages (Full details)
    const res = await apiCall(`/helpdesk/tickets/${id}`);
    if (res && res.data) {
        const fullTicket = res.data;
        const messagesContainer = document.getElementById('view-ticket-messages');
        
        // First message is the ticket description
        let html = `
            <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="flex justify-between items-start mb-2">
                    <div class="flex items-center">
                        <span class="font-medium text-gray-900 dark:text-white">${fullTicket.user_name || 'Kullanıcı'}</span>
                        <span class="ml-2 text-xs text-gray-500 dark:text-gray-400">Oluşturdu</span>
                    </div>
                    <span class="text-xs text-gray-500 dark:text-gray-400">${formatDate(fullTicket.created_at)}</span>
                </div>
                <p class="text-gray-700 dark:text-gray-300 whitespace-pre-wrap">${fullTicket.description}</p>
            </div>
        `;

        // Append other messages if any
        if (fullTicket.messages && fullTicket.messages.length > 0) {
            html += fullTicket.messages.map(msg => `
                <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 ${msg.user_id === fullTicket.user_id ? '' : 'border-l-4 border-indigo-500'}">
                    <div class="flex justify-between items-start mb-2">
                        <div class="flex items-center">
                            <span class="font-medium text-gray-900 dark:text-white">${msg.user_name}</span>
                            ${msg.user_id !== fullTicket.user_id ? '<span class="ml-2 px-2 py-0.5 rounded bg-indigo-100 text-indigo-800 text-xs">Destek Ekibi</span>' : ''}
                        </div>
                        <span class="text-xs text-gray-500 dark:text-gray-400">${formatDate(msg.created_at)}</span>
                    </div>
                    <p class="text-gray-700 dark:text-gray-300 whitespace-pre-wrap">${msg.message}</p>
                </div>
            `).join('');
        }

        messagesContainer.innerHTML = html;
        showModal('helpdesk-view-modal');
        
        // Scroll to bottom
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }
}

async function handleHelpdeskMessageSubmit(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData.entries());
    const id = data.ticket_id;

    const res = await apiCall(`/helpdesk/tickets/${id}/messages`, 'POST', { message: data.message });
    if (res && (res.success || res.data)) {
        e.target.reset();
        // Refresh view
        viewHelpdeskTicket(id);
        // Also refresh list to update timestamp
        loadHelpdeskTickets();
    } else {
        showToast(res.error || 'Mesaj gönderilemedi', 'error');
    }
}

async function updateHelpdeskTicketStatus(status) {
    const id = document.getElementById('view-ticket-id').value;
    const res = await apiCall(`/helpdesk/tickets/${id}`, 'PUT', { status });
    if (res && res.success) {
        showToast('Durum güncellendi', 'success');
        loadHelpdeskTickets();
    } else {
        showToast(res.error || 'Güncelleme başarısız', 'error');
    }
}

function getStatusColor(status) {
    switch (status) {
        case 'open': return 'bg-green-100 text-green-800';
        case 'in_progress': return 'bg-blue-100 text-blue-800';
        case 'resolved': return 'bg-purple-100 text-purple-800';
        case 'closed': return 'bg-gray-100 text-gray-800';
        default: return 'bg-gray-100 text-gray-800';
    }
}

function getStatusLabel(status) {
    const labels = {
        'open': 'Açık',
        'in_progress': 'İşleniyor',
        'resolved': 'Çözüldü',
        'closed': 'Kapalı'
    };
    return labels[status] || status;
}

function getPriorityColor(priority) {
    switch (priority) {
        case 'high': return 'text-red-500';
        case 'medium': return 'text-yellow-500';
        case 'low': return 'text-green-500';
        default: return 'text-gray-500';
    }
}

function getPriorityLabel(priority) {
    const labels = {
        'high': 'Yüksek',
        'medium': 'Orta',
        'low': 'Düşük'
    };
    return labels[priority] || priority;
}



