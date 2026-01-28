import { apiCall } from '../api.js';
import { showToast, showModal, closeModal, showConfirm } from '../utils.js';

let crmCustomers = [];

export async function init() {
    console.log('CRM Module Initialized');
    
    // Make functions globally available for HTML onclicks
    window.switchCrmTab = switchCrmTab;
    window.editCrmCustomer = editCrmCustomer;
    window.deleteCrmCustomer = deleteCrmCustomer;
    window.editCrmDeal = editCrmDeal;
    window.deleteCrmDeal = deleteCrmDeal;
    window.editCrmActivity = editCrmActivity;
    window.deleteCrmActivity = deleteCrmActivity;
    window.handleCrmCustomerSubmit = handleCrmCustomerSubmit;
    window.handleCrmDealSubmit = handleCrmDealSubmit;
    window.handleCrmActivitySubmit = handleCrmActivitySubmit;
    window.loadCrmRelatedOptions = loadCrmRelatedOptions; // for onchange

    injectModals();
    
    // Load Stats first
    await loadCrmStats();
    
    // Default to customers tab
    await switchCrmTab('customers');
}

function injectModals() {
    const modalContainer = document.getElementById('modal-container');
    if (document.getElementById('crm-customer-modal')) return;

    const modalsHTML = `
    <!-- Customer Modal -->
    <div id="crm-customer-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center">
        <div class="bg-white dark:bg-gray-800 rounded-lg w-full max-w-lg mx-4 overflow-hidden shadow-xl transform transition-all">
            <div class="flex justify-between items-center px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white" id="modal-title-crm-customer">Müşteri Ekle</h3>
                <button onclick="closeModal('crm-customer-modal')" class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="crm-customer-form" onsubmit="handleCrmCustomerSubmit(event)" class="p-6 space-y-4">
                <input type="hidden" name="id">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Ad Soyad</label>
                    <input type="text" name="name" required class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white sm:text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Şirket</label>
                    <input type="text" name="company" class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white sm:text-sm">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">E-posta</label>
                        <input type="email" name="email" class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white sm:text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Telefon</label>
                        <input type="text" name="phone" class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white sm:text-sm">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Durum</label>
                    <select name="status" class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white sm:text-sm">
                        <option value="lead">Potansiyel (Lead)</option>
                        <option value="active">Aktif</option>
                        <option value="inactive">Pasif</option>
                    </select>
                </div>
                <div class="flex justify-end pt-4">
                    <button type="button" onclick="closeModal('crm-customer-modal')" class="bg-white dark:bg-gray-700 py-2 px-4 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-200 mr-3">İptal</button>
                    <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">Kaydet</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Deal Modal -->
    <div id="crm-deal-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center">
        <div class="bg-white dark:bg-gray-800 rounded-lg w-full max-w-lg mx-4 overflow-hidden shadow-xl transform transition-all">
            <div class="flex justify-between items-center px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white" id="modal-title-crm-deal">Fırsat Ekle</h3>
                <button onclick="closeModal('crm-deal-modal')" class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="crm-deal-form" onsubmit="handleCrmDealSubmit(event)" class="p-6 space-y-4">
                <input type="hidden" name="id">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Fırsat Adı</label>
                    <input type="text" name="title" required class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white sm:text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Müşteri</label>
                    <select name="customer_id" id="crm-deal-customer-select" class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white sm:text-sm">
                        <option value="">Seçiniz...</option>
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Değer (TL)</label>
                        <input type="number" name="value" step="0.01" class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white sm:text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Aşama</label>
                        <select name="stage" class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white sm:text-sm">
                            <option value="lead">Potansiyel</option>
                            <option value="proposal">Teklif</option>
                            <option value="negotiation">Pazarlık</option>
                            <option value="won">Kazanıldı</option>
                            <option value="lost">Kaybedildi</option>
                        </select>
                    </div>
                </div>
                <div class="flex justify-end pt-4">
                    <button type="button" onclick="closeModal('crm-deal-modal')" class="bg-white dark:bg-gray-700 py-2 px-4 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-200 mr-3">İptal</button>
                    <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">Kaydet</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Activity Modal -->
    <div id="crm-activity-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center">
        <div class="bg-white dark:bg-gray-800 rounded-lg w-full max-w-lg mx-4 overflow-hidden shadow-xl transform transition-all">
            <div class="flex justify-between items-center px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Aktivite Ekle</h3>
                <button onclick="closeModal('crm-activity-modal')" class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="crm-activity-form" onsubmit="handleCrmActivitySubmit(event)" class="p-6 space-y-4">
                <input type="hidden" name="id">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Konu</label>
                    <input type="text" name="subject" required class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white sm:text-sm">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tip</label>
                        <select name="type" class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white sm:text-sm">
                            <option value="call">Arama</option>
                            <option value="meeting">Toplantı</option>
                            <option value="email">E-posta</option>
                            <option value="note">Not</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tarih</label>
                        <input type="datetime-local" name="date" required class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white sm:text-sm">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">İlgili Kayıt</label>
                    <div class="flex space-x-2">
                        <select name="related_to_type" id="crm-activity-related-type" onchange="loadCrmRelatedOptions()" class="mt-1 block w-1/3 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white sm:text-sm">
                            <option value="customer">Müşteri</option>
                            <option value="deal">Fırsat</option>
                        </select>
                        <select name="related_to_id" id="crm-activity-related-id" class="mt-1 block w-2/3 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white sm:text-sm">
                            <option value="">Seçiniz...</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Açıklama</label>
                    <textarea name="description" rows="2" class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white sm:text-sm"></textarea>
                </div>
                <div class="flex justify-end pt-4">
                    <button type="button" onclick="closeModal('crm-activity-modal')" class="bg-white dark:bg-gray-700 py-2 px-4 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-200 mr-3">İptal</button>
                    <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">Kaydet</button>
                </div>
            </form>
        </div>
    </div>
    `;
    
    modalContainer.innerHTML += modalsHTML;
}

