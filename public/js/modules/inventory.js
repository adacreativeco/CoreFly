import { apiCall } from '../api.js';
import { showToast, showModal, closeModal, showConfirm, formatDate } from '../utils.js';

let invItems = [];
let invCategories = [];
let invSuppliers = [];

export async function init() {
    console.log('Inventory Module Initialized');
    
    // Expose functions
    window.switchInventoryTab = switchInventoryTab;
    window.editInventoryItem = editInventoryItem;
    window.deleteInventoryItem = deleteInventoryItem;
    window.handleInventoryItemSubmit = handleInventoryItemSubmit;
    window.handleInventoryMovementSubmit = handleInventoryMovementSubmit;
    window.handleInventoryCategorySubmit = handleInventoryCategorySubmit;
    window.handleInventorySupplierSubmit = handleInventorySupplierSubmit;
    window.deleteInventoryCategory = deleteInventoryCategory;
    window.deleteInventorySupplier = deleteInventorySupplier;
    window.filterInventoryItems = filterInventoryItems;
    window.showModal = showModal;
    window.closeModal = closeModal;

    injectModals();
    
    // Check if elements exist before loading to prevent errors if switching views rapidly
    if (document.getElementById('inv-stat-total')) {
        await loadInventoryStats();
    }
    
    await loadInventoryCategories(); // Need for filter
    await switchInventoryTab('items');
}

function injectModals() {
    const modalContainer = document.getElementById('modal-container');
    if (document.getElementById('inventory-item-modal')) return;

    const modalsHTML = `
    <!-- Item Modal -->
    <div id="inventory-item-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center">
        <div class="bg-white dark:bg-gray-800 rounded-lg w-full max-w-lg mx-4 overflow-hidden shadow-xl transform transition-all">
            <div class="flex justify-between items-center px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white" id="modal-title-inventory-item">Ürün Ekle</h3>
                <button onclick="closeModal('inventory-item-modal')" class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form onsubmit="handleInventoryItemSubmit(event)" class="p-6 space-y-4">
                <input type="hidden" name="id">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Ürün Adı</label>
                    <input type="text" name="name" required class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white sm:text-sm">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">SKU / Kod</label>
                        <input type="text" name="sku" class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white sm:text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Kategori</label>
                        <select name="category_id" id="inv-item-category" class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white sm:text-sm">
                            <option value="">Seçiniz...</option>
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Miktar</label>
                        <input type="number" name="quantity" value="0" min="0" class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white sm:text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Kritik Seviye</label>
                        <input type="number" name="min_quantity" value="10" min="0" class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white sm:text-sm">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Alış Fiyatı</label>
                        <input type="number" name="purchase_price" step="0.01" class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white sm:text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Satış Fiyatı</label>
                        <input type="number" name="sale_price" step="0.01" class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white sm:text-sm">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tedarikçi</label>
                    <select name="supplier_id" id="inv-item-supplier" class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white sm:text-sm">
                        <option value="">Seçiniz...</option>
                    </select>
                </div>
                <div class="flex justify-end pt-4">
                    <button type="button" onclick="closeModal('inventory-item-modal')" class="bg-white dark:bg-gray-700 py-2 px-4 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-200 mr-3">İptal</button>
                    <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">Kaydet</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Movement Modal -->
    <div id="inventory-movement-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center">
        <div class="bg-white dark:bg-gray-800 rounded-lg w-full max-w-md mx-4 overflow-hidden shadow-xl transform transition-all">
            <div class="flex justify-between items-center px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Stok Hareketi</h3>
                <button onclick="closeModal('inventory-movement-modal')" class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form onsubmit="handleInventoryMovementSubmit(event)" class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Ürün</label>
                    <select name="item_id" id="inv-movement-item" required class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white sm:text-sm">
                        <option value="">Seçiniz...</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">İşlem Tipi</label>
                    <select name="type" required class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white sm:text-sm">
                        <option value="in">Giriş (Ekle)</option>
                        <option value="out">Çıkış (Azalt)</option>
                        <option value="adjustment">Düzeltme (Sayım)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Miktar</label>
                    <input type="number" name="quantity" required min="1" class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white sm:text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Açıklama</label>
                    <textarea name="notes" rows="2" class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white sm:text-sm"></textarea>
                </div>
                <div class="flex justify-end pt-4">
                    <button type="button" onclick="closeModal('inventory-movement-modal')" class="bg-white dark:bg-gray-700 py-2 px-4 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-200 mr-3">İptal</button>
                    <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">Kaydet</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Category Modal -->
    <div id="inventory-category-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center">
        <div class="bg-white dark:bg-gray-800 rounded-lg w-full max-w-sm mx-4 overflow-hidden shadow-xl transform transition-all">
            <div class="flex justify-between items-center px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Kategori Ekle</h3>
                <button onclick="closeModal('inventory-category-modal')" class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form onsubmit="handleInventoryCategorySubmit(event)" class="p-6 space-y-4">
                <input type="hidden" name="id">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Kategori Adı</label>
                    <input type="text" name="name" required class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white sm:text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Açıklama</label>
                    <textarea name="description" rows="2" class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white sm:text-sm"></textarea>
                </div>
                <div class="flex justify-end pt-4">
                    <button type="button" onclick="closeModal('inventory-category-modal')" class="bg-white dark:bg-gray-700 py-2 px-4 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-200 mr-3">İptal</button>
                    <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">Kaydet</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Supplier Modal -->
    <div id="inventory-supplier-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center">
        <div class="bg-white dark:bg-gray-800 rounded-lg w-full max-w-md mx-4 overflow-hidden shadow-xl transform transition-all">
            <div class="flex justify-between items-center px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Tedarikçi Ekle</h3>
                <button onclick="closeModal('inventory-supplier-modal')" class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form onsubmit="handleInventorySupplierSubmit(event)" class="p-6 space-y-4">
                <input type="hidden" name="id">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Firma Adı</label>
                    <input type="text" name="name" required class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white sm:text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">İlgili Kişi</label>
                    <input type="text" name="contact_name" class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white sm:text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Telefon</label>
                    <input type="text" name="phone" class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white sm:text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">E-posta</label>
                    <input type="email" name="email" class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white sm:text-sm">
                </div>
                <div class="flex justify-end pt-4">
                    <button type="button" onclick="closeModal('inventory-supplier-modal')" class="bg-white dark:bg-gray-700 py-2 px-4 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-200 mr-3">İptal</button>
                    <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">Kaydet</button>
                </div>
            </form>
        </div>
    </div>
    `;
    
    modalContainer.innerHTML += modalsHTML;
}

