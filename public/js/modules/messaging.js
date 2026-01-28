import { apiCall, getCurrentUser } from '../api.js';
import { showToast, showModal, closeModal, formatDate } from '../utils.js';

let activeChatId = null;
let activeChatType = null;
let pollingInterval = null;

export async function init() {
    console.log('Messaging Module Initialized');
    
    // Make functions globally available
    window.handleSendMessage = handleSendMessage;
    window.selectChat = selectChat;
    window.toggleMessagingSidebar = toggleMessagingSidebar;
    window.handleNewGroupSubmit = handleNewGroupSubmit;
    window.startVideoCall = startVideoCall;
    window.startVoiceCall = startVoiceCall;
    window.toggleChatInfo = toggleChatInfo;
    window.filterConversations = filterConversations;
    window.openNewChatModal = openNewChatModal;
    window.handleNewChatSearch = handleNewChatSearch;
    window.startPrivateChat = startPrivateChat;

    injectModals();
    await loadConversations();
    
    // Start polling for new messages (Simple real-time simulation)
    startPolling();

    // Initialize WebRTC
    if (window.webrtcManager) window.webrtcManager.init();
}

function startPolling() {
    if (pollingInterval) clearInterval(pollingInterval);
    pollingInterval = setInterval(async () => {
        // Only poll if we are on messaging view
        if (!document.getElementById('view-messaging')) {
            clearInterval(pollingInterval);
            return;
        }
        
        if (activeChatId) {
             await loadMessages(activeChatId, activeChatType, false); // false = don't scroll unless new
        }
    }, 5000);
}

