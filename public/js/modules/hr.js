import { apiCall } from '../api.js';
import { showToast, showModal, closeModal, showConfirm } from '../utils.js';

let hrEmployees = [];
let hrDepartments = [];

export async function init() {
    console.log('HR Module Initialized');
    
    // Make functions globally available for HTML onclicks
    window.showModal = showModal;
    window.closeModal = closeModal;
    window.switchHrTab = switchHrTab;
    window.editHrEmployee = editHrEmployee;
    window.deleteHrEmployee = deleteHrEmployee;
    window.editHrDepartment = editHrDepartment;
    window.deleteHrDepartment = deleteHrDepartment;
    window.updateHrLeaveStatus = updateHrLeaveStatus;
    window.updateHrPayrollStatus = updateHrPayrollStatus;
    window.deleteHrPayroll = deleteHrPayroll;
    window.handleHrEmployeeSubmit = handleHrEmployeeSubmit;
    window.handleHrDepartmentSubmit = handleHrDepartmentSubmit;
    window.handleHrLeaveSubmit = handleHrLeaveSubmit;
    window.handleHrPayrollSubmit = handleHrPayrollSubmit;
    window.loadHrPayrolls = loadHrPayrolls; // for onchange
    window.updatePayrollBaseSalary = updatePayrollBaseSalary;

    injectModals();
    
    // Default to employees tab
    await switchHrTab('employees');
}

function injectModals() {
    const modalContainer = document.getElementById('modal-container');
    if (document.getElementById('hr-employee-modal')) return;

    const modalsHTML = `
    <!-- Employee Modal -->
    <div id="hr-employee-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center">
        <div class="bg-white dark:bg-gray-800 rounded-lg w-full max-w-2xl mx-4 overflow-hidden shadow-xl transform transition-all">
            <div class="flex justify-between items-center px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white" id="modal-title-hr-employee">Personel Ekle</h3>
                <button onclick="closeModal('hr-employee-modal')" class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form onsubmit="handleHrEmployeeSubmit(event)" class="p-6 space-y-4">
                <input type="hidden" name="id">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Ad</label>
                        <input type="text" name="first_name" required class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white sm:text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Soyad</label>
                        <input type="text" name="last_name" required class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white sm:text-sm">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">E-posta</label>
                        <input type="email" name="email" required class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white sm:text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Telefon</label>
                        <input type="text" name="phone" class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white sm:text-sm">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Departman</label>
                        <select name="department_id" id="hr-employee-department" class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white sm:text-sm">
                            <option value="">Seçiniz...</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Pozisyon</label>
                        <input type="text" name="position" class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white sm:text-sm">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">İşe Başlama</label>
                        <input type="date" name="hire_date" required class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white sm:text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Maaş</label>
                        <input type="number" name="salary" step="0.01" class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white sm:text-sm">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Durum</label>
                    <select name="status" class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white sm:text-sm">
                        <option value="active">Aktif</option>
                        <option value="inactive">Pasif</option>
                        <option value="on_leave">İzinde</option>
                        <option value="terminated">Ayrıldı</option>
                    </select>
                </div>
                <div class="flex justify-end pt-4">
                    <button type="button" onclick="closeModal('hr-employee-modal')" class="bg-white dark:bg-gray-700 py-2 px-4 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-200 mr-3">İptal</button>
                    <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">Kaydet</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Department Modal -->
    <div id="hr-department-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center">
        <div class="bg-white dark:bg-gray-800 rounded-lg w-full max-w-lg mx-4 overflow-hidden shadow-xl transform transition-all">
            <div class="flex justify-between items-center px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white" id="modal-title-hr-department">Departman Ekle</h3>
                <button onclick="closeModal('hr-department-modal')" class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form onsubmit="handleHrDepartmentSubmit(event)" class="p-6 space-y-4">
                <input type="hidden" name="id">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Departman Adı</label>
                    <input type="text" name="name" required class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white sm:text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Yönetici</label>
                    <select name="manager_id" id="hr-department-manager" class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white sm:text-sm">
                        <option value="">Seçiniz...</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Açıklama</label>
                    <textarea name="description" rows="3" class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white sm:text-sm"></textarea>
                </div>
                <div class="flex justify-end pt-4">
                    <button type="button" onclick="closeModal('hr-department-modal')" class="bg-white dark:bg-gray-700 py-2 px-4 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-200 mr-3">İptal</button>
                    <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">Kaydet</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Leave Modal -->
    <div id="hr-leave-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center">
        <div class="bg-white dark:bg-gray-800 rounded-lg w-full max-w-lg mx-4 overflow-hidden shadow-xl transform transition-all">
            <div class="flex justify-between items-center px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">İzin Talebi</h3>
                <button onclick="closeModal('hr-leave-modal')" class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form onsubmit="handleHrLeaveSubmit(event)" class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Personel</label>
                    <select name="employee_id" id="hr-leave-employee" required class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white sm:text-sm">
                        <option value="">Seçiniz...</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">İzin Türü</label>
                    <select name="type" required class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white sm:text-sm">
                        <option value="annual">Yıllık İzin</option>
                        <option value="sick">Hastalık İzni</option>
                        <option value="unpaid">Ücretsiz İzin</option>
                        <option value="other">Diğer</option>
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Başlangıç</label>
                        <input type="date" name="start_date" required class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white sm:text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Bitiş</label>
                        <input type="date" name="end_date" required class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white sm:text-sm">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Açıklama</label>
                    <textarea name="reason" rows="2" class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white sm:text-sm"></textarea>
                </div>
                <div class="flex justify-end pt-4">
                    <button type="button" onclick="closeModal('hr-leave-modal')" class="bg-white dark:bg-gray-700 py-2 px-4 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-200 mr-3">İptal</button>
                    <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">Kaydet</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Payroll Modal -->
    <div id="hr-payroll-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center">
        <div class="bg-white dark:bg-gray-800 rounded-lg w-full max-w-lg mx-4 overflow-hidden shadow-xl transform transition-all">
            <div class="flex justify-between items-center px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Bordro Oluştur</h3>
                <button onclick="closeModal('hr-payroll-modal')" class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form onsubmit="handleHrPayrollSubmit(event)" class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Personel</label>
                    <select name="employee_id" id="hr-payroll-employee" required onchange="updatePayrollBaseSalary(this)" class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white sm:text-sm">
                        <option value="">Seçiniz...</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Dönem</label>
                    <input type="month" name="period" required class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white sm:text-sm">
                </div>
                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Brüt Maaş</label>
                        <input type="number" name="base_salary" id="payroll-base-salary" step="0.01" required class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white sm:text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Ek Ödeme</label>
                        <input type="number" name="bonus" value="0" step="0.01" class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white sm:text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Kesinti</label>
                        <input type="number" name="deductions" value="0" step="0.01" class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white sm:text-sm">
                    </div>
                </div>
                <div class="flex justify-end pt-4">
                    <button type="button" onclick="closeModal('hr-payroll-modal')" class="bg-white dark:bg-gray-700 py-2 px-4 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-200 mr-3">İptal</button>
                    <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">Kaydet</button>
                </div>
            </form>
        </div>
    </div>
    `;
    
    modalContainer.innerHTML += modalsHTML;
}

