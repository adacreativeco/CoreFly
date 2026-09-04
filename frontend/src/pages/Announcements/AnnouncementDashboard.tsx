import React, { useEffect, useState } from 'react';
import { useAuthStore } from '@/store/authStore';
import { getAnnouncements, createAnnouncement, deleteAnnouncement } from '@/services/supportService';
import { Announcement, AnnouncementPriority } from '@/types/support';
import { Bell, Plus, Pin, Trash2, Megaphone, Calendar } from 'lucide-react';

export const AnnouncementDashboard: React.FC = () => {
  const user = useAuthStore((state) => state.user);
  const tenantId = user?.tenant_id || '';

  const [announcements, setAnnouncements] = useState<Announcement[]>([]);
  const [loading, setLoading] = useState(true);
  const [isModalOpen, setIsModalOpen] = useState(false);

  const [form, setForm] = useState({
    title: '',
    content: '',
    priority: 'normal' as AnnouncementPriority,
    is_pinned: false,
    author_name: user?.full_name || 'Şirket Yönetimi',
  });

  const loadData = async () => {
    if (!tenantId) return;
    try {
      const data = await getAnnouncements(tenantId);
      setAnnouncements(data);
    } catch (err) {
      console.error('Duyurular yüklenemedi:', err);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    loadData();
  }, [tenantId]);

  const handleCreate = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!form.title || !form.content) return;
    await createAnnouncement(tenantId, form);
    setIsModalOpen(false);
    setForm({
      title: '',
      content: '',
      priority: 'normal',
      is_pinned: false,
      author_name: user?.full_name || 'Şirket Yönetimi',
    });
    loadData();
  };

  const handleDelete = async (id: string) => {
    if (!confirm('Duyuruyu silmek istediğinizden emin misiniz?')) return;
    await deleteAnnouncement(tenantId, id);
    loadData();
  };

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
          <h1 className="text-2xl font-bold text-gray-900 dark:text-white">
            Şirket İçi Duyurular & Haberler
          </h1>
          <p className="text-sm text-gray-500">
            Tüm çalışanlar ve departmanlar için kurumsal bildirim ve duyuru akışı.
          </p>
        </div>

        <button
          onClick={() => setIsModalOpen(true)}
          className="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors shadow-sm"
        >
          <Plus className="w-4 h-4" />
          Yeni Duyuru Yayınla
        </button>
      </div>

      {/* Announcements Stream */}
      {loading ? (
        <div className="py-20 text-center text-gray-400">Yükleniyor...</div>
      ) : announcements.length === 0 ? (
        <div className="bg-white dark:bg-gray-800 rounded-xl p-12 text-center border border-gray-200 dark:border-gray-700 text-gray-400">
          <Megaphone className="w-12 h-12 mx-auto mb-3 opacity-30 text-indigo-500" />
          <p className="text-base font-semibold">Henüz bir duyuru bulunmuyor.</p>
          <p className="text-xs text-gray-400 mt-1">Yeni bir şirket duyurusu yayınlayarak ilk bildirimi başlatabilirsiniz.</p>
        </div>
      ) : (
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          {announcements.map((a) => (
            <div
              key={a.id}
              className={`bg-white dark:bg-gray-800 rounded-xl p-5 border shadow-sm transition-shadow relative flex flex-col justify-between ${
                a.is_pinned
                  ? 'border-indigo-300 dark:border-indigo-800 ring-1 ring-indigo-500/20'
                  : 'border-gray-200 dark:border-gray-700'
              }`}
            >
              <div>
                <div className="flex items-start justify-between gap-3 mb-2">
                  <div className="flex items-center gap-2">
                    {a.is_pinned && (
                      <span className="flex items-center gap-1 text-[11px] font-bold text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-950/60 px-2 py-0.5 rounded-full">
                        <Pin className="w-3 h-3" /> Sabitlendi
                      </span>
                    )}
                    <span
                      className={`text-[10px] font-bold uppercase px-2 py-0.5 rounded-full ${
                        a.priority === 'urgent'
                          ? 'bg-red-100 text-red-800'
                          : a.priority === 'high'
                          ? 'bg-orange-100 text-orange-800'
                          : 'bg-blue-100 text-blue-800'
                      }`}
                    >
                      {a.priority}
                    </span>
                  </div>

                  <button
                    onClick={() => handleDelete(a.id)}
                    className="text-gray-400 hover:text-red-500 transition-colors p-1"
                    title="Duyuruyu Sil"
                  >
                    <Trash2 className="w-4 h-4" />
                  </button>
                </div>

                <h3 className="font-bold text-gray-900 dark:text-white text-base mb-2">
                  {a.title}
                </h3>

                <p className="text-sm text-gray-600 dark:text-gray-300 whitespace-pre-line leading-relaxed mb-4">
                  {a.content}
                </p>
              </div>

              <div className="pt-3 border-t border-gray-100 dark:border-gray-700 flex items-center justify-between text-xs text-gray-400">
                <span>Yayınlayan: <strong className="text-gray-600 dark:text-gray-300">{a.author_name}</strong></span>
                <span className="flex items-center gap-1">
                  <Calendar className="w-3 h-3" />
                  {new Date(a.created_at).toLocaleDateString('tr-TR')}
                </span>
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
              Yeni Şirket Duyurusu Yayınla
            </h3>
            <form onSubmit={handleCreate} className="space-y-4">
              <div>
                <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                  Duyuru Başlığı *
                </label>
                <input
                  type="text"
                  required
                  value={form.title}
                  onChange={(e) => setForm({ ...form, title: e.target.value })}
                  placeholder="Örn: 2026 Yıllık İzin Planlaması"
                  className="w-full px-3 py-2 border rounded-lg text-sm bg-gray-50 dark:bg-gray-700 border-gray-300 dark:border-gray-600"
                />
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Önem Derecesi
                  </label>
                  <select
                    value={form.priority}
                    onChange={(e) => setForm({ ...form, priority: e.target.value as any })}
                    className="w-full px-3 py-2 border rounded-lg text-sm bg-gray-50 dark:bg-gray-700 border-gray-300 dark:border-gray-600"
                  >
                    <option value="low">Düşük</option>
                    <option value="normal">Normal</option>
                    <option value="high">Yüksek</option>
                    <option value="urgent">Acil / Kritik</option>
                  </select>
                </div>

                <div>
                  <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Yayınlayan İsim
                  </label>
                  <input
                    type="text"
                    value={form.author_name}
                    onChange={(e) => setForm({ ...form, author_name: e.target.value })}
                    className="w-full px-3 py-2 border rounded-lg text-sm bg-gray-50 dark:bg-gray-700 border-gray-300 dark:border-gray-600"
                  />
                </div>
              </div>

              <div>
                <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                  Duyuru İçeriği *
                </label>
                <textarea
                  rows={4}
                  required
                  value={form.content}
                  onChange={(e) => setForm({ ...form, content: e.target.value })}
                  placeholder="Duyuru metnini giriniz..."
                  className="w-full px-3 py-2 border rounded-lg text-sm bg-gray-50 dark:bg-gray-700 border-gray-300 dark:border-gray-600"
                />
              </div>

              <div className="flex items-center gap-2">
                <input
                  type="checkbox"
                  id="is_pinned"
                  checked={form.is_pinned}
                  onChange={(e) => setForm({ ...form, is_pinned: e.target.checked })}
                  className="rounded text-indigo-600 focus:ring-indigo-500"
                />
                <label htmlFor="is_pinned" className="text-xs text-gray-700 dark:text-gray-300 font-medium cursor-pointer">
                  Bu duyuruyu en üstte sabitle (Pin)
                </label>
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
                  Duyuruyu Yayınla
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
};