function injectModals() {
    const modalContainer = document.getElementById('modal-container');
    
    // Remove existing messaging modals to ensure updates are applied
    const existingIds = [
        'messaging-new-chat-modal', 
        'messaging-new-group-modal', 
        'call-modal', 
        'call-settings-modal', 
        'incoming-call-modal', 
        'chat-info-modal'
    ];
    
    existingIds.forEach(id => {
        const el = document.getElementById(id);
        if (el) el.remove();
    });

    const modalsHTML = `
    <!-- New Chat Modal -->
    <div id="messaging-new-chat-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center">
        <div class="bg-white dark:bg-gray-800 rounded-lg w-full max-w-md mx-4 overflow-hidden shadow-xl transform transition-all h-[600px] flex flex-col">
            <div class="flex justify-between items-center px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Yeni Sohbet Başlat</h3>
                <button onclick="closeModal('messaging-new-chat-modal')" class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                 <input type="text" oninput="handleNewChatSearch(event)" placeholder="Kişi ara..." class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white">
            </div>
            <div class="flex-1 overflow-y-auto p-4 space-y-2" id="new-chat-users-list">
                 <div class="text-center text-gray-500 mt-10">Kişiler yükleniyor...</div>
            </div>
        </div>
    </div>

    <div id="messaging-new-group-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center">
        <div class="bg-white dark:bg-gray-800 rounded-lg w-full max-w-md mx-4 overflow-hidden shadow-xl transform transition-all">
            <div class="flex justify-between items-center px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Yeni Grup Oluştur</h3>
                <button onclick="closeModal('messaging-new-group-modal')" class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form onsubmit="handleNewGroupSubmit(event)" class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Grup Adı</label>
                    <input type="text" name="name" required class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white sm:text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Üyeler (Email)</label>
                    <input type="text" name="members" placeholder="user1@example.com, user2@example.com" class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white sm:text-sm">
                    <p class="text-xs text-gray-500 mt-1">Virgülle ayırarak birden fazla e-posta girebilirsiniz.</p>
                </div>
                <div class="flex justify-end pt-4">
                    <button type="button" onclick="closeModal('messaging-new-group-modal')" class="bg-white dark:bg-gray-700 py-2 px-4 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-200 mr-3">İptal</button>
                    <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">Oluştur</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Call Modal -->
    <div id="call-modal" class="fixed inset-0 bg-black bg-opacity-90 z-50 hidden">
        <div class="flex flex-col h-full relative">
            <!-- Remote Video -->
            <div class="flex-1 flex items-center justify-center bg-gray-900">
                <video id="remote-video" autoplay playsinline class="max-w-full max-h-full object-contain"></video>
                <div id="remote-audio-placeholder" class="hidden flex flex-col items-center">
                    <div class="w-24 h-24 rounded-full bg-gray-700 flex items-center justify-center mb-4">
                        <i class="fas fa-user text-4xl text-gray-400"></i>
                    </div>
                    <span class="text-white text-xl" id="call-remote-name">Kullanıcı</span>
                </div>
            </div>
            
            <!-- Local Video (PIP) -->
            <div class="absolute top-4 right-4 w-32 h-48 bg-gray-800 rounded-lg overflow-hidden shadow-lg border border-gray-700">
                <video id="local-video" autoplay muted playsinline class="w-full h-full object-cover"></video>
            </div>
            
            <!-- Status -->
            <div class="absolute top-8 left-1/2 transform -translate-x-1/2 bg-black bg-opacity-50 px-4 py-2 rounded-full z-10">
                <span id="call-status" class="text-white text-sm font-medium">Aranıyor...</span>
            </div>
            
            <!-- Controls -->
            <div class="absolute bottom-8 left-1/2 transform -translate-x-1/2">
                <div class="flex space-x-6">
                    <button id="mute-btn" onclick="window.webrtcManager.toggleAudio()" class="bg-gray-600 hover:bg-gray-500 p-4 rounded-full transition-colors">
                        <i class="fas fa-microphone text-white text-xl"></i>
                    </button>
                    <button id="end-call-btn" onclick="window.webrtcManager.endCall()" class="bg-red-600 hover:bg-red-500 p-4 rounded-full transition-colors shadow-lg transform hover:scale-105">
                        <i class="fas fa-phone-slash text-white text-xl"></i>
                    </button>
                    <button id="video-toggle-btn" onclick="window.webrtcManager.toggleVideo()" class="bg-gray-600 hover:bg-gray-500 p-4 rounded-full transition-colors">
                        <i class="fas fa-video text-white text-xl"></i>
                    </button>
                    <button id="settings-btn" onclick="window.webrtcManager.openSettings()" class="bg-gray-600 hover:bg-gray-500 p-4 rounded-full transition-colors ml-4">
                        <i class="fas fa-cog text-white text-xl"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Call Settings Modal -->
    <div id="call-settings-modal" class="fixed inset-0 bg-black bg-opacity-80 z-50 hidden flex items-center justify-center">
        <div class="bg-white dark:bg-gray-800 rounded-lg w-full max-w-sm mx-4 overflow-hidden shadow-xl p-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Arama Ayarları</h3>
            
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Mikrofon</label>
                    <select id="audio-input-select" class="w-full bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md py-2 px-3 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Kamera</label>
                    <select id="video-input-select" class="w-full bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md py-2 px-3 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </select>
                </div>
            </div>

            <div class="flex justify-end pt-6 space-x-3">
                <button onclick="document.getElementById('call-settings-modal').classList.add('hidden')" class="px-4 py-2 text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-white">İptal</button>
                <button onclick="window.webrtcManager.saveSettings()" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">Kaydet</button>
            </div>
        </div>
    </div>

    <!-- Incoming Call Modal -->
    <div id="incoming-call-modal" class="fixed inset-0 bg-black bg-opacity-80 z-50 hidden flex items-center justify-center">
        <div class="bg-gray-800 rounded-lg p-8 max-w-sm w-full text-center shadow-2xl border border-gray-700">
            <div class="mb-6">
                <img id="incoming-call-avatar" src="/images/default-avatar.svg" class="w-24 h-24 rounded-full mx-auto border-4 border-indigo-500">
            </div>
            <h3 id="incoming-call-name" class="text-xl font-bold text-white mb-2">Bilinmeyen</h3>
            <p id="incoming-call-type" class="text-indigo-400 mb-8">Sesli Arama</p>
            <div class="flex justify-center space-x-8">
                <button onclick="window.webrtcManager.rejectIncomingCall()" class="bg-red-600 hover:bg-red-500 p-4 rounded-full transition-colors animate-pulse">
                    <i class="fas fa-phone-slash text-white text-xl"></i>
                </button>
                <button onclick="window.webrtcManager.acceptIncomingCall()" class="bg-green-600 hover:bg-green-500 p-4 rounded-full transition-colors animate-bounce">
                    <i class="fas fa-phone text-white text-xl"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Chat Info Modal -->
    <div id="chat-info-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center">
        <div class="bg-white dark:bg-gray-800 rounded-lg w-full max-w-md mx-4 overflow-hidden shadow-xl" id="chat-info-content">
            <!-- Content loaded dynamically -->
        </div>
    </div>
    `;
    modalContainer.innerHTML += modalsHTML;
}