async function switchHrTab(tab) {
    const tabs = ['employees', 'departments', 'leaves', 'payrolls'];
    tabs.forEach(t => {
        const el = document.getElementById(`tab-hr-${t}`);
        if (el) {
            el.className = tab === t
                ? 'hr-tab border-indigo-500 text-indigo-600 whitespace-nowrap py-4 px-6 border-b-2 font-medium text-sm w-1/4 text-center transition-colors'
                : 'hr-tab border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-6 border-b-2 font-medium text-sm w-1/4 text-center transition-colors';
        }
        const content = document.getElementById(`hr-content-${t}`);
        if (content) {
            content.classList.toggle('hidden', tab !== t);
        }
    });

    if (tab === 'employees') await loadHrEmployees();
    if (tab === 'departments') await loadHrDepartments();
    if (tab === 'leaves') await loadHrLeaves();
    if (tab === 'payrolls') await loadHrPayrolls();
}

async function loadHrEmployees() {
    const data = await apiCall('/hr/employees');
    if (!data || !data.data) return;
    
    const items = data.data.items || data.data;
    hrEmployees = items;
    
    // Load departments for reference if needed
    if(hrDepartments.length === 0) {
         const deptData = await apiCall('/hr/departments');
         if(deptData && deptData.data) hrDepartments = deptData.data;
    }

    // Populate Selects in Modals
    const empSelects = [
        document.getElementById('hr-payroll-employee'),
        document.getElementById('hr-leave-employee'),
        document.getElementById('hr-department-manager')
    ];
    
    const optionsHtml = '<option value="">Seçiniz...</option>' + 
        items.map(e => `<option value="${e.id}">${e.first_name} ${e.last_name}</option>`).join('');

    empSelects.forEach(sel => {
        if(sel) sel.innerHTML = optionsHtml;
    });

    // Populate Table
    const tbody = document.querySelector('#hr-employees-table tbody');
    if (!tbody) return;

    tbody.innerHTML = items.map(item => {
        const dept = Array.isArray(hrDepartments) ? hrDepartments.find(d => d.id === item.department_id) : null;
        return `
        <tr>
            <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-gray-900 dark:text-white">${item.first_name} ${item.last_name}</div>
                <div class="text-xs text-gray-500 dark:text-gray-400">${item.position || '-'}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">${dept ? dept.name : '-'}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                <div>${item.email || ''}</div>
                <div>${item.phone || ''}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">${new Date(item.hire_date).toLocaleDateString('tr-TR')}</td>
            <td class="px-6 py-4 whitespace-nowrap">
                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${item.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}">
                    ${item.status === 'active' ? 'Aktif' : 'Pasif'}
                </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                <button onclick="editHrEmployee('${item.id}')" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300 mr-3"><i class="fas fa-edit"></i></button>
                <button onclick="deleteHrEmployee('${item.id}')" class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300"><i class="fas fa-trash"></i></button>
            </td>
        </tr>
    `}).join('');
    
     if (items.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">Personel bulunamadı</td></tr>';
    }
}

