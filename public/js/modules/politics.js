import { apiCall } from '../api.js';
import { showToast, showModal, closeModal, showConfirm } from '../utils.js';

export async function init() {
    console.log('Politics Module Initialized');
    
    // Default to analytics tab
    await switchPoliticsTab('analytics');

    // Make functions available globally for onclick handlers in HTML
    window.switchPoliticsTab = switchPoliticsTab;
    window.showModal = showModal; // Re-expose for HTML onclicks
    
    // Attach event listeners for dynamic forms
    // Since modals are in index.html (global) or need to be injected, 
    // we need to make sure the modals exist.
    // Wait! Modals were in dashboard.html. We need to move them to views/politics.html OR
    // inject them into #modal-container.
    // Best practice for SPA: Load modals into DOM when module inits.
    
    injectModals();
}

function injectModals() {
    const modalContainer = document.getElementById('modal-container');
    // Check if already injected
    if (document.getElementById('politics-box-modal')) return;

    const modalsHTML = `
    <!-- Politics Box Modal -->
    <div id="politics-box-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center">
        <div class="bg-white dark:bg-gray-800 rounded-lg w-full max-w-lg mx-4 overflow-hidden shadow-xl transform transition-all">
            <div class="flex justify-between items-center px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white" id="modal-title-politics-box">Sandık Ekle</h3>
                <button onclick="closeModal('politics-box-modal')" class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form onsubmit="handlePoliticsBoxSubmit(event)" class="p-6 space-y-4">
                <input type="hidden" name="id">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Sandık No</label>
                        <input type="text" name="box_number" required class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white sm:text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Bölge</label>
                        <input type="text" name="region" class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white sm:text-sm">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Mahalle</label>
                    <input type="text" name="neighborhood" class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white sm:text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Okul Adı</label>
                    <input type="text" name="school_name" class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white sm:text-sm">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Görevli Adı</label>
                        <input type="text" name="official_name" class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white sm:text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Görevli Tel</label>
                        <input type="text" name="official_phone" class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white sm:text-sm">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Müşahit Adı</label>
                        <input type="text" name="observer_name" class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white sm:text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Müşahit Tel</label>
                        <input type="text" name="observer_phone" class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white sm:text-sm">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Seçmen Sayısı</label>
                    <input type="number" name="voter_count" value="0" class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white sm:text-sm">
                </div>
                <div class="flex justify-end pt-4">
                    <button type="button" onclick="closeModal('politics-box-modal')" class="bg-white dark:bg-gray-700 py-2 px-4 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-200 mr-3">İptal</button>
                    <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">Kaydet</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Politics Volunteer Modal -->
    <div id="politics-volunteer-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center">
        <div class="bg-white dark:bg-gray-800 rounded-lg w-full max-w-lg mx-4 overflow-hidden shadow-xl transform transition-all">
            <div class="flex justify-between items-center px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white" id="modal-title-politics-volunteer">Gönüllü Ekle</h3>
                <button onclick="closeModal('politics-volunteer-modal')" class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form onsubmit="handlePoliticsVolunteerSubmit(event)" class="p-6 space-y-4">
                <input type="hidden" name="id">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Ad Soyad</label>
                    <input type="text" name="full_name" required class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white sm:text-sm">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Telefon</label>
                        <input type="text" name="phone" class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white sm:text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">E-posta</label>
                        <input type="email" name="email" class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white sm:text-sm">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Rol</label>
                    <select name="role" class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white sm:text-sm">
                        <option value="observer">Müşahit</option>
                        <option value="field_worker">Saha Çalışanı</option>
                        <option value="coordinator">Koordinatör</option>
                        <option value="volunteer">Gönüllü</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Atanan Sandık</label>
                    <select name="assigned_box_id" class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white sm:text-sm">
                        <option value="">Sandık Seçin...</option>
                        <!-- Dynamically loaded -->
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Durum</label>
                    <select name="status" class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white sm:text-sm">
                        <option value="active">Aktif</option>
                        <option value="inactive">Pasif</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Notlar</label>
                    <textarea name="notes" rows="2" class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white sm:text-sm"></textarea>
                </div>
                <div class="flex justify-end pt-4">
                    <button type="button" onclick="closeModal('politics-volunteer-modal')" class="bg-white dark:bg-gray-700 py-2 px-4 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-200 mr-3">İptal</button>
                    <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">Kaydet</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Politics Voter Modal -->
    <div id="politics-voter-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center">
        <div class="bg-white dark:bg-gray-800 rounded-lg w-full max-w-lg mx-4 overflow-hidden shadow-xl transform transition-all">
            <div class="flex justify-between items-center px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white" id="modal-title-politics-voter">Seçmen Ekle</h3>
                <button onclick="closeModal('politics-voter-modal')" class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form onsubmit="handlePoliticsVoterSubmit(event)" class="p-6 space-y-4">
                <input type="hidden" name="id">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Ad Soyad</label>
                    <input type="text" name="full_name" required class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white sm:text-sm">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">TC Kimlik No</label>
                        <input type="text" name="tc_no" class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white sm:text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Telefon</label>
                        <input type="text" name="phone" class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white sm:text-sm">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Adres</label>
                    <input type="text" name="address" class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white sm:text-sm">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Mahalle</label>
                        <input type="text" name="neighborhood" class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white sm:text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Sandık</label>
                        <select name="box_id" class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white sm:text-sm">
                            <option value="">Sandık Seçin...</option>
                            <!-- Dynamically loaded -->
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Durum</label>
                    <select name="status" class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white sm:text-sm">
                        <option value="undecided">Kararsız</option>
                        <option value="decided">Kararlı (Bizim)</option>
                        <option value="potential">Potansiyel</option>
                        <option value="opponent">Rakip</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Notlar</label>
                    <textarea name="notes" rows="2" class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white sm:text-sm"></textarea>
                </div>
                <div class="flex justify-end pt-4">
                    <button type="button" onclick="closeModal('politics-voter-modal')" class="bg-white dark:bg-gray-700 py-2 px-4 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-200 mr-3">İptal</button>
                    <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">Kaydet</button>
                </div>
            </form>
        </div>
    </div>
    `;
    
    modalContainer.innerHTML += modalsHTML;

    // Attach form handlers to window so HTML onsubmit works
    window.handlePoliticsBoxSubmit = handlePoliticsBoxSubmit;
    window.handlePoliticsVolunteerSubmit = handlePoliticsVolunteerSubmit;
    window.handlePoliticsVoterSubmit = handlePoliticsVoterSubmit;
    window.editPoliticsBox = editPoliticsBox;
    window.deletePoliticsBox = deletePoliticsBox;
    window.editPoliticsVolunteer = editPoliticsVolunteer;
    window.deletePoliticsVolunteer = deletePoliticsVolunteer;
    window.editPoliticsVoter = editPoliticsVoter;
    window.deletePoliticsVoter = deletePoliticsVoter;
}