async function loadConversations() {
    const res = await apiCall('/messages');
    if (!res || !res.data) return;
    
    let items = [];
    if (Array.isArray(res.data)) {
        items = res.data;
    } else if (res.data.conversations) {
        items = res.data.conversations;
    } else {
        if (res.data.groups) items = [...items, ...res.data.groups.map(g => ({...g, type: 'group'}))];
    }
    
    if (items.length === 0) {
        const userRes = await apiCall('/users');
        if (userRes && userRes.data && userRes.data.items) {
             items = userRes.data.items.map(u => ({
                 id: u.id,
                 name: `${u.first_name} ${u.last_name}`,
                 avatar: u.avatar,
                 type: 'user',
                 last_message: 'Sohbet başlatın',
                 unread_count: 0
             }));
        }
    }

    const list = document.getElementById('messaging-conversations-list');
    if (!list) return;

    list.innerHTML = items.map(item => `
        <div onclick="selectChat('${item.id}', '${item.type || 'user'}')" class="flex items-center p-4 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 border-b border-gray-100 dark:border-gray-700 transition-colors ${activeChatId === item.id ? 'bg-indigo-50 dark:bg-gray-700 border-l-4 border-l-indigo-500' : ''}">
            <div class="relative flex-shrink-0">
                <img src="${item.avatar || '/images/default-avatar.svg'}" class="w-10 h-10 rounded-full bg-gray-200">
                ${item.is_online ? '<span class="absolute bottom-0 right-0 w-3 h-3 bg-green-500 border-2 border-white dark:border-gray-800 rounded-full"></span>' : ''}
            </div>
            <div class="ml-3 flex-1 min-w-0">
                <div class="flex justify-between items-baseline">
                    <h3 class="text-sm font-medium text-gray-900 dark:text-white truncate">${item.name || item.title}</h3>
                    <span class="text-xs text-gray-400">${item.last_message_time ? formatDate(item.last_message_time).slice(0,5) : ''}</span>
                </div>
                <div class="flex justify-between items-center mt-1">
                    <p class="text-sm text-gray-500 dark:text-gray-400 truncate w-4/5">${item.last_message || ''}</p>
                    ${item.unread_count > 0 ? `<span class="bg-indigo-600 text-white text-xs font-bold px-2 py-0.5 rounded-full">${item.unread_count}</span>` : ''}
                </div>
            </div>
        </div>
    `).join('');
}

async function selectChat(id, type) {
    activeChatId = id;
    activeChatType = type;
    
    document.querySelectorAll('#messaging-conversations-list > div').forEach(el => {
        el.classList.remove('bg-indigo-50', 'dark:bg-gray-700', 'border-l-4', 'border-l-indigo-500');
    });
    await loadConversations(); 

    const chatArea = document.getElementById('messaging-chat-area');
    const sidebar = document.getElementById('messaging-sidebar');
    if (window.innerWidth < 768) {
        sidebar.classList.add('hidden');
        chatArea.classList.remove('hidden');
    }

    document.getElementById('messaging-input-area').classList.remove('hidden');
    document.getElementById('current-chat-id').value = id;
    document.getElementById('current-chat-type').value = type;

    const nameEl = document.getElementById('chat-header-name');
    nameEl.textContent = 'Yükleniyor...';
    
    await loadMessages(id, type, true);
}

async function loadMessages(id, type, scroll = true) {
    const res = await apiCall(`/messages?target_id=${id}&type=${type}`);
    
    const container = document.getElementById('messaging-messages-container');
    if (!res || !res.data) {
        container.innerHTML = '<p class="text-center text-gray-500 mt-4">Mesajlar yüklenemedi.</p>';
        return;
    }
    
    if (res.data.target) {
        document.getElementById('chat-header-name').textContent = res.data.target.name;
        document.getElementById('chat-header-avatar').src = res.data.target.avatar || '/images/default-avatar.svg';
    }

    const currentUser = getCurrentUser();
    const messages = res.data.messages || [];

    if (messages.length === 0) {
        container.innerHTML = '<p class="text-center text-gray-400 mt-10">Henüz mesaj yok. İlk mesajı siz gönderin!</p>';
        return;
    }

    const html = messages.map(msg => {
        const isMe = msg.sender_id === currentUser.id;
        return `
            <div class="flex ${isMe ? 'justify-end' : 'justify-start'} mb-4">
                ${!isMe ? `<img src="${msg.sender_avatar || '/images/default-avatar.svg'}" class="w-8 h-8 rounded-full bg-gray-200 mr-2 self-end">` : ''}
                <div class="max-w-[70%] ${isMe ? 'bg-indigo-600 text-white rounded-br-none' : 'bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 rounded-bl-none'} rounded-2xl px-4 py-2 shadow-sm">
                    ${!isMe && type === 'group' ? `<p class="text-xs font-bold text-indigo-500 mb-1">${msg.sender_name || 'Unknown'}</p>` : ''}
                    <p class="text-sm">${msg.message || msg.content || ''}</p>
                    <p class="text-xs ${isMe ? 'text-indigo-200' : 'text-gray-400'} text-right mt-1">${new Date(msg.created_at).toLocaleTimeString('tr-TR', {hour: '2-digit', minute: '2-digit'})}</p>
                </div>
            </div>
        `;
    }).join('');
    
    container.innerHTML = html;

    if (scroll) {
        container.scrollTop = container.scrollHeight;
    }
}

async function handleSendMessage(e) {
    e.preventDefault();
    const input = document.getElementById('message-input');
    const content = input.value.trim();
    const targetId = document.getElementById('current-chat-id').value;
    const type = document.getElementById('current-chat-type').value;

    if (!content || !targetId) return;

    const res = await apiCall('/messages/send', 'POST', {
        target_id: targetId,
        type: type, 
        content: content
    });

    if (res && (res.success || res.data)) {
        input.value = '';
        loadMessages(targetId, type, true);
    } else {
        showToast('Mesaj gönderilemedi', 'error');
    }
}

