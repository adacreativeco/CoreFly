import { apiCall } from '../api.js';
import { formatDate, showToast, showConfirm, showModal, closeModal } from '../utils.js';

let documents = [];

export async function init() {
    console.log('Documents Module Initialized');
    
    // Bind global functions
    window.deleteDocument = deleteDocument;
    window.downloadDocument = downloadDocument;
    window.showModal = showModal;
    window.closeModal = closeModal;

    // Load Data
    await loadDocuments();

    // Event Listeners
    document.getElementById('document-form').addEventListener('submit', handleUpload);
    document.getElementById('file-upload').addEventListener('change', (e) => {
        const fileName = e.target.files[0]?.name;
        document.getElementById('file-name-display').textContent = fileName || '';
    });
}

async function loadDocuments() {
    const table = document.getElementById('documents-table');
    
    try {
        const res = await apiCall('/documents');
        if (res && res.data) {
            documents = res.data.items || res.data;
            renderDocuments(documents);
        }
    } catch (error) {
        console.error('Failed to load documents:', error);
        table.innerHTML = '<tr><td colspan="6" class="px-6 py-4 text-center text-red-500">Dokümanlar yüklenirken hata oluştu.</td></tr>';
    }
}

function renderDocuments(items) {
    const table = document.getElementById('documents-table');
    
    if (!items || items.length === 0) {
        table.innerHTML = '<tr><td colspan="6" class="px-6 py-4 text-center text-gray-500">Henüz dosya yüklenmemiş.</td></tr>';
        return;
    }

    table.innerHTML = items.map(doc => `
        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
            <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center">
                    <div class="flex-shrink-0 h-10 w-10 flex items-center justify-center rounded-lg ${getFileIconClass(doc.type)}">
                        <i class="${getFileIcon(doc.type)} text-lg"></i>
                    </div>
                    <div class="ml-4">
                        <div class="text-sm font-medium text-gray-900 dark:text-white">${doc.name}</div>
                        <div class="text-xs text-gray-500">${doc.original_name || ''}</div>
                    </div>
                </div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                ${doc.type?.toUpperCase() || 'DOSYA'}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                ${formatSize(doc.size)}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                ${doc.uploaded_by_name || 'Bilinmiyor'}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                ${formatDate(doc.created_at)}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                <button onclick="downloadDocument('${doc.path}')" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 mr-3" title="İndir">
                    <i class="fas fa-download"></i>
                </button>
                <button onclick="deleteDocument(${doc.id})" class="text-red-600 hover:text-red-900 dark:text-red-400" title="Sil">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        </tr>
    `).join('');
}

async function handleUpload(e) {
    e.preventDefault();
    
    const fileInput = document.getElementById('file-upload');
    const nameInput = document.getElementById('doc-name');
    
    if (fileInput.files.length === 0) {
        showToast('Lütfen bir dosya seçin', 'warning');
        return;
    }

    const formData = new FormData();
    formData.append('file', fileInput.files[0]);
    if (nameInput.value) {
        formData.append('name', nameInput.value);
    }

    try {
        // Note: apiCall wrapper might not handle FormData automatically if it sets Content-Type to json
        // Using fetch directly or assuming apiCall handles it if body is FormData
        const token = localStorage.getItem('token');
        const res = await fetch('/documents', {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${token}`
                // Content-Type not set to let browser set boundary
            },
            body: formData
        });

        if (res.ok) {
            showToast('Dosya başarıyla yüklendi', 'success');
            closeModal('document-modal');
            e.target.reset();
            document.getElementById('file-name-display').textContent = '';
            await loadDocuments();
        } else {
            throw new Error('Upload failed');
        }
    } catch (error) {
        console.error(error);
        showToast('Yükleme sırasında hata oluştu', 'error');
    }
}

async function deleteDocument(id) {
    if (!await showConfirm('Bu dosyayı silmek istediğinize emin misiniz?')) return;

    try {
        await apiCall(`/documents/${id}`, 'DELETE');
        showToast('Dosya silindi', 'success');
        await loadDocuments();
    } catch (error) {
        showToast('Silme işlemi başarısız', 'error');
    }
}

function downloadDocument(path) {
    // In a real app, this would likely call a secure endpoint that streams the file
    // For now, assuming static serving or signed URL
    window.open(path, '_blank');
}

function getFileIcon(type) {
    if (!type) return 'fas fa-file';
    type = type.toLowerCase();
    if (type.includes('pdf')) return 'fas fa-file-pdf';
    if (type.includes('word') || type.includes('doc')) return 'fas fa-file-word';
    if (type.includes('excel') || type.includes('sheet') || type.includes('xls')) return 'fas fa-file-excel';
    if (type.includes('image') || type.includes('jpg') || type.includes('png')) return 'fas fa-file-image';
    return 'fas fa-file-alt';
}

function getFileIconClass(type) {
    if (!type) return 'bg-gray-100 text-gray-500';
    type = type.toLowerCase();
    if (type.includes('pdf')) return 'bg-red-100 text-red-500';
    if (type.includes('word') || type.includes('doc')) return 'bg-blue-100 text-blue-500';
    if (type.includes('excel') || type.includes('xls')) return 'bg-green-100 text-green-500';
    if (type.includes('image')) return 'bg-purple-100 text-purple-500';
    return 'bg-gray-100 text-gray-500';
}

function formatSize(bytes) {
    if (!bytes) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}