async function switchPoliticsTab(tab) {
    const tabs = ['voters', 'boxes', 'volunteers', 'analytics'];
    tabs.forEach(t => {
        const el = document.getElementById(`tab-politics-${t}`);
        if (el) {
            el.className = tab === t
                ? 'politics-tab border-indigo-500 text-indigo-600 whitespace-nowrap py-4 px-6 border-b-2 font-medium text-sm w-1/4 text-center transition-colors'
                : 'politics-tab border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-6 border-b-2 font-medium text-sm w-1/4 text-center transition-colors';
        }
        const content = document.getElementById(`politics-content-${t}`);
        if (content) {
            content.classList.toggle('hidden', tab !== t);
        }
    });

    if (tab === 'analytics') await loadPoliticsAnalytics();
    if (tab === 'boxes') await loadPoliticsBoxes();
    if (tab === 'volunteers') await loadPoliticsVolunteers();
    if (tab === 'voters') await loadPoliticsVoters();
}

async function loadPoliticsAnalytics() {
    const res = await apiCall('/politics/analytics');
    if (res && res.data) {
        setText('politics-stat-total', res.data.total_voters || '0');
        setText('politics-stat-target', '%' + (res.data.target_audience_percentage || '0'));
        setText('politics-stat-undecided', '%' + (res.data.undecided_percentage || '0'));
        setText('politics-stat-opponent', '%' + (res.data.opponent_percentage || '0'));
    }
}

