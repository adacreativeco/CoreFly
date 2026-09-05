import React, { useState, useEffect, useRef } from 'react';
import { useAuthStore } from '@/store/authStore';
import { useThemeStore } from '@/store/themeStore';
import { useLangStore } from '@/store/langStore';
import { logout } from '@/services/authService';
import { notificationService } from '@/services/notificationService';
import { NotificationItem } from '@/types/notification';
import { useNavigate } from 'react-router-dom';
import { Bell, Search, LogOut, User, Menu, Sun, Moon } from 'lucide-react';

interface HeaderProps {
  onToggleSidebar?: () => void;
}

export const Header: React.FC<HeaderProps> = ({ onToggleSidebar }) => {
  const { user } = useAuthStore();
  const { theme, toggleTheme } = useThemeStore();
  const { lang, toggleLang, t } = useLangStore();
  const navigate = useNavigate();
  const tenantId = user?.tenant_id || '';

  const [notifications, setNotifications] = useState<NotificationItem[]>([]);
  const [isOpen, setIsOpen] = useState(false);
  const dropdownRef = useRef<HTMLDivElement>(null);

  const loadNotifications = async () => {
    if (!tenantId) return;
    try {
      const data = await notificationService.getNotifications(tenantId);
      setNotifications(data || []);
    } catch (err) {
      console.error('Bildirimler alınamadı:', err);
    }
  };

  useEffect(() => {
    loadNotifications();
    const interval = setInterval(loadNotifications, 15000);
    return () => clearInterval(interval);
  }, [tenantId]);

  useEffect(() => {
    const handleClickOutside = (event: MouseEvent) => {
      if (dropdownRef.current && !dropdownRef.current.contains(event.target as Node)) {
        setIsOpen(false);
      }
    };
    document.addEventListener('mousedown', handleClickOutside);
    return () => document.removeEventListener('mousedown', handleClickOutside);
  }, []);

  const handleMarkAsRead = async (id: string) => {
    try {
      await notificationService.markAsRead(tenantId, id);
      setNotifications((prev) =>
        prev.map((n) => (n.id === id ? { ...n, read: true } : n))
      );
    } catch (err) {
      console.error('Okundu işaretlenemedi:', err);
    }
  };

  const handleLogout = () => {
    logout();
    navigate('/login');
  };

  const unreadCount = notifications.filter((n) => !n.read).length;
  const [searchQuery, setSearchQuery] = useState('');
  const [isSearchOpen, setIsSearchOpen] = useState(false);
  const isAdmin = user?.role === 'admin' || user?.role === 'superadmin' || user?.email === 'admin@corefly.com';

  const searchableModules = [
    { name: 'Ana Sayfa & Pano', path: '/dashboard', cat: 'Genel' },
    { name: 'Görevler & Şirket İşleri', path: '/tasks', cat: 'Operasyon' },
    { name: 'Projeler & Kanban', path: '/projects', cat: 'Operasyon' },
    { name: 'CRM & Satış Fırsatları', path: '/crm', cat: 'Satış' },
    { name: 'Ön Muhasebe, Kasa & Faturalar', path: '/accounting', cat: 'Finans' },
    { name: 'Envanter & Donanım Stoğu', path: '/inventory', cat: 'Depo' },
    { name: 'Çalışanlar & Personel Listesi', path: '/hr/employees', cat: 'İK' },
    { name: 'Departmanlar & Pozisyonlar', path: '/hr/departments', cat: 'İK' },
    { name: 'İzin Talepleri & Onay', path: '/hr/leaves', cat: 'İK' },
    { name: 'Maaş & Bordro Takibi', path: '/hr/payrolls', cat: 'İK' },
    { name: 'Saha Yönetimi & Ekipler', path: '/field', cat: 'Operasyon' },
    { name: 'Bağış & Kaynak Geliştirme', path: '/donations', cat: 'STK' },
    { name: 'Teşkilat & Sandık Görevlileri', path: '/politics', cat: 'Siyaset' },
    { name: 'Takvim & Şirket Toplantıları', path: '/calendar', cat: 'İletişim' },
    { name: 'Duyurular & Haberler', path: '/announcements', cat: 'İletişim' },
    { name: 'Destek Masası (Helpdesk)', path: '/helpdesk', cat: 'Destek' },
    { name: 'Mesajlar & Sohbet Odaları', path: '/messages', cat: 'İletişim' },
    { name: 'Dosyalar & Doküman Deposu', path: '/files', cat: 'Depolama' },
    { name: 'Rol & Yetki Yönetimi (RBAC)', path: '/admin/roles', cat: 'Yönetim', adminOnly: true },
    { name: 'Sistem Denetim Günlükleri (Audit)', path: '/admin/logs', cat: 'Yönetim', adminOnly: true },
    { name: 'Müşteriler & Kiracılar (Tenants)', path: '/admin/tenants', cat: 'Yönetim', adminOnly: true },
    { name: 'Güvenlik & Şifre Değiştir', path: '/settings/security', cat: 'Ayarlar' },
  ];

  const searchResults = searchQuery.trim() === '' ? [] : searchableModules
    .filter(m => !m.adminOnly || isAdmin)
    .filter(m => 
      m.name.toLowerCase().includes(searchQuery.toLowerCase()) || 
      m.cat.toLowerCase().includes(searchQuery.toLowerCase())
    );

  return (
    <header className="bg-white dark:bg-gray-900 border-b border-gray-200 dark:border-gray-800 shadow-sm h-16 flex items-center justify-between px-4 sm:px-6 z-20 relative transition-colors">
      <div className="flex items-center gap-3 flex-1 min-w-0">
        {onToggleSidebar && (
          <button
            type="button"
            onClick={onToggleSidebar}
            className="lg:hidden p-2 rounded-lg text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
            title="Menüyü Aç"
          >
            <Menu className="w-5 h-5" />
          </button>
        )}

        <div className="relative w-full max-w-xs sm:max-w-md">
          <span className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
            <Search className="h-4 w-4 text-gray-400" />
          </span>
          <input
            type="text"
            value={searchQuery}
            onChange={(e) => {
              setSearchQuery(e.target.value);
              setIsSearchOpen(true);
            }}
            onFocus={() => setIsSearchOpen(true)}
            onBlur={() => setTimeout(() => setIsSearchOpen(false), 200)}
            className="block w-full pl-9 pr-3 py-1.5 sm:py-2 border border-gray-300 dark:border-gray-700 rounded-lg leading-5 bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:bg-white dark:focus:bg-gray-800 focus:ring-2 focus:ring-indigo-500 text-xs sm:text-sm transition"
            placeholder={t('header.search_placeholder')}
          />

          {isSearchOpen && searchResults.length > 0 && (
            <div className="absolute top-12 left-0 w-full bg-white dark:bg-gray-800 rounded-xl shadow-xl border border-gray-100 dark:border-gray-700 py-2 z-50 max-h-72 overflow-y-auto">
              <div className="px-3 py-1 text-[11px] font-semibold text-gray-400 uppercase tracking-wider">
                Arama Sonuçları
              </div>
              {searchResults.map((item) => (
                <div
                  key={item.path}
                  onMouseDown={() => {
                    navigate(item.path);
                    setSearchQuery('');
                    setIsSearchOpen(false);
                  }}
                  className="px-4 py-2 hover:bg-indigo-50 dark:hover:bg-gray-700 cursor-pointer flex items-center justify-between transition"
                >
                  <span className="text-xs font-medium text-gray-800 dark:text-gray-200">{item.name}</span>
                  <span className="text-[10px] bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 px-2 py-0.5 rounded-full font-medium">
                    {item.cat}
                  </span>
                </div>
              ))}
            </div>
          )}
        </div>
      </div>

      <div className="flex items-center space-x-2 sm:space-x-4 ml-2">
        {/* Dark Mode Toggle */}
        <button
          type="button"
          onClick={toggleTheme}
          className="p-2 rounded-full text-gray-400 hover:text-gray-600 dark:text-gray-300 dark:hover:text-amber-400 hover:bg-gray-100 dark:hover:bg-gray-800 transition"
          title={theme === 'dark' ? 'Açık Temaya Geç' : 'Koyu Temaya Geç'}
        >
          {theme === 'dark' ? (
            <Sun className="h-5 w-5 text-amber-400" />
          ) : (
            <Moon className="h-5 w-5 text-gray-600" />
          )}
        </button>

        {/* Language Switcher */}
        <button
          type="button"
          onClick={toggleLang}
          className="flex items-center gap-1 px-2.5 py-1.5 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-xs font-bold text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 transition select-none"
          title={lang === 'tr' ? 'Switch to English' : 'Türkçe\'ye Geç'}
        >
          <span>{lang === 'tr' ? '🇹🇷 TR' : '🇬🇧 EN'}</span>
        </button>

        {/* Bildirim Dropdown */}
        <div className="relative" ref={dropdownRef}>
          <button
            type="button"
            onClick={() => setIsOpen(!isOpen)}
            className="p-2 rounded-full text-gray-400 hover:text-gray-600 dark:text-gray-300 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-800 focus:outline-none relative transition"
            title="Bildirimler"
          >
            <Bell className="h-5 w-5" />
            {unreadCount > 0 && (
              <span className="absolute top-1 right-1 flex h-4 w-4 items-center justify-center rounded-full bg-rose-500 text-[10px] font-bold text-white">
                {unreadCount > 9 ? '9+' : unreadCount}
              </span>
            )}
          </button>

          {isOpen && (
            <div className="absolute right-0 mt-2 w-80 sm:w-96 bg-white dark:bg-gray-800 rounded-xl shadow-xl border border-gray-100 dark:border-gray-700 py-2 z-50">
              <div className="px-4 py-2.5 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                <div className="font-semibold text-gray-800 dark:text-white text-sm">Bildirimler</div>
                {unreadCount > 0 && (
                  <span className="text-xs text-indigo-600 dark:text-indigo-400 font-medium">
                    {unreadCount} yeni
                  </span>
                )}
              </div>

              <div className="max-h-80 overflow-y-auto divide-y divide-gray-50 dark:divide-gray-700">
                {notifications.length === 0 ? (
                  <div className="py-8 text-center text-xs text-gray-400">
                    Henüz bir bildirim yok
                  </div>
                ) : (
                  notifications.map((n) => (
                    <div
                      key={n.id}
                      onClick={() => !n.read && handleMarkAsRead(n.id)}
                      className={`p-3.5 hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer transition flex items-start justify-between gap-3 ${
                        !n.read ? 'bg-indigo-50/40 dark:bg-indigo-950/30' : ''
                      }`}
                    >
                      <div className="flex-1 min-w-0">
                        <div className="text-xs font-semibold text-gray-900 dark:text-white truncate">
                          {n.title}
                        </div>
                        <p className="text-xs text-gray-500 dark:text-gray-400 mt-0.5 line-clamp-2">
                          {n.message}
                        </p>
                        <span className="text-[10px] text-gray-400 mt-1 block">
                          {new Date(n.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}
                        </span>
                      </div>
                      {!n.read && (
                        <span className="w-2 h-2 rounded-full bg-indigo-600 flex-shrink-0 mt-1.5" />
                      )}
                    </div>
                  ))
                )}
              </div>
            </div>
          )}
        </div>

        {/* Profil Kısmı */}
        <div 
          onClick={() => navigate('/settings/profile')}
          className="relative flex items-center space-x-2 sm:space-x-3 pl-2 sm:pl-4 border-l border-gray-200 dark:border-gray-700 cursor-pointer group"
          title="Profil Ayarları"
        >
          <div className="hidden sm:flex flex-col items-end">
            <span className="text-sm font-medium text-gray-900 dark:text-white group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">
              {user?.name}
            </span>
            <span className="text-xs text-gray-500 dark:text-gray-400">{user?.email}</span>
          </div>
          <div className="h-8 w-8 rounded-full bg-indigo-100 dark:bg-indigo-900/60 flex items-center justify-center text-indigo-600 dark:text-indigo-400 group-hover:bg-indigo-200 transition-colors">
            <User className="h-4 w-4 sm:h-5 sm:w-5" />
          </div>
        </div>

        <button
          type="button"
          onClick={handleLogout}
          className="p-2 text-gray-400 hover:text-red-600 dark:hover:text-red-400 transition-colors"
          title="Çıkış Yap"
        >
          <LogOut className="h-5 w-5" />
        </button>
      </div>
    </header>
  );
};
