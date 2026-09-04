import React, { useState } from 'react';
import { AccountingTransaction, AccountingAccount } from '@/types/accounting';
import { Plus, ArrowDownRight, ArrowUpRight, Wallet } from 'lucide-react';

interface TransactionListProps {
  transactions: AccountingTransaction[];
  accounts: AccountingAccount[];
  onCreateTransaction: (data: Partial<AccountingTransaction>) => Promise<void>;
}

export const TransactionList: React.FC<TransactionListProps> = ({
  transactions,
  accounts,
  onCreateTransaction,
}) => {
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [form, setForm] = useState({
    account_id: accounts[0]?.id || '',
    type: 'income' as 'income' | 'expense',
    amount: 0,
    category: 'Satış / Tahsilat',
    description: '',
    payment_method: 'bank' as 'cash' | 'bank' | 'credit_card',
    date: new Date().toISOString().split('T')[0],
  });

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!form.account_id || form.amount <= 0) return;

    await onCreateTransaction(form);
    setIsModalOpen(false);
    setForm({
      account_id: accounts[0]?.id || '',
      type: 'income',
      amount: 0,
      category: 'Satış / Tahsilat',
      description: '',
      payment_method: 'bank',
      date: new Date().toISOString().split('T')[0],
    });
  };

  return (
    <div className="space-y-4">
      <div className="flex justify-between items-center">
        <h3 className="font-semibold text-gray-900 dark:text-white text-sm">
          Kasa & Banka Gelir/Gider Hareketleri
        </h3>
        <button
          onClick={() => setIsModalOpen(true)}
          className="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors shadow-sm"
        >
          <Plus className="w-4 h-4" />
          Yeni Gelir / Gider Ekle
        </button>
      </div>

      {/* Table */}
      <div className="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full text-left text-sm">
            <thead className="bg-gray-50 dark:bg-gray-900/50 text-gray-500 font-medium border-b border-gray-200 dark:border-gray-700">
              <tr>
                <th className="px-6 py-3">Tür</th>
                <th className="px-6 py-3">Kasa / Banka Hesabı</th>
                <th className="px-6 py-3">Kategori & Açıklama</th>
                <th className="px-6 py-3">Ödeme Yöntemi</th>
                <th className="px-6 py-3">Tutar</th>
                <th className="px-6 py-3 text-right">Tarih</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-200 dark:divide-gray-700">
              {transactions.length === 0 ? (
                <tr>
                  <td colSpan={6} className="text-center py-12 text-gray-400">
                    <Wallet className="w-8 h-8 mx-auto mb-2 opacity-40" />
                    Henüz finansal hareket kaydı bulunmuyor.
                  </td>
                </tr>
              ) : (
                transactions.map((t) => (
                  <tr key={t.id} className="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                    <td className="px-6 py-4">
                      {t.type === 'income' ? (
                        <span className="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-200">
                          <ArrowDownRight className="w-3.5 h-3.5" /> Gelir (+)
                        </span>
                      ) : (
                        <span className="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-rose-100 text-rose-800 dark:bg-rose-900/50 dark:text-rose-200">
                          <ArrowUpRight className="w-3.5 h-3.5" /> Gider (-)
                        </span>
                      )}
                    </td>
                    <td className="px-6 py-4 text-xs font-medium text-gray-800 dark:text-gray-200">
                      {t.account_name || 'Ana Kasa'}
                    </td>
                    <td className="px-6 py-4">
                      <div className="font-semibold text-gray-900 dark:text-white text-xs">{t.category || 'Genel'}</div>
                      {t.description && <div className="text-xs text-gray-500 mt-0.5">{t.description}</div>}
                    </td>
                    <td className="px-6 py-4 text-xs uppercase font-mono text-gray-500">
                      {t.payment_method === 'bank' ? 'Banka Transferi' : t.payment_method === 'credit_card' ? 'Kredi Kartı' : 'Nakit'}
                    </td>
                    <td className={`px-6 py-4 font-bold ${
                      t.type === 'income' ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400'
                    }`}>
                      {t.type === 'income' ? '+' : '-'}
                      {Number(t.amount).toLocaleString('tr-TR')} {t.currency}
                    </td>
                    <td className="px-6 py-4 text-xs text-gray-400 text-right">
                      {t.date}
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>
      </div>

      {/* Modal */}
      {isModalOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
          <div className="bg-white dark:bg-gray-800 rounded-2xl w-full max-w-md p-6 shadow-xl border border-gray-200 dark:border-gray-700">
            <h3 className="text-lg font-bold text-gray-900 dark:text-white mb-4">
              Kasa / Banka Hareketi Ekle
            </h3>
            <form onSubmit={handleSubmit} className="space-y-3">
              <div>
                <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                  Hareket Türü
                </label>
                <div className="grid grid-cols-2 gap-3">
                  <button
                    type="button"
                    onClick={() => setForm({ ...form, type: 'income', category: 'Tahsilat / Satış' })}
                    className={`py-2 text-xs font-bold rounded-lg border text-center transition-colors ${
                      form.type === 'income'
                        ? 'bg-emerald-600 text-white border-emerald-600'
                        : 'bg-gray-50 border-gray-300 text-gray-700'
                    }`}
                  >
                    + Gelir (Para Girişi)
                  </button>
                  <button
                    type="button"
                    onClick={() => setForm({ ...form, type: 'expense', category: 'Operasyonel Gider' })}
                    className={`py-2 text-xs font-bold rounded-lg border text-center transition-colors ${
                      form.type === 'expense'
                        ? 'bg-rose-600 text-white border-rose-600'
                        : 'bg-gray-50 border-gray-300 text-gray-700'
                    }`}
                  >
                    - Gider (Para Çıkışı)
                  </button>
                </div>
              </div>

              <div>
                <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                  Kasa / Banka Hesabı *
                </label>
                <select
                  required
                  value={form.account_id}
                  onChange={(e) => setForm({ ...form, account_id: e.target.value })}
                  className="w-full px-3 py-2 border rounded-lg text-sm bg-gray-50 dark:bg-gray-700 border-gray-300 dark:border-gray-600"
                >
                  {accounts.map((a) => (
                    <option key={a.id} value={a.id}>
                      {a.name} ({Number(a.balance).toLocaleString('tr-TR')} ₺)
                    </option>
                  ))}
                </select>
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Tutar (₺) *
                  </label>
                  <input
                    type="number"
                    min="0.01"
                    step="0.01"
                    required
                    value={form.amount}
                    onChange={(e) => setForm({ ...form, amount: parseFloat(e.target.value) || 0 })}
                    className="w-full px-3 py-2 border rounded-lg text-sm bg-gray-50 dark:bg-gray-700 border-gray-300 dark:border-gray-600"
                  />
                </div>
                <div>
                  <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Tarih
                  </label>
                  <input
                    type="date"
                    required
                    value={form.date}
                    onChange={(e) => setForm({ ...form, date: e.target.value })}
                    className="w-full px-3 py-2 border rounded-lg text-sm bg-gray-50 dark:bg-gray-700 border-gray-300 dark:border-gray-600"
                  />
                </div>
              </div>

              <div>
                <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                  Kategori
                </label>
                <input
                  type="text"
                  value={form.category}
                  onChange={(e) => setForm({ ...form, category: e.target.value })}
                  placeholder="Kira, Maaş, Fatura Tahsilatı vb."
                  className="w-full px-3 py-2 border rounded-lg text-sm bg-gray-50 dark:bg-gray-700 border-gray-300 dark:border-gray-600"
                />
              </div>

              <div>
                <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                  Açıklama
                </label>
                <input
                  type="text"
                  value={form.description}
                  onChange={(e) => setForm({ ...form, description: e.target.value })}
                  placeholder="İşlem açıklaması"
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
                  Hareketi Kaydet
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
};
