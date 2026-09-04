import React, { useEffect, useState } from 'react';
import { useAuthStore } from '@/store/authStore';
import {
  getHelpdeskStats,
  getTickets,
  getTicketDetails,
  createTicket,
  addTicketMessage,
  updateTicketStatus,
} from '@/services/supportService';
import { HelpdeskTicket, HelpdeskStats, TicketStatus, TicketPriority } from '@/types/support';
import { LifeBuoy, Plus, Search, Send, Clock, CheckCircle2, MessageSquare, AlertCircle } from 'lucide-react';

export const HelpdeskDashboard: React.FC = () => {
  const user = useAuthStore((state) => state.user);
  const tenantId = user?.tenant_id || '';

  const [stats, setStats] = useState<HelpdeskStats | null>(null);
  const [tickets, setTickets] = useState<HelpdeskTicket[]>([]);
  const [selectedTicket, setSelectedTicket] = useState<HelpdeskTicket | null>(null);
  const [loading, setLoading] = useState(true);

  // Filters
  const [search, setSearch] = useState('');
  const [statusFilter, setStatusFilter] = useState('');
  const [priorityFilter, setPriorityFilter] = useState('');

  // Modals & Inputs
  const [isNewModalOpen, setIsNewModalOpen] = useState(false);
  const [newForm, setNewForm] = useState({
    title: '',
    description: '',
    category: 'Sistem / Yazılım',
    priority: 'medium' as TicketPriority,
  });
  const [replyMessage, setReplyMessage] = useState('');

  const loadData = async () => {
    if (!tenantId) return;
    try {
      const [s, t] = await Promise.all([
        getHelpdeskStats(tenantId),
        getTickets(tenantId, { search, status: statusFilter, priority: priorityFilter }),
      ]);
      setStats(s);
      setTickets(t);
      if (selectedTicket) {
        const updated = await getTicketDetails(tenantId, selectedTicket.id);
        setSelectedTicket(updated);
      }
    } catch (err) {
      console.error('Destek verileri yüklenemedi:', err);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    loadData();
  }, [tenantId, search, statusFilter, priorityFilter]);

  const handleSelectTicket = async (ticket: HelpdeskTicket) => {
    try {
      const details = await getTicketDetails(tenantId, ticket.id);
      setSelectedTicket(details);
    } catch (err) {
      console.error('Bilet detayı alınamadı:', err);
    }
  };

  const handleCreateTicket = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!newForm.title || !newForm.description) return;
    await createTicket(tenantId, newForm);
    setIsNewModalOpen(false);
    setNewForm({ title: '', description: '', category: 'Sistem / Yazılım', priority: 'medium' });
    loadData();
  };

  const handleSendMessage = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!selectedTicket || !replyMessage.trim()) return;
    await addTicketMessage(tenantId, selectedTicket.id, replyMessage, user?.full_name || 'Kullanıcı');
    setReplyMessage('');
    const updated = await getTicketDetails(tenantId, selectedTicket.id);
    setSelectedTicket(updated);
  };

  const handleStatusChange = async (newStatus: TicketStatus) => {
    if (!selectedTicket) return;
    await updateTicketStatus(tenantId, selectedTicket.id, newStatus);
    const updated = await getTicketDetails(tenantId, selectedTicket.id);
    setSelectedTicket(updated);
    loadData();
  };

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
          <h1 className="text-2xl font-bold text-gray-900 dark:text-white">
            Destek Merkezi (Helpdesk)
          </h1>
          <p className="text-sm text-gray-500">
            Kullanıcı talepleri, IT ve kurumsal destek biletleri yönetimi.
          </p>
        </div>

        <button
          onClick={() => setIsNewModalOpen(true)}
          className="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors shadow-sm"
        >
          <Plus className="w-4 h-4" />
          Yeni Destek Talebi Aç
        </button>
      </div>

      {/* Metrics */}
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div className="bg-white dark:bg-gray-800 p-5 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm flex items-center gap-4">
          <div className="w-12 h-12 bg-indigo-50 text-indigo-600 rounded-lg flex items-center justify-center">
            <LifeBuoy className="w-6 h-6" />
          </div>
          <div>
            <div className="text-xs font-medium text-gray-500">Toplam Bilet</div>
            <div className="text-2xl font-bold text-gray-900 dark:text-white">
              {stats?.total_tickets ?? 0}
            </div>
          </div>
        </div>

        <div className="bg-white dark:bg-gray-800 p-5 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm flex items-center gap-4">
          <div className="w-12 h-12 bg-amber-50 text-amber-600 rounded-lg flex items-center justify-center">
            <AlertCircle className="w-6 h-6" />
          </div>
          <div>
            <div className="text-xs font-medium text-gray-500">Açık Talepler</div>
            <div className="text-2xl font-bold text-amber-600">
              {stats?.open_tickets ?? 0}
            </div>
          </div>
        </div>

        <div className="bg-white dark:bg-gray-800 p-5 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm flex items-center gap-4">
          <div className="w-12 h-12 bg-blue-50 text-blue-600 rounded-lg flex items-center justify-center">
            <Clock className="w-6 h-6" />
          </div>
          <div>
            <div className="text-xs font-medium text-gray-500">İncelenen / İşlemde</div>
            <div className="text-2xl font-bold text-blue-600">
              {stats?.in_progress_tickets ?? 0}
            </div>
          </div>
        </div>

        <div className="bg-white dark:bg-gray-800 p-5 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm flex items-center gap-4">
          <div className="w-12 h-12 bg-emerald-50 text-emerald-600 rounded-lg flex items-center justify-center">
            <CheckCircle2 className="w-6 h-6" />
          </div>
          <div>
            <div className="text-xs font-medium text-gray-500">Çözümlenen Talepler</div>
            <div className="text-2xl font-bold text-emerald-600">
              {stats?.resolved_tickets ?? 0}
            </div>
          </div>
        </div>
      </div>

      {/* Main 2-Pane Content */}
      <div className="grid grid-cols-1 lg:grid-cols-12 gap-6 min-h-[500px]">
        {/* Left Pane: Ticket List */}
        <div className="lg:col-span-5 space-y-3">
          <div className="flex gap-2">
            <div className="relative flex-1">
              <Search className="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" />
              <input
                type="text"
                placeholder="Talep ara..."
                value={search}
                onChange={(e) => setSearch(e.target.value)}
                className="w-full pl-9 pr-3 py-1.5 border border-gray-200 dark:border-gray-700 rounded-lg text-sm bg-white dark:bg-gray-800 focus:outline-none"
              />
            </div>
            <select
              value={statusFilter}
              onChange={(e) => setStatusFilter(e.target.value)}
              className="px-2 py-1.5 border border-gray-200 dark:border-gray-700 rounded-lg text-xs bg-white dark:bg-gray-800 focus:outline-none"
            >
              <option value="">Tüm Durumlar</option>
              <option value="open">Açık</option>
              <option value="in_progress">İşlemde</option>
              <option value="resolved">Çözüldü</option>
            </select>
          </div>

          <div className="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 divide-y divide-gray-100 dark:divide-gray-700 overflow-hidden shadow-sm max-h-[600px] overflow-y-auto">
            {tickets.length === 0 ? (
              <div className="p-8 text-center text-sm text-gray-400">
                Talep bulunamadı.
              </div>
            ) : (
              tickets.map((t) => (
                <div
                  key={t.id}
                  onClick={() => handleSelectTicket(t)}
                  className={`p-4 cursor-pointer transition-colors ${
                    selectedTicket?.id === t.id
                      ? 'bg-indigo-50/70 dark:bg-indigo-950/40 border-l-4 border-indigo-600'
                      : 'hover:bg-gray-50 dark:hover:bg-gray-700/50'
                  }`}
                >
                  <div className="flex items-start justify-between gap-2 mb-1">
                    <h4 className="font-semibold text-gray-900 dark:text-white text-sm line-clamp-1">
                      {t.title}
                    </h4>
                    <span
                      className={`text-[10px] font-bold px-2 py-0.5 rounded-full uppercase ${
                        t.priority === 'urgent'
                          ? 'bg-red-100 text-red-800'
                          : t.priority === 'high'
                          ? 'bg-orange-100 text-orange-800'
                          : t.priority === 'medium'
                          ? 'bg-blue-100 text-blue-800'
                          : 'bg-gray-100 text-gray-800'
                      }`}
                    >
                      {t.priority}
                    </span>
                  </div>

                  <p className="text-xs text-gray-500 line-clamp-2 mb-2">
                    {t.description}
                  </p>

                  <div className="flex items-center justify-between text-[11px] text-gray-400">
                    <span>{t.category}</span>
                    <span>{new Date(t.created_at).toLocaleDateString('tr-TR')}</span>
                  </div>
                </div>
              ))
            )}
          </div>
        </div>

        {/* Right Pane: Ticket Detail & Thread */}
        <div className="lg:col-span-7 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm flex flex-col min-h-[500px]">
          {selectedTicket ? (
            <>
              {/* Ticket Top bar */}
              <div className="p-4 border-b border-gray-200 dark:border-gray-700 flex flex-wrap items-center justify-between gap-3 bg-gray-50 dark:bg-gray-900/40 rounded-t-xl">
                <div>
                  <h3 className="font-bold text-gray-900 dark:text-white text-base">
                    {selectedTicket.title}
                  </h3>
                  <div className="text-xs text-gray-400 mt-0.5">
                    Kategori: {selectedTicket.category} | Açılış: {new Date(selectedTicket.created_at).toLocaleString('tr-TR')}
                  </div>
                </div>

                <div className="flex items-center gap-2">
                  <select
                    value={selectedTicket.status}
                    onChange={(e) => handleStatusChange(e.target.value as any)}
                    className="text-xs font-semibold px-2.5 py-1 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 focus:outline-none"
                  >
                    <option value="open">Açık</option>
                    <option value="in_progress">İşlemde</option>
                    <option value="resolved">Çözümlendi</option>
                    <option value="closed">Kapatıldı</option>
                  </select>
                </div>
              </div>

              {/* Messages Area */}
              <div className="flex-1 p-5 overflow-y-auto space-y-4">
                {/* Initial Description */}
                <div className="bg-gray-50 dark:bg-gray-700/50 p-4 rounded-xl border border-gray-100 dark:border-gray-600">
                  <div className="text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                    Talep Açıklaması
                  </div>
                  <p className="text-sm text-gray-800 dark:text-gray-200 whitespace-pre-line">
                    {selectedTicket.description}
                  </p>
                </div>

                {/* Conversation Messages */}
                {selectedTicket.messages?.map((m) => (
                  <div key={m.id} className="flex flex-col space-y-1">
                    <div className="flex items-center gap-2">
                      <span className="text-xs font-bold text-gray-800 dark:text-gray-200">
                        {m.user_name || 'Destek Yetkilisi'}
                      </span>
                      <span className="text-[10px] text-gray-400">
                        {new Date(m.created_at).toLocaleTimeString('tr-TR', { hour: '2-digit', minute: '2-digit' })}
                      </span>
                    </div>
                    <div className="bg-indigo-50/60 dark:bg-indigo-950/30 p-3 rounded-xl border border-indigo-100/60 dark:border-indigo-900/40 text-sm text-gray-800 dark:text-gray-200 max-w-xl">
                      {m.message}
                    </div>
                  </div>
                ))}
              </div>

              {/* Reply Form */}
              <form onSubmit={handleSendMessage} className="p-4 border-t border-gray-200 dark:border-gray-700 flex gap-2">
                <input
                  type="text"
                  placeholder="Bu talebe bir yanıt yazın..."
                  value={replyMessage}
                  onChange={(e) => setReplyMessage(e.target.value)}
                  className="flex-1 px-4 py-2 text-sm border border-gray-200 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                />
                <button
                  type="submit"
                  className="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors flex items-center gap-1.5"
                >
                  <Send className="w-4 h-4" />
                  Gönder
                </button>
              </form>
            </>
          ) : (
            <div className="flex-1 flex flex-col items-center justify-center p-8 text-center text-gray-400">
              <MessageSquare className="w-12 h-12 mb-3 opacity-30 text-indigo-500" />
              <p className="text-sm font-medium">Detayları ve mesaj geçmişini görmek için soldan bir destek bileti seçiniz.</p>
            </div>
          )}
        </div>
      </div>

      {/* New Ticket Modal */}
      {isNewModalOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
          <div className="bg-white dark:bg-gray-800 rounded-2xl w-full max-w-md p-6 shadow-xl border border-gray-200 dark:border-gray-700">
            <h3 className="text-lg font-bold text-gray-900 dark:text-white mb-4">
              Yeni Destek Talebi Aç
            </h3>
            <form onSubmit={handleCreateTicket} className="space-y-4">
              <div>
                <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                  Talep Konusu *
                </label>
                <input
                  type="text"
                  required
                  value={newForm.title}
                  onChange={(e) => setNewForm({ ...newForm, title: e.target.value })}
                  placeholder="Örn: E-posta şifre sıfırlama sorunu"
                  className="w-full px-3 py-2 border rounded-lg text-sm bg-gray-50 dark:bg-gray-700 border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-indigo-500"
                />
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Kategori
                  </label>
                  <select
                    value={newForm.category}
                    onChange={(e) => setNewForm({ ...newForm, category: e.target.value })}
                    className="w-full px-3 py-2 border rounded-lg text-sm bg-gray-50 dark:bg-gray-700 border-gray-300 dark:border-gray-600"
                  >
                    <option value="Sistem / Yazılım">Sistem / Yazılım</option>
                    <option value="Donanım & Cihaz">Donanım & Cihaz</option>
                    <option value="Ağ / VPN">Ağ / VPN</option>
                    <option value="İdari / Tesis">İdari / Tesis</option>
                    <option value="Diğer">Diğer</option>
                  </select>
                </div>

                <div>
                  <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Öncelik
                  </label>
                  <select
                    value={newForm.priority}
                    onChange={(e) => setNewForm({ ...newForm, priority: e.target.value as any })}
                    className="w-full px-3 py-2 border rounded-lg text-sm bg-gray-50 dark:bg-gray-700 border-gray-300 dark:border-gray-600"
                  >
                    <option value="low">Düşük</option>
                    <option value="medium">Normal</option>
                    <option value="high">Yüksek</option>
                    <option value="urgent">Acil</option>
                  </select>
                </div>
              </div>

              <div>
                <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                  Ayrıntılı Açıklama *
                </label>
                <textarea
                  rows={4}
                  required
                  value={newForm.description}
                  onChange={(e) => setNewForm({ ...newForm, description: e.target.value })}
                  placeholder="Lütfen karşılaştığınız sorunu ve adımları detaylandırınız..."
                  className="w-full px-3 py-2 border rounded-lg text-sm bg-gray-50 dark:bg-gray-700 border-gray-300 dark:border-gray-600"
                />
              </div>

              <div className="flex justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                <button
                  type="button"
                  onClick={() => setIsNewModalOpen(false)}
                  className="px-4 py-2 text-sm text-gray-600 hover:text-gray-800 dark:text-gray-400"
                >
                  İptal
                </button>
                <button
                  type="submit"
                  className="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium transition-colors"
                >
                  Talebi Oluştur
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
};
