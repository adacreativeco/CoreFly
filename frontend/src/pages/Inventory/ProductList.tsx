import React, { useState } from 'react';
import { InventoryProduct, InventoryCategory, MovementType } from '@/types/inventory';
import { Plus, Search, AlertTriangle, ArrowDownRight, ArrowUpRight, Trash2, Package } from 'lucide-react';

interface ProductListProps {
  products: InventoryProduct[];
  categories: InventoryCategory[];
  onAddProduct: (data: Partial<InventoryProduct>) => Promise<void>;
  onAdjustStock: (productId: string, data: { type: MovementType; quantity: number; reason: string }) => Promise<void>;
  onDeleteProduct: (productId: string) => Promise<void>;
  onSearch: (query: string) => void;
  onFilterCategory: (categoryId: string) => void;
  onToggleLowStock: (lowStock: boolean) => void;
  isLowStockFilterActive: boolean;
}

export const ProductList: React.FC<ProductListProps> = ({
  products,
  categories,
  onAddProduct,
  onAdjustStock,
  onDeleteProduct,
  onSearch,
  onFilterCategory,
  onToggleLowStock,
  isLowStockFilterActive,
}) => {
  const [isAddModalOpen, setIsAddModalOpen] = useState(false);
  const [isAdjustModalOpen, setIsAdjustModalOpen] = useState(false);
  const [selectedProduct, setSelectedProduct] = useState<InventoryProduct | null>(null);

  // Add Product Form
  const [productForm, setProductForm] = useState<Partial<InventoryProduct>>({
    name: '',
    category_id: '',
    sku: '',
    quantity: 0,
    min_quantity: 5,
    unit: 'Adet',
    unit_cost: 0,
    unit_price: 0,
    location: '',
  });

  // Adjust Stock Form
  const [adjustForm, setAdjustForm] = useState<{ type: MovementType; quantity: number; reason: string }>({
    type: 'in',
    quantity: 1,
    reason: '',
  });

  const handleAddSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!productForm.name) return;
    await onAddProduct(productForm);
    setIsAddModalOpen(false);
    setProductForm({ name: '', category_id: '', sku: '', quantity: 0, min_quantity: 5, unit: 'Adet', unit_cost: 0, unit_price: 0, location: '' });
  };

  const handleAdjustSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!selectedProduct) return;
    await onAdjustStock(selectedProduct.id, adjustForm);
    setIsAdjustModalOpen(false);
    setSelectedProduct(null);
    setAdjustForm({ type: 'in', quantity: 1, reason: '' });
  };

  const openAdjustModal = (product: InventoryProduct, defaultType: MovementType) => {
    setSelectedProduct(product);
    setAdjustForm({ type: defaultType, quantity: 1, reason: defaultType === 'in' ? 'Stok alımı' : 'Satış / Sevkiyat' });
    setIsAdjustModalOpen(true);
  };

  return (
    <div className="space-y-4">
      {/* Search & Actions Bar */}
      <div className="flex flex-col lg:flex-row justify-between gap-4 items-stretch lg:items-center">
        <div className="flex flex-1 flex-col sm:flex-row gap-3">
          <div className="relative flex-1">
            <Search className="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" />
            <input
              type="text"
              placeholder="Ürün adı, SKU veya barkod ara..."
              onChange={(e) => onSearch(e.target.value)}
              className="w-full pl-9 pr-4 py-2 border border-gray-200 dark:border-gray-700 rounded-lg text-sm bg-white dark:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-indigo-500"
            />
          </div>

          <select
            onChange={(e) => onFilterCategory(e.target.value)}
            className="px-3 py-2 border border-gray-200 dark:border-gray-700 rounded-lg text-sm bg-white dark:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-indigo-500"
          >
            <option value="">Tüm Kategoriler</option>
            {categories.map((c) => (
              <option key={c.id} value={c.id}>
                {c.name}
              </option>
            ))}
          </select>

          <button
            onClick={() => onToggleLowStock(!isLowStockFilterActive)}
            className={`flex items-center justify-center gap-1.5 px-3 py-2 border rounded-lg text-sm font-medium transition-colors ${
              isLowStockFilterActive
                ? 'bg-rose-50 border-rose-300 text-rose-700 dark:bg-rose-950/40 dark:border-rose-800'
                : 'bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-50'
            }`}
          >
            <AlertTriangle className="w-4 h-4 text-rose-500" />
            Kritik Stoktakiler
          </button>
        </div>

        <button
          onClick={() => setIsAddModalOpen(true)}
          className="flex items-center justify-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors shadow-sm"
        >
          <Plus className="w-4 h-4" />
          Yeni Ürün Ekle
        </button>
      </div>

      {/* Product Table */}
      <div className="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full text-left text-sm">
            <thead className="bg-gray-50 dark:bg-gray-900/50 text-gray-500 font-medium border-b border-gray-200 dark:border-gray-700">
              <tr>
                <th className="px-6 py-3">Ürün & SKU</th>
                <th className="px-6 py-3">Kategori</th>
                <th className="px-6 py-3">Mevcut Stok</th>
                <th className="px-6 py-3">Birim Fiyatlar</th>
                <th className="px-6 py-3">Toplam Değer</th>
                <th className="px-6 py-3 text-right">Stok Hareketi & İşlem</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-200 dark:divide-gray-700">
              {products.length === 0 ? (
                <tr>
                  <td colSpan={6} className="text-center py-12 text-gray-400">
                    <Package className="w-8 h-8 mx-auto mb-2 opacity-40" />
                    Kayıtlı ürün bulunamadı.
                  </td>
                </tr>
              ) : (
                products.map((p) => {
                  const isLow = p.quantity <= p.min_quantity;
                  const totalVal = p.quantity * Number(p.unit_cost || 0);

                  return (
                    <tr key={p.id} className="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                      <td className="px-6 py-4">
                        <div className="font-semibold text-gray-900 dark:text-white">{p.name}</div>
                        <div className="text-xs text-gray-400 font-mono mt-0.5">
                          SKU: {p.sku || 'Belirtilmedi'} {p.location ? `| Raf: ${p.location}` : ''}
                        </div>
                      </td>
                      <td className="px-6 py-4 text-xs text-gray-600 dark:text-gray-300">
                        {p.category_name || 'Kategorisiz'}
                      </td>
                      <td className="px-6 py-4">
                        <div className="flex items-center gap-2">
                          <span
                            className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold ${
                              isLow
                                ? 'bg-rose-100 text-rose-800 dark:bg-rose-900/50 dark:text-rose-200'
                                : 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-200'
                            }`}
                          >
                            {p.quantity} {p.unit}
                          </span>
                          {isLow && (
                            <span className="text-[11px] text-rose-500 font-medium flex items-center">
                              <AlertTriangle className="w-3 h-3 mr-0.5" /> Kritik
                            </span>
                          )}
                        </div>
                      </td>
                      <td className="px-6 py-4 space-y-0.5 text-xs">
                        <div className="text-gray-500">Alış: {Number(p.unit_cost).toLocaleString('tr-TR')} ₺</div>
                        <div className="font-medium text-gray-900 dark:text-white">Satış: {Number(p.unit_price).toLocaleString('tr-TR')} ₺</div>
                      </td>
                      <td className="px-6 py-4 font-bold text-indigo-600 dark:text-indigo-400 text-xs">
                        {totalVal.toLocaleString('tr-TR')} ₺
                      </td>
                      <td className="px-6 py-4 text-right">
                        <div className="flex items-center justify-end gap-1.5">
                          <button
                            onClick={() => openAdjustModal(p, 'in')}
                            className="flex items-center gap-1 text-xs bg-emerald-50 hover:bg-emerald-100 text-emerald-700 px-2 py-1 rounded transition-colors"
                            title="Stok Girişi Yap"
                          >
                            <ArrowDownRight className="w-3 h-3" />
                            Giriş
                          </button>
                          <button
                            onClick={() => openAdjustModal(p, 'out')}
                            className="flex items-center gap-1 text-xs bg-amber-50 hover:bg-amber-100 text-amber-700 px-2 py-1 rounded transition-colors"
                            title="Stok Çıkışı Yap"
                          >
                            <ArrowUpRight className="w-3 h-3" />
                            Çıkış
                          </button>
                          <button
                            onClick={() => onDeleteProduct(p.id)}
                            className="text-gray-400 hover:text-rose-600 p-1 rounded transition-colors ml-1"
                            title="Ürünü Sil"
                          >
                            <Trash2 className="w-4 h-4" />
                          </button>
                        </div>
                      </td>
                    </tr>
                  );
                })
              )}
            </tbody>
          </table>
        </div>
      </div>

      {/* Add Product Modal */}
      {isAddModalOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
          <div className="bg-white dark:bg-gray-800 rounded-2xl w-full max-w-lg p-6 shadow-xl border border-gray-200 dark:border-gray-700">
            <h3 className="text-lg font-bold text-gray-900 dark:text-white mb-4">
              Yeni Ürün / Envanter Kaydı
            </h3>
            <form onSubmit={handleAddSubmit} className="space-y-3">
              <div>
                <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                  Ürün Adı *
                </label>
                <input
                  type="text"
                  required
                  value={productForm.name}
                  onChange={(e) => setProductForm({ ...productForm, name: e.target.value })}
                  placeholder="Örn: Logitech MX Master 3S Mouse"
                  className="w-full px-3 py-2 border rounded-lg text-sm bg-gray-50 dark:bg-gray-700 border-gray-300 dark:border-gray-600"
                />
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Kategori
                  </label>
                  <select
                    value={productForm.category_id}
                    onChange={(e) => setProductForm({ ...productForm, category_id: e.target.value })}
                    className="w-full px-3 py-2 border rounded-lg text-sm bg-gray-50 dark:bg-gray-700 border-gray-300 dark:border-gray-600"
                  >
                    <option value="">Seçiniz...</option>
                    {categories.map((c) => (
                      <option key={c.id} value={c.id}>{c.name}</option>
                    ))}
                  </select>
                </div>
                <div>
                  <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                    SKU / Stok Kodu
                  </label>
                  <input
                    type="text"
                    value={productForm.sku}
                    onChange={(e) => setProductForm({ ...productForm, sku: e.target.value })}
                    placeholder="Otomatik veya özel"
                    className="w-full px-3 py-2 border rounded-lg text-sm bg-gray-50 dark:bg-gray-700 border-gray-300 dark:border-gray-600"
                  />
                </div>
              </div>

              <div className="grid grid-cols-3 gap-3">
                <div>
                  <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Başlangıç Stoğu
                  </label>
                  <input
                    type="number"
                    min="0"
                    value={productForm.quantity}
                    onChange={(e) => setProductForm({ ...productForm, quantity: parseInt(e.target.value) || 0 })}
                    className="w-full px-3 py-2 border rounded-lg text-sm bg-gray-50 dark:bg-gray-700 border-gray-300 dark:border-gray-600"
                  />
                </div>
                <div>
                  <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Kritik Limit
                  </label>
                  <input
                    type="number"
                    min="1"
                    value={productForm.min_quantity}
                    onChange={(e) => setProductForm({ ...productForm, min_quantity: parseInt(e.target.value) || 5 })}
                    className="w-full px-3 py-2 border rounded-lg text-sm bg-gray-50 dark:bg-gray-700 border-gray-300 dark:border-gray-600"
                  />
                </div>
                <div>
                  <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Birim
                  </label>
                  <input
                    type="text"
                    value={productForm.unit}
                    onChange={(e) => setProductForm({ ...productForm, unit: e.target.value })}
                    placeholder="Adet, Koli, Kg"
                    className="w-full px-3 py-2 border rounded-lg text-sm bg-gray-50 dark:bg-gray-700 border-gray-300 dark:border-gray-600"
                  />
                </div>
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Birim Alış Maliyeti (₺)
                  </label>
                  <input
                    type="number"
                    min="0"
                    step="0.01"
                    value={productForm.unit_cost}
                    onChange={(e) => setProductForm({ ...productForm, unit_cost: parseFloat(e.target.value) || 0 })}
                    className="w-full px-3 py-2 border rounded-lg text-sm bg-gray-50 dark:bg-gray-700 border-gray-300 dark:border-gray-600"
                  />
                </div>
                <div>
                  <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Birim Satış Fiyatı (₺)
                  </label>
                  <input
                    type="number"
                    min="0"
                    step="0.01"
                    value={productForm.unit_price}
                    onChange={(e) => setProductForm({ ...productForm, unit_price: parseFloat(e.target.value) || 0 })}
                    className="w-full px-3 py-2 border rounded-lg text-sm bg-gray-50 dark:bg-gray-700 border-gray-300 dark:border-gray-600"
                  />
                </div>
              </div>

              <div>
                <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                  Depo / Raf Konumu
                </label>
                <input
                  type="text"
                  value={productForm.location || ''}
                  onChange={(e) => setProductForm({ ...productForm, location: e.target.value })}
                  placeholder="Örn: A Blok - Raf 3"
                  className="w-full px-3 py-2 border rounded-lg text-sm bg-gray-50 dark:bg-gray-700 border-gray-300 dark:border-gray-600"
                />
              </div>

              <div className="flex justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                <button
                  type="button"
                  onClick={() => setIsAddModalOpen(false)}
                  className="px-4 py-2 text-sm text-gray-600 hover:text-gray-800 dark:text-gray-400"
                >
                  İptal
                </button>
                <button
                  type="submit"
                  className="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium transition-colors"
                >
                  Ürünü Kaydet
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* Adjust Stock Modal */}
      {isAdjustModalOpen && selectedProduct && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
          <div className="bg-white dark:bg-gray-800 rounded-2xl w-full max-w-md p-6 shadow-xl border border-gray-200 dark:border-gray-700">
            <h3 className="text-lg font-bold text-gray-900 dark:text-white mb-2">
              Stok Hareketi: {selectedProduct.name}
            </h3>
            <p className="text-xs text-gray-500 mb-4">
              Mevcut Stok: <span className="font-bold text-indigo-600">{selectedProduct.quantity} {selectedProduct.unit}</span>
            </p>

            <form onSubmit={handleAdjustSubmit} className="space-y-4">
              <div>
                <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                  İşlem Türü
                </label>
                <div className="grid grid-cols-3 gap-2">
                  <button
                    type="button"
                    onClick={() => setAdjustForm({ ...adjustForm, type: 'in' })}
                    className={`py-2 text-xs font-bold rounded-lg border text-center transition-colors ${
                      adjustForm.type === 'in'
                        ? 'bg-emerald-600 text-white border-emerald-600'
                        : 'bg-gray-50 border-gray-300 text-gray-700'
                    }`}
                  >
                    + Stok Girişi
                  </button>
                  <button
                    type="button"
                    onClick={() => setAdjustForm({ ...adjustForm, type: 'out' })}
                    className={`py-2 text-xs font-bold rounded-lg border text-center transition-colors ${
                      adjustForm.type === 'out'
                        ? 'bg-rose-600 text-white border-rose-600'
                        : 'bg-gray-50 border-gray-300 text-gray-700'
                    }`}
                  >
                    - Stok Çıkışı
                  </button>
                  <button
                    type="button"
                    onClick={() => setAdjustForm({ ...adjustForm, type: 'adjustment' })}
                    className={`py-2 text-xs font-bold rounded-lg border text-center transition-colors ${
                      adjustForm.type === 'adjustment'
                        ? 'bg-amber-600 text-white border-amber-600'
                        : 'bg-gray-50 border-gray-300 text-gray-700'
                    }`}
                  >
                    Sayım Düzeltmesi
                  </button>
                </div>
              </div>

              <div>
                <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                  {adjustForm.type === 'adjustment' ? 'Yeni Toplam Sayım Miktarı' : 'İşlem Miktarı'}
                </label>
                <input
                  type="number"
                  min="1"
                  required
                  value={adjustForm.quantity}
                  onChange={(e) => setAdjustForm({ ...adjustForm, quantity: parseInt(e.target.value) || 0 })}
                  className="w-full px-3 py-2 border rounded-lg text-sm bg-gray-50 dark:bg-gray-700 border-gray-300 dark:border-gray-600"
                />
              </div>

              <div>
                <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                  Açıklama / Fiş No
                </label>
                <input
                  type="text"
                  required
                  value={adjustForm.reason}
                  onChange={(e) => setAdjustForm({ ...adjustForm, reason: e.target.value })}
                  placeholder="Örn: Fatura No: 2026-0045"
                  className="w-full px-3 py-2 border rounded-lg text-sm bg-gray-50 dark:bg-gray-700 border-gray-300 dark:border-gray-600"
                />
              </div>

              <div className="flex justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                <button
                  type="button"
                  onClick={() => setIsAdjustModalOpen(false)}
                  className="px-4 py-2 text-sm text-gray-600 hover:text-gray-800 dark:text-gray-400"
                >
                  İptal
                </button>
                <button
                  type="submit"
                  className="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium transition-colors"
                >
                  Hareketi Onayla
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
};
