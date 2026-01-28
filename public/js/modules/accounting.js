import { apiCall } from '../api.js';
import { formatDate, showToast, showConfirm } from '../utils.js';

let transactions = [];

export async function init() {
    console.log('Accounting Module Initialized');
    
    // Bind global functions
    window.deleteTransaction = deleteTransaction;

    // Set today's date in modal
    document.getElementById('trx-date').valueAsDate = new Date();

    // Load Data
    await loadTransactions();

    // Event Listeners
    document.getElementById('transaction-form').addEventListener('submit', handleTransactionSubmit);
    document.getElementById('filter-type').addEventListener('change', filterTransactions);
}

async function loadTransactions() {
    const table = document.getElementById('transactions-table');
    
    try {
        const res = await apiCall('/accounting/transactions');
        if (res && res.data) {
            transactions = res.data.items || res.data;
            updateStats(transactions);
            renderTransactions(transactions);
        }
    } catch (error) {
        console.error('Failed to load transactions:', error);
        table.innerHTML = '<tr><td colspan="6" class="px-6 py-4 text-center text-red-500">Veriler yüklenirken hata oluştu.</td></tr>';
    }
}

function updateStats(items) {
    let income = 0;
    let expense = 0;

    items.forEach(item => {
        const amount = parseFloat(item.amount);
        if (item.type === 'income') income += amount;
        else if (item.type === 'expense') expense += amount;
    });

    document.getElementById('stat-total-income').textContent = formatCurrency(income);
    document.getElementById('stat-total-expense').textContent = formatCurrency(expense);
    
    const net = income - expense;
    const netEl = document.getElementById('stat-net-balance');
    netEl.textContent = formatCurrency(net);
    netEl.className = `text-2xl font-bold ${net >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'}`;
}

function renderTransactions(items) {
    const table = document.getElementById('transactions-table');
    
    if (!items || items.length === 0) {
        table.innerHTML = '<tr><td colspan="6" class="px-6 py-4 text-center text-gray-500">Kayıtlı işlem bulunamadı.</td></tr>';
        return;
    }

    // Sort by date desc
    const sorted = [...items].sort((a, b) => new Date(b.date) - new Date(a.date));

    table.innerHTML = sorted.map(trx => `
        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                ${formatDate(trx.date).split(' ')[0]}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white font-medium">
                ${trx.description || '-'}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                <span class="px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded text-xs">
                    ${trx.category || 'Genel'}
                </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${
                    trx.type === 'income' 
                    ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' 
                    : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300'
                }">
                    ${trx.type === 'income' ? 'Gelir' : 'Gider'}
                </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-bold ${
                trx.type === 'income' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'
            }">
                ${trx.type === 'income' ? '+' : '-'}${formatCurrency(trx.amount)}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                <button onclick="deleteTransaction(${trx.id})" class="text-red-600 hover:text-red-900 dark:text-red-400" title="Sil">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        </tr>
    `).join('');
}

function filterTransactions() {
    const type = document.getElementById('filter-type').value;
    
    let filtered = transactions;
    if (type) {
        filtered = transactions.filter(t => t.type === type);
    }
    
    renderTransactions(filtered);
}

async function handleTransactionSubmit(e) {
    e.preventDefault();
    
    const data = {
        type: document.getElementById('trx-type').value,
        date: document.getElementById('trx-date').value,
        amount: parseFloat(document.getElementById('trx-amount').value),
        category: document.getElementById('trx-category').value,
        description: document.getElementById('trx-description').value
    };

    try {
        await apiCall('/accounting/transactions', 'POST', data);
        showToast('İşlem başarıyla kaydedildi', 'success');
        closeModal('transaction-modal');
        e.target.reset();
        document.getElementById('trx-date').valueAsDate = new Date(); // Reset date to today
        await loadTransactions();
    } catch (error) {
        showToast('İşlem kaydedilemedi', 'error');
        console.error(error);
    }
}

async function deleteTransaction(id) {
    if (!await showConfirm('Bu işlemi silmek istediğinize emin misiniz? Bakiye güncellenecektir.')) return;

    try {
        await apiCall(`/accounting/transactions/${id}`, 'DELETE');
        showToast('İşlem silindi', 'success');
        await loadTransactions();
    } catch (error) {
        showToast('Silme işlemi başarısız', 'error');
    }
}

function formatCurrency(amount) {
    return new Intl.NumberFormat('tr-TR', { style: 'currency', currency: 'TRY' }).format(amount);
}


