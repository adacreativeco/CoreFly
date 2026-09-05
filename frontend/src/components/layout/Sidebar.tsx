import React, { useState } from 'react';
import { NavLink } from 'react-router-dom';
import { 
  LayoutDashboard, 
  FolderKanban, 
  Users, 
  MessageSquare, 
  FileText, 
  Settings, 
  ChevronDown, 
  ChevronRight, 
  Building2, 
  User, 
  Target, 
  Boxes, 
  Landmark, 
  LifeBuoy, 
  Megaphone, 
  CalendarDays, 
  MapPin, 
  Heart, 
  Flag, 
  ShieldCheck, 
  CalendarCheck, 
  DollarSign, 
  CheckSquare, 
  ShieldAlert,
  X 
} from 'lucide-react';
import clsx from 'clsx';
import { useAuthStore } from '@/store/authStore';
import { useLangStore } from '@/store/langStore';

interface NavChild {
  name: string;
  key: string;
  path: string;
  icon: React.ComponentType<{ className?: string }>;
}

interface NavItem {
  name: string;
  key: string;
  path: string;
  icon: React.ComponentType<{ className?: string }>;
  adminOnly?: boolean;
  children?: NavChild[];
}

const navItems: NavItem[] = [
  { name: 'Ana Sayfa', key: 'nav.dashboard', path: '/dashboard', icon: LayoutDashboard },
  { name: 'Görevler', key: 'nav.tasks', path: '/tasks', icon: CheckSquare },
  { name: 'Projeler', key: 'nav.projects', path: '/projects', icon: FolderKanban },
  { name: 'CRM & Satış', key: 'nav.crm', path: '/crm', icon: Target },
  { name: 'Ön Muhasebe', key: 'nav.accounting', path: '/accounting', icon: Landmark },
  { name: 'Envanter & Stok', key: 'nav.inventory', path: '/inventory', icon: Boxes },
  { 
    name: 'İnsan Kaynakları', 
    key: 'nav.hr',
    path: '/hr', 
    icon: Users,
    children: [
      { name: 'Çalışanlar', key: 'nav.hr.employees', path: '/hr/employees', icon: User },
      { name: 'Departmanlar', key: 'nav.hr.departments', path: '/hr/departments', icon: Building2 },
      { name: 'İzin Talepleri', key: 'nav.hr.leaves', path: '/hr/leaves', icon: CalendarCheck },
      { name: 'Maaş & Bordro', key: 'nav.hr.payrolls', path: '/hr/payrolls', icon: DollarSign },
    ]
  },
  { name: 'Saha Yönetimi', key: 'nav.field', path: '/field', icon: MapPin },
  { name: 'Bağış & Kaynak', key: 'nav.donations', path: '/donations', icon: Heart },
  { name: 'Teşkilat & Seçim', key: 'nav.politics', path: '/politics', icon: Flag },
  { name: 'Takvim & Etkinlikler', key: 'nav.calendar', path: '/calendar', icon: CalendarDays },
  { name: 'Duyurular', key: 'nav.announcements', path: '/announcements', icon: Megaphone },
  { name: 'Destek (Helpdesk)', key: 'nav.helpdesk', path: '/helpdesk', icon: LifeBuoy },
  { name: 'Mesajlar', key: 'nav.messages', path: '/messages', icon: MessageSquare },
  { name: 'Dosyalar', key: 'nav.files', path: '/files', icon: FileText },
  { 
    name: 'Süper Admin', 
    key: 'nav.admin',
    path: '/admin', 
    icon: ShieldCheck,
    adminOnly: true,
    children: [
      { name: 'Müşteriler & Kiracılar', key: 'nav.admin.tenants', path: '/admin/tenants', icon: Building2 },
      { name: 'Rol & Yetki Yönetimi', key: 'nav.admin.roles', path: '/admin/roles', icon: ShieldCheck },
      { name: 'Denetim Günlükleri', key: 'nav.admin.logs', path: '/admin/logs', icon: ShieldAlert },
    ]
  },
  { name: 'Ayarlar', key: 'nav.settings', path: '/settings', icon: Settings },
];

interface SidebarProps {
  onClose?: () => void;
}