async function switchInventoryTab(tab) {
    const tabs = ['items', 'movements', 'categories', 'suppliers'];
    tabs.forEach(t => {
        const el = document.getElementById(`tab-inv-${t}`);
        if (el) {
            el.className = tab === t
                ? 'inv-tab border-indigo-500 text-indigo-600 whitespace-nowrap py-4 px-6 border-b-2 font-medium text-sm w-1/4 text-center transition-colors'
                : 'inv-tab border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-6 border-b-2 font-medium text-sm w-1/4 text-center transition-colors';
        }
        const content = document.getElementById(`inv-content-${t}`);
        if (content) {
            content.classList.toggle('hidden', tab !== t);
        }
    });

    if (tab === 'items') await loadInventoryItems();
    if (tab === 'movements') await loadInventoryMovements();
    if (tab === 'categories') await loadInventoryCategories();
    if (tab === 'suppliers') await loadInventorySuppliers();
}

async function loadInventoryStats() {
    const res = await apiCall('/inventory/statistics');
    if (res && res.data) {
        setText('inv-stat-total', res.data.total_items);
        setText('inv-stat-value', '₺' + parseFloat(res.data.total_value || 0).toFixed(2));
        setText('inv-stat-low', res.data.low_stock_count);
        setText('inv-stat-movements', res.data.monthly_movements);
    }
}

async function loadInventoryItems() {
    const res = await apiCall('/inventory');
    if (!res || !res.data) return;
    
    invItems = res.data.items || res.data; // Store for select/filter

    // Populate Selects
    const itemSelect = document.getElementById('inv-movement-item');
    if (itemSelect) {
        itemSelect.innerHTML = '<option value="">Seçiniz...</option>' + 
            invItems.map(i => `<option value="${i.id}">${i.name} (${i.quantity})</option>`).join('');
    }

    filterInventoryItems(); // Render table with filters applied
}

