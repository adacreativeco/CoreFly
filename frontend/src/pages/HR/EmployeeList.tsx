import React, { useEffect, useState, useCallback } from 'react';
import { useAuthStore } from '@/store/authStore';
import { getEmployees, createEmployee, getDepartments } from '@/services/hrService';
import { Employee, Department } from '@/types/hr';
import { Search, UserPlus, Mail, Building2, Briefcase, DollarSign, Calendar, X } from 'lucide-react';

export const EmployeeList: React.FC = () => {
  const { user } = useAuthStore();
  const [employees, setEmployees] = useState<Employee[]>([]);
  const [departments, setDepartments] = useState<Department[]>([]);
  const [loading, setLoading] = useState(true);
  const [searchTerm, setSearchTerm] = useState('');
  
  // Modal State
  const [showModal, setShowModal] = useState(false);
  const [formData, setFormData] = useState({
    full_name: '',
    email: '',
    employee_number: '',
    department_id: '',
    salary: 45000,
    hire_date: new Date().toISOString().split('T')[0],
  });
  const [isSubmitting, setIsSubmitting] = useState(false);

  const loadData = useCallback(async () => {
    try {
      if (!user?.tenant_id) return;
      const [empData, deptData] = await Promise.all([
        getEmployees(user.tenant_id),
        getDepartments(user.tenant_id)
      ]);
      setEmployees(empData || []);
      setDepartments(deptData || []);
    } catch (error) {
      console.error('Failed to load employee data:', error);
    } finally {
      setLoading(false);
    }
  }, [user?.tenant_id]);

  useEffect(() => {
    if (user?.tenant_id) {
      loadData();
    }
  }, [user?.tenant_id, loadData]);

  const handleCreate = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!user?.tenant_id || !formData.full_name || !formData.email) return;

    try {
      setIsSubmitting(true);
      await createEmployee(user.tenant_id, {
        full_name: formData.full_name,
        email: formData.email,
        employee_number: formData.employee_number || undefined,
        department_id: formData.department_id || undefined,
        salary: Number(formData.salary),
        hire_date: formData.hire_date,
      });
      setShowModal(false);
      setFormData({
        full_name: '',
        email: '',
        employee_number: '',
        department_id: '',
        salary: 45000,
        hire_date: new Date().toISOString().split('T')[0],
      });
      loadData();
    } catch (error) {
      console.error('Çalışan eklenirken hata:', error);
      alert('Çalışan kaydedilemedi. Lütfen bilgileri kontrol edin.');
    } finally {
      setIsSubmitting(false);
    }
  };

  const filteredEmployees = employees.filter((emp) => {
    const term = searchTerm.toLowerCase();
    const nameMatch = (emp.full_name || '').toLowerCase().includes(term);
    const numMatch = (emp.employee_number || '').toLowerCase().includes(term);
    const deptMatch = (emp.department_name || '').toLowerCase().includes(term);
    return nameMatch || numMatch || deptMatch;
  });

  if (loading) return <div className="p-8 text-center text-gray-500">Çalışan kadrosu yükleniyor...</div>;

  return (
    <div className="space-y-6">
      <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">Çalışanlar & Kadro</h1>
          <p className="text-sm text-gray-500 mt-1">
            Toplam {employees.length} kayıtlı personel bulunmaktadır.
          </p>
        </div>
        <button 
          onClick={() => setShowModal(true)}
          className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 transition"
        >
          <UserPlus className="h-4 w-4 mr-2" />
          Yeni Çalışan Ekle
        </button>
      </div>

      {/* Arama Alanı */}
      <div className="relative">
        <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
          <Search className="h-4 w-4 text-gray-400" />
        </div>
        <input
          type="text"
          className="block w-full pl-9 pr-3 py-2 border border-gray-300 rounded-lg bg-white placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
          placeholder="İsim, departman veya personel numarasına göre filtrele..."
          value={searchTerm}
          onChange={(e) => setSearchTerm(e.target.value)}
        />
      </div>

      {/* Çalışan Kartları */}
      <div className="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
        {filteredEmployees.length === 0 ? (
          <div className="col-span-full py-12 text-center text-gray-400 bg-white rounded-xl border border-gray-200">
            Aramanızla eşleşen çalışan bulunamadı.
          </div>
        ) : (
          filteredEmployees.map((employee) => {
            const initials = (employee.full_name || 'CP')
              .split(' ')
              .map((n) => n[0])
              .join('')
              .substring(0, 2)
              .toUpperCase();

            return (
              <div key={employee.id} className="bg-white rounded-xl border border-gray-200 p-5 hover:shadow-md transition">
                <div className="flex items-start justify-between">
                  <div className="flex items-center space-x-3">
                    <div className="w-12 h-12 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center font-bold text-base">
                      {initials}
                    </div>
                    <div>
                      <h3 className="font-semibold text-gray-900 text-base">
                        {employee.full_name || 'İsimsiz Personel'}
                      </h3>
                      <p className="text-xs text-indigo-600 font-medium flex items-center mt-0.5">
                        <Briefcase className="w-3 h-3 mr-1" />
                        {employee.position_title || 'Uzman'}
                      </p>
                    </div>
                  </div>
                  <span className="text-[10px] bg-gray-100 text-gray-600 font-mono px-2 py-0.5 rounded">
                    {employee.employee_number || 'EMP'}
                  </span>
                </div>

                <div className="mt-4 pt-4 border-t border-gray-100 space-y-2 text-xs text-gray-600">
                  <div className="flex items-center justify-between">
                    <span className="flex items-center text-gray-400">
                      <Building2 className="w-3.5 h-3.5 mr-1.5" /> Departman
                    </span>
                    <span className="font-medium text-gray-800">
                      {employee.department_name || 'Genel'}
                    </span>
                  </div>

                  {employee.email && (
                    <div className="flex items-center justify-between">
                      <span className="flex items-center text-gray-400">
                        <Mail className="w-3.5 h-3.5 mr-1.5" /> E-Posta
                      </span>
                      <span className="font-medium text-gray-700 truncate max-w-[180px]">
                        {employee.email}
                      </span>
                    </div>
                  )}

                  <div className="flex items-center justify-between">
                    <span className="flex items-center text-gray-400">
                      <DollarSign className="w-3.5 h-3.5 mr-1.5" /> Net Maaş
                    </span>
                    <span className="font-bold text-emerald-700">
                      {employee.salary ? Number(employee.salary).toLocaleString('tr-TR', { style: 'currency', currency: 'TRY' }) : 'Belirtilmedi'}
                    </span>
                  </div>

                  <div className="flex items-center justify-between">
                    <span className="flex items-center text-gray-400">
                      <Calendar className="w-3.5 h-3.5 mr-1.5" /> Başlama
                    </span>
                    <span className="text-gray-500">
                      {employee.hire_date || '2024-01-15'}
                    </span>
                  </div>
                </div>
              </div>
            );
          })
        )}
      </div>

      {/* Yeni Çalışan Ekleme Modalı */}
      {showModal && (
        <div className="fixed inset-0 z-50 overflow-y-auto bg-black/50 backdrop-blur-sm flex items-center justify-center p-4">
          <div className="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl relative">
            <div className="flex items-center justify-between pb-4 border-b border-gray-100 mb-5">
              <h3 className="text-lg font-bold text-gray-900">Yeni Çalışan Ekle</h3>
              <button 
                onClick={() => setShowModal(false)}
                className="text-gray-400 hover:text-gray-600 p-1"
              >
                <X className="w-5 h-5" />
              </button>
            </div>

            <form onSubmit={handleCreate} className="space-y-4">
              <div>
                <label className="block text-xs font-semibold text-gray-700 mb-1">Ad Soyad *</label>
                <input
                  type="text"
                  required
                  placeholder="Örn: Ayşe Kaya"
                  value={formData.full_name}
                  onChange={(e) => setFormData({ ...formData, full_name: e.target.value })}
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-1 focus:ring-indigo-500"
                />
              </div>

              <div>
                <label className="block text-xs font-semibold text-gray-700 mb-1">Kurumsal E-Posta *</label>
                <input
                  type="email"
                  required
                  placeholder="ayse.kaya@corefly.com"
                  value={formData.email}
                  onChange={(e) => setFormData({ ...formData, email: e.target.value })}
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-1 focus:ring-indigo-500"
                />
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block text-xs font-semibold text-gray-700 mb-1">Personel No</label>
                  <input
                    type="text"
                    placeholder="EMP-012"
                    value={formData.employee_number}
                    onChange={(e) => setFormData({ ...formData, employee_number: e.target.value })}
                    className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-1 focus:ring-indigo-500"
                  />
                </div>
                <div>
                  <label className="block text-xs font-semibold text-gray-700 mb-1">Maaş (TL)</label>
                  <input
                    type="number"
                    value={formData.salary}
                    onChange={(e) => setFormData({ ...formData, salary: Number(e.target.value) })}
                    className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-1 focus:ring-indigo-500"
                  />
                </div>
              </div>

              <div>
                <label className="block text-xs font-semibold text-gray-700 mb-1">Departman</label>
                <select
                  value={formData.department_id}
                  onChange={(e) => setFormData({ ...formData, department_id: e.target.value })}
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-1 focus:ring-indigo-500"
                >
                  <option value="">Departman Seçin...</option>
                  {departments.map((d) => (
                    <option key={d.id} value={d.id}>
                      {d.name}
                    </option>
                  ))}
                </select>
              </div>

              <div>
                <label className="block text-xs font-semibold text-gray-700 mb-1">İşe Başlama Tarihi</label>
                <input
                  type="date"
                  value={formData.hire_date}
                  onChange={(e) => setFormData({ ...formData, hire_date: e.target.value })}
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-1 focus:ring-indigo-500"
                />
              </div>

              <div className="pt-4 flex justify-end space-x-3 border-t border-gray-100">
                <button
                  type="button"
                  onClick={() => setShowModal(false)}
                  className="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg text-sm hover:bg-gray-50"
                >
                  Vazgeç
                </button>
                <button
                  type="submit"
                  disabled={isSubmitting}
                  className="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 disabled:opacity-50"
                >
                  {isSubmitting ? 'Kaydediliyor...' : 'Personeli Kaydet'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
};