async function handleNewGroupSubmit(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData.entries());
    
    if (data.members) {
        data.members = data.members.split(',').map(e => e.trim());
    }

    const res = await apiCall('/messages/groups', 'POST', data);
    if (res && (res.success || res.data)) {
        showToast('Grup oluşturuldu', 'success');
        closeModal('messaging-new-group-modal');
        loadConversations();
    } else {
        showToast(res.error || 'Hata oluştu', 'error');
    }
}

function toggleMessagingSidebar() {
    const sidebar = document.getElementById('messaging-sidebar');
    const chatArea = document.getElementById('messaging-chat-area');
    
    sidebar.classList.toggle('hidden');
    chatArea.classList.toggle('hidden');
}

function filterConversations(e) {
    const term = e.target.value.toLowerCase();
    const items = document.querySelectorAll('#messaging-conversations-list > div');
    
    items.forEach(item => {
        const name = item.querySelector('h3').textContent.toLowerCase();
        if (name.includes(term)) {
            item.classList.remove('hidden');
        } else {
            item.classList.add('hidden');
        }
    });
}

// New Chat Functions
async function openNewChatModal() {
    showModal('messaging-new-chat-modal');
    await loadUsersForChat();
}

async function loadUsersForChat(search = '') {
    const list = document.getElementById('new-chat-users-list');
    list.innerHTML = '<div class="text-center text-gray-500 mt-10">Kişiler yükleniyor...</div>';
    
    try {
        const res = await apiCall(`/messages/users?search=${encodeURIComponent(search)}`);
        if (res && res.data && res.data.users) {
            if (res.data.users.length === 0) {
                list.innerHTML = '<div class="text-center text-gray-500 mt-10">Kişi bulunamadı.</div>';
                return;
            }
            
            list.innerHTML = res.data.users.map(user => `
                <div onclick="startPrivateChat('${user.id}')" class="flex items-center p-3 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg cursor-pointer transition-colors border border-transparent hover:border-gray-200 dark:hover:border-gray-600">
                    <img src="${user.avatar || '/images/default-avatar.svg'}" class="w-10 h-10 rounded-full bg-gray-200 mr-3 object-cover">
                    <div>
                        <h4 class="text-sm font-medium text-gray-900 dark:text-white">${user.name}</h4>
                        <p class="text-xs text-gray-500 dark:text-gray-400">${user.email}</p>
                    </div>
                </div>
            `).join('');
        } else {
             list.innerHTML = '<div class="text-center text-red-500 mt-10">Kişiler yüklenemedi.</div>';
        }
    } catch (e) {
        console.error(e);
        list.innerHTML = '<div class="text-center text-red-500 mt-10">Hata oluştu.</div>';
    }
}

let searchTimeout;
function handleNewChatSearch(e) {
    clearTimeout(searchTimeout);
    const term = e.target.value;
    searchTimeout = setTimeout(() => {
        loadUsersForChat(term);
    }, 300);
}

async function startPrivateChat(userId) {
    closeModal('messaging-new-chat-modal');
    
    // Check if conversation already exists in the list
    // Or just load it directly. selectChat handles loading messages.
    // However, selectChat expects the chat item to exist in the sidebar to highlight it.
    // If it doesn't exist (new chat), we should probably reload conversations first or mock it.
    
    // Let's try to just select it. If it's a new chat, loadMessages will handle empty state.
    // But we need to make sure the sidebar updates to show this new user if they weren't there.
    // The easiest way is to set active chat and reload conversations list.
    
    activeChatId = userId;
    activeChatType = 'user';
    
    // Manually trigger chat selection UI
    document.getElementById('messaging-input-area').classList.remove('hidden');
    document.getElementById('current-chat-id').value = userId;
    document.getElementById('current-chat-type').value = 'user';
    
    // Show Loading state in header
    document.getElementById('chat-header-name').textContent = 'Yükleniyor...';
    
    // Load messages (will likely be empty)
    await loadMessages(userId, 'user', true);
    
    // Reload sidebar to ensure this user appears at top if we just started talking (although usually they appear after first message)
    // If no messages exist, they won't appear in the sidebar list returned by backend unless we handle it.
    // For now, let's just let them chat. Once they send a message, the sidebar will update on next poll/reload.
    
    const chatArea = document.getElementById('messaging-chat-area');
    const sidebar = document.getElementById('messaging-sidebar');
    if (window.innerWidth < 768) {
        sidebar.classList.add('hidden');
        chatArea.classList.remove('hidden');
    }
}

class WebRTCManager {
    constructor() {
        this.peerConnection = null;
        this.localStream = null;
        this.remoteStream = null;
        this.currentCallId = null;
        this.callType = null;
        this.pollingInterval = null;
        this.signalPollingInterval = null;
        this.pendingCallInterval = null;
        this.isInitiator = false;
        this.currentAudioDeviceId = null;
        this.currentVideoDeviceId = null;
        this.iceCandidateQueue = [];
        
        // ICE Servers (Google STUN is free)
        this.iceServers = {
            iceServers: [
                { urls: 'stun:stun.l.google.com:19302' },
                { urls: 'stun:stun1.l.google.com:19302' }
            ]
        };
    }