export const Sidebar: React.FC<SidebarProps> = ({ onClose }) => {
  const { user } = useAuthStore();
  const { t } = useLangStore();
  const [openSubmenus, setOpenSubmenus] = useState<string[]>(['İnsan Kaynakları']);

  // Sadece süper admin veya admin e-postasına sahip kullanıcılara admin menüsü açık
  const isAdmin = user?.role === 'admin' || user?.role === 'superadmin' || user?.email === 'admin@corefly.com';

  const toggleSubmenu = (name: string) => {
    setOpenSubmenus(prev => 
      prev.includes(name) ? prev.filter(n => n !== name) : [...prev, name]
    );
  };

  const visibleNavItems = navItems.filter(item => !item.adminOnly || isAdmin);

  return (
    <aside className="flex flex-col w-64 bg-gray-900 text-white h-screen flex-shrink-0 select-none border-r border-gray-800 z-30 shadow-2xl lg:shadow-none">
      {/* Logo Header */}
      <div className="flex items-center justify-between px-6 h-16 border-b border-gray-800 flex-shrink-0">
        <div className="flex items-center space-x-2.5">
          <img
            src="/brand/logo-white.png"
            alt="CoreFly"
            className="w-8 h-8 rounded-lg object-contain"
            onError={(e) => {
              // Fallback to stylized CF badge if image fails
              (e.currentTarget as HTMLElement).style.display = 'none';
            }}
          />
          <span className="text-lg font-bold tracking-wider text-white">COREFLY</span>
        </div>
        <div className="flex items-center gap-1.5">
          <span className="text-[10px] bg-indigo-950 text-indigo-400 border border-indigo-800 px-1.5 py-0.5 rounded font-mono font-semibold">
            v2.0
          </span>
          {onClose && (
            <button
              onClick={onClose}
              className="lg:hidden p-1.5 text-gray-400 hover:text-white rounded-lg hover:bg-gray-800 transition-colors"
              title="Menüyü Kapat"
            >
              <X className="w-5 h-5" />
            </button>
          )}
        </div>
      </div>

      {/* Kaydırılabilir Menü Navigasyonu */}
      <nav className="flex-1 px-3 py-4 space-y-1 overflow-y-auto overflow-x-hidden">
        {visibleNavItems.map((item) => (
          <div key={item.name}>
            {item.children ? (
              <div>
                <button
                  onClick={() => toggleSubmenu(item.name)}
                  className="w-full flex items-center justify-between px-3 py-2 text-sm font-medium rounded-lg text-gray-300 hover:bg-gray-800 hover:text-white transition-colors group"
                >
                  <div className="flex items-center min-w-0">
                    <item.icon className="mr-3 h-4 w-4 text-gray-400 group-hover:text-indigo-400 flex-shrink-0 transition-colors" />
                    <span className="truncate">{t(item.key) || item.name}</span>
                  </div>
                  {openSubmenus.includes(item.name) ? (
                    <ChevronDown className="h-4 w-4 text-gray-400 flex-shrink-0" />
                  ) : (
                    <ChevronRight className="h-4 w-4 text-gray-400 flex-shrink-0" />
                  )}
                </button>
                {openSubmenus.includes(item.name) && (
                  <div className="ml-7 pl-2 border-l border-gray-800 space-y-1 mt-1">
                    {item.children.map((child) => (
                      <NavLink
                        key={child.name}
                        to={child.path}
                        onClick={() => onClose?.()}
                        className={({ isActive }) =>
                          clsx(
                            'flex items-center px-3 py-1.5 text-xs font-medium rounded-md transition-colors',
                            isActive
                              ? 'bg-indigo-600/20 text-indigo-300 border border-indigo-500/30'
                              : 'text-gray-400 hover:bg-gray-800 hover:text-gray-200'
                          )
                        }
                      >
                        <child.icon className="mr-2 h-3.5 w-3.5 flex-shrink-0" />
                        <span className="truncate">{t(child.key) || child.name}</span>
                      </NavLink>
                    ))}
                  </div>
                )}
              </div>
            ) : (
              <NavLink
                to={item.path}
                onClick={() => onClose?.()}
                className={({ isActive }) =>
                  clsx(
                    'flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors group',
                    isActive
                      ? 'bg-indigo-600 text-white shadow-sm'
                      : 'text-gray-300 hover:bg-gray-800 hover:text-white'
                  )
                }
              >
                <item.icon className="mr-3 h-4 w-4 text-gray-400 group-hover:text-white flex-shrink-0 transition-colors" />
                <span className="truncate">{t(item.key) || item.name}</span>
              </NavLink>
            )}
          </div>
        ))}
      </nav>

      {/* Sabit Alt Bilgi */}
      <div className="p-3 border-t border-gray-800 flex-shrink-0 bg-gray-950/40">
        <div className="flex items-center justify-between px-2">
          <div className="min-w-0">
            <p className="text-xs font-semibold text-gray-200 truncate">{user?.name || user?.email || 'CoreFly ERP'}</p>
            <p className="text-[10px] text-gray-400 uppercase tracking-wider">
              {isAdmin ? '🛡️ Yönetici (Admin)' : '👤 Çalışan (Member)'}
            </p>
          </div>
        </div>
      </div>
    </aside>
  );
};
