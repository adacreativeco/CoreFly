import React, { useState, useEffect } from 'react';
import { useAuthStore } from '@/store/authStore';
import { hrExtensionService } from '@/services/hrExtensionService';
import { CompanyTaskItem } from '@/types/hrExtensions';
import { CheckSquare, Plus, Clock, AlertCircle, CheckCircle2, Search, Trash2 } from 'lucide-react';

export const TaskList: React.FC = () => {
  const user = useAuthStore((state) => state.user);
  const tenantId = user?.tenant_id || '';

  const [tasks, setTasks] = useState<CompanyTaskItem[]>([]);
  const [loading, setLoading] = useState(true);
  const [statusFilter, setStatusFilter] = useState('all');
  const [search, setSearch] = useState('');
  const [isModalOpen, setIsModalOpen] = useState(false);

  // Form states
  const [title, setTitle] = useState('');
  const [description, setDescription] = useState('');
  const [priority, setPriority] = useState<'low' | 'medium' | 'high' | 'urgent'>('medium');
  const [dueDate, setDueDate] = useState('');

  const loadData = async () => {
    if (!tenantId) return;
    try {
      setLoading(true);
      const data = await hrExtensionService.getTasks(tenantId);
      setTasks(data);
    } catch (err) {
      console.error('Görevler yüklenemedi:', err);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    loadData();
  }, [tenantId]);

  const handleCreate = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!title.trim()) return;

    try {
      await hrExtensionService.createTask(tenantId, {
        title,
        description,
        priority,
        status: 'todo',
        due_date: dueDate || undefined,
      });
      setIsModalOpen(false);
      setTitle('');
      setDescription('');
      setPriority('medium');
      setDueDate('');
      loadData();
    } catch (err) {
      alert('Görev eklenemedi.');
    }
  };

  const handleStatusChange = async (id: string, newStatus: 'todo' | 'in_progress' | 'review' | 'done') => {
    try {
      await hrExtensionService.updateTask(tenantId, id, { status: newStatus });
      loadData();
    } catch (err) {
      alert('Durum güncellenemedi.');
    }
  };

  const handleDelete = async (id: string) => {
    if (!window.confirm('Bu görevi silmek istediğinize emin misiniz?')) return;
    try {
      await hrExtensionService.deleteTask(tenantId, id);
      loadData();
    } catch (err) {
      alert('Silinemedi.');
    }
  };

  const filteredTasks = tasks.filter((t) => {
    const matchesStatus = statusFilter === 'all' || t.status === statusFilter;
    const matchesSearch = t.title.toLowerCase().includes(search.toLowerCase()) || (t.description || '').toLowerCase().includes(search.toLowerCase());
    return matchesStatus && matchesSearch;
  });

  const todoCount = tasks.filter((t) => t.status === 'todo').length;
  const inProgressCount = tasks.filter((t) => t.status === 'in_progress').length;
  const doneCount = tasks.filter((t) => t.status === 'done').length;

  return (
    <div className="p-6 space-y-6">
      <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
          <h1 className="text-2xl font-bold text-gray-900 flex items-center gap-2">
            <CheckSquare className="w-7 h-7 text-indigo-600" />
            Genel Görev Yönetimi
          </h1>
          <p className="text-sm text-gray-500 mt-1">
            Şirket içi operasyonel işler, departman görevleri ve bireysel ajanda takibi
          </p>
        </div>
        <button
          onClick={() => setIsModalOpen(true)}
          className="flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium shadow-sm transition"
        >
          <Plus className="w-4 h-4" />
          Yeni Görev Ekle
        </button>
      </div>

      {/* İstatistikler */}
      <div className="grid grid-cols-1 sm:grid-cols-3 gap-6">
        <div className="bg-white p-5 rounded-xl border border-gray-100 shadow-sm flex items-center justify-between">
          <div>
            <div className="text-xs font-semibold uppercase tracking-wider text-gray-500">Yapılacaklar</div>
            <div className="text-2xl font-bold text-gray-800 mt-1">{todoCount}</div>
          </div>
          <div className="p-3 bg-gray-50 rounded-lg text-gray-600">
            <Clock className="w-6 h-6" />
          </div>
        </div>

        <div className="bg-white p-5 rounded-xl border border-gray-100 shadow-sm flex items-center justify-between">
          <div>
            <div className="text-xs font-semibold uppercase tracking-wider text-gray-500">Devam Edenler</div>
            <div className="text-2xl font-bold text-blue-600 mt-1">{inProgressCount}</div>
          </div>
          <div className="p-3 bg-blue-50 rounded-lg text-blue-600">
            <AlertCircle className="w-6 h-6" />
          </div>
        </div>

        <div className="bg-white p-5 rounded-xl border border-gray-100 shadow-sm flex items-center justify-between">
          <div>
            <div className="text-xs font-semibold uppercase tracking-wider text-gray-500">Tamamlananlar</div>
            <div className="text-2xl font-bold text-emerald-600 mt-1">{doneCount}</div>
          </div>
          <div className="p-3 bg-emerald-50 rounded-lg text-emerald-600">
            <CheckCircle2 className="w-6 h-6" />
          </div>
        </div>
      </div>

      {/* Filtre ve Arama */}
      <div className="bg-white p-4 rounded-xl border border-gray-100 shadow-sm flex flex-col sm:flex-row gap-4 justify-between items-center">
        <div className="flex items-center gap-2 w-full sm:w-80 relative">
          <Search className="w-4 h-4 text-gray-400 absolute left-3" />
          <input
            type="text"
            placeholder="Görev ara..."
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            className="w-full pl-9 pr-3 py-1.5 border border-gray-300 rounded-lg text-sm bg-white focus:ring-2 focus:ring-indigo-500 outline-none"
          />
        </div>
        <div className="flex gap-2">
          {['all', 'todo', 'in_progress', 'review', 'done'].map((st) => (
            <button
              key={st}
              onClick={() => setStatusFilter(st)}
              className={`px-3 py-1.5 rounded-lg text-xs font-medium transition ${
                statusFilter === st ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
              }`}
            >
              {st === 'all'
                ? 'Tümü'
                : st === 'todo'
                ? 'Yapılacak'
                : st === 'in_progress'
                ? 'Sürüyor'
                : st === 'review'
                ? 'İncelemede'
                : 'Bitti'}
            </button>
          ))}
        </div>
      </div>

      {/* Görev Kartları Listesi */}
      {loading ? (
        <div className="p-12 text-center text-gray-500">Yükleniyor...</div>
      ) : filteredTasks.length === 0 ? (
        <div className="bg-white p-12 rounded-xl border border-gray-100 text-center text-gray-400">
          Görev bulunamadı.
        </div>
      ) : (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          {filteredTasks.map((t) => (
            <div key={t.id} className="bg-white p-5 rounded-xl border border-gray-100 shadow-sm hover:shadow-md transition space-y-3">
              <div className="flex justify-between items-start">
                <span
                  className={`px-2 py-0.5 rounded text-[11px] font-semibold uppercase tracking-wider ${
                    t.priority === 'urgent'
                      ? 'bg-red-100 text-red-800'
                      : t.priority === 'high'
                      ? 'bg-amber-100 text-amber-800'
                      : t.priority === 'low'
                      ? 'bg-gray-100 text-gray-700'
                      : 'bg-blue-100 text-blue-800'
                  }`}
                >
                  {t.priority === 'urgent'
                    ? 'Acil'
                    : t.priority === 'high'
                    ? 'Yüksek'
                    : t.priority === 'low'
                    ? 'Düşük'
                    : 'Normal'}
                </span>
                <button
                  onClick={() => handleDelete(t.id)}
                  className="text-gray-300 hover:text-red-600 transition"
                  title="Sil"
                >
                  <Trash2 className="w-4 h-4" />
                </button>
              </div>

              <h3 className="font-semibold text-gray-900 text-base">{t.title}</h3>
              {t.description && <p className="text-xs text-gray-500 line-clamp-2">{t.description}</p>}

              {t.due_date && (
                <div className="text-xs text-gray-400 flex items-center gap-1">
                  <Clock className="w-3.5 h-3.5" />
                  Termin: {t.due_date}
                </div>
              )}

              <div className="pt-2 border-t border-gray-100 flex items-center justify-between">
                <select
                  value={t.status}
                  onChange={(e) => handleStatusChange(t.id, e.target.value as any)}
                  className="text-xs font-semibold px-2 py-1 border rounded bg-gray-50 text-gray-700 outline-none"
                >
                  <option value="todo">Yapılacak</option>
                  <option value="in_progress">Sürüyor</option>
                  <option value="review">İncelemede</option>
                  <option value="done">Tamamlandı</option>
                </select>
                <span className="text-[11px] text-gray-400">{new Date(t.created_at).toLocaleDateString()}</span>
              </div>
            </div>
          ))}
        </div>
      )}

      {/* Modal */}
      {isModalOpen && (
        <div className="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4">
          <div className="bg-white rounded-xl shadow-xl max-w-md w-full p-6 space-y-4">
            <div className="flex justify-between items-center border-b pb-3">
              <h3 className="text-lg font-bold text-gray-900">Yeni Görev Tanımla</h3>
              <button onClick={() => setIsModalOpen(false)} className="text-gray-400 hover:text-gray-600 text-lg font-bold">
                ✕
              </button>
            </div>
            <form onSubmit={handleCreate} className="space-y-4">
              <div>
                <label className="block text-xs font-medium text-gray-700 mb-1">Görev Başlığı *</label>
                <input
                  type="text"
                  required
                  value={title}
                  onChange={(e) => setTitle(e.target.value)}
                  placeholder="Örn: Yıllık Mali Raporun Hazırlanması"
                  className="w-full border border-gray-300 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-indigo-500 outline-none"
                />
              </div>
              <div>
                <label className="block text-xs font-medium text-gray-700 mb-1">Açıklama</label>
                <textarea
                  value={description}
                  onChange={(e) => setDescription(e.target.value)}
                  rows={3}
                  placeholder="Görev detayları..."
                  className="w-full border border-gray-300 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-indigo-500 outline-none"
                />
              </div>
              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block text-xs font-medium text-gray-700 mb-1">Öncelik</label>
                  <select
                    value={priority}
                    onChange={(e) => setPriority(e.target.value as any)}
                    className="w-full border border-gray-300 rounded-lg p-2.5 text-sm bg-white focus:ring-2 focus:ring-indigo-500 outline-none"
                  >
                    <option value="low">Düşük</option>
                    <option value="medium">Normal</option>
                    <option value="high">Yüksek</option>
                    <option value="urgent">Acil</option>
                  </select>
                </div>
                <div>
                  <label className="block text-xs font-medium text-gray-700 mb-1">Termin Tarihi</label>
                  <input
                    type="date"
                    value={dueDate}
                    onChange={(e) => setDueDate(e.target.value)}
                    className="w-full border border-gray-300 rounded-lg p-2 text-sm focus:ring-2 focus:ring-indigo-500 outline-none"
                  />
                </div>
              </div>
              <div className="flex justify-end gap-2 pt-2">
                <button
                  type="button"
                  onClick={() => setIsModalOpen(false)}
                  className="px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-50"
                >
                  İptal
                </button>
                <button type="submit" className="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium">
                  Kaydet
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
};