    async init() {
        this.startIncomingCallPolling();
    }

    startIncomingCallPolling() {
        if (this.pollingInterval) clearInterval(this.pollingInterval);
        this.pollingInterval = setInterval(async () => {
            // Check for incoming calls
            // Only if not currently in a call
            if (this.currentCallId) return;

            try {
                const res = await apiCall('/calls/poll');
                if (res && res.data && res.data.incoming_call) {
                    this.showIncomingCall(res.data.incoming_call);
                }
            } catch (e) {
                // console.error("Poll error", e);
            }
        }, 3000);
    }

    showIncomingCall(call) {
        this.currentCallId = call.id;
        this.callType = call.call_type;
        this.isInitiator = false;
        
        const modal = document.getElementById('incoming-call-modal');
        document.getElementById('incoming-call-name').textContent = `${call.first_name} ${call.last_name}`;
        document.getElementById('incoming-call-avatar').src = call.avatar || '/images/default-avatar.svg';
        document.getElementById('incoming-call-type').textContent = call.call_type === 'video' ? 'Görüntülü Arama' : 'Sesli Arama';
        
        modal.classList.remove('hidden');
        
        // Start polling to check if caller cancels
        this.startPendingCallPolling();
    }

    startPendingCallPolling() {
        if (this.pendingCallInterval) clearInterval(this.pendingCallInterval);
        this.pendingCallInterval = setInterval(async () => {
            if (!this.currentCallId) {
                clearInterval(this.pendingCallInterval);
                return;
            }
            try {
                const res = await apiCall(`/calls/status?call_id=${this.currentCallId}`);
                if (res && res.data && res.data.status === 'ended') {
                    // Call was cancelled by caller
                    this.rejectIncomingCall(true); // true = silent/remote cancelled
                }
            } catch (e) {
                console.error("Pending poll error", e);
            }
        }, 2000);
    }

    async acceptIncomingCall() {
        if (this.pendingCallInterval) clearInterval(this.pendingCallInterval);
        document.getElementById('incoming-call-modal').classList.add('hidden');
        await this.startCallSession(this.callType);
    }

    async rejectIncomingCall(isRemoteCancelled = false) {
        if (this.pendingCallInterval) clearInterval(this.pendingCallInterval);
        
        if (this.currentCallId && !isRemoteCancelled) {
            await apiCall('/calls/end', 'POST', { call_id: this.currentCallId });
        }
        this.cleanup();
        document.getElementById('incoming-call-modal').classList.add('hidden');
        
        if (isRemoteCancelled) {
            showToast('Arama sonlandırıldı', 'info');
        }
    }

    async startCall(targetId, type) {
        this.callType = type;
        this.isInitiator = true;
        
        try {
            // 1. Create Call Record
            const res = await apiCall('/calls/start', 'POST', {
                target_id: targetId,
                call_type: type
            });
            
            if (res && res.data) {
                this.currentCallId = res.data.call_id;
                await this.startCallSession(type);
            } else {
                showToast(res.error || 'Arama başlatılamadı', 'error');
            }
        } catch (error) {
            console.error(error);
            showToast('Arama hatası', 'error');
        }
    }