async function switchCrmTab(tab) {
    const tabs = ['customers', 'deals', 'activities'];
    tabs.forEach(t => {
        const el = document.getElementById(`tab-crm-${t}`);
        if (el) {
            el.className = tab === t
                ? 'crm-tab border-indigo-500 text-indigo-600 whitespace-nowrap py-4 px-6 border-b-2 font-medium text-sm w-1/3 text-center transition-colors'
                : 'crm-tab border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-6 border-b-2 font-medium text-sm w-1/3 text-center transition-colors';
        }
        const content = document.getElementById(`crm-content-${t}`);
        if (content) {
            content.classList.toggle('hidden', tab !== t);
        }
    });

    if (tab === 'customers') await loadCrmCustomers();
    if (tab === 'deals') await loadCrmDeals();
    if (tab === 'activities') await loadCrmActivities();
}

async function loadCrmStats() {
    const data = await apiCall('/crm/stats');
    if (data && data.data) {
         setText('crm-total-customers', data.data.total_customers);
         setText('crm-pipeline-value', '₺' + parseFloat(data.data.total_pipeline_value).toFixed(2));
         
         // Calculate open deals count
         const openDeals = data.data.pipeline.reduce((acc, curr) => {
             if (curr.stage !== 'won' && curr.stage !== 'lost') return acc + curr.count;
             return acc;
         }, 0);
         setText('crm-open-deals', openDeals);
    }
}

async function loadCrmCustomers() {
    const data = await apiCall('/crm/customers');
    if (!data || !data.data || !data.data.items) return;
    
    crmCustomers = data.data.items; // Store for select options

    // Populate Select in Deal Modal
    const dealCustSelect = document.getElementById('crm-deal-customer-select');
    if(dealCustSelect) {
        dealCustSelect.innerHTML = '<option value="">Seçiniz...</option>' + 
            crmCustomers.map(c => `<option value="${c.id}">${c.name}</option>`).join('');
    }

    const tbody = document.querySelector('#crm-customers-table tbody');
    if (!tbody) return;

    tbody.innerHTML = crmCustomers.map(item => `
        <tr>
            <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-gray-900 dark:text-white">${item.name}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">${item.company || '-'}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                <div>${item.email || ''}</div>
                <div>${item.phone || ''}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${item.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'}">
                    ${item.status}
                </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                <button onclick="editCrmCustomer('${item.id}')" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300 mr-3"><i class="fas fa-edit"></i></button>
                <button onclick="deleteCrmCustomer('${item.id}')" class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300"><i class="fas fa-trash"></i></button>
            </td>
        </tr>
    `).join('');
    
     if (crmCustomers.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">Müşteri bulunamadı</td></tr>';
    }
}