async function loadHrDepartments() {
    console.log('Loading HR Departments...');
    const data = await apiCall('/hr/departments');
    console.log('HR Departments Response:', data);

    if (!data) return;
    
    if (data.error) {
        console.error('Error loading departments:', data.message);
        showToast('Departmanlar yüklenemedi: ' + data.message, 'error');
        return;
    }
    
    // Handle both array and object wrapper formats
    let items = [];
    if (Array.isArray(data.data)) {
        items = data.data;
    } else if (data.data && Array.isArray(data.data.items)) {
        items = data.data.items;
    } else if (Array.isArray(data)) {
         items = data;
    }
    
    console.log('Parsed items:', items);
    hrDepartments = items;

    // Populate Select
    const deptSelect = document.getElementById('hr-employee-department');
    if (deptSelect) {
        deptSelect.innerHTML = '<option value="">Seçiniz...</option>' + 
            items.map(d => `<option value="${d.id}">${d.name}</option>`).join('');
    }

    const tbody = document.querySelector('#hr-departments-table tbody');
    if (!tbody) return;

    if (items.length === 0) {
        console.warn('No departments found in list');
        tbody.innerHTML = '<tr><td colspan="4" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">Departman bulunamadı (Liste boş)</td></tr>';
        return;
    }

    tbody.innerHTML = items.map(item => `
        <tr>
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">${item.name || '-'}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">${item.manager_first ? item.manager_first + ' ' + item.manager_last : '-'}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">${item.employee_count || 0}</td>
            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                <button onclick="editHrDepartment('${item.id}')" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300 mr-3"><i class="fas fa-edit"></i></button>
                <button onclick="deleteHrDepartment('${item.id}')" class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300"><i class="fas fa-trash"></i></button>
            </td>
        </tr>
    `).join('');
}

async function loadHrLeaves() {
    const data = await apiCall('/hr/leave-requests'); // Endpoint fix: leave-requests
    if (!data || !data.data) return;

    const tbody = document.querySelector('#hr-leaves-table tbody');
    if (!tbody) return;

    tbody.innerHTML = data.data.map(item => `
        <tr>
            <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-gray-900 dark:text-white">${item.employee_name}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">${item.type}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                ${new Date(item.start_date).toLocaleDateString('tr-TR')} - ${new Date(item.end_date).toLocaleDateString('tr-TR')}
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                    ${item.status === 'approved' ? 'bg-green-100 text-green-800' : 
                      item.status === 'rejected' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800'}">
                    ${item.status === 'approved' ? 'Onaylandı' : item.status === 'rejected' ? 'Reddedildi' : 'Bekliyor'}
                </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                ${item.status === 'pending' ? `
                <button onclick="updateHrLeaveStatus('${item.id}', 'approved')" class="text-green-600 hover:text-green-900 mr-3" title="Onayla"><i class="fas fa-check"></i></button>
                <button onclick="updateHrLeaveStatus('${item.id}', 'rejected')" class="text-red-600 hover:text-red-900" title="Reddet"><i class="fas fa-times"></i></button>
                ` : ''}
            </td>
        </tr>
    `).join('');
    
    if (data.data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">İzin talebi bulunamadı</td></tr>';
    }
}

