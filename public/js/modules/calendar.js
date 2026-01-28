import { apiCall } from '../api.js';
import { formatDate, showToast, showConfirm, showModal, closeModal } from '../utils.js';

let events = [];
let currentDate = new Date();

export async function init() {
    console.log('Calendar Module Initialized');
    
    // Bind global functions
    window.openEventModal = openEventModal;
    window.handleDayClick = handleDayClick;
    window.showModal = showModal;
    window.closeModal = closeModal;

    // Initial Load
    await loadEvents();
    renderCalendar();

    // Event Listeners
    document.getElementById('prev-month').addEventListener('click', () => changeMonth(-1));
    document.getElementById('next-month').addEventListener('click', () => changeMonth(1));
    document.getElementById('event-form').addEventListener('submit', handleEventSubmit);
    document.getElementById('btn-delete-event').addEventListener('click', deleteCurrentEvent);
}

async function loadEvents() {
    try {
        // Fetch events for current month window (plus padding)
        // In a real app, you might pass start/end dates to API
        const res = await apiCall('/events');
        if (res && res.data) {
            events = res.data.items || res.data;
            renderUpcomingEvents();
        }
    } catch (error) {
        console.error('Failed to load events:', error);
        showToast('Etkinlikler yüklenemedi', 'error');
    }
}

function renderCalendar() {
    const grid = document.getElementById('calendar-grid');
    const label = document.getElementById('current-month-label');
    
    const year = currentDate.getFullYear();
    const month = currentDate.getMonth();

    // Update Label
    label.textContent = currentDate.toLocaleDateString('tr-TR', { month: 'long', year: 'numeric' });

    // Calculate Days
    const firstDay = new Date(year, month, 1);
    const lastDay = new Date(year, month + 1, 0);
    const daysInMonth = lastDay.getDate();
    
    // Adjust for Monday start (0=Sun, 1=Mon) -> (1=Mon ... 7=Sun)
    let startDay = firstDay.getDay() || 7; 
    startDay--; // Make 0=Mon, 6=Sun for grid calculation

    grid.innerHTML = '';

    // Empty cells before first day
    for (let i = 0; i < startDay; i++) {
        grid.innerHTML += `<div class="bg-gray-50 dark:bg-gray-900 border-b border-r border-gray-200 dark:border-gray-700 min-h-[100px]"></div>`;
    }

    // Days
    for (let day = 1; day <= daysInMonth; day++) {
        const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
        const dayEvents = events.filter(e => e.start_date.startsWith(dateStr));
        const isToday = new Date().toDateString() === new Date(year, month, day).toDateString();

        let eventsHtml = dayEvents.map(ev => `
            <div onclick="openEventModal('${ev.id}'); event.stopPropagation();" 
                 class="mx-1 mb-1 px-2 py-1 text-xs rounded truncate cursor-pointer hover:opacity-80 transition-opacity ${getEventColor(ev.type)}">
                ${new Date(ev.start_date).toLocaleTimeString('tr-TR', {hour:'2-digit', minute:'2-digit'})} ${ev.title}
            </div>
        `).join('');

        grid.innerHTML += `
            <div onclick="handleDayClick('${dateStr}')" class="bg-white dark:bg-gray-800 border-b border-r border-gray-200 dark:border-gray-700 min-h-[100px] relative hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors cursor-pointer group">
                <span class="absolute top-2 right-2 text-sm font-medium ${isToday ? 'bg-indigo-600 text-white w-7 h-7 flex items-center justify-center rounded-full' : 'text-gray-700 dark:text-gray-300'}">
                    ${day}
                </span>
                <div class="pt-8 pb-1">
                    ${eventsHtml}
                </div>
                <div class="absolute bottom-1 right-2 opacity-0 group-hover:opacity-100 transition-opacity">
                    <i class="fas fa-plus text-indigo-400"></i>
                </div>
            </div>
        `;
    }
}

