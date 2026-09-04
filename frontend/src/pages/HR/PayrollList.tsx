import React, { useState, useEffect } from 'react';
import { useAuthStore } from '@/store/authStore';
import { hrExtensionService } from '@/services/hrExtensionService';
import { getEmployees } from '@/services/hrService';
import { Payroll } from '@/types/hrExtensions';
import { Employee } from '@/types/hr';
import { DollarSign, Plus, CheckCircle2, Clock, Search, Trash2, Calendar } from 'lucide-react';

export const PayrollList: React.FC = () => {
  const user = useAuthStore((state) => state.user);
  const tenantId = user?.tenant_id || '';

  const [payrolls, setPayrolls] = useState<Payroll[]>([]);
  const [employees, setEmployees] = useState<Employee[]>([]);
  const [loading, setLoading] = useState(true);
  const [search, setSearch] = useState('');
  const [isModalOpen, setIsModalOpen] = useState(false);

  // Form states
  const [employeeId, setEmployeeId] = useState('');
  const [period, setPeriod] = useState(new Date().toISOString().slice(0, 7)); // 'YYYY-MM'
  const [baseSalary, setBaseSalary] = useState('');
  const [bonus, setBonus] = useState('0');
  const [deductions, setDeductions] = useState('0');

  const loadData = async () => {
    if (!tenantId) return;
    try {
      setLoading(true);
      const [payrollsData, empsData] = await Promise.all([
        hrExtensionService.getPayrolls(tenantId),
        getEmployees(tenantId).catch(() => []),
      ]);
      setPayrolls(payrollsData);
      setEmployees(empsData);
      if (empsData.length > 0 && !employeeId) {
        setEmployeeId(empsData[0].id);
      }
    } catch (err) {
      console.error('Bordrolar yüklenemedi:', err);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    loadData();
  }, [tenantId]);

  const handleCreate = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!employeeId || !period || !baseSalary) return;

    try {
      await hrExtensionService.createPayroll(tenantId, {
        employee_id: employeeId,
        period,
        base_salary: parseFloat(baseSalary) || 0,
        bonus: parseFloat(bonus) || 0,
        deductions: parseFloat(deductions) || 0,
      });
      setIsModalOpen(false);
      setBaseSalary('');
      setBonus('0');
      setDeductions('0');
      loadData();
    } catch (err) {
      alert('Bordro oluşturulurken hata meydana geldi.');
    }
  };

  const handleStatusChange = async (id: string, newStatus: 'paid' | 'pending') => {
    try {
      await hrExtensionService.updatePayrollStatus(tenantId, id, newStatus);
      loadData();
    } catch (err) {
      alert('Durum güncellenemedi.');
    }
  };

  const handleDelete = async (id: string) => {
    if (!window.confirm('Bu bordroyu silmek istediğinize emin misiniz?')) return;
    try {
      await hrExtensionService.deletePayroll(tenantId, id);
      loadData();
    } catch (err) {
      alert('Silinemedi.');
    }
  };

  const filteredPayrolls = payrolls.filter((item) => {
    const fullName = `${item.first_name || ''} ${item.last_name || ''}`.toLowerCase();
    return fullName.includes(search.toLowerCase()) || item.period.includes(search);
  });

  const totalPayroll = payrolls.reduce((sum, p) => sum + Number(p.net_salary || 0), 0);
  const paidCount = payrolls.filter((p) => p.status === 'paid').length;
  const pendingCount = payrolls.filter((p) => p.status === 'pending').length;

  return (
    <div className="p-6 space-y-6">
      <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
          <h1 className="text-2xl font-bold text-gray-900 flex items-center gap-2">
            <DollarSign className="w-7 h-7 text-emerald-600" />
            Bordro ve Maaş Yönetimi
          </h1>
          <p className="text-sm text-gray-500 mt-1">
            Çalışan maaşları, primler, yasal kesintiler ve net hakediş ödemeleri
          </p>
        </div>
        <button
          onClick={() => setIsModalOpen(true)}
          className="flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-sm font-medium shadow-sm transition"
        >
          <Plus className="w-4 h-4" />
          Yeni Bordro Hesabı Ekle
        </button>
      </div>

      {/* İstatistikler */}
      <div className="grid grid-cols-1 sm:grid-cols-3 gap-6">
        <div className="bg-white p-5 rounded-xl border border-gray-100 shadow-sm flex items-center justify-between">
          <div>
            <div className="text-xs font-semibold uppercase tracking-wider text-gray-500">Toplam Bordro Hacmi</div>
            <div className="text-2xl font-bold text-emerald-600 mt-1">
              ₺{totalPayroll.toLocaleString('tr-TR', { minimumFractionDigits: 2 })}
            </div>
          </div>
          <div className="p-3 bg-emerald-50 rounded-lg text-emerald-600">
            <DollarSign className="w-6 h-6" />
          </div>
        </div>

        <div className="bg-white p-5 rounded-xl border border-gray-100 shadow-sm flex items-center justify-between">
          <div>
            <div className="text-xs font-semibold uppercase tracking-wider text-gray-500">Ödenen Bordrolar</div>
            <div className="text-2xl font-bold text-gray-900 mt-1">{paidCount}</div>
          </div>
          <div className="p-3 bg-blue-50 rounded-lg text-blue-600">
            <CheckCircle2 className="w-6 h-6" />
          </div>
        </div>

        <div className="bg-white p-5 rounded-xl border border-gray-100 shadow-sm flex items-center justify-between">
          <div>
            <div className="text-xs font-semibold uppercase tracking-wider text-gray-500">Ödeme Bekleyenler</div>
            <div className="text-2xl font-bold text-amber-600 mt-1">{pendingCount}</div>
          </div>
          <div className="p-3 bg-amber-50 rounded-lg text-amber-600">
            <Clock className="w-6 h-6" />
          </div>
        </div>
      </div>

      {/* Arama */}
      <div className="bg-white p-4 rounded-xl border border-gray-100 shadow-sm flex items-center gap-3">
        <Search className="w-4 h-4 text-gray-400" />
        <input
          type="text"
          placeholder="Personel veya dönem ara (Örn: 2026-09)..."
          value={search}
          onChange={(e) => setSearch(e.target.value)}
          className="border border-gray-300 rounded-lg px-3 py-1.5 text-sm bg-white focus:ring-2 focus:ring-emerald-500 outline-none w-full sm:w-80"
        />
      </div>

      {/* Tablo */}
      <div className="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <div className="p-4 border-b border-gray-100">
          <h2 className="text-base font-semibold text-gray-800">Personel Maaş Bordroları</h2>
        </div>
        {loading ? (
          <div className="p-10 text-center text-gray-500">Yükleniyor...</div>
        ) : filteredPayrolls.length === 0 ? (
          <div className="p-12 text-center text-gray-400">Kayıtlı bordro bulunamadı.</div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-left text-sm">
              <thead className="bg-gray-50 text-gray-600 text-xs uppercase font-medium">
                <tr>
                  <th className="px-6 py-3">Personel</th>
                  <th className="px-6 py-3">Dönem</th>
                  <th className="px-6 py-3">Taban Maaş</th>
                  <th className="px-6 py-3">Prim / Bonus</th>
                  <th className="px-6 py-3">Kesintiler</th>
                  <th className="px-6 py-3">Net Ödenecek</th>
                  <th className="px-6 py-3">Durum</th>
                  <th className="px-6 py-3 text-right">İşlemler</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-100">
                {filteredPayrolls.map((item) => (
                  <tr key={item.id} className="hover:bg-gray-50">
                    <td className="px-6 py-4">
                      <div className="font-semibold text-gray-900">
                        {item.first_name ? `${item.first_name} ${item.last_name}` : 'Çalışan'}
                      </div>
                      <div className="text-xs text-gray-400">{item.department_name || item.employee_email}</div>
                    </td>
                    <td className="px-6 py-4">
                      <span className="font-mono text-xs font-semibold px-2 py-1 bg-gray-100 text-gray-700 rounded">
                        {item.period}
                      </span>
                    </td>
                    <td className="px-6 py-4 text-gray-600">
                      ₺{Number(item.base_salary).toLocaleString('tr-TR', { minimumFractionDigits: 2 })}
                    </td>
                    <td className="px-6 py-4 text-emerald-600 font-medium">
                      +₺{Number(item.bonus || 0).toLocaleString('tr-TR', { minimumFractionDigits: 2 })}
                    </td>
                    <td className="px-6 py-4 text-rose-600 font-medium">
                      -₺{Number(item.deductions || 0).toLocaleString('tr-TR', { minimumFractionDigits: 2 })}
                    </td>
                    <td className="px-6 py-4 font-bold text-gray-900">
                      ₺{Number(item.net_salary).toLocaleString('tr-TR', { minimumFractionDigits: 2 })}
                    </td>
                    <td className="px-6 py-4">
                      {item.status === 'paid' ? (
                        <span className="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">
                          <CheckCircle2 className="w-3 h-3" />
                          Ödendi
                        </span>
                      ) : (
                        <span className="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-800">
                          <Clock className="w-3 h-3" />
                          Bekliyor
                        </span>
                      )}
                    </td>
                    <td className="px-6 py-4 text-right space-x-2">
                      {item.status !== 'paid' ? (
                        <button
                          onClick={() => handleStatusChange(item.id, 'paid')}
                          className="px-2.5 py-1 bg-emerald-600 hover:bg-emerald-700 text-white rounded text-xs font-medium transition"
                        >
                          Ödendi Yap
                        </button>
                      ) : (
                        <button
                          onClick={() => handleStatusChange(item.id, 'pending')}
                          className="px-2.5 py-1 border border-gray-300 text-gray-600 hover:bg-gray-100 rounded text-xs transition"
                        >
                          Geri Al
                        </button>
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
              <h3 className="text-lg font-bold text-gray-900">Yeni Bordro Kaydı</h3>
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
                  className="w-full border border-gray-300 rounded-lg p-2.5 text-sm bg-white focus:ring-2 focus:ring-emerald-500 outline-none"
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
                <label className="block text-xs font-medium text-gray-700 mb-1">Dönem (Ay) *</label>
                <input
                  type="month"
                  required
                  value={period}
                  onChange={(e) => setPeriod(e.target.value)}
                  className="w-full border border-gray-300 rounded-lg p-2 text-sm focus:ring-2 focus:ring-emerald-500 outline-none"
                />
              </div>
              <div>
                <label className="block text-xs font-medium text-gray-700 mb-1">Taban Maaş (TL) *</label>
                <input
                  type="number"
                  step="0.01"
                  required
                  value={baseSalary}
                  onChange={(e) => setBaseSalary(e.target.value)}
                  placeholder="35000.00"
                  className="w-full border border-gray-300 rounded-lg p-2 text-sm focus:ring-2 focus:ring-emerald-500 outline-none"
                />
              </div>
              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block text-xs font-medium text-gray-700 mb-1">Prim / Bonus (TL)</label>
                  <input
                    type="number"
                    step="0.01"
                    value={bonus}
                    onChange={(e) => setBonus(e.target.value)}
                    placeholder="0.00"
                    className="w-full border border-gray-300 rounded-lg p-2 text-sm focus:ring-2 focus:ring-emerald-500 outline-none"
                  />
                </div>
                <div>
                  <label className="block text-xs font-medium text-gray-700 mb-1">Yasal / Diğer Kesinti (TL)</label>
                  <input
                    type="number"
                    step="0.01"
                    value={deductions}
                    onChange={(e) => setDeductions(e.target.value)}
                    placeholder="0.00"
                    className="w-full border border-gray-300 rounded-lg p-2 text-sm focus:ring-2 focus:ring-emerald-500 outline-none"
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
                <button type="submit" className="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-sm font-medium">
                  Bordro Oluştur
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
};