async function loadHrPayrolls() {
    const filterEl = document.getElementById('hr-payroll-period-filter');
    const period = filterEl && filterEl.value ? filterEl.value : new Date().toISOString().slice(0, 7);
    if(filterEl) filterEl.value = period;

    const data = await apiCall(`/hr/payrolls?period=${period}`);
    if (!data || !data.data) return;

    const tbody = document.querySelector('#hr-payrolls-table tbody');
    if (!tbody) return;

    tbody.innerHTML = data.data.map(item => `
        <tr>
            <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-gray-900 dark:text-white">${item.first_name} ${item.last_name}</div>
                <div class="text-xs text-gray-500 dark:text-gray-400">${item.iban || '-'}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">${item.period}</td>
            <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-500 dark:text-gray-400">₺${parseFloat(item.base_salary).toFixed(2)}</td>
            <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-green-600">+₺${parseFloat(item.bonus).toFixed(2)}</td>
            <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-red-600">-₺${parseFloat(item.deductions).toFixed(2)}</td>
            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-bold text-gray-900 dark:text-white">₺${parseFloat(item.net_salary).toFixed(2)}</td>
            <td class="px-6 py-4 whitespace-nowrap text-center">
                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${item.status === 'paid' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'}">
                    ${item.status === 'paid' ? 'Ödendi' : 'Bekliyor'}
                </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                ${item.status === 'pending' ? `
                    <button onclick="updateHrPayrollStatus('${item.id}', 'paid')" class="text-green-600 hover:text-green-900 mr-3" title="Öde"><i class="fas fa-check-double"></i></button>
                    <button onclick="deleteHrPayroll('${item.id}')" class="text-red-600 hover:text-red-900" title="Sil"><i class="fas fa-trash"></i></button>
                ` : ''}
            </td>
        </tr>
    `).join('');

    if (data.data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">Bu dönem için bordro bulunamadı</td></tr>';
    }
}

// --- CRUD Handlers ---

async function handleHrEmployeeSubmit(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData.entries());
    const id = data.id;
    const url = id ? `/hr/employees/${id}` : '/hr/employees'; // Note: API might not support PUT employee yet, need to check
    const method = id ? 'PUT' : 'POST';
    
    // Fallback if PUT not implemented
    if (method === 'PUT') {
         // Assuming backend supports it, if not need to add it
    }

    const res = await apiCall(url, method, data);
    if (res && (res.success || res.data)) {
        showToast(id ? 'Personel güncellendi' : 'Personel eklendi', 'success');
        closeModal('hr-employee-modal');
        loadHrEmployees();
    } else {
        showToast(res.error || 'Hata oluştu', 'error');
    }
}

async function handleHrDepartmentSubmit(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData.entries());
    const id = data.id;
    const method = id ? 'PUT' : 'POST';
    const url = id ? `/hr/departments/${id}` : '/hr/departments'; // Note: Check backend support
    
    const res = await apiCall(url, method, data);
    if (res && (res.success || res.data)) {
        showToast(id ? 'Departman güncellendi' : 'Departman oluşturuldu', 'success');
        closeModal('hr-department-modal');
        loadHrDepartments();
    } else {
        showToast(res.error || 'Hata oluştu', 'error');
    }
}

async function handleHrLeaveSubmit(e) {
    e.preventDefault();
    // Leave requests usually don't have update in this UI, just create
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData.entries());
    
    // Using correct endpoint POST /api/hr/leave-requests
    const res2 = await apiCall('/hr/leave-requests', 'POST', data);
    if (res2 && (res2.success || res2.data)) {
        showToast('İzin talebi oluşturuldu', 'success');
        closeModal('hr-leave-modal');
        loadHrLeaves();
    } else {
        showToast(res2.error || 'Hata oluştu', 'error');
    }
}