async function loadPoliticsBoxes() {
    const res = await apiCall('/politics/boxes');
    if (!res || !res.data) return;

    const tbody = document.querySelector('#politics-boxes-table tbody');
    if (!tbody) return;
    
    tbody.innerHTML = res.data.map(item => `
        <tr>
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">${item.box_number}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                <div>${item.region || '-'} / ${item.neighborhood || '-'}</div>
                <div class="text-xs">${item.school_name || ''}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                <div>G: ${item.official_name || '-'}</div>
                <div>M: ${item.observer_name || '-'}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">${item.voter_count}</td>
            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                <button onclick="editPoliticsBox('${item.id}')" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300 mr-3"><i class="fas fa-edit"></i></button>
                <button onclick="deletePoliticsBox('${item.id}')" class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300"><i class="fas fa-trash"></i></button>
            </td>
        </tr>
    `).join('');

    if (res.data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">Sandık bulunamadı</td></tr>';
    }
}

async function handlePoliticsBoxSubmit(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData.entries());
    const id = data.id;
    const url = id ? `/politics/boxes/${id}` : '/politics/boxes';
    const method = id ? 'PUT' : 'POST';
    
    const res = await apiCall(url, method, data);
    if (res && (res.success || res.data)) {
        showToast(id ? 'Sandık güncellendi' : 'Sandık oluşturuldu', 'success');
        closeModal('politics-box-modal');
        loadPoliticsBoxes();
    } else {
        showToast(res.message || 'İşlem başarısız', 'error');
    }
}

async function deletePoliticsBox(id) {
    if(!await showConfirm('Bu sandığı silmek istediğinize emin misiniz?')) return;
    const res = await apiCall(`/politics/boxes/${id}`, 'DELETE');
    if(res && res.success) {
        showToast('Sandık silindi', 'success');
        loadPoliticsBoxes();
    } else {
        showToast(res.message || 'Silinemedi', 'error');
    }
}

async function editPoliticsBox(id) {
    const res = await apiCall('/politics/boxes');
    if (res && res.data) {
        const box = res.data.find(b => b.id === id);
        if (box) {
            const form = document.querySelector('#politics-box-modal form');
            form.reset();
            form.querySelector('[name="id"]').value = box.id;
            form.querySelector('[name="box_number"]').value = box.box_number;
            form.querySelector('[name="region"]').value = box.region || '';
            form.querySelector('[name="neighborhood"]').value = box.neighborhood || '';
            form.querySelector('[name="school_name"]').value = box.school_name || '';
            form.querySelector('[name="official_name"]').value = box.official_name || '';
            form.querySelector('[name="official_phone"]').value = box.official_phone || '';
            form.querySelector('[name="observer_name"]').value = box.observer_name || '';
            form.querySelector('[name="observer_phone"]').value = box.observer_phone || '';
            form.querySelector('[name="voter_count"]').value = box.voter_count;
            
            document.getElementById('modal-title-politics-box').textContent = 'Sandık Düzenle';
            showModal('politics-box-modal');
        }
    }
}

