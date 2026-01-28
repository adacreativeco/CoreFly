import { apiCall } from '../api.js';
import { formatDate, showToast, showConfirm, showModal, closeModal } from '../utils.js';

let donations = [];

export async function init() {
    console.log('Donations Module Initialized');
    
    // Bind global functions
    window.editDonation = editDonation;
    window.deleteDonation = deleteDonation;

    // Set default date
    document.getElementById('donation-date').valueAsDate = new Date();

    // Load Data
    await loadDonations();

    // Event Listeners
    const form = document.getElementById('donation-form');
    if (form) form.addEventListener('submit', handleDonationSubmit);
    
    const searchInput = document.getElementById('donation-search');
    if (searchInput) searchInput.addEventListener('input', filterDonations);
}

async function loadDonations() {
    const table = document.getElementById('donations-table');
    if (!table) return;
    
    try {
        const res = await apiCall('/donations');
        if (res && res.data) {
            // Handle different response structures
            if (Array.isArray(res.data)) {
                donations = res.data;
            } else if (Array.isArray(res.data.donations)) {
                donations = res.data.donations;
            } else if (Array.isArray(res.data.items)) {
                donations = res.data.items;
            } else {
                donations = [];
                console.warn('Unexpected donations response structure', res);
            }
            
            updateStats(donations);
            renderDonations(donations);
        }
    } catch (error) {
        console.error('Failed to load donations:', error);
        table.innerHTML = '<tr><td colspan="6" class="px-6 py-4 text-center text-red-500">Veriler yüklenirken hata oluştu.</td></tr>';
    }
}

function updateStats(items) {
    const total = items.reduce((sum, item) => sum + parseFloat(item.amount), 0);
    const count = items.length;
    const avg = count > 0 ? total / count : 0;

    // Mock logic for monthly/yearly (assuming all data is returned)
    // In real app, backend should provide stats or we filter by date
    const now = new Date();
    const currentMonth = now.getMonth();
    const currentYear = now.getFullYear();

    const monthlyTotal = items
        .filter(i => {
            const d = new Date(i.date || i.created_at);
            return d.getMonth() === currentMonth && d.getFullYear() === currentYear;
        })
        .reduce((sum, i) => sum + parseFloat(i.amount), 0);

    const yearlyTotal = items
        .filter(i => new Date(i.date || i.created_at).getFullYear() === currentYear)
        .reduce((sum, i) => sum + parseFloat(i.amount), 0);

    document.getElementById('stat-monthly-total').textContent = formatCurrency(monthlyTotal);
    document.getElementById('stat-yearly-total').textContent = formatCurrency(yearlyTotal);
    document.getElementById('stat-donor-count').textContent = count;
    document.getElementById('stat-avg-donation').textContent = formatCurrency(avg);
}

function renderDonations(items) {
    const table = document.getElementById('donations-table');
    
    if (!items || items.length === 0) {
        table.innerHTML = '<tr><td colspan="6" class="px-6 py-4 text-center text-gray-500">Kayıt bulunamadı.</td></tr>';
        return;
    }

    // Sort by date desc
    const sorted = [...items].sort((a, b) => new Date(b.date || b.created_at) - new Date(a.date || a.created_at));

    table.innerHTML = sorted.map(item => `
        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
            <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-gray-900 dark:text-white">${item.donor_name}</div>
                <div class="text-xs text-gray-500">${item.contact_info || item.donor_email || '-'}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-green-600 dark:text-green-400">
                ${formatCurrency(item.amount)}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                ${formatDate(item.date || item.created_at).split(' ')[0]}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                ${getPaymentMethodLabel(item.payment_method)}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                <span class="px-2 py-1 bg-indigo-50 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300 rounded text-xs">
                    ${getCampaignLabel(item.campaign)}
                </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                <button onclick="editDonation('${item.id}')" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 mr-3">
                    <i class="fas fa-edit"></i>
                </button>
                <button onclick="deleteDonation('${item.id}')" class="text-red-600 hover:text-red-900 dark:text-red-400">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        </tr>
    `).join('');
}

function filterDonations() {
    const search = document.getElementById('donation-search').value.toLowerCase();
    
    const filtered = donations.filter(item => 
        item.donor_name.toLowerCase().includes(search) || 
        (item.contact_info && item.contact_info.toLowerCase().includes(search))
    );
    
    renderDonations(filtered);
}

async function handleDonationSubmit(e) {
    e.preventDefault();
    
    const id = document.getElementById('donation-id').value;
    const data = {
        donor_name: document.getElementById('donor-name').value,
        contact_info: document.getElementById('donor-contact').value,
        amount: parseFloat(document.getElementById('donation-amount').value),
        date: document.getElementById('donation-date').value,
        payment_method: document.getElementById('payment-method').value,
        campaign: document.getElementById('campaign').value,
        notes: document.getElementById('donation-notes').value
    };

    try {
        const url = id ? `/donations/${id}` : '/donations';
        const method = id ? 'PUT' : 'POST';
        
        await apiCall(url, method, data);
        showToast(id ? 'Bağış güncellendi' : 'Bağış kaydedildi', 'success');
        closeModal('donation-modal');
        await loadDonations();
    } catch (error) {
        showToast('İşlem başarısız', 'error');
        console.error(error);
    }
}

async function editDonation(id) {
    const item = donations.find(d => d.id === id);
    if (!item) return;

    document.getElementById('donation-id').value = item.id;
    document.getElementById('donor-name').value = item.donor_name;
    document.getElementById('donor-contact').value = item.contact_info || '';
    document.getElementById('donation-amount').value = item.amount;
    document.getElementById('donation-date').value = item.date;
    document.getElementById('payment-method').value = item.payment_method;
    document.getElementById('campaign').value = item.campaign || 'general';
    document.getElementById('donation-notes').value = item.notes || '';
    
    showModal('donation-modal');
}

async function deleteDonation(id) {
    if (!await showConfirm('Bu kaydı silmek istediğinize emin misiniz?')) return;

    try {
        await apiCall(`/donations/${id}`, 'DELETE');
        showToast('Kayıt silindi', 'success');
        await loadDonations();
    } catch (error) {
        showToast('Silme işlemi başarısız', 'error');
    }
}

function formatCurrency(amount) {
    return new Intl.NumberFormat('tr-TR', { style: 'currency', currency: 'TRY' }).format(amount);
}

function getPaymentMethodLabel(method) {
    const map = {
        'credit_card': 'Kredi Kartı',
        'bank_transfer': 'Havale/EFT',
        'cash': 'Nakit'
    };
    return map[method] || method;
}

function getCampaignLabel(camp) {
    const map = {
        'general': 'Genel Bağış',
        'ramadan': 'Ramazan Kolisi',
        'education': 'Eğitim Bursu'
    };
    return map[camp] || camp;
}