async function handleHrPayrollSubmit(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData.entries());
    
    const res = await apiCall('/hr/payrolls', 'POST', data);
    if (res && (res.success || res.data)) {
        showToast('Bordro oluşturuldu', 'success');
        closeModal('hr-payroll-modal');
        loadHrPayrolls();
    } else {
        showToast(res.error || 'Hata oluştu', 'error');
    }
}

async function updateHrLeaveStatus(id, status) {
    // Check routes/api.php for PUT /hr/leave-requests/{id}/status
    // It is now available
    const res = await apiCall(`/hr/leave-requests/${id}/status`, 'PUT', { status });
    if (res && res.success) {
        showToast('İzin durumu güncellendi', 'success');
        loadHrLeaves();
    } else {
        showToast(res.error || 'Hata oluştu', 'error');
    }
}

async function updateHrPayrollStatus(id, status) {
    const confirmed = await showConfirm('Bu ödemeyi yapıldı olarak işaretlemek istiyor musunuz?');
    if (!confirmed) return;
    const res = await apiCall(`/hr/payrolls/${id}/status`, 'PUT', { status });
    if (res && res.success) {
        showToast('Ödeme durumu güncellendi', 'success');
        loadHrPayrolls();
    } else {
        showToast(res.error || 'Hata oluştu', 'error');
    }
}

async function deleteHrPayroll(id) {
    const confirmed = await showConfirm('Bu bordroyu silmek istediğinize emin misiniz?');
    if (!confirmed) return;
    const res = await apiCall(`/hr/payrolls/${id}`, 'DELETE');
    if (res && res.success) {
        showToast('Bordro silindi', 'success');
        loadHrPayrolls();
    } else {
        showToast(res.error || 'Hata oluştu', 'error');
    }
}

function editHrEmployee(id) {
    const emp = hrEmployees.find(e => e.id === id);
    if (emp) {
        const form = document.querySelector('#hr-employee-modal form');
        form.reset();
        form.querySelector('[name=id]').value = emp.id;
        form.querySelector('[name=first_name]').value = emp.first_name;
        form.querySelector('[name=last_name]').value = emp.last_name;
        form.querySelector('[name=email]').value = emp.email;
        form.querySelector('[name=phone]').value = emp.phone || '';
        form.querySelector('[name=department_id]').value = emp.department_id || '';
        form.querySelector('[name=position]').value = emp.position || '';
        form.querySelector('[name=hire_date]').value = emp.hire_date ? emp.hire_date.split('T')[0] : '';
        form.querySelector('[name=salary]').value = emp.salary || '';
        form.querySelector('[name=status]').value = emp.status || 'active';
        
        document.getElementById('modal-title-hr-employee').textContent = 'Personel Düzenle';
        showModal('hr-employee-modal');
    }
}

function editHrDepartment(id) {
    const dept = hrDepartments.find(d => d.id === id);
    if (dept) {
        const form = document.querySelector('#hr-department-modal form');
        form.reset();
        form.querySelector('[name=id]').value = dept.id;
        form.querySelector('[name=name]').value = dept.name;
        form.querySelector('[name=manager_id]').value = dept.manager_id || '';
        form.querySelector('[name=description]').value = dept.description || '';
        document.getElementById('modal-title-hr-department').textContent = 'Departman Düzenle';
        showModal('hr-department-modal');
    }
}

async function deleteHrEmployee(id) {
    if(await showConfirm('Bu personeli silmek istediğinize emin misiniz?')) {
        // Check route existence
        const res = await apiCall(`/hr/employees/${id}`, 'DELETE'); // Assuming route exists
        if (res && res.success) {
            showToast('Personel silindi', 'success');
            loadHrEmployees();
        } else {
             showToast(res.error || 'Silme başarısız', 'error');
        }
    }
}

async function deleteHrDepartment(id) {
    if(await showConfirm('Bu departmanı silmek istediğinize emin misiniz?')) {
        const res = await apiCall(`/hr/departments/${id}`, 'DELETE'); // Assuming route exists
        if (res && res.success) {
            showToast('Departman silindi', 'success');
            loadHrDepartments();
        } else {
             showToast(res.error || 'Silme başarısız', 'error');
        }
    }
}

function updatePayrollBaseSalary(select) {
    const empId = select.value;
    const emp = hrEmployees.find(e => e.id === empId);
    if (emp) {
        document.getElementById('payroll-base-salary').value = emp.salary || 0;
    }
}
