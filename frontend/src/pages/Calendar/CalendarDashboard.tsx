import React, { useEffect, useState } from 'react';
import { useAuthStore } from '@/store/authStore';
import { getCalendarEvents, createCalendarEvent, deleteCalendarEvent } from '@/services/supportService';
import { CalendarEvent, CalendarEventType } from '@/types/support';
import { Calendar as CalendarIcon, Plus, MapPin, Clock, Trash2, CalendarDays } from 'lucide-react';

export const CalendarDashboard: React.FC = () => {
  const user = useAuthStore((state) => state.user);
  const tenantId = user?.tenant_id || '';

  const [events, setEvents] = useState<CalendarEvent[]>([]);
  const [loading, setLoading] = useState(true);
  const [isModalOpen, setIsModalOpen] = useState(false);

  const [form, setForm] = useState({
    title: '',
    description: '',
    event_type: 'meeting' as CalendarEventType,
    start_date: new Date().toISOString().slice(0, 16),
    end_date: '',
    location: '',
  });

  const loadData = async () => {
    if (!tenantId) return;
    try {
      const data = await getCalendarEvents(tenantId);
      setEvents(data);
    } catch (err) {
      console.error('Etkinlikler yüklenemedi:', err);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    loadData();
  }, [tenantId]);

  const handleCreate = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!form.title || !form.start_date) return;
    await createCalendarEvent(tenantId, form);
    setIsModalOpen(false);
    setForm({
      title: '',
      description: '',
      event_type: 'meeting',
      start_date: new Date().toISOString().slice(0, 16),
      end_date: '',
      location: '',
    });
    loadData();
  };

  const handleDelete = async (id: string) => {
    if (!confirm('Etkinliği silmek istediğinizden emin misiniz?')) return;
    await deleteCalendarEvent(tenantId, id);
    loadData();
  };

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
          <h1 className="text-2xl font-bold text-gray-900 dark:text-white">
            Kurumsal Takvim & Etkinlikler
          </h1>
          <p className="text-sm text-gray-500">
            Toplantılar, şirket etkinlikleri, tatiller ve teslim tarihleri.
          </p>
        </div>

        <button
          onClick={() => setIsModalOpen(true)}
          className="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors shadow-sm"
        >
          <Plus className="w-4 h-4" />
          Yeni Etkinlik Ekle
        </button>
      </div>

      {/* Events Agenda / Stream */}
      {loading ? (
        <div className="py-20 text-center text-gray-400">Yükleniyor...</div>
      ) : events.length === 0 ? (
        <div className="bg-white dark:bg-gray-800 rounded-xl p-12 text-center border border-gray-200 dark:border-gray-700 text-gray-400">
          <CalendarDays className="w-12 h-12 mx-auto mb-3 opacity-30 text-indigo-500" />
          <p className="text-base font-semibold">Planlanmış bir etkinlik bulunmuyor.</p>
          <p className="text-xs text-gray-400 mt-1">Sağ üstteki butondan yeni bir toplantı veya şirket etkinliği ekleyebilirsiniz.</p>
        </div>
      ) : (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          {events.map((e) => (
            <div
              key={e.id}
              className="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-200 dark:border-gray-700 shadow-sm flex flex-col justify-between hover:shadow-md transition-shadow relative"
            >
              <div>
                <div className="flex items-start justify-between gap-2 mb-2">
                  <span
                    className={`text-[10px] font-bold uppercase px-2.5 py-0.5 rounded-full ${
                      e.event_type === 'meeting'
                        ? 'bg-blue-100 text-blue-800'
                        : e.event_type === 'deadline'
                        ? 'bg-red-100 text-red-800'
                        : e.event_type === 'holiday'
                        ? 'bg-emerald-100 text-emerald-800'
                        : 'bg-purple-100 text-purple-800'
                    }`}
                  >
                    {e.event_type === 'meeting' ? 'Toplantı' : e.event_type === 'deadline' ? 'Termin / Deadline' : e.event_type === 'holiday' ? 'Tatil / İzin' : 'Etkinlik'}
                  </span>

                  <button
                    onClick={() => handleDelete(e.id)}
                    className="text-gray-400 hover:text-red-500 transition-colors p-1"
                    title="Etkinliği Sil"
                  >
                    <Trash2 className="w-4 h-4" />
                  </button>
                </div>

                <h3 className="font-bold text-gray-900 dark:text-white text-base mb-1.5">
                  {e.title}
                </h3>

                {e.description && (
                  <p className="text-xs text-gray-500 mb-3 line-clamp-2">
                    {e.description}
                  </p>
                )}
              </div>

              <div className="space-y-1.5 pt-3 border-t border-gray-100 dark:border-gray-700 text-xs text-gray-500">
                <div className="flex items-center gap-1.5">
                  <Clock className="w-3.5 h-3.5 text-indigo-500" />
                  <span>{new Date(e.start_date).toLocaleString('tr-TR', { dateStyle: 'medium', timeStyle: 'short' })}</span>
                </div>

                {e.location && (
                  <div className="flex items-center gap-1.5">
                    <MapPin className="w-3.5 h-3.5 text-rose-500" />
                    <span className="truncate">{e.location}</span>
                  </div>
                )}
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
              Yeni Takvim Etkinliği Ekle
            </h3>
            <form onSubmit={handleCreate} className="space-y-3">
              <div>
                <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                  Etkinlik / Toplantı Başlığı *
                </label>
                <input
                  type="text"
                  required
                  value={form.title}
                  onChange={(e) => setForm({ ...form, title: e.target.value })}
                  placeholder="Örn: Sprint Planlama & Retrospektif"
                  className="w-full px-3 py-2 border rounded-lg text-sm bg-gray-50 dark:bg-gray-700 border-gray-300 dark:border-gray-600"
                />
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Etkinlik Türü
                  </label>
                  <select
                    value={form.event_type}
                    onChange={(e) => setForm({ ...form, event_type: e.target.value as any })}
                    className="w-full px-3 py-2 border rounded-lg text-sm bg-gray-50 dark:bg-gray-700 border-gray-300 dark:border-gray-600"
                  >
                    <option value="meeting">Toplantı</option>
                    <option value="deadline">Termin / Deadline</option>
                    <option value="holiday">Tatil / İzin</option>
                    <option value="event">Genel Etkinlik</option>
                  </select>
                </div>

                <div>
                  <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Konum / Link
                  </label>
                  <input
                    type="text"
                    value={form.location}
                    onChange={(e) => setForm({ ...form, location: e.target.value })}
                    placeholder="Toplantı Odası A veya Zoom"
                    className="w-full px-3 py-2 border rounded-lg text-sm bg-gray-50 dark:bg-gray-700 border-gray-300 dark:border-gray-600"
                  />
                </div>
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Başlangıç Zamanı *
                  </label>
                  <input
                    type="datetime-local"
                    required
                    value={form.start_date}
                    onChange={(e) => setForm({ ...form, start_date: e.target.value })}
                    className="w-full px-3 py-2 border rounded-lg text-sm bg-gray-50 dark:bg-gray-700 border-gray-300 dark:border-gray-600"
                  />
                </div>
                <div>
                  <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Bitiş Zamanı
                  </label>
                  <input
                    type="datetime-local"
                    value={form.end_date}
                    onChange={(e) => setForm({ ...form, end_date: e.target.value })}
                    className="w-full px-3 py-2 border rounded-lg text-sm bg-gray-50 dark:bg-gray-700 border-gray-300 dark:border-gray-600"
                  />
                </div>
              </div>

              <div>
                <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                  Açıklama / Gündem
                </label>
                <textarea
                  rows={3}
                  value={form.description}
                  onChange={(e) => setForm({ ...form, description: e.target.value })}
                  placeholder="Etkinlik detayları..."
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
                  Etkinliği Kaydet
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
};