function renderUpcomingEvents() {
    const list = document.getElementById('upcoming-events-list');
    
    // Filter future events and sort
    const now = new Date();
    const upcoming = events
        .filter(e => new Date(e.start_date) >= now)
        .sort((a, b) => new Date(a.start_date) - new Date(b.start_date))
        .slice(0, 5);

    if (upcoming.length === 0) {
        list.innerHTML = '<p class="text-center text-gray-500 text-sm">Yaklaşan etkinlik yok</p>';
        return;
    }

    list.innerHTML = upcoming.map(ev => `
        <div class="flex items-start space-x-3 p-2 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg cursor-pointer" onclick="openEventModal('${ev.id}')">
            <div class="flex-shrink-0 w-10 text-center bg-gray-100 dark:bg-gray-900 rounded p-1">
                <div class="text-xs font-bold text-indigo-600">${new Date(ev.start_date).toLocaleString('tr-TR', { month: 'short' })}</div>
                <div class="text-lg font-bold text-gray-900 dark:text-white">${new Date(ev.start_date).getDate()}</div>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-900 dark:text-white truncate">${ev.title}</p>
                <p class="text-xs text-gray-500">${new Date(ev.start_date).toLocaleTimeString('tr-TR', {hour:'2-digit', minute:'2-digit'})}</p>
            </div>
        </div>
    `).join('');
}

function changeMonth(delta) {
    currentDate.setMonth(currentDate.getMonth() + delta);
    renderCalendar();
}

function handleDayClick(dateStr) {
    // Open modal for new event on this date
    document.getElementById('event-form').reset();
    document.getElementById('event-id').value = '';
    
    // Set default start/end times
    const start = new Date(dateStr);
    start.setHours(9, 0);
    const end = new Date(dateStr);
    end.setHours(10, 0);

    // Format for datetime-local input (YYYY-MM-DDTHH:mm)
    document.getElementById('event-start').value = toLocalISOString(start);
    document.getElementById('event-end').value = toLocalISOString(end);
    
    document.getElementById('btn-delete-event').classList.add('hidden');
    showModal('event-modal');
}

async function openEventModal(id) {
    const ev = events.find(e => e.id == id); // Loose equality for string/int match
    if (!ev) return;

    document.getElementById('event-id').value = ev.id;
    document.getElementById('event-title').value = ev.title;
    document.getElementById('event-desc').value = ev.description || '';
    document.getElementById('event-type').value = ev.type;
    document.getElementById('event-start').value = ev.start_date; // Assuming API returns ISO format
    document.getElementById('event-end').value = ev.end_date;

    document.getElementById('btn-delete-event').classList.remove('hidden');
    showModal('event-modal');
}

async function handleEventSubmit(e) {
    e.preventDefault();
    
    const id = document.getElementById('event-id').value;
    const data = {
        title: document.getElementById('event-title').value,
        description: document.getElementById('event-desc').value,
        type: document.getElementById('event-type').value,
        start_date: document.getElementById('event-start').value,
        end_date: document.getElementById('event-end').value
    };

    try {
        const url = id ? `/events/${id}` : '/events';
        const method = id ? 'PUT' : 'POST';

        await apiCall(url, method, data);
        showToast(id ? 'Etkinlik güncellendi' : 'Etkinlik oluşturuldu', 'success');
        closeModal('event-modal');
        await loadEvents();
        renderCalendar();
    } catch (error) {
        showToast('İşlem başarısız', 'error');
        console.error(error);
    }
}

async function deleteCurrentEvent() {
    const id = document.getElementById('event-id').value;
    if (!id) return;
    
    if (!await showConfirm('Bu etkinliği silmek istediğinize emin misiniz?')) return;

    try {
        await apiCall(`/events/${id}`, 'DELETE');
        showToast('Etkinlik silindi', 'success');
        closeModal('event-modal');
        await loadEvents();
        renderCalendar();
    } catch (error) {
        showToast('Silme işlemi başarısız', 'error');
    }
}

function getEventColor(type) {
    switch(type) {
        case 'meeting': return 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200';
        case 'task': return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200';
        case 'reminder': return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200';
        default: return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200';
    }
}

// Helper to handle timezone offset for input[type="datetime-local"]
function toLocalISOString(date) {
    const pad = (num) => (num < 10 ? '0' : '') + num;
    return date.getFullYear() +
        '-' + pad(date.getMonth() + 1) +
        '-' + pad(date.getDate()) +
        'T' + pad(date.getHours()) +
        ':' + pad(date.getMinutes());
}