    async startCallSession(type) {
        try {
            // Show Call Modal
            const modal = document.getElementById('call-modal');
            modal.classList.remove('hidden');
            
            // Initial Status
            const statusEl = document.getElementById('call-status');
            statusEl.textContent = 'Kamera izni bekleniyor...';
            
            if (type === 'voice') {
                document.getElementById('local-video').classList.add('hidden');
                document.getElementById('remote-audio-placeholder').classList.remove('hidden');
            } else {
                document.getElementById('local-video').classList.remove('hidden');
                document.getElementById('remote-audio-placeholder').classList.add('hidden');
            }

            // Get User Media
            const constraints = {
                audio: true,
                video: type === 'video'
            };
            
            // Timeout warning
            const permissionTimeout = setTimeout(() => {
                statusEl.textContent = 'Lütfen izin verin...';
                showToast('Lütfen tarayıcıda kamera/mikrofon izni verin.', 'info');
            }, 3000);

            try {
                this.localStream = await navigator.mediaDevices.getUserMedia(constraints);
                clearTimeout(permissionTimeout);
                statusEl.textContent = 'Bağlantı kuruluyor...';
            } catch (err) {
                clearTimeout(permissionTimeout);
                if (err.name === 'NotFoundError' && type === 'video') {
                     showToast('Kamera bulunamadı, sesli aramaya geçiliyor...', 'warning');
                     statusEl.textContent = 'Kamera yok, sesliye geçiliyor...';
                     constraints.video = false;
                     this.localStream = await navigator.mediaDevices.getUserMedia(constraints);
                     // Update UI for voice
                     document.getElementById('local-video').classList.add('hidden');
                     document.getElementById('remote-audio-placeholder').classList.remove('hidden');
                } else {
                    statusEl.textContent = 'Erişim hatası!';
                    throw err;
                }
            }
            
            document.getElementById('local-video').srcObject = this.localStream;
            
            // Store current device IDs
            this.localStream.getTracks().forEach(track => {
                 const settings = track.getSettings();
                 if (track.kind === 'audio') this.currentAudioDeviceId = settings.deviceId;
                 if (track.kind === 'video') this.currentVideoDeviceId = settings.deviceId;
            });
            
            // Initialize Peer Connection
            this.peerConnection = new RTCPeerConnection(this.iceServers);
            
            // Connection State Changes
            this.peerConnection.onconnectionstatechange = () => {
                const state = this.peerConnection.connectionState;
                const statusEl = document.getElementById('call-status');
                if (statusEl) {
                    if (state === 'connected') statusEl.textContent = 'Bağlandı';
                    else if (state === 'disconnected') statusEl.textContent = 'Bağlantı koptu';
                    else if (state === 'failed') statusEl.textContent = 'Bağlantı hatası';
                    else if (state === 'closed') statusEl.textContent = 'Arama sonlandı';
                    else if (state === 'checking') statusEl.textContent = 'Sunucu aranıyor...';
                }
            };

            this.peerConnection.oniceconnectionstatechange = () => {
                const state = this.peerConnection.iceConnectionState;
                const statusEl = document.getElementById('call-status');
                if (statusEl) {
                    if (state === 'connected' || state === 'completed') statusEl.textContent = 'Bağlandı';
                    else if (state === 'failed') statusEl.textContent = 'Bağlantı kurulamadı';
                    else if (state === 'disconnected') statusEl.textContent = 'Bağlantı kesildi';
                    else if (state === 'checking') statusEl.textContent = 'Sunucu aranıyor...';
                }
            };

            // Add Tracks
            this.localStream.getTracks().forEach(track => {
                this.peerConnection.addTrack(track, this.localStream);
            });
            
            // Handle Remote Stream
            this.peerConnection.ontrack = (event) => {
                this.remoteStream = event.streams[0];
                const remoteVideo = document.getElementById('remote-video');
                remoteVideo.srcObject = this.remoteStream;
                document.getElementById('call-status').textContent = 'Bağlandı';
            };

            // Handle ICE Candidates
            this.peerConnection.onicecandidate = (event) => {
                if (event.candidate) {
                    this.sendSignal('candidate', event.candidate);
                }
            };
            
            // Start Signaling Polling
            this.startSignalPolling();

            // Create Offer if Initiator
            if (this.isInitiator) {
                const offer = await this.peerConnection.createOffer();
                await this.peerConnection.setLocalDescription(offer);
                await this.sendSignal('offer', offer);
            } else {
                // Waiting for Offer...
            }

        } catch (error) {
            console.error('Media Access Error:', error);
            if (error.name === 'NotAllowedError') {
                showToast('Kamera/Mikrofon izni reddedildi.', 'error');
            } else {
                showToast('Kamera/Mikrofon erişimi sağlanamadı', 'error');
            }
            this.endCall();
        }
    }

    async sendSignal(type, payload) {
        if (!this.currentCallId) return;
        
        // Base64 encode payload to avoid SDP newline issues during transport
        const encodedPayload = btoa(JSON.stringify(payload));
        
        await apiCall('/calls/signal', 'POST', {
            call_id: this.currentCallId,
            type: type,
            payload: encodedPayload
        });
    }

    startSignalPolling() {
        if (this.signalPollingInterval) clearInterval(this.signalPollingInterval);
        this.signalPollingInterval = setInterval(async () => {
            if (!this.currentCallId) return;

            try {
                const res = await apiCall(`/calls/signals?call_id=${this.currentCallId}`);
                if (res && res.data && res.data.signals) {
                    for (const signal of res.data.signals) {
                        await this.handleSignal(signal);
                    }
                }
            } catch (e) {
                console.error("Signal poll error", e);
            }
        }, 1000); // Poll every 1 second
    }

    normalizeSDP(sdp) {
        if (!sdp) return sdp;
        // Robust normalization: Split by any newline char, trim lines, rejoin with CRLF
        return sdp.split(/\r\n|\r|\n/)
            .map(l => l.trim())
            .filter(l => l.length > 0)
            .join('\r\n') + '\r\n';
    }

