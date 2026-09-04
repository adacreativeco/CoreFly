import React, { useEffect, useState } from 'react';
import { useAuthStore } from '@/store/authStore';
import { getFieldTasks, createFieldTask, updateFieldTaskStatus, deleteFieldTask } from '@/services/specializedService';
import { FieldTask, TaskStatus } from '@/types/specialized';
import { Compass, Plus, Search, MapPin, Users, Calendar, Trash2, CheckCircle, Clock } from 'lucide-react';

export const FieldDashboard: React.FC = () => {
  const user = useAuthStore((state) => state.user);
  const tenantId = user?.tenant_id || '';

  const [tasks, setTasks] = useState<FieldTask[]>([]);
  const [loading, setLoading] = useState(true);
  const [search, setSearch] = useState('');
  const [isModalOpen, setIsModalOpen] = useState(false);

  const [form, setForm] = useState({
    title: '',
    description: '',
    team_name: 'Saha Ekibi 1',
    location: '',
    assigned_to: '',
    task_date: new Date().toISOString().split('T')[0],
    priority: 'medium' as any,
  });

  const loadData = async () => {
    if (!tenantId) return;
    try {
      const data = await getFieldTasks(tenantId, search);
      setTasks(data);
    } catch (err) {
      console.error('Saha görevleri yüklenemedi:', err);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    loadData();
  }, [tenantId, search]);

  const handleCreate = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!form.title) return;
    await createFieldTask(tenantId, form);
    setIsModalOpen(false);
    setForm({
      title: '',
      description: '',
      team_name: 'Saha Ekibi 1',
      location: '',
      assigned_to: '',
      task_date: new Date().toISOString().split('T')[0],
      priority: 'medium',
    });
    loadData();
  };

  const handleStatus = async (taskId: string, status: TaskStatus) => {
    await updateFieldTaskStatus(tenantId, taskId, status);
    loadData();
  };

  const handleDelete = async (taskId: string) => {
    if (!confirm('Saha görevini silmek istediğinize emin misiniz?')) return;
    await deleteFieldTask(tenantId, taskId);
    loadData();
  };

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
          <h1 className="text-2xl font-bold text-gray-900 dark:text-white">
            Saha Operasyon Yönetimi
          </h1>
          <p className="text-sm text-gray-500">
            Saha ekipleri, lokasyon bazlı görevler ve operasyonel takip.
          </p>
        </div>

        <button
          onClick={() => setIsModalOpen(true)}
          className="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors shadow-sm"
        >
          <Plus className="w-4 h-4" />
          Yeni Saha Görevi Ekle
        </button>
      </div>

      {/* Search Bar */}
      <div className="relative w-full sm:w-80">
        <Search className="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" />
        <input
          type="text"
          placeholder="Görev, ekip veya konum ara..."
          value={search}
          onChange={(e) => setSearch(e.target.value)}
          className="w-full pl-9 pr-4 py-2 border border-gray-200 dark:border-gray-700 rounded-lg text-sm bg-white dark:bg-gray-800 focus:outline-none"
        />
      </div>

      {/* Task Cards Grid */}
      {loading ? (
        <div className="py-20 text-center text-gray-400">Yükleniyor...</div>
      ) : tasks.length === 0 ? (
        <div className="bg-white dark:bg-gray-800 rounded-xl p-12 text-center border border-gray-200 dark:border-gray-700 text-gray-400">
          <Compass className="w-12 h-12 mx-auto mb-3 opacity-30 text-indigo-500" />
          <p className="text-base font-semibold">Aktif saha görevi bulunamadı.</p>
        </div>
      ) : (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          {tasks.map((t) => (
            <div
              key={t.id}
              className="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-200 dark:border-gray-700 shadow-sm flex flex-col justify-between hover:shadow-md transition-shadow relative"
            >
              <div>
                <div className="flex items-start justify-between gap-2 mb-2">
                  <span className={`text-[10px] font-bold uppercase px-2.5 py-0.5 rounded-full ${
                    t.status === 'completed'
                      ? 'bg-emerald-100 text-emerald-800'
                      : t.status === 'in_progress'
                      ? 'bg-blue-100 text-blue-800'
                      : 'bg-amber-100 text-amber-800'
                  }`}>
                    {t.status === 'completed' ? 'Tamamlandı' : t.status === 'in_progress' ? 'Devam Ediyor' : 'Planlandı'}
                  </span>

                  <button
                    onClick={() => handleDelete(t.id)}
                    className="text-gray-400 hover:text-red-500 transition-colors p-1"
                  >
                    <Trash2 className="w-4 h-4" />
                  </button>
                </div>

                <h3 className="font-bold text-gray-900 dark:text-white text-base mb-1.5">
                  {t.title}
                </h3>

                {t.description && (
                  <p className="text-xs text-gray-500 mb-3 line-clamp-2">
                    {t.description}
                  </p>
                )}
              </div>

              <div className="space-y-2 pt-3 border-t border-gray-100 dark:border-gray-700 text-xs text-gray-500">
                <div className="flex items-center gap-1.5">
                  <Users className="w-3.5 h-3.5 text-indigo-500" />
                  <span className="font-semibold text-gray-700 dark:text-gray-300">{t.team_name}</span>
                  {t.assigned_to && <span className="text-gray-400">({t.assigned_to})</span>}
                </div>

                {t.location && (
                  <div className="flex items-center gap-1.5">
                    <MapPin className="w-3.5 h-3.5 text-rose-500" />
                    <span>{t.location}</span>
                  </div>
                )}

                <div className="flex items-center justify-between pt-2">
                  <div className="flex items-center gap-1 text-[11px] text-gray-400">
                    <Calendar className="w-3 h-3" />
                    <span>{t.task_date}</span>
                  </div>

                  {t.status !== 'completed' && (
                    <button
                      onClick={() => handleStatus(t.id, 'completed')}
                      className="flex items-center gap-1 text-xs bg-emerald-50 hover:bg-emerald-100 text-emerald-700 px-2 py-1 rounded transition-colors"
                    >
                      <CheckCircle className="w-3 h-3" /> Tamamla
                    </button>
                  )}
                </div>
              </div>
            </div>
          ))}
        </div>
      )}

      {/* Modal */}
      {isModalOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
          <div className="bg-white dark:bg-gray-800 rounded-2xl w-full max-w-md p-6 shadow-xl border border-gray-200 dark:border-gray-700">
            <h3 className="text-lg font-bold text-gray-900 dark:text-white mb-4">
              Yeni Saha Görevi Oluştur
            </h3>
            <form onSubmit={handleCreate} className="space-y-3">
              <div>
                <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                  Görev Başlığı *
                </label>
                <input
                  type="text"
                  required
                  value={form.title}
                  onChange={(e) => setForm({ ...form, title: e.target.value })}
                  placeholder="Örn: Bölge Dağıtımı ve Denetim"
                  className="w-full px-3 py-2 border rounded-lg text-sm bg-gray-50 dark:bg-gray-700 border-gray-300 dark:border-gray-600"
                />
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Ekip Adı
                  </label>
                  <input
                    type="text"
                    value={form.team_name}
                    onChange={(e) => setForm({ ...form, team_name: e.target.value })}
                    className="w-full px-3 py-2 border rounded-lg text-sm bg-gray-50 dark:bg-gray-700 border-gray-300 dark:border-gray-600"
                  />
                </div>
                <div>
                  <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Görevli Kişi
                  </label>
                  <input
                    type="text"
                    value={form.assigned_to}
                    onChange={(e) => setForm({ ...form, assigned_to: e.target.value })}
                    placeholder="Ali Veli"
                    className="w-full px-3 py-2 border rounded-lg text-sm bg-gray-50 dark:bg-gray-700 border-gray-300 dark:border-gray-600"
                  />
                </div>
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Konum / Lokasyon
                  </label>
                  <input
                    type="text"
                    value={form.location}
                    onChange={(e) => setForm({ ...form, location: e.target.value })}
                    placeholder="Kadıköy Meydan"
                    className="w-full px-3 py-2 border rounded-lg text-sm bg-gray-50 dark:bg-gray-700 border-gray-300 dark:border-gray-600"
                  />
                </div>
                <div>
                  <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Tarih
                  </label>
                  <input
                    type="date"
                    value={form.task_date}
                    onChange={(e) => setForm({ ...form, task_date: e.target.value })}
                    className="w-full px-3 py-2 border rounded-lg text-sm bg-gray-50 dark:bg-gray-700 border-gray-300 dark:border-gray-600"
                  />
                </div>
              </div>

              <div>
                <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                  Açıklama / Yönergeler
                </label>
                <textarea
                  rows={3}
                  value={form.description}
                  onChange={(e) => setForm({ ...form, description: e.target.value })}
                  className="w-full px-3 py-2 border rounded-lg text-sm bg-gray-50 dark:bg-gray-700 border-gray-300 dark:border-gray-600"
                />
              </div>

              <div className="flex justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                <button
                  type="button"
                  onClick={() => setIsModalOpen(false)}
                  className="px-4 py-2 text-sm text-gray-600 hover:text-gray-800 dark:text-gray-400"
                >
                  İptal
                </button>
                <button
                  type="submit"
                  className="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium transition-colors"
                >
                  Görevi Kaydet
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
};