async function loadPoliticsVolunteers() {
    const res = await apiCall('/politics/volunteers');
    if (!res || !res.data) return;

    const tbody = document.querySelector('#politics-volunteers-table tbody');
    if (!tbody) return;

    tbody.innerHTML = res.data.map(item => `
        <tr>
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">${item.full_name}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                <div>${item.phone || '-'}</div>
                <div class="text-xs">${item.email || ''}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                ${item.role === 'observer' ? 'Müşahit' : 
                  item.role === 'field_worker' ? 'Saha Çalışanı' : 
                  item.role === 'coordinator' ? 'Koordinatör' : 'Gönüllü'}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                ${item.assigned_box_number ? 'Sandık: ' + item.assigned_box_number : '-'}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm">
                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${item.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}">
                    ${item.status === 'active' ? 'Aktif' : 'Pasif'}
                </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                <button onclick="editPoliticsVolunteer('${item.id}')" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300 mr-3"><i class="fas fa-edit"></i></button>
                <button onclick="deletePoliticsVolunteer('${item.id}')" class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300"><i class="fas fa-trash"></i></button>
            </td>
        </tr>
    `).join('');

    if (res.data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">Gönüllü bulunamadı</td></tr>';
    }
}

async function handlePoliticsVolunteerSubmit(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData.entries());
    const id = data.id;
    const url = id ? `/politics/volunteers/${id}` : '/politics/volunteers';
    const method = id ? 'PUT' : 'POST';
    
    const res = await apiCall(url, method, data);
    if (res && (res.success || res.data)) {
        showToast(id ? 'Gönüllü güncellendi' : 'Gönüllü eklendi', 'success');
        closeModal('politics-volunteer-modal');
        loadPoliticsVolunteers();
    } else {
        showToast(res.message || 'İşlem başarısız', 'error');
    }
}

async function deletePoliticsVolunteer(id) {
    if(!await showConfirm('Bu gönüllüyü silmek istediğinize emin misiniz?')) return;
    const res = await apiCall(`/politics/volunteers/${id}`, 'DELETE');
    if(res && res.success) {
        showToast('Gönüllü silindi', 'success');
        loadPoliticsVolunteers();
    } else {
        showToast(res.message || 'Silinemedi', 'error');
    }
}

async function editPoliticsVolunteer(id) {
    // Load boxes for dropdown
    const boxRes = await apiCall('/politics/boxes');
    const boxSelect = document.querySelector('#politics-volunteer-modal select[name="assigned_box_id"]');
    if(boxSelect) {
        boxSelect.innerHTML = '<option value="">Sandık Seçin...</option>';
        if (boxRes && boxRes.data) {
            boxRes.data.forEach(box => {
                boxSelect.innerHTML += `<option value="${box.id}">${box.box_number} - ${box.school_name || ''}</option>`;
            });
        }
    }

    // Fetch volunteer data
    const res = await apiCall('/politics/volunteers');
    if (res && res.data) {
        const volunteer = res.data.find(v => v.id === id);
        if (volunteer) {
            const form = document.querySelector('#politics-volunteer-modal form');
            form.reset();
            form.querySelector('[name="id"]').value = volunteer.id;
            form.querySelector('[name="full_name"]').value = volunteer.full_name;
            form.querySelector('[name="phone"]').value = volunteer.phone || '';
            form.querySelector('[name="email"]').value = volunteer.email || '';
            form.querySelector('[name="role"]').value = volunteer.role || 'volunteer';
            form.querySelector('[name="assigned_box_id"]').value = volunteer.assigned_box_id || '';
            form.querySelector('[name="status"]').value = volunteer.status || 'active';
            form.querySelector('[name="notes"]').value = volunteer.notes || '';
            
            document.getElementById('modal-title-politics-volunteer').textContent = 'Gönüllü Düzenle';
            showModal('politics-volunteer-modal');
        }
    }
}