function filterInventoryItems() {
    const search = document.getElementById('inv-search').value.toLowerCase();
    const categoryId = document.getElementById('inv-category-filter').value;

    const filtered = invItems.filter(item => {
        const matchesSearch = item.name.toLowerCase().includes(search) || (item.sku && item.sku.toLowerCase().includes(search));
        const matchesCategory = categoryId === '' || item.category_id === categoryId;
        return matchesSearch && matchesCategory;
    });

    const tbody = document.querySelector('#inv-items-table tbody');
    if (!tbody) return;

    tbody.innerHTML = filtered.map(item => {
        const categoryName = item.category_name || (invCategories.find(c => c.id === item.category_id)?.name) || '-';
        return `
        <tr>
            <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-gray-900 dark:text-white">${item.name}</div>
                <div class="text-xs text-gray-500 dark:text-gray-400">SKU: ${item.sku || '-'}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">${categoryName}</td>
            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium ${item.quantity <= item.min_quantity ? 'text-red-600' : 'text-gray-900 dark:text-white'}">
                ${item.quantity}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-500 dark:text-gray-400">₺${parseFloat(item.sale_price || 0).toFixed(2)}</td>
            <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-500 dark:text-gray-400">₺${(item.quantity * (item.sale_price || 0)).toFixed(2)}</td>
            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                <button onclick="editInventoryItem('${item.id}')" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300 mr-3"><i class="fas fa-edit"></i></button>
                <button onclick="deleteInventoryItem('${item.id}')" class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300"><i class="fas fa-trash"></i></button>
            </td>
        </tr>
    `}).join('');

    if (filtered.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">Ürün bulunamadı</td></tr>';
    }
}

async function loadInventoryMovements() {
    const res = await apiCall('/inventory/movements');
    if (!res || !res.data) return;

    const tbody = document.querySelector('#inv-movements-table tbody');
    if (!tbody) return;

    tbody.innerHTML = res.data.map(m => `
        <tr>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">${new Date(m.created_at).toLocaleString('tr-TR')}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">${m.item_name}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                ${m.type === 'in' ? '<span class="text-green-600">Giriş</span>' : 
                  m.type === 'out' ? '<span class="text-red-600">Çıkış</span>' : '<span class="text-blue-600">Düzeltme</span>'}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium ${m.type === 'out' ? 'text-red-600' : 'text-green-600'}">
                ${m.type === 'out' ? '-' : '+'}${m.quantity}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">${m.user_name || 'Sistem'}</td>
        </tr>
    `).join('');
}

async function loadInventoryCategories() {
    const res = await apiCall('/inventory/categories');
    if (res && res.data) {
        invCategories = res.data;
        
        // Update Filters and Modals
        const filterSelect = document.getElementById('inv-category-filter');
        const modalSelect = document.getElementById('inv-item-category');
        
        const options = '<option value="">Seçiniz...</option>' + 
            res.data.map(c => `<option value="${c.id}">${c.name}</option>`).join('');
            
        if (filterSelect) filterSelect.innerHTML = '<option value="">Tüm Kategoriler</option>' + res.data.map(c => `<option value="${c.id}">${c.name}</option>`).join('');
        if (modalSelect) modalSelect.innerHTML = options;

        // Render Table
        const tbody = document.querySelector('#inv-categories-table tbody');
        if (tbody) {
            tbody.innerHTML = res.data.map(c => `
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">${c.name}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">${c.description || '-'}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <button onclick="deleteInventoryCategory('${c.id}')" class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300"><i class="fas fa-trash"></i></button>
                    </td>
                </tr>
            `).join('');
        }
    }
}

async function loadInventorySuppliers() {
    const res = await apiCall('/inventory/suppliers');
    if (res && res.data) {
        invSuppliers = res.data;
        
        const modalSelect = document.getElementById('inv-item-supplier');
        if (modalSelect) {
            modalSelect.innerHTML = '<option value="">Seçiniz...</option>' + 
                res.data.map(s => `<option value="${s.id}">${s.name}</option>`).join('');
        }

        const tbody = document.querySelector('#inv-suppliers-table tbody');
        if (tbody) {
            tbody.innerHTML = res.data.map(s => `
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">${s.name}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">${s.contact_name || '-'}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">${s.phone || '-'}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <button onclick="deleteInventorySupplier('${s.id}')" class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300"><i class="fas fa-trash"></i></button>
                    </td>
                </tr>
            `).join('');
        }
    }
}