    async handleSignal(signal) {
        if (!this.peerConnection) return;
        
        let payload = signal.payload;
        // Decode if string (Base64)
        if (typeof payload === 'string') {
            try {
                payload = JSON.parse(atob(payload));
            } catch (e) {
                console.error('Error decoding payload: ' + e.message);
                return;
            }
        }

        try {
            if (signal.type === 'offer') {
                if (this.isInitiator) return; // Should not happen
                
                await this.peerConnection.setRemoteDescription(new RTCSessionDescription(payload));
                
                // Process queued candidates
                if (this.iceCandidateQueue.length > 0) {
                    while (this.iceCandidateQueue.length > 0) {
                        const candidate = this.iceCandidateQueue.shift();
                        try {
                            await this.peerConnection.addIceCandidate(candidate);
                        } catch (e) {
                            console.error("Error adding queued candidate", e);
                        }
                    }
                }

                const answer = await this.peerConnection.createAnswer();
                await this.peerConnection.setLocalDescription(answer);
                await this.sendSignal('answer', answer);
            } else if (signal.type === 'answer') {
                if (!this.isInitiator) return;
                
                await this.peerConnection.setRemoteDescription(new RTCSessionDescription(payload));
                
                // Process queued candidates
                if (this.iceCandidateQueue.length > 0) {
                    while (this.iceCandidateQueue.length > 0) {
                        const candidate = this.iceCandidateQueue.shift();
                        try {
                            await this.peerConnection.addIceCandidate(candidate);
                        } catch (e) {
                            console.error("Error adding queued candidate", e);
                        }
                    }
                }
            } else if (signal.type === 'candidate') {
                try {
                    const candidate = new RTCIceCandidate(payload);
                    if (this.peerConnection.remoteDescription && this.peerConnection.remoteDescription.type) {
                        await this.peerConnection.addIceCandidate(candidate);
                    } else {
                        console.log("Queueing candidate as remote description is not set");
                        this.iceCandidateQueue.push(candidate);
                    }
                } catch (e) {
                    console.error("Error adding ICE candidate", e);
                }
            }
        } catch (e) {
            console.error("Error handling signal", e, signal);
        }
    }

    async endCall() {
        if (this.currentCallId) {
            // Use sendBeacon for reliability on unload, or regular apiCall
            await apiCall('/calls/end', 'POST', { call_id: this.currentCallId });
        }
        this.cleanup();
    }

    cleanup() {
        if (this.localStream) {
            this.localStream.getTracks().forEach(track => {
                track.stop();
                track.enabled = false;
            });
        }
        if (this.peerConnection) {
            this.peerConnection.close();
        }
        if (this.signalPollingInterval) {
            clearInterval(this.signalPollingInterval);
        }
        
        // Release srcObjects
        const localVideo = document.getElementById('local-video');
        const remoteVideo = document.getElementById('remote-video');
        if (localVideo) localVideo.srcObject = null;
        if (remoteVideo) remoteVideo.srcObject = null;

        this.localStream = null;
        this.remoteStream = null;
        this.peerConnection = null;
        this.currentCallId = null;
        this.signalPollingInterval = null;
        this.isInitiator = false;
        this.iceCandidateQueue = [];
        
        document.getElementById('call-modal').classList.add('hidden');
        document.getElementById('incoming-call-modal').classList.add('hidden');
    }

    async getDevices() {
        try {
            const devices = await navigator.mediaDevices.enumerateDevices();
            const audioInputs = devices.filter(device => device.kind === 'audioinput');
            const videoInputs = devices.filter(device => device.kind === 'videoinput');
            return { audioInputs, videoInputs };
        } catch (e) {
            console.error("Error getting devices", e);
            return { audioInputs: [], videoInputs: [] };
        }
    }

    async openSettings() {
        const modal = document.getElementById('call-settings-modal');
        const audioSelect = document.getElementById('audio-input-select');
        const videoSelect = document.getElementById('video-input-select');
        
        // Clear options
        audioSelect.innerHTML = '';
        videoSelect.innerHTML = '';
        
        const { audioInputs, videoInputs } = await this.getDevices();
        
        audioInputs.forEach(device => {
            const option = document.createElement('option');
            option.value = device.deviceId;
            option.text = device.label || `Microphone ${audioSelect.length + 1}`;
            if (this.currentAudioDeviceId === device.deviceId) option.selected = true;
            audioSelect.appendChild(option);
        });

        videoInputs.forEach(device => {
            const option = document.createElement('option');
            option.value = device.deviceId;
            option.text = device.label || `Camera ${videoSelect.length + 1}`;
            if (this.currentVideoDeviceId === device.deviceId) option.selected = true;
            videoSelect.appendChild(option);
        });
        
        modal.classList.remove('hidden');
    }

    async saveSettings() {
        const audioSelect = document.getElementById('audio-input-select');
        const videoSelect = document.getElementById('video-input-select');
        
        const newAudioId = audioSelect.value;
        const newVideoId = videoSelect.value;
        
        if (newAudioId !== this.currentAudioDeviceId) {
            this.currentAudioDeviceId = newAudioId;
            await this.switchDevice('audio');
        }
        
        if (newVideoId !== this.currentVideoDeviceId) {
            this.currentVideoDeviceId = newVideoId;
            await this.switchDevice('video');
        }
        
        document.getElementById('call-settings-modal').classList.add('hidden');
    }