async function loadPoliticsVoters() {
    const res = await apiCall('/politics/voters');
    if (!res || !res.data) return;

    const tbody = document.querySelector('#politics-voters-table tbody');
    if (!tbody) return;

    tbody.innerHTML = res.data.map(item => `
        <tr>
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">${item.full_name}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                <div>${item.phone || '-'}</div>
                <div class="text-xs">${item.tc_no || ''}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                <div>${item.address || '-'}</div>
                <div class="text-xs">${item.neighborhood || ''}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm">
                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                    ${item.status === 'decided' ? 'bg-green-100 text-green-800' : 
                      item.status === 'undecided' ? 'bg-yellow-100 text-yellow-800' : 
                      item.status === 'opponent' ? 'bg-red-100 text-red-800' : 'bg-blue-100 text-blue-800'}">
                    ${item.status === 'decided' ? 'Kararlı' : 
                      item.status === 'undecided' ? 'Kararsız' : 
                      item.status === 'opponent' ? 'Rakip' : 'Potansiyel'}
                </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                <button onclick="editPoliticsVoter('${item.id}')" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300 mr-3"><i class="fas fa-edit"></i></button>
                <button onclick="deletePoliticsVoter('${item.id}')" class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300"><i class="fas fa-trash"></i></button>
            </td>
        </tr>
    `).join('');

    if (res.data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">Seçmen bulunamadı</td></tr>';
    }
}

async function handlePoliticsVoterSubmit(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData.entries());
    const id = data.id;
    const url = id ? `/politics/voters/${id}` : '/politics/voters';
    const method = id ? 'PUT' : 'POST';
    
    const res = await apiCall(url, method, data);
    if (res && (res.success || res.data)) {
        showToast(id ? 'Seçmen güncellendi' : 'Seçmen eklendi', 'success');
        closeModal('politics-voter-modal');
        loadPoliticsVoters();
    } else {
        showToast(res.message || 'İşlem başarısız', 'error');
    }
}

async function deletePoliticsVoter(id) {
    if(!await showConfirm('Bu seçmeni silmek istediğinize emin misiniz?')) return;
    const res = await apiCall(`/politics/voters/${id}`, 'DELETE');
    if(res && res.success) {
        showToast('Seçmen silindi', 'success');
        loadPoliticsVoters();
    } else {
        showToast(res.message || 'Silinemedi', 'error');
    }
}

async function editPoliticsVoter(id) {
    // Load boxes for dropdown
    const boxRes = await apiCall('/politics/boxes');
    const boxSelect = document.querySelector('#politics-voter-modal select[name="box_id"]');
    if(boxSelect) {
        boxSelect.innerHTML = '<option value="">Sandık Seçin...</option>';
        if (boxRes && boxRes.data) {
            boxRes.data.forEach(box => {
                boxSelect.innerHTML += `<option value="${box.id}">${box.box_number} - ${box.school_name || ''}</option>`;
            });
        }
    }

    // Fetch voter data (using list endpoint since getOne is not implemented, or filter from list)
    // Better to fetch all or implement getOne. For now fetching list.
    const res = await apiCall('/politics/voters');
    if (res && res.data) {
        const voter = res.data.find(v => v.id === id);
        if (voter) {
            const form = document.querySelector('#politics-voter-modal form');
            form.reset();
            form.querySelector('[name="id"]').value = voter.id;
            form.querySelector('[name="full_name"]').value = voter.full_name;
            form.querySelector('[name="tc_no"]').value = voter.tc_no || '';
            form.querySelector('[name="phone"]').value = voter.phone || '';
            form.querySelector('[name="address"]').value = voter.address || '';
            form.querySelector('[name="neighborhood"]').value = voter.neighborhood || '';
            form.querySelector('[name="box_id"]').value = voter.box_id || '';
            form.querySelector('[name="status"]').value = voter.status || 'undecided';
            form.querySelector('[name="notes"]').value = voter.notes || '';
            
            document.getElementById('modal-title-politics-voter').textContent = 'Seçmen Düzenle';
            showModal('politics-voter-modal');
        }
    }
}

function setText(id, text) {
    const el = document.getElementById(id);
    if (el) el.textContent = text;
}
