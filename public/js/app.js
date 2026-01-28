import { apiCall, getCurrentUser } from './api.js';
import { showToast } from './utils.js';

// Application Core
const App = {
    user: null,
    tenant: null,
    currentModule: null,

    async init() {
        console.log('CoreFlow SPA initializing...');
        
        // Check Auth
        const token = localStorage.getItem('token');
        if (!token) {
            window.location.href = '/login.html';
            return;
        }

        // Fetch Profile & Config
        const res = await apiCall('/auth/profile');
        if (res && res.data) {
            this.user = res.data.user || res.data; // Handle {data: {user: ...}} vs {data: ...}
            this.tenant = this.user.tenant || res.data.tenant;
            this.renderSidebar();
            this.renderHeader();
            this.handleRoute();
            
            // Initial Notification Fetch
            this.fetchNotifications();
            // Poll every 60 seconds
            setInterval(() => this.fetchNotifications(), 60000);
            
        } else {
            // Token invalid
            window.location.href = '/login.html';
        }

        // Global Event Listeners
        window.addEventListener('hashchange', () => this.handleRoute());
        
        // Expose utils globally for legacy compatibility during migration
        window.showToast = showToast;
        window.showModal = (id) => {
             const modal = document.getElementById(id);
             if (modal) modal.classList.remove('hidden');
        };
        window.closeModal = (id) => {
             const modal = document.getElementById(id);
             if (modal) modal.classList.add('hidden');
        };
        window.showConfirm = (msg) => confirm(msg);
    },

    async handleRoute() {
        let hash = window.location.hash.slice(1);
        // Clean up hash (remove leading slash if present)
        if (hash.startsWith('/')) hash = hash.slice(1);
        
        // Root Admin Routing
        if (hash.startsWith('root/')) {
            await this.loadRootView(hash);
            return;
        }

        // Default to dashboard if empty
        if (!hash) hash = 'dashboard';

        const [module, subview] = hash.split('/');
        
        console.log(`Routing to: ${module} ${subview ? '/ ' + subview : ''}`);
        
        // Highlight menu
        document.querySelectorAll('.nav-link').forEach(el => {
            el.classList.remove('bg-gray-100', 'dark:bg-gray-700', 'text-gray-900', 'dark:text-white');
            el.classList.add('text-gray-600', 'dark:text-gray-300');
            if (el.dataset.target === module) {
                el.classList.add('bg-gray-100', 'dark:bg-gray-700', 'text-gray-900', 'dark:text-white');
                el.classList.remove('text-gray-600', 'dark:text-gray-300');
            }
        });

        await this.loadView(module, subview);
    },

    async loadRootView(hash) {
        // e.g. root/dashboard, root/tenants
        const parts = hash.split('/');
        const viewName = parts[1] || 'dashboard'; // default to dashboard
        
        console.log(`Loading Root View: ${viewName}`);

        // 1. Render Root Sidebar (Different from Tenant Sidebar)
        this.renderRootSidebar(viewName);

        // 2. Load View Content
        const container = document.getElementById('app');
        container.innerHTML = `
            <div class="flex items-center justify-center h-full">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-red-600"></div>
            </div>`;

        try {
            // Attempt to load from specific root views folder
            // We'll store them in public/views/root/
            const viewRes = await fetch(`/views/root/${viewName}.html`);
            if (!viewRes.ok) throw new Error(`Root View not found: ${viewName}`);
            const html = await viewRes.text();
            container.innerHTML = html;

            // Load JS Module
            try {
                const timestamp = new Date().getTime();
                const module = await import(`./modules/root/${viewName}.js?v=${timestamp}`);
                if (module.init) {
                    await module.init();
                }
            } catch (jsError) {
                console.warn(`No JS module found for root/${viewName}:`, jsError);
            }

        } catch (error) {
            console.error('Root View Load Error:', error);
            container.innerHTML = `
                <div class="text-center py-12">
                    <h3 class="text-lg font-medium text-red-600">Root Console Error</h3>
                    <p class="text-gray-500">Unable to load system administration view.</p>
                </div>`;
        }
    },

    renderRootSidebar(activeView) {
        const menuContainer = document.getElementById('sidebar-menu');
        const headerTitle = document.getElementById('page-title');
        
        // Update Header to indicate Root Mode
        if (headerTitle) {
            headerTitle.innerHTML = '<span class="text-red-600 font-bold"><i class="fas fa-shield-alt mr-2"></i>PLATFORM OWNER</span>';
        }

        const menuItems = [
            { id: 'dashboard', icon: 'fa-chart-line', label: 'Genel Bakış' },
            { id: 'tenants', icon: 'fa-building', label: 'Kurum Yönetimi' },
            { id: 'users', icon: 'fa-users-cog', label: 'Global Kullanıcılar' },
            { id: 'billing', icon: 'fa-file-invoice-dollar', label: 'Ödeme & Paketler' },
            { id: 'settings', icon: 'fa-cogs', label: 'Sistem Ayarları' },
            { id: 'logs', icon: 'fa-list-alt', label: 'Sistem Logları' }
        ];

        let html = `
            <div class="px-3 mb-2">
                <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-3 mb-4">
                    <p class="text-xs text-red-800 dark:text-red-200 font-semibold uppercase tracking-wider mb-1">Root Console</p>
                    <p class="text-xs text-red-600 dark:text-red-300">Tam Yetkili Erişim</p>
                </div>
            </div>
        `;

        menuItems.forEach(item => {
            const isActive = activeView === item.id;
            const activeClass = isActive ? 'bg-red-50 text-red-700 dark:bg-red-900/30 dark:text-red-200' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700';
            
            html += `
                <a href="#root/${item.id}" class="group flex items-center px-2 py-2 text-sm font-medium rounded-md transition-colors ${activeClass}">
                    <i class="fas ${item.icon} mr-3 flex-shrink-0 h-6 w-6 text-center ${isActive ? 'text-red-600' : 'text-gray-400 group-hover:text-gray-500'}"></i>
                    ${item.label}
                </a>
            `;
        });

        // Exit Button
        html += `
            <div class="mt-8 pt-4 border-t border-gray-200 dark:border-gray-700">
                <a href="#dashboard" class="group flex items-center px-2 py-2 text-sm font-medium rounded-md text-indigo-600 hover:bg-indigo-50 dark:text-indigo-400 dark:hover:bg-gray-800">
                    <i class="fas fa-sign-out-alt mr-3 flex-shrink-0 h-6 w-6 text-center"></i>
                    Tenant Moduna Dön
                </a>
            </div>
        `;

        menuContainer.innerHTML = html;
    },

    async loadView(moduleName, subview = null) {
        const container = document.getElementById('app');
        
        // Show Loading
        container.innerHTML = `
            <div class="flex items-center justify-center h-full">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600"></div>
            </div>`;

        try {
            console.log(`Attempting to load view: ${moduleName}`);
            // 1. Load HTML View
            const viewRes = await fetch(`/views/${moduleName}.html`);
            if (!viewRes.ok) throw new Error(`View not found: ${moduleName} (Status: ${viewRes.status})`);
            const html = await viewRes.text();
            container.innerHTML = html;

            // 2. Load JS Module
            try {
                // Cache busting for development
                const timestamp = new Date().getTime();
                const module = await import(`./modules/${moduleName}.js?v=${timestamp}`);
                if (module.init) {
                    await module.init(subview);
                }
            } catch (jsError) {
                console.warn(`No JS module found for ${moduleName} or init failed:`, jsError);
            }

        } catch (error) {
            console.error('View Load Error:', error);
            container.innerHTML = `
                <div class="text-center py-12">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Sayfa Yüklenemedi</h3>
                    <p class="text-gray-500 dark:text-gray-400">Aradığınız modül bulunamadı veya erişim yetkiniz yok.</p>
                </div>`;
        }
    },

    async fetchNotifications() {
        const res = await apiCall('/notifications?limit=10&unread=false');
        if (res && res.data) {
            this.notifications = res.data.notifications || [];
            this.unreadCount = res.data.unread_count || 0;
            this.updateNotificationUI();
        }
    },

    updateNotificationUI() {
        const badge = document.getElementById('notification-badge');
        const list = document.getElementById('notification-list');
        const countEl = document.getElementById('notification-count-text');
        
        if (badge) {
            if (this.unreadCount > 0) {
                badge.textContent = this.unreadCount > 9 ? '9+' : this.unreadCount;
                badge.classList.remove('hidden');
            } else {
                badge.classList.add('hidden');
            }
        }
        
        if (countEl) {
            countEl.textContent = this.unreadCount > 0 ? `${this.unreadCount} okunmamış` : 'Bildiriminiz yok';
        }

        if (list) {
            if (this.notifications.length === 0) {
                list.innerHTML = `<div class="px-4 py-6 text-center text-sm text-gray-500">Bildirim bulunmuyor.</div>`;
            } else {
                list.innerHTML = this.notifications.map(n => `
                    <div class="px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors border-b border-gray-100 dark:border-gray-700 ${n.is_read ? 'opacity-75' : 'bg-blue-50 dark:bg-blue-900/20'}">
                        <div class="flex justify-between items-start">
                            <p class="text-sm font-medium text-gray-900 dark:text-white">${n.title}</p>
                            <span class="text-xs text-gray-500">${this.formatDate(n.created_at)}</span>
                        </div>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 line-clamp-2">${n.message}</p>
                        ${!n.is_read ? `
                        <button onclick="App.markNotificationRead('${n.id}')" class="text-xs text-indigo-600 hover:text-indigo-800 mt-2 font-medium">
                            Okundu İşaretle
                        </button>` : ''}
                    </div>
                `).join('');
            }
        }
    },

    async markNotificationRead(id) {
        await apiCall(`/notifications/${id}/read`, 'PUT');
        // Optimistic update
        const n = this.notifications.find(item => item.id === id);
        if (n) {
            n.is_read = true;
            this.unreadCount = Math.max(0, this.unreadCount - 1);
            this.updateNotificationUI();
        }
    },

    async markAllRead() {
        await apiCall('/notifications/mark-all-read', 'POST');
        this.notifications.forEach(n => n.is_read = true);
        this.unreadCount = 0;
        this.updateNotificationUI();
    },

    formatDate(dateStr) {
        if (!dateStr) return '';
        const date = new Date(dateStr);
        const now = new Date();
        const diff = Math.floor((now - date) / 1000); // seconds
        
        if (diff < 60) return 'Şimdi';
        if (diff < 3600) return `${Math.floor(diff/60)}d önce`;
        if (diff < 86400) return `${Math.floor(diff/3600)}s önce`;
        return date.toLocaleDateString('tr-TR');
    },

    renderHeader() {
        const userMenu = document.getElementById('user-menu-button');
        const userDropdown = document.getElementById('user-dropdown');
        const notificationBtn = document.getElementById('notification-btn');
        const notificationDropdown = document.getElementById('notification-dropdown');
        const userAvatar = document.getElementById('header-user-avatar');
        const userName = document.getElementById('header-user-name');
        
        if (this.user) {
            userName.textContent = `${this.user.first_name} ${this.user.last_name}`;
            if (this.user.avatar) userAvatar.src = this.user.avatar;

        }

        // Toggle User Menu
        userMenu.addEventListener('click', (e) => {
            e.stopPropagation();
            userDropdown.classList.toggle('hidden');
            if (!notificationDropdown.classList.contains('hidden')) {
                notificationDropdown.classList.add('hidden');
            }
        });

        // Toggle Notifications
        if (notificationBtn) {
            notificationBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                notificationDropdown.classList.toggle('hidden');
                if (!userDropdown.classList.contains('hidden')) {
                    userDropdown.classList.add('hidden');
                }
            });
        }

        // Close dropdowns when clicking outside
        document.addEventListener('click', () => {
            if (!userDropdown.classList.contains('hidden')) userDropdown.classList.add('hidden');
            if (notificationDropdown && !notificationDropdown.classList.contains('hidden')) notificationDropdown.classList.add('hidden');
        });

        // Logout
        document.getElementById('logout-btn').addEventListener('click', async () => {
            await apiCall('/auth/logout', 'POST');
            localStorage.removeItem('token');
            window.location.href = '/login.html';
        });

        // Theme Toggle
        const themeBtn = document.getElementById('theme-toggle');
        themeBtn.addEventListener('click', () => {
            document.documentElement.classList.toggle('dark');
            const isDark = document.documentElement.classList.contains('dark');
            localStorage.setItem('theme', isDark ? 'dark' : 'light');
        });
        
        // Init Theme
        if (localStorage.getItem('theme') === 'dark' || 
           (!localStorage.getItem('theme') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        }
    },

    renderSidebar() {
        // Update User Info in Sidebar Footer
        if (this.user) {
            const sidebarName = document.getElementById('sidebar-user-name');
            const sidebarRole = document.getElementById('sidebar-user-role');
            const sidebarAvatar = document.getElementById('sidebar-user-avatar');

            if (sidebarName) sidebarName.textContent = `${this.user.first_name} ${this.user.last_name}`;
            if (sidebarRole) sidebarRole.textContent = this.user.role_id || this.user.role || 'Kullanıcı';
            if (sidebarAvatar && this.user.avatar) sidebarAvatar.src = this.user.avatar;
        }

        const menuContainer = document.getElementById('sidebar-menu');
        if (!this.user) return;

        let html = '';

        // Check for Root Impersonation Mode
        if (localStorage.getItem('root_token_backup')) {
            html += `
                <div class="px-2 mb-4">
                    <button id="stop-impersonation-btn" class="w-full bg-red-600 hover:bg-red-700 text-white text-sm font-bold py-2 px-4 rounded shadow-lg flex items-center justify-center transition-colors animate-pulse">
                        <i class="fas fa-sign-out-alt mr-2"></i>
                        ROOT'A DÖN
                    </button>
                </div>
            `;
            // Add listener after render
            setTimeout(() => {
                document.getElementById('stop-impersonation-btn')?.addEventListener('click', () => {
                    const rootToken = localStorage.getItem('root_token_backup');
                    if (rootToken) {
                        localStorage.setItem('token', rootToken);
                        localStorage.removeItem('root_token_backup');
                        window.location.href = '/#root/tenants';
                        window.location.reload();
                    }
                });
            }, 100);
        }

        // Base Menu Items
        const menuItems = [
            { id: 'dashboard', icon: 'fa-home', label: 'Ana Sayfa' },
            { id: 'announcements', icon: 'fa-bullhorn', label: 'Duyurular' },
            { id: 'documents', icon: 'fa-folder', label: 'Dokümanlar' },
            { id: 'projects', icon: 'fa-project-diagram', label: 'Projeler' },
            { id: 'tasks', icon: 'fa-tasks', label: 'Görevler' },
            { id: 'calendar', icon: 'fa-calendar', label: 'Takvim' },
            { id: 'messaging', icon: 'fa-comments', label: 'Mesajlar' },
        ];

        // Conditional Modules based on Tenant Config & Permissions
        // This logic should match CheckModuleEnabled middleware
        let activeModules = [];
        try {
            if (this.tenant && this.tenant.active_modules) {
                if (typeof this.tenant.active_modules === 'string') {
                    activeModules = JSON.parse(this.tenant.active_modules);
                } else if (Array.isArray(this.tenant.active_modules)) {
                    activeModules = this.tenant.active_modules;
                }
            }
        } catch (e) {
            console.error('Error parsing active_modules:', e);
            activeModules = [];
        }
        
        const permissions = this.user.role?.permissions || []; // Assuming backend sends flattened permissions

        if (activeModules.includes('helpdesk')) {
            menuItems.push({ id: 'helpdesk', icon: 'fa-headset', label: 'Destek Masası' });
        }
        if (activeModules.includes('hr')) {
            menuItems.push({ id: 'hr', icon: 'fa-users', label: 'İnsan Kaynakları' });
        }
        if (activeModules.includes('crm')) {
            menuItems.push({ id: 'crm', icon: 'fa-handshake', label: 'Müşteri İlişkileri' });
        }
        if (activeModules.includes('accounting')) {
            menuItems.push({ id: 'accounting', icon: 'fa-calculator', label: 'Ön Muhasebe' });
        }
        if (activeModules.includes('inventory')) {
            menuItems.push({ id: 'inventory', icon: 'fa-boxes', label: 'Stok Takibi' });
        }
        if (activeModules.includes('politics')) {
            menuItems.push({ id: 'politics', icon: 'fa-vote-yea', label: 'Siyasi Parti' });
        }
        if (activeModules.includes('donations')) {
            menuItems.push({ id: 'donations', icon: 'fa-donate', label: 'Bağış Yönetimi' });
        }
        if (activeModules.includes('field')) {
            menuItems.push({ id: 'field', icon: 'fa-map-marked-alt', label: 'Saha Yönetimi' });
        }

        // Render
        menuItems.forEach(item => {
            html += `
                <a href="#${item.id}" data-target="${item.id}" class="nav-link group flex items-center px-2 py-2 text-sm font-medium rounded-md text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white transition-colors">
                    <i class="fas ${item.icon} mr-3 flex-shrink-0 h-6 w-6 text-center"></i>
                    ${item.label}
                </a>
            `;
        });
        
        // Settings & System Settings at bottom
        html += `
            <div class="mt-8 pt-4 border-t border-gray-200 dark:border-gray-700">
                <h3 class="px-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Ayarlar</h3>
                <a href="#settings" data-target="settings" class="nav-link mt-1 group flex items-center px-2 py-2 text-sm font-medium rounded-md text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white">
                    <i class="fas fa-cog mr-3 flex-shrink-0 h-6 w-6 text-center"></i>
                    Hesap Ayarları
                </a>
        `;

        // Super Admin - Tenants Module
        const userRole = this.user.role_id || this.user.role;
        if (userRole === 'super-admin-role') {
             // Add Platform Owner Link
             html += `
                <div class="mt-8 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <h3 class="px-3 text-xs font-semibold text-red-600 dark:text-red-400 uppercase tracking-wider">Sistem Yönetimi</h3>
                    <a href="#root/dashboard" class="nav-link mt-1 group flex items-center px-2 py-2 text-sm font-bold rounded-md text-red-700 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20">
                        <i class="fas fa-shield-alt mr-3 flex-shrink-0 h-6 w-6 text-center"></i>
                        ROOT CONSOLE
                    </a>
                </div>
            `;
        } else if (userRole === 'tenant-admin-role' || permissions.includes('system_settings:read')) {
             html += `
                <a href="#settings/system" data-target="settings" class="nav-link mt-1 group flex items-center px-2 py-2 text-sm font-medium rounded-md text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white">
                    <i class="fas fa-server mr-3 flex-shrink-0 h-6 w-6 text-center"></i>
                    Sistem Ayarları
                </a>
            `;
        }

        html += `</div>`;
        menuContainer.innerHTML = html;
    }
};

// Expose App globally
window.App = App;

// Initialize
document.addEventListener('DOMContentLoaded', () => App.init());
