import React, { useState, useEffect } from 'react';
import { useAuthStore } from '../../store/authStore';
import { getDonations, createDonation, getDonationStats } from '../../services/specializedService';
import { Donation, DonationStats } from '../../types/specialized';
import { Heart, Plus, TrendingUp, Users, DollarSign, Calendar, Search } from 'lucide-react';

export const DonationDashboard: React.FC = () => {
    const user = useAuthStore((state) => state.user);
    const tenantId = user?.tenant_id || '';

    const [donations, setDonations] = useState<Donation[]>([]);
    const [stats, setStats] = useState<DonationStats | null>(null);
    const [loading, setLoading] = useState(true);
    const [search, setSearch] = useState('');
    const [isModalOpen, setIsModalOpen] = useState(false);

    const [donorName, setDonorName] = useState('');
    const [donorPhone, setDonorPhone] = useState('');
    const [amount, setAmount] = useState('');
    const [campaignName, setCampaignName] = useState('Genel Bağış');
    const [paymentMethod, setPaymentMethod] = useState<'bank' | 'credit_card' | 'cash'>('bank');
    const [notes, setNotes] = useState('');

    const loadData = async () => {
        if (!tenantId) return;
        try {
            setLoading(true);
            const [data, s] = await Promise.all([
                getDonations(tenantId, search),
                getDonationStats(tenantId).catch(() => null)
            ]);
            setDonations(data);
            setStats(s);
        } catch (error) {
            console.error('Bağışlar yüklenirken hata:', error);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        loadData();
    }, [tenantId, search]);

    const handleCreate = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!donorName || !amount || !tenantId) return;
        try {
            await createDonation(tenantId, {
                donor_name: donorName,
                donor_phone: donorPhone,
                amount: parseFloat(amount),
                currency: 'TRY',
                campaign_name: campaignName,
                payment_method: paymentMethod,
                notes: notes
            });
            setIsModalOpen(false);
            setDonorName('');
            setDonorPhone('');
            setAmount('');
            setNotes('');
            loadData();
        } catch (error) {
            alert('Bağış kaydedilirken hata oluştu.');
        }
    };

    const totalDonations = stats?.total_amount ?? donations.reduce((sum, d) => sum + Number(d.amount || 0), 0);
    const donorCount = stats?.total_donors ?? new Set(donations.map(d => d.donor_name)).size;
    const campaigns = Array.from(new Set(donations.map(d => d.campaign_name).filter(Boolean)));

    return (
        <div className="p-6 space-y-6">
            <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h1 className="text-2xl font-bold text-gray-900 flex items-center gap-2">
                        <Heart className="w-7 h-7 text-rose-600" />
                        Bağış ve Kaynak Yönetimi
                    </h1>
                    <p className="text-sm text-gray-500 mt-1">
                        Sivil toplum, vakıf ve dernekler için bağışçı takibi, kampanya ve gelir raporlama
                    </p>
                </div>
                <button
                    onClick={() => setIsModalOpen(true)}
                    className="flex items-center gap-2 px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-lg text-sm font-medium shadow-sm transition"
                >
                    <Plus className="w-4 h-4" />
                    Yeni Bağış Girişi
                </button>
            </div>

            {/* İstatistikler */}
            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div className="bg-white p-5 rounded-xl border border-gray-100 shadow-sm flex items-center justify-between">
                    <div>
                        <div className="text-xs font-semibold uppercase tracking-wider text-gray-500">Toplam Toplanan Bağış</div>
                        <div className="text-2xl font-black text-rose-600 mt-1">
                            ₺{Number(totalDonations).toLocaleString('tr-TR', { minimumFractionDigits: 2 })}
                        </div>
                    </div>
                    <div className="p-3 bg-rose-50 rounded-lg text-rose-600">
                        <DollarSign className="w-6 h-6" />
                    </div>
                </div>

                <div className="bg-white p-5 rounded-xl border border-gray-100 shadow-sm flex items-center justify-between">
                    <div>
                        <div className="text-xs font-semibold uppercase tracking-wider text-gray-500">Tekil Bağışçı Sayısı</div>
                        <div className="text-2xl font-bold text-gray-900 mt-1">{donorCount}</div>
                    </div>
                    <div className="p-3 bg-blue-50 rounded-lg text-blue-600">
                        <Users className="w-6 h-6" />
                    </div>
                </div>

                <div className="bg-white p-5 rounded-xl border border-gray-100 shadow-sm flex items-center justify-between">
                    <div>
                        <div className="text-xs font-semibold uppercase tracking-wider text-gray-500">Aktif Kampanyalar</div>
                        <div className="text-2xl font-bold text-gray-900 mt-1">{campaigns.length}</div>
                    </div>
                    <div className="p-3 bg-emerald-50 rounded-lg text-emerald-600">
                        <TrendingUp className="w-6 h-6" />
                    </div>
                </div>
            </div>

            {/* Arama */}
            <div className="bg-white p-4 rounded-xl border border-gray-100 shadow-sm flex items-center gap-3">
                <Search className="w-4 h-4 text-gray-400" />
                <input
                    type="text"
                    placeholder="Bağışçı veya kampanya ara..."
                    value={search}
                    onChange={(e) => setSearch(e.target.value)}
                    className="border border-gray-300 rounded-lg px-3 py-1.5 text-sm bg-white focus:ring-2 focus:ring-rose-500 outline-none w-full sm:w-80"
                />
            </div>

            {/* Bağış Tablosu */}
            <div className="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
                <div className="p-4 border-b border-gray-100">
                    <h2 className="text-base font-semibold text-gray-800">Bağış Geçmişi</h2>
                </div>
                {loading ? (
                    <div className="p-10 text-center text-gray-500">Yükleniyor...</div>
                ) : donations.length === 0 ? (
                    <div className="p-12 text-center text-gray-400">
                        Henüz kayıtlı bağış bulunamadı.
                    </div>
                ) : (
                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-sm">
                            <thead className="bg-gray-50 text-gray-600 text-xs uppercase font-medium">
                                <tr>
                                    <th className="px-6 py-3">Bağışçı</th>
                                    <th className="px-6 py-3">Tutar</th>
                                    <th className="px-6 py-3">Kampanya</th>
                                    <th className="px-6 py-3">Ödeme Metodu</th>
                                    <th className="px-6 py-3">Tarih</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100">
                                {donations.map((item) => (
                                    <tr key={item.id} className="hover:bg-gray-50">
                                        <td className="px-6 py-4">
                                            <div className="font-medium text-gray-900">{item.donor_name}</div>
                                            {item.donor_phone && (
                                                <div className="text-xs text-gray-400">{item.donor_phone}</div>
                                            )}
                                        </td>
                                        <td className="px-6 py-4 font-bold text-gray-900">
                                            ₺{Number(item.amount).toLocaleString('tr-TR', { minimumFractionDigits: 2 })}
                                        </td>
                                        <td className="px-6 py-4 text-gray-600">
                                            <span className="px-2 py-1 bg-gray-100 text-gray-700 rounded text-xs">
                                                {item.campaign_name || 'Genel'}
                                            </span>
                                        </td>
                                        <td className="px-6 py-4 text-gray-600 capitalize">
                                            {item.payment_method === 'bank' ? 'Havale / EFT' : item.payment_method === 'credit_card' ? 'Kredi Kartı' : 'Nakit'}
                                        </td>
                                        <td className="px-6 py-4 text-gray-500 text-xs flex items-center gap-1.5">
                                            <Calendar className="w-3.5 h-3.5 text-gray-400" />
                                            {new Date(item.created_at).toLocaleDateString('tr-TR')}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </div>

            {/* Yeni Bağış Modal */}
            {isModalOpen && (
                <div className="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4">
                    <div className="bg-white rounded-xl shadow-xl max-w-md w-full p-6 space-y-4">
                        <div className="flex justify-between items-center border-b pb-3">
                            <h3 className="text-lg font-bold text-gray-900">Yeni Bağış Kaydı</h3>
                            <button
                                onClick={() => setIsModalOpen(false)}
                                className="text-gray-400 hover:text-gray-600 text-lg font-bold"
                            >
                                ✕
                            </button>
                        </div>
                        <form onSubmit={handleCreate} className="space-y-4">
                            <div>
                                <label className="block text-xs font-medium text-gray-700 mb-1">Bağışçı Adı Soyadı / Kurum *</label>
                                <input
                                    type="text"
                                    required
                                    value={donorName}
                                    onChange={(e) => setDonorName(e.target.value)}
                                    placeholder="Örn: Ahmet Yılmaz"
                                    className="w-full border border-gray-300 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-rose-500 outline-none"
                                />
                            </div>
                            <div>
                                <label className="block text-xs font-medium text-gray-700 mb-1">İletişim Telefonu</label>
                                <input
                                    type="text"
                                    value={donorPhone}
                                    onChange={(e) => setDonorPhone(e.target.value)}
                                    placeholder="05xx xxx xx xx"
                                    className="w-full border border-gray-300 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-rose-500 outline-none"
                                />
                            </div>
                            <div className="grid grid-cols-2 gap-3">
                                <div>
                                    <label className="block text-xs font-medium text-gray-700 mb-1">Tutar (TL) *</label>
                                    <input
                                        type="number"
                                        step="0.01"
                                        required
                                        value={amount}
                                        onChange={(e) => setAmount(e.target.value)}
                                        placeholder="1000.00"
                                        className="w-full border border-gray-300 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-rose-500 outline-none"
                                    />
                                </div>
                                <div>
                                    <label className="block text-xs font-medium text-gray-700 mb-1">Ödeme Türü</label>
                                    <select
                                        value={paymentMethod}
                                        onChange={(e) => setPaymentMethod(e.target.value as any)}
                                        className="w-full border border-gray-300 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-rose-500 outline-none bg-white"
                                    >
                                        <option value="bank">Havale / EFT</option>
                                        <option value="credit_card">Kredi Kartı</option>
                                        <option value="cash">Nakit</option>
                                    </select>
                                </div>
                            </div>
                            <div>
                                <label className="block text-xs font-medium text-gray-700 mb-1">Kampanya</label>
                                <input
                                    type="text"
                                    value={campaignName}
                                    onChange={(e) => setCampaignName(e.target.value)}
                                    placeholder="Genel, Ramazan vb."
                                    className="w-full border border-gray-300 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-rose-500 outline-none"
                                />
                            </div>
                            <div>
                                <label className="block text-xs font-medium text-gray-700 mb-1">Açıklama / Not</label>
                                <textarea
                                    value={notes}
                                    onChange={(e) => setNotes(e.target.value)}
                                    rows={2}
                                    placeholder="Makbuz no vb."
                                    className="w-full border border-gray-300 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-rose-500 outline-none"
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
                                    className="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-lg text-sm font-medium"
                                >
                                    Kaydet
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </div>
    );
};