    async switchDevice(type) {
        if (!this.localStream) return;
        
        const constraints = {};
        if (type === 'audio') {
            constraints.audio = { deviceId: { exact: this.currentAudioDeviceId } };
            constraints.video = false;
        } else {
            constraints.video = { deviceId: { exact: this.currentVideoDeviceId } };
            constraints.audio = false;
        }

        try {
            const newStream = await navigator.mediaDevices.getUserMedia(constraints);
            const newTrack = newStream.getTracks()[0];

            if (!newTrack) return;

            // 1. Replace in PeerConnection
            if (this.peerConnection) {
                const sender = this.peerConnection.getSenders().find(s => s.track && s.track.kind === type);
                if (sender) {
                    await sender.replaceTrack(newTrack);
                }
            }

            // 2. Replace in Local Stream (UI)
            const oldTrack = this.localStream.getTracks().find(t => t.kind === type);
            if (oldTrack) {
                this.localStream.removeTrack(oldTrack);
                oldTrack.stop();
            }
            this.localStream.addTrack(newTrack);
            
        } catch (e) {
            console.error("Error switching device", e);
            showToast('Cihaz değiştirilemedi: ' + e.message, 'error');
        }
    }

    toggleAudio() {
        if (this.localStream) {
            const audioTrack = this.localStream.getAudioTracks()[0];
            if (audioTrack) {
                audioTrack.enabled = !audioTrack.enabled;
                const btn = document.getElementById('mute-btn');
                btn.classList.toggle('bg-red-600');
                btn.innerHTML = audioTrack.enabled ? '<i class="fas fa-microphone text-white text-xl"></i>' : '<i class="fas fa-microphone-slash text-white text-xl"></i>';
            }
        }
    }

    toggleVideo() {
        if (this.localStream && this.callType === 'video') {
            const videoTrack = this.localStream.getVideoTracks()[0];
            if (videoTrack) {
                videoTrack.enabled = !videoTrack.enabled;
                const btn = document.getElementById('video-toggle-btn');
                btn.classList.toggle('bg-red-600');
                btn.innerHTML = videoTrack.enabled ? '<i class="fas fa-video text-white text-xl"></i>' : '<i class="fas fa-video-slash text-white text-xl"></i>';
            }
        }
    }
}

// Initialize WebRTC Manager
window.webrtcManager = new WebRTCManager();

function startVideoCall() {
    if (!activeChatId) return;
    window.webrtcManager.startCall(activeChatId, 'video');
}

function startVoiceCall() {
    if (!activeChatId) return;
    window.webrtcManager.startCall(activeChatId, 'voice');
}

async function toggleChatInfo() {
    if (!activeChatId) return;
    
    try {
        const res = await apiCall(`/messages/chat-info?chat_id=${activeChatId}&type=${activeChatType}`);
        
        if (res && res.data) {
            showChatInfoModal(res.data);
        }
    } catch (error) {
        console.error('Chat info load failed:', error);
        showToast('Sohbet bilgileri yüklenemedi', 'error');
    }
}

function showChatInfoModal(data) {
    const modal = document.getElementById('chat-info-modal');
    const content = document.getElementById('chat-info-content');
    
    content.innerHTML = `
        <div class="p-6">
            <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-white">Sohbet Bilgileri</h3>
            
            <div class="flex items-center mb-6">
                <img src="${data.avatar || '/images/default-avatar.svg'}" class="w-16 h-16 rounded-full mr-4 border-2 border-indigo-100">
                <div>
                    <h4 class="text-xl font-bold text-gray-900 dark:text-white">${data.name}</h4>
                    <p class="text-sm text-gray-500 dark:text-gray-400 capitalize">${data.type === 'group' ? 'Grup Sohbeti' : 'Kişisel Sohbet'}</p>
                </div>
            </div>
            
            <!-- Participants -->
            <div class="mb-6">
                <h4 class="font-medium mb-3 text-gray-700 dark:text-gray-300 border-b pb-2">Katılımcılar (${data.participants.length})</h4>
                <div id="participants-list" class="max-h-60 overflow-y-auto space-y-2">
                    ${data.participants.map(p => `
                        <div class="flex items-center p-2 hover:bg-gray-50 dark:hover:bg-gray-700 rounded transition-colors">
                            <img src="${p.avatar || '/images/default-avatar.svg'}" class="w-8 h-8 rounded-full mr-3">
                            <span class="text-gray-800 dark:text-gray-200">${p.name}</span>
                        </div>
                    `).join('')}
                </div>
            </div>
            
            <!-- Actions -->
            <div class="flex justify-end space-x-3 pt-4 border-t border-gray-100 dark:border-gray-700">
                <button onclick="document.getElementById('chat-info-modal').classList.add('hidden')" 
                        class="px-4 py-2 text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-white transition-colors">
                    Kapat
                </button>
            </div>
        </div>
    `;
    
    modal.classList.remove('hidden');
}
