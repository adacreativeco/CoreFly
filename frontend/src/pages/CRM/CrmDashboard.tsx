import React, { useEffect, useState } from 'react';
import { useAuthStore } from '@/store/authStore';
import {
  getCrmStats,
  getCustomers,
  createCustomer,
  deleteCustomer,
  getDeals,
  createDeal,
  updateDealStage,
  deleteDeal,
} from '@/services/crmService';
import { CrmCustomer, CrmDeal, CrmStats, DealStage } from '@/types/crm';
import { DealKanban } from './DealKanban';
import { LeadList } from './LeadList';
import { Users, Target, TrendingUp, Award, Plus, Layers, List } from 'lucide-react';

export const CrmDashboard: React.FC = () => {
  const user = useAuthStore((state) => state.user);
  const tenantId = user?.tenant_id || '';

  const [activeTab, setActiveTab] = useState<'kanban' | 'customers'>('kanban');
  const [stats, setStats] = useState<CrmStats | null>(null);
  const [customers, setCustomers] = useState<CrmCustomer[]>([]);
  const [deals, setDeals] = useState<CrmDeal[]>([]);
  const [loading, setLoading] = useState(true);

  // New Deal Modal State
  const [isDealModalOpen, setIsDealModalOpen] = useState(false);
  const [dealForm, setDealForm] = useState<Partial<CrmDeal>>({
    title: '',
    customer_id: '',
    value: 0,
    currency: 'TRY',
    stage: 'lead',
  });

  const loadData = async (searchQuery?: string) => {
    if (!tenantId) return;
    try {
      const [statsData, customersData, dealsData] = await Promise.all([
        getCrmStats(tenantId),
        getCustomers(tenantId, searchQuery),
        getDeals(tenantId),
      ]);
      setStats(statsData);
      setCustomers(customersData);
      setDeals(dealsData);
    } catch (err) {
      console.error('CRM veri yükleme hatası:', err);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    loadData();
  }, [tenantId]);

  const handleStageChange = async (dealId: string, stage: DealStage) => {
    try {
      await updateDealStage(tenantId, dealId, stage);
      // Optimistic update
      setDeals((prev) => prev.map((d) => (d.id === dealId ? { ...d, stage } : d)));
      // Refresh stats
      getCrmStats(tenantId).then(setStats);
    } catch (err) {
      console.error('Fırsat aşama güncelleme hatası:', err);
    }
  };

  const handleDeleteDeal = async (dealId: string) => {
    if (!confirm('Bu fırsatı silmek istediğinizden emin misiniz?')) return;
    try {
      await deleteDeal(tenantId, dealId);
      setDeals((prev) => prev.filter((d) => d.id !== dealId));
      getCrmStats(tenantId).then(setStats);
    } catch (err) {
      console.error('Fırsat silme hatası:', err);
    }
  };

  const handleAddCustomer = async (data: Partial<CrmCustomer>) => {
    await createCustomer(tenantId, data);
    loadData();
  };

  const handleDeleteCustomer = async (id: string) => {
    if (!confirm('Müşteriyi silmek istediğinizden emin misiniz?')) return;
    await deleteCustomer(tenantId, id);
    loadData();
  };

  const handleCreateDeal = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!dealForm.title || !dealForm.customer_id) return;
    try {
      await createDeal(tenantId, dealForm);
      setIsDealModalOpen(false);
      setDealForm({ title: '', customer_id: '', value: 0, currency: 'TRY', stage: 'lead' });
      loadData();
    } catch (err) {
      console.error('Fırsat ekleme hatası:', err);
    }
  };

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
          <h1 className="text-2xl font-bold text-gray-900 dark:text-white">
            CRM & Satış Yönetimi
          </h1>
          <p className="text-sm text-gray-500">
            Müşteri ilişkileri, potansiyel fırsatlar ve satış hunisi yönetimi.
          </p>
        </div>

        <div className="flex items-center gap-2">
          <button
            onClick={() => setIsDealModalOpen(true)}
            className="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors shadow-sm"
          >
            <Plus className="w-4 h-4" />
            Yeni Fırsat Ekle
          </button>
        </div>
      </div>

      {/* Metrics Cards */}
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div className="bg-white dark:bg-gray-800 p-5 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm flex items-center gap-4">
          <div className="w-12 h-12 bg-blue-50 text-blue-600 rounded-lg flex items-center justify-center">
            <Users className="w-6 h-6" />
          </div>
          <div>
            <div className="text-xs font-medium text-gray-500">Toplam Müşteri / Aday</div>
            <div className="text-2xl font-bold text-gray-900 dark:text-white">
              {stats?.total_customers ?? 0}
            </div>
          </div>
        </div>

        <div className="bg-white dark:bg-gray-800 p-5 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm flex items-center gap-4">
          <div className="w-12 h-12 bg-amber-50 text-amber-600 rounded-lg flex items-center justify-center">
            <Target className="w-6 h-6" />
          </div>
          <div>
            <div className="text-xs font-medium text-gray-500">Aktif Fırsatlar</div>
            <div className="text-2xl font-bold text-gray-900 dark:text-white">
              {stats?.total_deals ?? 0}
            </div>
          </div>
        </div>

        <div className="bg-white dark:bg-gray-800 p-5 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm flex items-center gap-4">
          <div className="w-12 h-12 bg-indigo-50 text-indigo-600 rounded-lg flex items-center justify-center">
            <TrendingUp className="w-6 h-6" />
          </div>
          <div>
            <div className="text-xs font-medium text-gray-500">Satış Hunisi Değeri</div>
            <div className="text-2xl font-bold text-indigo-600 dark:text-indigo-400">
              {Number(stats?.pipeline_value || 0).toLocaleString('tr-TR')} ₺
            </div>
          </div>
        </div>

        <div className="bg-white dark:bg-gray-800 p-5 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm flex items-center gap-4">
          <div className="w-12 h-12 bg-emerald-50 text-emerald-600 rounded-lg flex items-center justify-center">
            <Award className="w-6 h-6" />
          </div>
          <div>
            <div className="text-xs font-medium text-gray-500">Kazanılan Satışlar</div>
            <div className="text-2xl font-bold text-emerald-600 dark:text-emerald-400">
              {Number(stats?.won_value || 0).toLocaleString('tr-TR')} ₺
            </div>
          </div>
        </div>
      </div>

      {/* Tabs */}
      <div className="border-b border-gray-200 dark:border-gray-700 flex gap-6">
        <button
          onClick={() => setActiveTab('kanban')}
          className={`flex items-center gap-2 pb-3 text-sm font-medium border-b-2 transition-colors ${
            activeTab === 'kanban'
              ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400'
              : 'border-transparent text-gray-500 hover:text-gray-700'
          }`}
        >
          <Layers className="w-4 h-4" />
          Satış Hunisi (Kanban Panosu)
        </button>

        <button
          onClick={() => setActiveTab('customers')}
          className={`flex items-center gap-2 pb-3 text-sm font-medium border-b-2 transition-colors ${
            activeTab === 'customers'
              ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400'
              : 'border-transparent text-gray-500 hover:text-gray-700'
          }`}
        >
          <List className="w-4 h-4" />
          Müşteri ve Aday Rehberi
        </button>
      </div>

      {/* Tab Content */}
      {loading ? (
        <div className="flex items-center justify-center py-20 text-gray-400">
          Yükleniyor...
        </div>
      ) : activeTab === 'kanban' ? (
        <DealKanban
          deals={deals}
          onStageChange={handleStageChange}
          onDeleteDeal={handleDeleteDeal}
        />
      ) : (
        <LeadList
          customers={customers}
          onAddCustomer={handleAddCustomer}
          onDeleteCustomer={handleDeleteCustomer}
          onSearch={(q) => loadData(q)}
        />
      )}

      {/* Create Deal Modal */}
      {isDealModalOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
          <div className="bg-white dark:bg-gray-800 rounded-2xl w-full max-w-md p-6 shadow-xl border border-gray-200 dark:border-gray-700">
            <h3 className="text-lg font-bold text-gray-900 dark:text-white mb-4">
              Yeni Fırsat / Anlaşma Oluştur
            </h3>
            <form onSubmit={handleCreateDeal} className="space-y-4">
              <div>
                <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                  Fırsat / Proje Başlığı *
                </label>
                <input
                  type="text"
                  required
                  value={dealForm.title}
                  onChange={(e) => setDealForm({ ...dealForm, title: e.target.value })}
                  placeholder="Örn: Kurumsal Web Sitesi & ERP Entegrasyonu"
                  className="w-full px-3 py-2 border rounded-lg text-sm bg-gray-50 dark:bg-gray-700 border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-indigo-500"
                />
              </div>

              <div>
                <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                  İlgili Müşteri *
                </label>
                <select
                  required
                  value={dealForm.customer_id}
                  onChange={(e) => setDealForm({ ...dealForm, customer_id: e.target.value })}
                  className="w-full px-3 py-2 border rounded-lg text-sm bg-gray-50 dark:bg-gray-700 border-gray-300 dark:border-gray-600"
                >
                  <option value="">Müşteri Seçiniz...</option>
                  {customers.map((c) => (
                    <option key={c.id} value={c.id}>
                      {c.name} {c.company ? `(${c.company})` : ''}
                    </option>
                  ))}
                </select>
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Tahmini Tutar
                  </label>
                  <input
                    type="number"
                    min="0"
                    value={dealForm.value}
                    onChange={(e) => setDealForm({ ...dealForm, value: parseFloat(e.target.value) || 0 })}
                    className="w-full px-3 py-2 border rounded-lg text-sm bg-gray-50 dark:bg-gray-700 border-gray-300 dark:border-gray-600"
                  />
                </div>
                <div>
                  <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Para Birimi
                  </label>
                  <select
                    value={dealForm.currency}
                    onChange={(e) => setDealForm({ ...dealForm, currency: e.target.value })}
                    className="w-full px-3 py-2 border rounded-lg text-sm bg-gray-50 dark:bg-gray-700 border-gray-300 dark:border-gray-600"
                  >
                    <option value="TRY">₺ (TRY)</option>
                    <option value="USD">$ (USD)</option>
                    <option value="EUR">€ (EUR)</option>
                  </select>
                </div>
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Aşama
                  </label>
                  <select
                    value={dealForm.stage}
                    onChange={(e) => setDealForm({ ...dealForm, stage: e.target.value as any })}
                    className="w-full px-3 py-2 border rounded-lg text-sm bg-gray-50 dark:bg-gray-700 border-gray-300 dark:border-gray-600"
                  >
                    <option value="lead">Yeni Fırsat</option>
                    <option value="proposal">Teklif Aşaması</option>
                    <option value="negotiation">Pazarlık</option>
                    <option value="won">Kazanıldı</option>
                    <option value="lost">Kaybedildi</option>
                  </select>
                </div>
                <div>
                  <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Hedef Kapanış Tarihi
                  </label>
                  <input
                    type="date"
                    value={dealForm.expected_close_date || ''}
                    onChange={(e) => setDealForm({ ...dealForm, expected_close_date: e.target.value })}
                    className="w-full px-3 py-2 border rounded-lg text-sm bg-gray-50 dark:bg-gray-700 border-gray-300 dark:border-gray-600"
                  />
                </div>
              </div>

              <div className="flex justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                <button
                  type="button"
                  onClick={() => setIsDealModalOpen(false)}
                  className="px-4 py-2 text-sm text-gray-600 hover:text-gray-800 dark:text-gray-400"
                >
                  İptal
                </button>
                <button
                  type="submit"
                  className="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium transition-colors"
                >
                  Fırsatı Oluştur
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
};