async function loadCrmDeals() {
    const data = await apiCall('/crm/deals');
    if (!data || !data.data) return;

    const tbody = document.querySelector('#crm-deals-table tbody');
    if (!tbody) return;

    tbody.innerHTML = data.data.map(item => `
        <tr>
            <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-gray-900 dark:text-white">${item.title}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">${item.customer_name || '-'}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">₺${parseFloat(item.value).toFixed(2)}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">${item.stage}</td>
            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                <button onclick="editCrmDeal('${item.id}')" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300 mr-3"><i class="fas fa-edit"></i></button>
                <button onclick="deleteCrmDeal('${item.id}')" class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300"><i class="fas fa-trash"></i></button>
            </td>
        </tr>
    `).join('');
    
    if (data.data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">Fırsat bulunamadı</td></tr>';
    }
}

async function loadCrmActivities() {
    const data = await apiCall('/crm/activities');
    if (!data || !data.data) return;

    const tbody = document.querySelector('#crm-activities-table tbody');
    if (!tbody) return;

    tbody.innerHTML = data.data.map(item => {
        let typeLabel = item.type;
        if(item.type === 'call') typeLabel = 'Arama';
        if(item.type === 'meeting') typeLabel = 'Toplantı';
        if(item.type === 'email') typeLabel = 'E-posta';
        if(item.type === 'note') typeLabel = 'Not';

        let statusBadge = item.status === 'completed' 
            ? '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Tamamlandı</span>'
            : '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">Planlandı</span>';

        return `
        <tr>
            <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-gray-900 dark:text-white">${item.subject}</div>
                <div class="text-xs text-gray-500 dark:text-gray-400">${item.description || ''}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">${typeLabel}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                ${item.related_to_type === 'customer' ? 'Müşteri' : 'Fırsat'}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">${new Date(item.date).toLocaleString('tr-TR')}</td>
            <td class="px-6 py-4 whitespace-nowrap">
                ${statusBadge}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                <button onclick="editCrmActivity('${item.id}')" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300 mr-3"><i class="fas fa-edit"></i></button>
                <button onclick="deleteCrmActivity('${item.id}')" class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300"><i class="fas fa-trash"></i></button>
            </td>
        </tr>
    `}).join('');
    
    if (data.data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">Aktivite bulunamadı</td></tr>';
    }
}

// --- Handlers ---

async function handleCrmCustomerSubmit(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData.entries());
    const id = data.id;
    const url = id ? `/crm/customers/${id}` : '/crm/customers';
    const method = id ? 'PUT' : 'POST';
    
    const res = await apiCall(url, method, data);
    if (res && (res.success || res.data)) {
        showToast(id ? 'Müşteri güncellendi' : 'Müşteri eklendi', 'success');
        closeModal('crm-customer-modal');
        loadCrmCustomers();
        loadCrmStats();
    } else {
        showToast(res.error || 'Hata oluştu', 'error');
    }
}

async function handleCrmDealSubmit(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData.entries());
    const id = data.id;
    const url = id ? `/crm/deals/${id}` : '/crm/deals';
    const method = id ? 'PUT' : 'POST';
    
    const res = await apiCall(url, method, data);
    if (res && (res.success || res.data)) {
        showToast(id ? 'Fırsat güncellendi' : 'Fırsat eklendi', 'success');
        closeModal('crm-deal-modal');
        loadCrmDeals();
        loadCrmStats();
    } else {
        showToast(res.error || 'Hata oluştu', 'error');
    }
}

async function handleCrmActivitySubmit(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData.entries());
    const id = data.id;
    const url = id ? `/crm/activities/${id}` : '/crm/activities';
    const method = id ? 'PUT' : 'POST';
    
    const res = await apiCall(url, method, data);
    if (res && (res.success || res.data)) {
        showToast('Aktivite kaydedildi', 'success');
        closeModal('crm-activity-modal');
        loadCrmActivities();
    } else {
        showToast(res.error || 'Hata oluştu', 'error');
    }
}

async function deleteCrmCustomer(id) {
    if(await showConfirm('Bu müşteriyi silmek istediğinize emin misiniz?')) {
        const res = await apiCall(`/crm/customers/${id}`, 'DELETE');
        if (res && res.success) {
            showToast('Müşteri silindi', 'success');
            loadCrmCustomers();
            loadCrmStats();
        } else {
             showToast(res.error || 'Silme başarısız', 'error');
        }
    }
}