// --- Handlers ---

async function handleInventoryItemSubmit(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData.entries());
    const id = data.id;
    const url = id ? `/inventory/${id}` : '/inventory';
    const method = id ? 'PUT' : 'POST';
    
    const res = await apiCall(url, method, data);
    if (res && (res.success || res.data)) {
        showToast(id ? 'Ürün güncellendi' : 'Ürün eklendi', 'success');
        closeModal('inventory-item-modal');
        loadInventoryItems();
        loadInventoryStats();
    } else {
        showToast(res.error || 'Hata oluştu', 'error');
    }
}

async function handleInventoryMovementSubmit(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData.entries());
    const itemId = data.item_id;
    
    const res = await apiCall(`/inventory/${itemId}/adjust-quantity`, 'POST', data);
    if (res && (res.success || res.data)) {
        showToast('Stok hareketi kaydedildi', 'success');
        closeModal('inventory-movement-modal');
        loadInventoryItems();
        loadInventoryStats();
        loadInventoryMovements();
    } else {
        showToast(res.error || 'Hata oluştu', 'error');
    }
}

async function handleInventoryCategorySubmit(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData.entries());
    
    const res = await apiCall('/inventory/categories', 'POST', data);
    if (res && (res.success || res.data)) {
        showToast('Kategori eklendi', 'success');
        closeModal('inventory-category-modal');
        loadInventoryCategories();
    } else {
        showToast(res.error || 'Hata oluştu', 'error');
    }
}

async function handleInventorySupplierSubmit(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData.entries());
    
    const res = await apiCall('/inventory/suppliers', 'POST', data);
    if (res && (res.success || res.data)) {
        showToast('Tedarikçi eklendi', 'success');
        closeModal('inventory-supplier-modal');
        loadInventorySuppliers();
    } else {
        showToast(res.error || 'Hata oluştu', 'error');
    }
}

function editInventoryItem(id) {
    const item = invItems.find(i => i.id === id);
    if (item) {
        const form = document.querySelector('#inventory-item-modal form');
        form.reset();
        form.querySelector('[name=id]').value = item.id;
        form.querySelector('[name=name]').value = item.name;
        form.querySelector('[name=sku]').value = item.sku || '';
        form.querySelector('[name=category_id]').value = item.category_id || '';
        form.querySelector('[name=quantity]').value = item.quantity;
        form.querySelector('[name=min_quantity]').value = item.min_quantity || 10;
        form.querySelector('[name=purchase_price]').value = item.purchase_price || '';
        form.querySelector('[name=sale_price]').value = item.sale_price || '';
        form.querySelector('[name=supplier_id]').value = item.supplier_id || '';
        
        document.getElementById('modal-title-inventory-item').textContent = 'Ürün Düzenle';
        showModal('inventory-item-modal');
    }
}

async function deleteInventoryItem(id) {
    if(await showConfirm('Bu ürünü silmek istediğinize emin misiniz?')) {
        const res = await apiCall(`/inventory/${id}`, 'DELETE');
        if (res && res.success) {
            showToast('Ürün silindi', 'success');
            loadInventoryItems();
            loadInventoryStats();
        } else {
             showToast(res.error || 'Silme başarısız', 'error');
        }
    }
}

async function deleteInventoryCategory(id) {
    if(await showConfirm('Bu kategoriyi silmek istediğinize emin misiniz?')) {
        const res = await apiCall(`/inventory/categories/${id}`, 'DELETE');
        if (res && res.success) {
            showToast('Kategori silindi', 'success');
            loadInventoryCategories();
        } else {
             showToast(res.error || 'Silme başarısız', 'error');
        }
    }
}

async function deleteInventorySupplier(id) {
    if(await showConfirm('Bu tedarikçiyi silmek istediğinize emin misiniz?')) {
        const res = await apiCall(`/inventory/suppliers/${id}`, 'DELETE');
        if (res && res.success) {
            showToast('Tedarikçi silindi', 'success');
            loadInventorySuppliers();
        } else {
             showToast(res.error || 'Silme başarısız', 'error');
        }
    }
}

function setText(id, text) {
    const el = document.getElementById(id);
    if (el) el.textContent = text;
}
