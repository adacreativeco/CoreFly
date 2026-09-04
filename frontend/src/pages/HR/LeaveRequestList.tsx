import React, { useState, useEffect } from 'react';
import { useAuthStore } from '@/store/authStore';
import { hrExtensionService } from '@/services/hrExtensionService';
import { getEmployees } from '@/services/hrService';
import { LeaveRequest } from '@/types/hrExtensions';
import { Employee } from '@/types/hr';
import { CalendarCheck, Plus, CheckCircle2, XCircle, Clock, Search, Trash2 } from 'lucide-react';

export const LeaveRequestList: React.FC = () => {
  const user = useAuthStore((state) => state.user);
  const tenantId = user?.tenant_id || '';

  const [leaves, setLeaves] = useState<LeaveRequest[]>([]);
  const [employees, setEmployees] = useState<Employee[]>([]);
  const [loading, setLoading] = useState(true);
  const [statusFilter, setStatusFilter] = useState('all');
  const [search, setSearch] = useState('');
  const [isModalOpen, setIsModalOpen] = useState(false);

  // Form states
  const [employeeId, setEmployeeId] = useState('');
  const [leaveType, setLeaveType] = useState('Yıllık İzin');
  const [startDate, setStartDate] = useState('');
  const [endDate, setEndDate] = useState('');
  const [days, setDays] = useState('1');
  const [reason, setReason] = useState('');

  const loadData = async () => {
    if (!tenantId) return;
    try {
      setLoading(true);
      const [leavesData, empsData] = await Promise.all([
        hrExtensionService.getLeaves(tenantId),
        getEmployees(tenantId).catch(() => []),
      ]);
      setLeaves(leavesData);
      setEmployees(empsData);
      if (empsData.length > 0 && !employeeId) {
        setEmployeeId(empsData[0].id);
      }
    } catch (err) {
      console.error('İzin talepleri yüklenemedi:', err);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    loadData();
  }, [tenantId]);

  const handleCreate = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!employeeId || !startDate || !endDate) return;

    try {
      await hrExtensionService.createLeave(tenantId, {
        employee_id: employeeId,
        leave_type: leaveType,
        start_date: startDate,
        end_date: endDate,
        days: parseInt(days, 10) || 1,
        reason,
      });
      setIsModalOpen(false);
      setReason('');
      loadData();
    } catch (err) {
      alert('İzin talebi oluşturulurken hata meydana geldi.');
    }
  };

  const handleStatusChange = async (id: string, newStatus: 'approved' | 'rejected') => {
    try {
      await hrExtensionService.updateLeaveStatus(tenantId, id, newStatus);
      loadData();
    } catch (err) {
      alert('Durum güncellenemedi.');
    }
  };

  const handleDelete = async (id: string) => {
    if (!window.confirm('Bu izin talebini silmek istediğinize emin misiniz?')) return;
    try {
      await hrExtensionService.deleteLeave(tenantId, id);
      loadData();
    } catch (err) {
      alert('Silinemedi.');
    }
  };

  const filteredLeaves = leaves.filter((item) => {
    const matchesStatus = statusFilter === 'all' || item.status === statusFilter;
    const fullName = `${item.first_name || ''} ${item.last_name || ''}`.toLowerCase();
    const matchesSearch = fullName.includes(search.toLowerCase()) || item.leave_type.toLowerCase().includes(search.toLowerCase());
    return matchesStatus && matchesSearch;
  });

  const pendingCount = leaves.filter((l) => l.status === 'pending').length;
  const approvedCount = leaves.filter((l) => l.status === 'approved').length;

  return (
    <div className="p-6 space-y-6">
      <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
          <h1 className="text-2xl font-bold text-gray-900 flex items-center gap-2">
            <CalendarCheck className="w-7 h-7 text-indigo-600" />
            İzin Yönetimi ve Talepler
          </h1>
          <p className="text-sm text-gray-500 mt-1">
            Personel izin başvuruları, onay/ret süreçleri ve izin takvimi takibi
          </p>
        </div>
        <button
          onClick={() => setIsModalOpen(true)}
          className="flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium shadow-sm transition"
        >
          <Plus className="w-4 h-4" />
          Yeni İzin Talebi Oluştur
        </button>
      </div>

      {/* İstatistikler */}
      <div className="grid grid-cols-1 sm:grid-cols-3 gap-6">
        <div className="bg-white p-5 rounded-xl border border-gray-100 shadow-sm flex items-center justify-between">
          <div>
            <div className="text-xs font-semibold uppercase tracking-wider text-gray-500">Onay Bekleyen Talepler</div>
            <div className="text-2xl font-bold text-amber-600 mt-1">{pendingCount}</div>
          </div>
          <div className="p-3 bg-amber-50 rounded-lg text-amber-600">
            <Clock className="w-6 h-6" />
          </div>
        </div>

        <div className="bg-white p-5 rounded-xl border border-gray-100 shadow-sm flex items-center justify-between">
          <div>
            <div className="text-xs font-semibold uppercase tracking-wider text-gray-500">Onaylanan İzinler</div>
            <div className="text-2xl font-bold text-emerald-600 mt-1">{approvedCount}</div>
          </div>
          <div className="p-3 bg-emerald-50 rounded-lg text-emerald-600">
            <CheckCircle2 className="w-6 h-6" />
          </div>
        </div>

        <div className="bg-white p-5 rounded-xl border border-gray-100 shadow-sm flex items-center justify-between">
          <div>
            <div className="text-xs font-semibold uppercase tracking-wider text-gray-500">Toplam Başvuru</div>
            <div className="text-2xl font-bold text-gray-900 mt-1">{leaves.length}</div>
          </div>
          <div className="p-3 bg-indigo-50 rounded-lg text-indigo-600">
            <CalendarCheck className="w-6 h-6" />
          </div>
        </div>
      </div>

      {/* Filtreler */}
      <div className="bg-white p-4 rounded-xl border border-gray-100 shadow-sm flex flex-col sm:flex-row gap-4 justify-between items-center">
        <div className="flex items-center gap-2 w-full sm:w-80 relative">
          <Search className="w-4 h-4 text-gray-400 absolute left-3" />
          <input
            type="text"
            placeholder="Personel veya izin türü ara..."
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            className="w-full pl-9 pr-3 py-1.5 border border-gray-300 rounded-lg text-sm bg-white focus:ring-2 focus:ring-indigo-500 outline-none"
          />
        </div>
        <div className="flex gap-2">
          {['all', 'pending', 'approved', 'rejected'].map((st) => (
            <button
              key={st}
              onClick={() => setStatusFilter(st)}
              className={`px-3 py-1.5 rounded-lg text-xs font-medium transition ${
                statusFilter === st ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
              }`}
            >
              {st === 'all' ? 'Tümü' : st === 'pending' ? 'Bekleyenler' : st === 'approved' ? 'Onaylananlar' : 'Reddedilenler'}
            </button>
          ))}
        </div>
      </div>

      {/* Tablo */}
      <div className="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <div className="p-4 border-b border-gray-100">
          <h2 className="text-base font-semibold text-gray-800">İzin Başvuru Geçmişi</h2>
        </div>
        {loading ? (
          <div className="p-10 text-center text-gray-500">Yükleniyor...</div>
        ) : filteredLeaves.length === 0 ? (
          <div className="p-12 text-center text-gray-400">Kayıtlı izin talebi bulunamadı.</div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-left text-sm">
              <thead className="bg-gray-50 text-gray-600 text-xs uppercase font-medium">
                <tr>
                  <th className="px-6 py-3">Personel</th>
                  <th className="px-6 py-3">İzin Türü</th>
                  <th className="px-6 py-3">Tarih Aralığı</th>
                  <th className="px-6 py-3">Süre</th>
                  <th className="px-6 py-3">Açıklama</th>
                  <th className="px-6 py-3">Durum</th>
                  <th className="px-6 py-3 text-right">İşlemler</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-100">
                {filteredLeaves.map((item) => (
                  <tr key={item.id} className="hover:bg-gray-50">
                    <td className="px-6 py-4">
                      <div className="font-semibold text-gray-900">
                        {item.first_name ? `${item.first_name} ${item.last_name}` : 'İsimsiz Çalışan'}
                      </div>
                      <div className="text-xs text-gray-400">{item.department_name || item.employee_email}</div>
                    </td>
                    <td className="px-6 py-4">
                      <span className="px-2 py-1 bg-indigo-50 text-indigo-700 rounded text-xs font-medium">
                        {item.leave_type}
                      </span>
                    </td>
                    <td className="px-6 py-4 text-gray-700 text-xs font-medium">
                      {item.start_date} → {item.end_date}
                    </td>
                    <td className="px-6 py-4 font-bold text-gray-800">{item.days} Gün</td>
                    <td className="px-6 py-4 text-gray-500 text-xs max-w-xs truncate">{item.reason || '-'}</td>
                    <td className="px-6 py-4">
                      {item.status === 'pending' ? (
                        <span className="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-800">
                          <Clock className="w-3 h-3" />
                          Bekliyor
                        </span>
                      ) : item.status === 'approved' ? (
                        <span className="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">
                          <CheckCircle2 className="w-3 h-3" />
                          Onaylandı
                        </span>
                      ) : (
                        <span className="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-800">
                          <XCircle className="w-3 h-3" />
                          Reddedildi
                        </span>
                      )}
                    </td>
                    <td className="px-6 py-4 text-right space-x-1">
                      {item.status === 'pending' && (
                        <>
                          <button
                            onClick={() => handleStatusChange(item.id, 'approved')}
                            className="px-2.5 py-1 bg-emerald-600 hover:bg-emerald-700 text-white rounded text-xs font-medium transition"
                          >
                            Onayla
                          </button>
                          <button
                            onClick={() => handleStatusChange(item.id, 'rejected')}
                            className="px-2.5 py-1 bg-rose-600 hover:bg-rose-700 text-white rounded text-xs font-medium transition"
                          >
                            Reddet
                          </button>
                        </>
                      )}
                      <button
                        onClick={() => handleDelete(item.id)}
                        className="p-1 text-gray-400 hover:text-red-600 rounded transition"
                        title="Sil"
                      >
                        <Trash2 className="w-4 h-4 inline" />
                      </button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </div>

      {/* Modal */}
      {isModalOpen && (
        <div className="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4">
          <div className="bg-white rounded-xl shadow-xl max-w-md w-full p-6 space-y-4">
            <div className="flex justify-between items-center border-b pb-3">
              <h3 className="text-lg font-bold text-gray-900">Yeni İzin Talebi</h3>
              <button onClick={() => setIsModalOpen(false)} className="text-gray-400 hover:text-gray-600 text-lg font-bold">
                ✕
              </button>
            </div>
            <form onSubmit={handleCreate} className="space-y-4">
              <div>
                <label className="block text-xs font-medium text-gray-700 mb-1">Çalışan *</label>
                <select
                  required
                  value={employeeId}
                  onChange={(e) => setEmployeeId(e.target.value)}
                  className="w-full border border-gray-300 rounded-lg p-2.5 text-sm bg-white focus:ring-2 focus:ring-indigo-500 outline-none"
                >
                  <option value="">Seçiniz</option>
                  {employees.map((emp) => (
                    <option key={emp.id} value={emp.id}>
                      {emp.first_name} {emp.last_name} ({emp.email})
                    </option>
                  ))}
                </select>
              </div>
              <div>
                <label className="block text-xs font-medium text-gray-700 mb-1">İzin Türü</label>
                <select
                  value={leaveType}
                  onChange={(e) => setLeaveType(e.target.value)}
                  className="w-full border border-gray-300 rounded-lg p-2.5 text-sm bg-white focus:ring-2 focus:ring-indigo-500 outline-none"
                >
                  <option value="Yıllık İzin">Yıllık İzin</option>
                  <option value="Mazeret İzni">Mazeret İzni</option>
                  <option value="Hastalık / Rapor">Hastalık / Rapor</option>
                  <option value="Ücretsiz İzin">Ücretsiz İzin</option>
                  <option value="Evlilik İzni">Evlilik İzni</option>
                </select>
              </div>
              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block text-xs font-medium text-gray-700 mb-1">Başlangıç Tarihi *</label>
                  <input
                    type="date"
                    required
                    value={startDate}
                    onChange={(e) => setStartDate(e.target.value)}
                    className="w-full border border-gray-300 rounded-lg p-2 text-sm focus:ring-2 focus:ring-indigo-500 outline-none"
                  />
                </div>
                <div>
                  <label className="block text-xs font-medium text-gray-700 mb-1">Bitiş Tarihi *</label>
                  <input
                    type="date"
                    required
                    value={endDate}
                    onChange={(e) => setEndDate(e.target.value)}
                    className="w-full border border-gray-300 rounded-lg p-2 text-sm focus:ring-2 focus:ring-indigo-500 outline-none"
                  />
                </div>
              </div>
              <div>
                <label className="block text-xs font-medium text-gray-700 mb-1">Toplam Gün Sayısı</label>
                <input
                  type="number"
                  min="1"
                  value={days}
                  onChange={(e) => setDays(e.target.value)}
                  className="w-full border border-gray-300 rounded-lg p-2 text-sm focus:ring-2 focus:ring-indigo-500 outline-none"
                />
              </div>
              <div>
                <label className="block text-xs font-medium text-gray-700 mb-1">Açıklama / Mazeret</label>
                <textarea
                  value={reason}
                  onChange={(e) => setReason(e.target.value)}
                  rows={2}
                  placeholder="İzin gerekçesi..."
                  className="w-full border border-gray-300 rounded-lg p-2 text-sm focus:ring-2 focus:ring-indigo-500 outline-none"
                />
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
                  Talebi Gönder
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
};