async function deleteCrmDeal(id) {
    if(await showConfirm('Bu fırsatı silmek istediğinize emin misiniz?')) {
        const res = await apiCall(`/crm/deals/${id}`, 'DELETE');
        if (res && res.success) {
            showToast('Fırsat silindi', 'success');
            loadCrmDeals();
            loadCrmStats();
        } else {
             showToast(res.error || 'Silme başarısız', 'error');
        }
    }
}

async function deleteCrmActivity(id) {
    if(await showConfirm('Bu aktiviteyi silmek istediğinize emin misiniz?')) {
        const res = await apiCall(`/crm/activities/${id}`, 'DELETE');
        if (res && res.success) {
            showToast('Aktivite silindi', 'success');
            loadCrmActivities();
        } else {
             showToast(res.error || 'Silme başarısız', 'error');
        }
    }
}

function editCrmCustomer(id) {
    const customer = crmCustomers.find(c => c.id === id);
    if (customer) {
        const form = document.querySelector('#crm-customer-modal form');
        form.reset();
        form.querySelector('[name=id]').value = customer.id;
        form.querySelector('[name=name]').value = customer.name;
        form.querySelector('[name=company]').value = customer.company || '';
        form.querySelector('[name=email]').value = customer.email || '';
        form.querySelector('[name=phone]').value = customer.phone || '';
        form.querySelector('[name=status]').value = customer.status || 'lead';
        
        document.getElementById('modal-title-crm-customer').textContent = 'Müşteri Düzenle';
        showModal('crm-customer-modal');
    }
}

async function editCrmDeal(id) {
    const res = await apiCall('/crm/deals');
    if (!res || !res.data) return;
    const deal = res.data.find(d => d.id === id);
    if (deal) {
        const form = document.querySelector('#crm-deal-modal form');
        form.reset();
        form.querySelector('[name=id]').value = deal.id;
        form.querySelector('[name=title]').value = deal.title;
        form.querySelector('[name=customer_id]').value = deal.customer_id || '';
        form.querySelector('[name=value]').value = deal.value || '';
        form.querySelector('[name=stage]').value = deal.stage || 'lead';
        
        document.getElementById('modal-title-crm-deal').textContent = 'Fırsat Düzenle';
        showModal('crm-deal-modal');
    }
}

async function editCrmActivity(id) {
    const res = await apiCall('/crm/activities');
    if (!res || !res.data) return;
    const act = res.data.find(a => a.id === id);
    if (act) {
        const form = document.querySelector('#crm-activity-modal form');
        form.reset();
        form.querySelector('[name=id]').value = act.id;
        form.querySelector('[name=subject]').value = act.subject;
        form.querySelector('[name=type]').value = act.type || 'call';
        form.querySelector('[name=date]').value = act.date ? act.date.slice(0,16) : ''; // datetime-local format
        form.querySelector('[name=related_to_type]').value = act.related_to_type || 'customer';
        form.querySelector('[name=description]').value = act.description || '';
        
        // Load related options then select
        await loadCrmRelatedOptions();
        setTimeout(() => {
             form.querySelector('[name=related_to_id]').value = act.related_to_id || '';
        }, 200);

        showModal('crm-activity-modal');
    }
}

async function loadCrmRelatedOptions() {
    const type = document.getElementById('crm-activity-related-type').value;
    const select = document.getElementById('crm-activity-related-id');
    select.innerHTML = '<option>Yükleniyor...</option>';
    
    let items = [];
    if (type === 'customer') {
        // Reuse existing data if possible or fetch
        if(crmCustomers.length > 0) {
            items = crmCustomers;
        } else {
            const data = await apiCall('/crm/customers');
            if (data && data.data && data.data.items) items = data.data.items;
        }
        select.innerHTML = items.map(i => `<option value="${i.id}">${i.name}</option>`).join('');
    } else if (type === 'deal') {
        const data = await apiCall('/crm/deals');
        if (data && data.data) items = data.data;
        select.innerHTML = items.map(i => `<option value="${i.id}">${i.title}</option>`).join('');
    }
}

function setText(id, text) {
    const el = document.getElementById(id);
    if (el) el.textContent = text;
}



