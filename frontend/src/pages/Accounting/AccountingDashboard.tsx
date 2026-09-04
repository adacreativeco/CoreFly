import React, { useEffect, useState } from 'react';
import { useAuthStore } from '@/store/authStore';
import {
  getAccountingStats,
  getInvoices,
  createInvoice,
  updateInvoiceStatus,
  deleteInvoice,
  getTransactions,
  createTransaction,
  getAccounts,
  sendEInvoice,
  checkEInvoiceStatus,
} from '@/services/accountingService';
import { toast } from '@/store/toastStore';
import {
  AccountingInvoice,
  AccountingTransaction,
  AccountingAccount,
  AccountingStats,
  InvoiceStatus,
} from '@/types/accounting';
import { InvoiceList } from './InvoiceList';
import { TransactionList } from './TransactionList';
import { CardSkeleton, TableSkeleton } from '@/components/common/SkeletonLoader';
import { TrendingUp, TrendingDown, DollarSign, FileCheck, Landmark, ReceiptText, ArrowLeftRight } from 'lucide-react';

export const AccountingDashboard: React.FC = () => {
  const user = useAuthStore((state) => state.user);
  const tenantId = user?.tenant_id || '';

  const [activeTab, setActiveTab] = useState<'invoices' | 'transactions'>('invoices');
  const [stats, setStats] = useState<AccountingStats | null>(null);
  const [invoices, setInvoices] = useState<AccountingInvoice[]>([]);
  const [transactions, setTransactions] = useState<AccountingTransaction[]>([]);
  const [accounts, setAccounts] = useState<AccountingAccount[]>([]);
  const [loading, setLoading] = useState(true);

  // Filters
  const [searchQuery, setSearchQuery] = useState('');
  const [filterType, setFilterType] = useState('');
  const [filterStatus, setFilterStatus] = useState('');

  const loadData = async () => {
    if (!tenantId) return;
    try {
      const [statsData, invData, accData] = await Promise.all([
        getAccountingStats(tenantId),
        getInvoices(tenantId, { search: searchQuery, type: filterType, status: filterStatus }),
        getAccounts(tenantId),
      ]);
      setStats(statsData);
      setInvoices(invData);
      setAccounts(accData);
    } catch (err) {
      console.error('Muhasebe verisi yükleme hatası:', err);
    } finally {
      setLoading(false);
    }
  };

  const loadTransactions = async () => {
    if (!tenantId) return;
    try {
      const trans = await getTransactions(tenantId);
      setTransactions(trans);
    } catch (err) {
      console.error('Hareketler yüklenemedi:', err);
    }
  };

  useEffect(() => {
    loadData();
  }, [tenantId, searchQuery, filterType, filterStatus]);

  useEffect(() => {
    if (activeTab === 'transactions') {
      loadTransactions();
    }
  }, [activeTab]);

  const handleUpdateStatus = async (invoiceId: string, status: InvoiceStatus) => {
    await updateInvoiceStatus(tenantId, invoiceId, status);
    loadData();
  };

  const handleDeleteInvoice = async (invoiceId: string) => {
    if (!confirm('Faturayı silmek istediğinizden emin misiniz?')) return;
    await deleteInvoice(tenantId, invoiceId);
    toast.success('Fatura Silindi', 'Seçilen fatura kaydı sistemden kaldırıldı.');
    loadData();
  };

  const handleCreateInvoice = async (data: Partial<AccountingInvoice>) => {
    await createInvoice(tenantId, data);
    toast.success('Fatura Kesildi', `${data.title} başarıyla oluşturuldu.`);
    loadData();
  };

  const handleCreateTransaction = async (data: Partial<AccountingTransaction>) => {
    await createTransaction(tenantId, data);
    toast.success('Hareket Kaydedildi', 'Kasa/Banka işlemi başarıyla işlendi.');
    loadData();
    if (activeTab === 'transactions') {
      loadTransactions();
    }
  };

  const handleSendEInvoice = async (invoiceId: string) => {
    try {
      const res = await sendEInvoice(tenantId, invoiceId);
      toast.success(
        'GİB e-Fatura Başarıyla Gönderildi',
        `ETTN: ${res.data.ettn} (Durum: ${res.data.gib_status_code} - ${res.data.gib_status_description})`
      );
      await loadData();
    } catch (err: any) {
      toast.error('e-Fatura Gönderim Hatası', err.response?.data?.error || 'GİB entegratörüne iletilemedi.');
    }
  };

  const handleCheckStatus = async (invoiceId: string) => {
    try {
      const res = await checkEInvoiceStatus(tenantId, invoiceId);
      toast.info(
        'GİB Durum Güncellendi',
        `Durum Kodu: ${res.data.gib_status_code} - ${res.data.gib_status_description}`
      );
      await loadData();
    } catch (err: any) {
      toast.error('Sorgulama Hatası', err.response?.data?.error || 'GİB durumu sorgulanamadı.');
    }
  };

  return (
    <div className="space-y-6">
      {/* Header */}
      <div>
        <h1 className="text-2xl font-bold text-gray-900 dark:text-white">
          Ön Muhasebe & Finans Yönetimi
        </h1>
        <p className="text-sm text-gray-500">
          Faturalama, resmi GİB e-Fatura/e-Arşiv gönderimi, kasa ve banka hesapları, nakit akışı ve gelir/gider takibi.
        </p>
      </div>

      {/* Metrics Cards */}
      {loading ? (
        <CardSkeleton count={4} />
      ) : (
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
          <div className="bg-white dark:bg-gray-800 p-5 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm flex items-center gap-4">
            <div className="w-12 h-12 bg-emerald-50 text-emerald-600 rounded-lg flex items-center justify-center">
              <TrendingUp className="w-6 h-6" />
            </div>
            <div>
              <div className="text-xs font-medium text-gray-500">Toplam Gelir</div>
              <div className="text-2xl font-bold text-emerald-600 dark:text-emerald-400">
                {Number(stats?.total_income || 0).toLocaleString('tr-TR')} ₺
              </div>
            </div>
          </div>

          <div className="bg-white dark:bg-gray-800 p-5 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm flex items-center gap-4">
            <div className="w-12 h-12 bg-rose-50 text-rose-600 rounded-lg flex items-center justify-center">
              <TrendingDown className="w-6 h-6" />
            </div>
            <div>
              <div className="text-xs font-medium text-gray-500">Toplam Gider</div>
              <div className="text-2xl font-bold text-rose-600 dark:text-rose-400">
                {Number(stats?.total_expense || 0).toLocaleString('tr-TR')} ₺
              </div>
            </div>
          </div>

          <div className="bg-white dark:bg-gray-800 p-5 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm flex items-center gap-4">
            <div className="w-12 h-12 bg-indigo-50 text-indigo-600 rounded-lg flex items-center justify-center">
              <DollarSign className="w-6 h-6" />
            </div>
            <div>
              <div className="text-xs font-medium text-gray-500">Net Bakiye / Kâr</div>
              <div className={`text-2xl font-bold ${
                (stats?.net_profit || 0) >= 0 ? 'text-indigo-600 dark:text-indigo-400' : 'text-rose-600'
              }`}>
                {Number(stats?.net_profit || 0).toLocaleString('tr-TR')} ₺
              </div>
            </div>
          </div>

          <div className="bg-white dark:bg-gray-800 p-5 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm flex items-center gap-4">
            <div className="w-12 h-12 bg-amber-50 text-amber-600 rounded-lg flex items-center justify-center">
              <FileCheck className="w-6 h-6" />
            </div>
            <div>
              <div className="text-xs font-medium text-gray-500">Bekleyen Tahsilat</div>
              <div className="text-2xl font-bold text-amber-600 dark:text-amber-400">
                {Number(stats?.total_receivables || 0).toLocaleString('tr-TR')} ₺
              </div>
            </div>
          </div>
        </div>
      )}

      {/* Tabs */}
      <div className="flex border-b border-gray-200 dark:border-gray-700 gap-6">
        <button
          onClick={() => setActiveTab('invoices')}
          className={`flex items-center gap-2 pb-3 text-sm font-medium border-b-2 transition-colors ${
            activeTab === 'invoices'
              ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400'
              : 'border-transparent text-gray-500 hover:text-gray-700'
          }`}
        >
          <ReceiptText className="w-4 h-4" />
          Faturalar & GİB e-Dönüşüm
        </button>

        <button
          onClick={() => setActiveTab('transactions')}
          className={`flex items-center gap-2 pb-3 text-sm font-medium border-b-2 transition-colors ${
            activeTab === 'transactions'
              ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400'
              : 'border-transparent text-gray-500 hover:text-gray-700'
          }`}
        >
          <ArrowLeftRight className="w-4 h-4" />
          Kasa & Banka Hareketleri
        </button>
      </div>

      {/* Content */}
      {loading ? (
        <TableSkeleton rows={6} cols={6} />
      ) : activeTab === 'invoices' ? (
        <InvoiceList
          tenantId={tenantId}
          invoices={invoices}
          onCreateInvoice={handleCreateInvoice}
          onUpdateStatus={handleUpdateStatus}
          onDeleteInvoice={handleDeleteInvoice}
          onSendEInvoice={handleSendEInvoice}
          onCheckStatus={handleCheckStatus}
          onSearch={setSearchQuery}
          onFilterType={setFilterType}
          onFilterStatus={setFilterStatus}
        />
      ) : (
        <TransactionList
          transactions={transactions}
          accounts={accounts}
          onCreateTransaction={handleCreateTransaction}
        />
      )}
    </div>
  );
};
