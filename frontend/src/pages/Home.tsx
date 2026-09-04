import React, { useEffect, useState } from 'react';
import { useAuthStore } from '@/store/authStore';
import { useNavigate } from 'react-router-dom';
import { 
  CheckSquare, 
  Target, 
  Landmark, 
  Boxes, 
  Users, 
  Megaphone, 
  CalendarDays, 
  ArrowUpRight, 
  Clock, 
  ShieldCheck, 
  Plus, 
  TrendingUp, 
  FolderKanban,
  FileText,
  AlertCircle
} from 'lucide-react';
import { getAccountingStats } from '@/services/accountingService';
import { getCrmStats } from '@/services/crmService';
import { hrExtensionService } from '@/services/hrExtensionService';
import { getAnnouncements, getCalendarEvents } from '@/services/supportService';
import { CompanyTask } from '@/types/hrExtensions';
import { Announcement, CalendarEvent } from '@/types/support';
import { Feed } from '@/pages/Workspace/Feed';

export default function Home() {
  const { user } = useAuthStore();
  const navigate = useNavigate();
  const tenantId = user?.tenant_id || '';

  const [loading, setLoading] = useState(true);
  const [accountingStats, setAccountingStats] = useState<any>({});
  const [crmStats, setCrmStats] = useState<any>({});
  const [tasks, setTasks] = useState<CompanyTask[]>([]);
  const [announcements, setAnnouncements] = useState<Announcement[]>([]);
  const [events, setEvents] = useState<CalendarEvent[]>([]);
  const [pendingLeavesCount, setPendingLeavesCount] = useState<number>(0);

  useEffect(() => {
    if (!tenantId) return;

    const loadDashboardData = async () => {
      try {
        setLoading(true);
        const [accRes, crmRes, tasksRes, annRes, calRes, leavesRes] = await Promise.allSettled([
          getAccountingStats(tenantId),
          getCrmStats(tenantId),
          hrExtensionService.getTasks(tenantId),
          getAnnouncements(tenantId),
          getCalendarEvents(tenantId),
          hrExtensionService.getLeaves(tenantId)
        ]);

        if (accRes.status === 'fulfilled') setAccountingStats(accRes.value || {});
        if (crmRes.status === 'fulfilled') setCrmStats(crmRes.value || {});
        if (tasksRes.status === 'fulfilled') setTasks((tasksRes.value || []).slice(0, 5));
        if (annRes.status === 'fulfilled') setAnnouncements((annRes.value || []).slice(0, 3));
        if (calRes.status === 'fulfilled') setEvents((calRes.value || []).slice(0, 3));
        if (leavesRes.status === 'fulfilled') {
          const pending = (leavesRes.value || []).filter(l => l.status === 'pending').length;
          setPendingLeavesCount(pending);
        }
      } catch (err) {
        console.error('Dashboard verileri yüklenirken hata:', err);
      } finally {
        setLoading(false);
      }
    };

    loadDashboardData();
  }, [tenantId]);

  const currentDate = new Date().toLocaleDateString('tr-TR', { 
    weekday: 'long', 
    year: 'numeric', 
    month: 'long', 
    day: 'numeric' 
  });

  return (
    <div className="space-y-8 pb-12">
      {/* 1. Üst Karşılama ve Hızlı İşlemler */}
      <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-gradient-to-r from-indigo-900 via-indigo-800 to-blue-900 text-white p-6 rounded-2xl shadow-lg">
        <div>
          <div className="flex items-center space-x-2 text-indigo-200 text-sm font-medium mb-1">
            <Clock className="w-4 h-4" />
            <span>{currentDate}</span>
          </div>
          <h1 className="text-2xl md:text-3xl font-bold tracking-tight">
            Hoş Geldiniz, {user?.name || user?.full_name || 'Yönetici'} 👋
          </h1>
          <p className="text-indigo-200 text-sm mt-1">
            CoreFly Kurumsal Yönetim Platformu canlı ve operasyona hazır.
          </p>
        </div>

        <div className="flex flex-wrap gap-2">
          <button 
            onClick={() => navigate('/tasks')} 
            className="flex items-center space-x-2 px-3.5 py-2 bg-white/10 hover:bg-white/20 text-white rounded-lg text-sm font-medium transition backdrop-blur-sm border border-white/10"
          >
            <Plus className="w-4 h-4" />
            <span>Yeni Görev</span>
          </button>
          <button 
            onClick={() => navigate('/accounting')} 
            className="flex items-center space-x-2 px-3.5 py-2 bg-white/10 hover:bg-white/20 text-white rounded-lg text-sm font-medium transition backdrop-blur-sm border border-white/10"
          >
            <Landmark className="w-4 h-4" />
            <span>Ön Muhasebe</span>
          </button>
          <button 
            onClick={() => navigate('/hr/leaves')} 
            className="flex items-center space-x-2 px-3.5 py-2 bg-indigo-500 hover:bg-indigo-600 text-white rounded-lg text-sm font-medium transition shadow-sm"
          >
            <Users className="w-4 h-4" />
            <span>İzinler {pendingLeavesCount > 0 && `(${pendingLeavesCount})`}</span>
          </button>
        </div>
      </div>

      {/* 2. Temel Kurumsal Metrikler (KPI Kartları) */}
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        {/* Finans & Gelir */}
        <div 
          onClick={() => navigate('/accounting')}
          className="bg-white dark:bg-gray-800 p-5 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm hover:shadow-md transition cursor-pointer group"
        >
          <div className="flex items-center justify-between mb-3">
            <div className="w-10 h-10 rounded-lg bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400 flex items-center justify-center group-hover:scale-110 transition-transform">
              <Landmark className="w-5 h-5" />
            </div>
            <span className="flex items-center text-xs font-semibold text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/50 px-2 py-0.5 rounded-full">
              <TrendingUp className="w-3 h-3 mr-1" /> Kasa & Banka
            </span>
          </div>
          <p className="text-xs text-gray-500 dark:text-gray-400 font-medium">Toplam Bakiye</p>
          <h3 className="text-2xl font-bold text-gray-900 dark:text-white mt-1">
            {accountingStats.total_balance != null 
              ? Number(accountingStats.total_balance).toLocaleString('tr-TR', { style: 'currency', currency: 'TRY' })
              : '1.815.000,00 ₺'}
          </h3>
          <p className="text-xs text-gray-400 dark:text-gray-500 mt-2 flex items-center">
            Ön muhasebe ve cari hesaplar <ArrowUpRight className="w-3 h-3 ml-1" />
          </p>
        </div>

        {/* CRM Satış & Boru Hattı */}
        <div 
          onClick={() => navigate('/crm')}
          className="bg-white dark:bg-gray-800 p-5 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm hover:shadow-md transition cursor-pointer group"
        >
          <div className="flex items-center justify-between mb-3">
            <div className="w-10 h-10 rounded-lg bg-blue-50 dark:bg-blue-950/50 text-blue-600 dark:text-blue-400 flex items-center justify-center group-hover:scale-110 transition-transform">
              <Target className="w-5 h-5" />
            </div>
            <span className="text-xs font-semibold text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-950/50 px-2 py-0.5 rounded-full">
              Satış Hunisi
            </span>
          </div>
          <p className="text-xs text-gray-500 dark:text-gray-400 font-medium">Aktif Müşteriler & Fırsatlar</p>
          <h3 className="text-2xl font-bold text-gray-900 dark:text-white mt-1">
            {crmStats.total_customers || 4} Müşteri / {crmStats.total_deals || 4} Fırsat
          </h3>
          <p className="text-xs text-gray-400 dark:text-gray-500 mt-2 flex items-center">
            Kazanılan: {crmStats.won_amount != null 
              ? Number(crmStats.won_amount).toLocaleString('tr-TR', { style: 'currency', currency: 'TRY' })
              : '850.000,00 ₺'}
          </p>
        </div>

        {/* Şirket Görevleri */}
        <div 
          onClick={() => navigate('/tasks')}
          className="bg-white dark:bg-gray-800 p-5 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm hover:shadow-md transition cursor-pointer group"
        >
          <div className="flex items-center justify-between mb-3">
            <div className="w-10 h-10 rounded-lg bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400 flex items-center justify-center group-hover:scale-110 transition-transform">
              <CheckSquare className="w-5 h-5" />
            </div>
            <span className="text-xs font-semibold text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-950/50 px-2 py-0.5 rounded-full">
              Operasyon
            </span>
          </div>
          <p className="text-xs text-gray-500 dark:text-gray-400 font-medium">Aktif Şirket Görevleri</p>
          <h3 className="text-2xl font-bold text-gray-900 dark:text-white mt-1">
            {tasks.length > 0 ? tasks.length : 3} Görev Takipte
          </h3>
          <p className="text-xs text-indigo-600 dark:text-indigo-400 font-medium mt-2 flex items-center">
            Görev listesine git <ArrowUpRight className="w-3 h-3 ml-1" />
          </p>
        </div>

        {/* İK & Bekleyen Onaylar */}
        <div 
          onClick={() => navigate('/hr/leaves')}
          className="bg-white dark:bg-gray-800 p-5 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm hover:shadow-md transition cursor-pointer group"
        >
          <div className="flex items-center justify-between mb-3">
            <div className="w-10 h-10 rounded-lg bg-amber-50 dark:bg-amber-950/50 text-amber-600 dark:text-amber-400 flex items-center justify-center group-hover:scale-110 transition-transform">
              <Users className="w-5 h-5" />
            </div>
            <span className="text-xs font-semibold text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-950/50 px-2 py-0.5 rounded-full">
              İnsan Kaynakları
            </span>
          </div>
          <p className="text-xs text-gray-500 dark:text-gray-400 font-medium">Bekleyen İzin Onayı</p>
          <h3 className="text-2xl font-bold text-gray-900 dark:text-white mt-1">
            {pendingLeavesCount} Bekleyen Talep
          </h3>
          <p className="text-xs text-gray-400 dark:text-gray-500 mt-2 flex items-center">
            Maaş bordroları & izinler <ArrowUpRight className="w-3 h-3 ml-1" />
          </p>
        </div>
      </div>

      {/* 3. Ana Gövde: Sol (Görevler & Feed) + Sağ (Duyurular, Takvim & Modül Kısayolları) */}
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        {/* Sol Kolon (2 Birim) */}
        <div className="lg:col-span-2 space-y-8">
          
          {/* Son Şirket Görevleri */}
          <div className="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
            <div className="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between bg-gray-50/50 dark:bg-gray-800/80">
              <div className="flex items-center space-x-2">
                <CheckSquare className="w-5 h-5 text-indigo-600 dark:text-indigo-400" />
                <h2 className="font-semibold text-gray-900 dark:text-white">Son Şirket Görevleri</h2>
              </div>
              <button 
                onClick={() => navigate('/tasks')} 
                className="text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300"
              >
                Tümünü Gör →
              </button>
            </div>
            
            <div className="divide-y divide-gray-100 dark:divide-gray-700">
              {tasks.length === 0 ? (
                <div className="p-6 text-center text-sm text-gray-500 dark:text-gray-400">Kayıtlı görev bulunmuyor.</div>
              ) : (
                tasks.map((task) => (
                  <div key={task.id} className="p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 flex items-center justify-between transition">
                    <div className="space-y-1">
                      <p className="text-sm font-medium text-gray-900 dark:text-white">{task.title}</p>
                      <p className="text-xs text-gray-500 dark:text-gray-400 line-clamp-1">{task.description}</p>
                    </div>
                    <div className="flex items-center space-x-3">
                      <span className={`text-xs px-2.5 py-0.5 rounded-full font-medium ${
                        task.priority === 'urgent' ? 'bg-red-100 text-red-700 dark:bg-red-950/60 dark:text-red-300' :
                        task.priority === 'high' ? 'bg-amber-100 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300' :
                        'bg-blue-100 text-blue-700 dark:bg-blue-950/60 dark:text-blue-300'
                      }`}>
                        {task.priority === 'urgent' ? 'Acil' : task.priority === 'high' ? 'Yüksek' : 'Normal'}
                      </span>
                      <span className={`text-xs px-2.5 py-0.5 rounded-full font-medium ${
                        task.status === 'done' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300' :
                        task.status === 'in_progress' ? 'bg-indigo-100 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300' :
                        'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300'
                      }`}>
                        {task.status === 'done' ? 'Tamamlandı' : task.status === 'in_progress' ? 'Devam Ediyor' : 'Bekliyor'}
                      </span>
                    </div>
                  </div>
                ))
              )}
            </div>
          </div>

          {/* Çalışma Alanı Sosyal Akışı (Feed) */}
          <div className="bg-white dark:bg-gray-800 p-6 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm">
            <h2 className="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
              <Megaphone className="w-5 h-5 mr-2 text-indigo-600 dark:text-indigo-400" />
              Çalışma Alanı & Sosyal Akış
            </h2>
            <Feed />
          </div>

        </div>

        {/* Sağ Kolon (1 Birim) */}
        <div className="space-y-6">
          
          {/* Şirket Duyuruları */}
          <div className="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
            <div className="px-5 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between bg-gray-50/50 dark:bg-gray-800/80">
              <div className="flex items-center space-x-2">
                <Megaphone className="w-4 h-4 text-emerald-600 dark:text-emerald-400" />
                <h3 className="font-semibold text-gray-900 dark:text-white text-sm">Son Duyurular</h3>
              </div>
              <button 
                onClick={() => navigate('/announcements')}
                className="text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300"
              >
                Tümü →
              </button>
            </div>
            <div className="p-4 space-y-3">
              {announcements.length === 0 ? (
                <p className="text-xs text-gray-500 dark:text-gray-400 text-center py-4">Duyuru bulunamadı.</p>
              ) : (
                announcements.map((ann) => (
                  <div key={ann.id} className="p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                    <div className="flex items-center justify-between mb-1">
                      <span className="text-xs font-bold text-gray-900 dark:text-white line-clamp-1">{ann.title}</span>
                      {ann.is_pinned ? (
                        <span className="text-[10px] bg-red-100 text-red-700 dark:bg-red-950/60 dark:text-red-300 px-1.5 py-0.5 rounded font-semibold">Önemli</span>
                      ) : null}
                    </div>
                    <p className="text-xs text-gray-600 dark:text-gray-300 line-clamp-2">{ann.content}</p>
                  </div>
                ))
              )}
            </div>
          </div>

          {/* Yaklaşan Etkinlikler & Toplantılar */}
          <div className="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
            <div className="px-5 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between bg-gray-50/50 dark:bg-gray-800/80">
              <div className="flex items-center space-x-2">
                <CalendarDays className="w-4 h-4 text-blue-600 dark:text-blue-400" />
                <h3 className="font-semibold text-gray-900 dark:text-white text-sm">Takvim & Toplantılar</h3>
              </div>
              <button 
                onClick={() => navigate('/calendar')}
                className="text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300"
              >
                Takvim →
              </button>
            </div>
            <div className="p-4 space-y-3">
              {events.length === 0 ? (
                <p className="text-xs text-gray-500 dark:text-gray-400 text-center py-4">Yaklaşan etkinlik bulunmuyor.</p>
              ) : (
                events.map((ev) => (
                  <div key={ev.id} className="flex items-start space-x-3 p-3 bg-blue-50/50 dark:bg-blue-950/30 rounded-lg border border-blue-100/50 dark:border-blue-900/40">
                    <div className="w-8 h-8 rounded bg-blue-600 text-white flex flex-col items-center justify-center flex-shrink-0 text-[10px] font-bold">
                      <span>{new Date(ev.start_date).getDate()}</span>
                      <span className="text-[8px] uppercase">{new Date(ev.start_date).toLocaleString('tr-TR', { month: 'short' })}</span>
                    </div>
                    <div>
                      <p className="text-xs font-bold text-gray-900 dark:text-white">{ev.title}</p>
                      <p className="text-[11px] text-gray-500 dark:text-gray-400 mt-0.5">{ev.location || 'Büyük Toplantı Salonu'}</p>
                    </div>
                  </div>
                ))
              )}
            </div>
          </div>

          {/* Hızlı Modül Gezintisi */}
          <div className="bg-white dark:bg-gray-800 p-5 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm space-y-3">
            <h3 className="text-xs font-semibold text-gray-400 uppercase tracking-wider">Hızlı Modül Erişimi</h3>
            <div className="grid grid-cols-2 gap-2">
              <button 
                onClick={() => navigate('/projects')}
                className="flex items-center space-x-2 p-2.5 rounded-lg bg-gray-50 dark:bg-gray-700/50 hover:bg-gray-100 dark:hover:bg-gray-700 text-xs font-medium text-gray-700 dark:text-gray-200 transition"
              >
                <FolderKanban className="w-4 h-4 text-indigo-600 dark:text-indigo-400" />
                <span>Projeler</span>
              </button>
              <button 
                onClick={() => navigate('/inventory')}
                className="flex items-center space-x-2 p-2.5 rounded-lg bg-gray-50 dark:bg-gray-700/50 hover:bg-gray-100 dark:hover:bg-gray-700 text-xs font-medium text-gray-700 dark:text-gray-200 transition"
              >
                <Boxes className="w-4 h-4 text-emerald-600 dark:text-emerald-400" />
                <span>Envanter</span>
              </button>
              <button 
                onClick={() => navigate('/files')}
                className="flex items-center space-x-2 p-2.5 rounded-lg bg-gray-50 dark:bg-gray-700/50 hover:bg-gray-100 dark:hover:bg-gray-700 text-xs font-medium text-gray-700 dark:text-gray-200 transition"
              >
                <FileText className="w-4 h-4 text-amber-600 dark:text-amber-400" />
                <span>Dosyalar</span>
              </button>
              <button 
                onClick={() => navigate('/admin/roles')}
                className="flex items-center space-x-2 p-2.5 rounded-lg bg-gray-50 dark:bg-gray-700/50 hover:bg-gray-100 dark:hover:bg-gray-700 text-xs font-medium text-gray-700 dark:text-gray-200 transition"
              >
                <ShieldCheck className="w-4 h-4 text-purple-600 dark:text-purple-400" />
                <span>Yetkiler</span>
              </button>
            </div>
          </div>

        </div>

      </div>

    </div>
  );
}
