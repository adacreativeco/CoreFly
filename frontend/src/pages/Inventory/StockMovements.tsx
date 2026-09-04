import React from 'react';
import { InventoryMovement } from '@/types/inventory';
import { ArrowDownRight, ArrowUpRight, RefreshCw, History } from 'lucide-react';

interface StockMovementsProps {
  movements: InventoryMovement[];
}

export const StockMovements: React.FC<StockMovementsProps> = ({ movements }) => {
  return (
    <div className="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
      <div className="p-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
        <div className="flex items-center gap-2">
          <History className="w-5 h-5 text-indigo-600" />
          <h3 className="font-semibold text-gray-900 dark:text-white text-sm">
            Son Stok Hareketleri
          </h3>
        </div>
        <span className="text-xs text-gray-400">Son 100 işlem</span>
      </div>

      <div className="overflow-x-auto">
        <table className="w-full text-left text-sm">
          <thead className="bg-gray-50 dark:bg-gray-900/50 text-gray-500 font-medium border-b border-gray-200 dark:border-gray-700">
            <tr>
              <th className="px-6 py-3">İşlem Türü</th>
              <th className="px-6 py-3">Ürün</th>
              <th className="px-6 py-3">Miktar</th>
              <th className="px-6 py-3">Önceki / Yeni Stok</th>
              <th className="px-6 py-3">Açıklama / Neden</th>
              <th className="px-6 py-3 text-right">Tarih</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-gray-200 dark:divide-gray-700">
            {movements.length === 0 ? (
              <tr>
                <td colSpan={6} className="text-center py-12 text-gray-400">
                  Henüz stok hareketi kaydedilmemiş.
                </td>
              </tr>
            ) : (
              movements.map((m) => (
                <tr key={m.id} className="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                  <td className="px-6 py-4">
                    {m.movement_type === 'in' ? (
                      <span className="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-200">
                        <ArrowDownRight className="w-3.5 h-3.5" /> Giriş
                      </span>
                    ) : m.movement_type === 'out' ? (
                      <span className="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-rose-100 text-rose-800 dark:bg-rose-900/50 dark:text-rose-200">
                        <ArrowUpRight className="w-3.5 h-3.5" /> Çıkış
                      </span>
                    ) : (
                      <span className="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-200">
                        <RefreshCw className="w-3.5 h-3.5" /> Düzeltme
                      </span>
                    )}
                  </td>
                  <td className="px-6 py-4">
                    <div className="font-semibold text-gray-900 dark:text-white">
                      {m.product_name}
                    </div>
                    {m.product_sku && (
                      <div className="text-xs text-gray-400 font-mono">
                        {m.product_sku}
                      </div>
                    )}
                  </td>
                  <td className="px-6 py-4 font-bold text-gray-900 dark:text-white">
                    {m.movement_type === 'in' ? '+' : m.movement_type === 'out' ? '-' : ''}
                    {m.quantity} {m.product_unit || 'Adet'}
                  </td>
                  <td className="px-6 py-4 text-xs text-gray-500">
                    <span className="text-gray-400">{m.previous_quantity}</span>
                    <span className="mx-1.5 font-bold text-gray-400">→</span>
                    <span className="font-bold text-indigo-600 dark:text-indigo-400">{m.new_quantity}</span>
                  </td>
                  <td className="px-6 py-4 text-xs text-gray-600 dark:text-gray-300">
                    {m.reason || 'Belirtilmedi'}
                  </td>
                  <td className="px-6 py-4 text-xs text-gray-400 text-right">
                    {new Date(m.created_at).toLocaleString('tr-TR')}
                  </td>
                </tr>
              ))
            )}
          </tbody>
        </table>
      </div>
    </div>
  );
};
