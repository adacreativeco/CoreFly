import React, { useEffect, useState } from 'react';
import { useAuthStore } from '@/store/authStore';
import {
  getInventoryStats,
  getProducts,
  createProduct,
  adjustStock,
  deleteProduct,
  getMovements,
  getCategories,
} from '@/services/inventoryService';
import { InventoryProduct, InventoryCategory, InventoryMovement, InventoryStats, MovementType } from '@/types/inventory';
import { ProductList } from './ProductList';
import { StockMovements } from './StockMovements';
import { SupplierListModal } from './SupplierListModal';
import { Package, Layers, DollarSign, AlertOctagon, Boxes, History, Building } from 'lucide-react';

export const InventoryDashboard: React.FC = () => {
  const user = useAuthStore((state) => state.user);
  const tenantId = user?.tenant_id || '';

  const [activeTab, setActiveTab] = useState<'products' | 'movements'>('products');
  const [stats, setStats] = useState<InventoryStats | null>(null);
  const [products, setProducts] = useState<InventoryProduct[]>([]);
  const [categories, setCategories] = useState<InventoryCategory[]>([]);
  const [movements, setMovements] = useState<InventoryMovement[]>([]);
  const [loading, setLoading] = useState(true);
  const [isSupplierModalOpen, setIsSupplierModalOpen] = useState(false);

  // Filters
  const [searchQuery, setSearchQuery] = useState('');
  const [selectedCategory, setSelectedCategory] = useState('');
  const [lowStockFilter, setLowStockFilter] = useState(false);

  const loadData = async () => {
    if (!tenantId) return;
    try {
      const [statsData, prodsData, catsData] = await Promise.all([
        getInventoryStats(tenantId),
        getProducts(tenantId, {
          search: searchQuery,
          category_id: selectedCategory,
          low_stock: lowStockFilter,
        }),
        getCategories(tenantId),
      ]);
      setStats(statsData);
      setProducts(prodsData);
      setCategories(catsData);
    } catch (err) {
      console.error('Envanter verisi yükleme hatası:', err);
    } finally {
      setLoading(false);
    }
  };

  const loadMovements = async () => {
    if (!tenantId) return;
    try {
      const movs = await getMovements(tenantId);
      setMovements(movs);
    } catch (err) {
      console.error('Hareketler yüklenemedi:', err);
    }
  };

  useEffect(() => {
    loadData();
  }, [tenantId, searchQuery, selectedCategory, lowStockFilter]);

  useEffect(() => {
    if (activeTab === 'movements') {
      loadMovements();
    }
  }, [activeTab]);

  const handleAddProduct = async (data: Partial<InventoryProduct>) => {
    await createProduct(tenantId, data);
    loadData();
  };

  const handleAdjustStock = async (
    productId: string,
    data: { type: MovementType; quantity: number; reason: string }
  ) => {
    await adjustStock(tenantId, productId, data);
    loadData();
    if (activeTab === 'movements') {
      loadMovements();
    }
  };

  const handleDeleteProduct = async (productId: string) => {
    if (!confirm('Bu ürünü ve bağlı stok kayıtlarını silmek istediğinize emin misiniz?')) return;
    await deleteProduct(tenantId, productId);
    loadData();
  };

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
          <h1 className="text-2xl font-bold text-gray-900 dark:text-white">
            Envanter & Stok Yönetimi
          </h1>
          <p className="text-sm text-gray-500">
            Depo durumları, ürün kataloğu ve anlık stok giriş/çıkış hareketleri.
          </p>
        </div>
        <button
          onClick={() => setIsSupplierModalOpen(true)}
          className="flex items-center gap-2 px-4 py-2 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-200 rounded-lg text-sm font-medium transition"
        >
          <Building className="w-4 h-4 text-indigo-600" />
          Tedarikçi Firmalar
        </button>
      </div>

      <SupplierListModal
        tenantId={tenantId}
        isOpen={isSupplierModalOpen}
        onClose={() => setIsSupplierModalOpen(false)}
      />

      {/* Metrics Cards */}
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div className="bg-white dark:bg-gray-800 p-5 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm flex items-center gap-4">
          <div className="w-12 h-12 bg-blue-50 text-blue-600 rounded-lg flex items-center justify-center">
            <Package className="w-6 h-6" />
          </div>
          <div>
            <div className="text-xs font-medium text-gray-500">Ürün Çeşidi</div>
            <div className="text-2xl font-bold text-gray-900 dark:text-white">
              {stats?.total_products ?? 0}
            </div>
          </div>
        </div>

        <div className="bg-white dark:bg-gray-800 p-5 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm flex items-center gap-4">
          <div className="w-12 h-12 bg-indigo-50 text-indigo-600 rounded-lg flex items-center justify-center">
            <Layers className="w-6 h-6" />
          </div>
          <div>
            <div className="text-xs font-medium text-gray-500">Depodaki Toplam Stok</div>
            <div className="text-2xl font-bold text-indigo-600 dark:text-indigo-400">
              {Number(stats?.total_quantity ?? 0).toLocaleString('tr-TR')}
            </div>
          </div>
        </div>

        <div className="bg-white dark:bg-gray-800 p-5 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm flex items-center gap-4">
          <div className="w-12 h-12 bg-emerald-50 text-emerald-600 rounded-lg flex items-center justify-center">
            <DollarSign className="w-6 h-6" />
          </div>
          <div>
            <div className="text-xs font-medium text-gray-500">Toplam Stok Değeri (Maliyet)</div>
            <div className="text-2xl font-bold text-emerald-600 dark:text-emerald-400">
              {Number(stats?.total_value ?? 0).toLocaleString('tr-TR')} ₺
            </div>
          </div>
        </div>

        <div className="bg-white dark:bg-gray-800 p-5 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm flex items-center gap-4">
          <div className={`w-12 h-12 rounded-lg flex items-center justify-center ${
            (stats?.low_stock_count ?? 0) > 0 ? 'bg-rose-50 text-rose-600' : 'bg-gray-50 text-gray-400'
          }`}>
            <AlertOctagon className="w-6 h-6" />
          </div>
          <div>
            <div className="text-xs font-medium text-gray-500">Kritik Stok Uyarısı</div>
            <div className={`text-2xl font-bold ${
              (stats?.low_stock_count ?? 0) > 0 ? 'text-rose-600' : 'text-gray-900 dark:text-white'
            }`}>
              {stats?.low_stock_count ?? 0}
            </div>
          </div>
        </div>
      </div>

      {/* Tabs */}
      <div className="border-b border-gray-200 dark:border-gray-700 flex gap-6">
        <button
          onClick={() => setActiveTab('products')}
          className={`flex items-center gap-2 pb-3 text-sm font-medium border-b-2 transition-colors ${
            activeTab === 'products'
              ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400'
              : 'border-transparent text-gray-500 hover:text-gray-700'
          }`}
        >
          <Boxes className="w-4 h-4" />
          Ürün & Stok Kataloğu
        </button>

        <button
          onClick={() => setActiveTab('movements')}
          className={`flex items-center gap-2 pb-3 text-sm font-medium border-b-2 transition-colors ${
            activeTab === 'movements'
              ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400'
              : 'border-transparent text-gray-500 hover:text-gray-700'
          }`}
        >
          <History className="w-4 h-4" />
          Stok Hareket Geçmişi
        </button>
      </div>

      {/* Tab Content */}
      {loading ? (
        <div className="flex items-center justify-center py-20 text-gray-400">
          Yükleniyor...
        </div>
      ) : activeTab === 'products' ? (
        <ProductList
          products={products}
          categories={categories}
          onAddProduct={handleAddProduct}
          onAdjustStock={handleAdjustStock}
          onDeleteProduct={handleDeleteProduct}
          onSearch={setSearchQuery}
          onFilterCategory={setSelectedCategory}
          onToggleLowStock={setLowStockFilter}
          isLowStockFilterActive={lowStockFilter}
        />
      ) : (
        <StockMovements movements={movements} />
      )}
    </div>
  );
};
