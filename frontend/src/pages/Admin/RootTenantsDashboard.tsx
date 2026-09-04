import React, { useState, useEffect } from 'react';
import { getTenants, createTenant, getRootStats } from '../../services/specializedService';
import { TenantItem, RootStats } from '../../types/specialized';
import { ShieldCheck, Plus, Building2, CheckCircle2, Users } from 'lucide-react';

export const RootTenantsDashboard: React.FC = () => {
    const [tenants, setTenants] = useState<TenantItem[]>([]);
    const [stats, setStats] = useState<RootStats | null>(null);
    const [loading, setLoading] = useState(true);
    const [isModalOpen, setIsModalOpen] = useState(false);

    const [name, setName] = useState('');

    const loadData = async () => {
        try {
            setLoading(true);
            const [tenantsData, statsData] = await Promise.all([
                getTenants(),
                getRootStats().catch(() => null)
            ]);
            setTenants(tenantsData);
            setStats(statsData);
        } catch (error) {
            console.error('Kiracılar yüklenirken hata:', error);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        loadData();
    }, []);

    const handleCreate = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!name) return;
        try {
            await createTenant(name);
            setIsModalOpen(false);
            setName('');
            loadData();
        } catch (error) {
            alert('Kiracı oluşturulurken bir hata meydana geldi.');
        }
    };

    const totalCount = stats?.total_tenants ?? tenants.length;
    const activeCount = stats?.active_tenants ?? tenants.filter(t => t.status === 'active').length;
    const totalUsers = stats?.total_users ?? 0;

    return (
        <div className="p-6 space-y-6">
            <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h1 className="text-2xl font-bold text-gray-900 flex items-center gap-2">
                        <ShieldCheck className="w-7 h-7 text-purple-600" />
                        Süper Admin - Müşteri & Kiracı (Tenant) Yönetimi
                    </h1>
                    <p className="text-sm text-gray-500 mt-1">
                        SaaS mimarisindeki tüm kurum ve şirketlerin merkezi yönetimi ve lisanslaması
                    </p>
                </div>
                <button
                    onClick={() => setIsModalOpen(true)}
                    className="flex items-center gap-2 px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-sm font-medium shadow-sm transition"
                >
                    <Plus className="w-4 h-4" />
                    Yeni Kiracı (Firma / Kurum) Ekle
                </button>
            </div>

            {/* İstatistikler */}
            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div className="bg-white p-5 rounded-xl border border-gray-100 shadow-sm flex items-center justify-between">
                    <div>
                        <div className="text-xs font-semibold uppercase tracking-wider text-gray-500">Toplam Kiracı</div>
                        <div className="text-2xl font-bold text-gray-900 mt-1">{totalCount}</div>
                    </div>
                    <div className="p-3 bg-purple-50 rounded-lg text-purple-600">
                        <Building2 className="w-6 h-6" />
                    </div>
                </div>

                <div className="bg-white p-5 rounded-xl border border-gray-100 shadow-sm flex items-center justify-between">
                    <div>
                        <div className="text-xs font-semibold uppercase tracking-wider text-gray-500">Aktif Şirketler</div>
                        <div className="text-2xl font-bold text-emerald-600 mt-1">{activeCount}</div>
                    </div>
                    <div className="p-3 bg-emerald-50 rounded-lg text-emerald-600">
                        <CheckCircle2 className="w-6 h-6" />
                    </div>
                </div>

                <div className="bg-white p-5 rounded-xl border border-gray-100 shadow-sm flex items-center justify-between">
                    <div>
                        <div className="text-xs font-semibold uppercase tracking-wider text-gray-500">Sistemdeki Toplam Kullanıcı</div>
                        <div className="text-2xl font-bold text-blue-600 mt-1">{totalUsers}</div>
                    </div>
                    <div className="p-3 bg-blue-50 rounded-lg text-blue-600">
                        <Users className="w-6 h-6" />
                    </div>
                </div>
            </div>

            {/* Kiracı Listesi */}
            <div className="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
                <div className="p-4 border-b border-gray-100">
                    <h2 className="text-base font-semibold text-gray-800">Tüm Kiracılar / Müşteriler</h2>
                </div>
                {loading ? (
                    <div className="p-10 text-center text-gray-500">Yükleniyor...</div>
                ) : tenants.length === 0 ? (
                    <div className="p-12 text-center text-gray-400">
                        Sistemde kayıtlı kiracı bulunmamaktadır.
                    </div>
                ) : (
                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-sm">
                            <thead className="bg-gray-50 text-gray-600 text-xs uppercase font-medium">
                                <tr>
                                    <th className="px-6 py-3">Tenant ID</th>
                                    <th className="px-6 py-3">Firma / Kurum Adı</th>
                                    <th className="px-6 py-3">Durum</th>
                                    <th className="px-6 py-3">Kayıt Tarihi</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100">
                                {tenants.map((item) => (
                                    <tr key={item.id} className="hover:bg-gray-50">
                                        <td className="px-6 py-4 font-mono text-xs text-gray-500">
                                            #{item.id}
                                        </td>
                                        <td className="px-6 py-4 font-semibold text-gray-900">
                                            {item.name}
                                        </td>
                                        <td className="px-6 py-4">
                                            <span className="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">
                                                <CheckCircle2 className="w-3.5 h-3.5" />
                                                {item.status || 'Aktif'}
                                            </span>
                                        </td>
                                        <td className="px-6 py-4 text-gray-500 text-xs">
                                            {item.created_at ? new Date(item.created_at).toLocaleDateString('tr-TR') : '-'}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </div>

            {/* Yeni Kiracı Modal */}
            {isModalOpen && (
                <div className="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4">
                    <div className="bg-white rounded-xl shadow-xl max-w-md w-full p-6 space-y-4">
                        <div className="flex justify-between items-center border-b pb-3">
                            <h3 className="text-lg font-bold text-gray-900">Yeni Kiracı (Tenant) Kurulumu</h3>
                            <button
                                onClick={() => setIsModalOpen(false)}
                                className="text-gray-400 hover:text-gray-600 text-lg font-bold"
                            >
                                ✕
                            </button>
                        </div>
                        <form onSubmit={handleCreate} className="space-y-4">
                            <div>
                                <label className="block text-xs font-medium text-gray-700 mb-1">Kurum / Firma Adı *</label>
                                <input
                                    type="text"
                                    required
                                    value={name}
                                    onChange={(e) => setName(e.target.value)}
                                    placeholder="Örn: Acme A.Ş."
                                    className="w-full border border-gray-300 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-purple-500 outline-none"
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
                                <button
                                    type="submit"
                                    className="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-sm font-medium"
                                >
                                    Oluştur
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </div>
    );
};
