import React, { useState, useEffect } from 'react';
import { useAuthStore } from '../../store/authStore';
import { getPoliticsVolunteers, createPoliticsVolunteer, getPoliticsStats } from '../../services/specializedService';
import { PoliticsVolunteer, PoliticsStats } from '../../types/specialized';
import { Flag, Plus, Users, Vote, MapPin, Search } from 'lucide-react';

export const PoliticsDashboard: React.FC = () => {
    const user = useAuthStore((state) => state.user);
    const tenantId = user?.tenant_id || '';

    const [volunteers, setVolunteers] = useState<PoliticsVolunteer[]>([]);
    const [stats, setStats] = useState<PoliticsStats | null>(null);
    const [loading, setLoading] = useState(true);
    const [searchQuery, setSearchQuery] = useState('');
    const [isModalOpen, setIsModalOpen] = useState(false);

    const [fullName, setFullName] = useState('');
    const [phone, setPhone] = useState('');
    const [city, setCity] = useState('');
    const [district, setDistrict] = useState('');
    const [neighborhood, setNeighborhood] = useState('');
    const [ballotBoxNumber, setBallotBoxNumber] = useState('');
    const [role, setRole] = useState<'member' | 'ballot_officer' | 'coordinator' | 'observer'>('member');
    const [notes, setNotes] = useState('');

    const loadData = async () => {
        if (!tenantId) return;
        try {
            setLoading(true);
            const [data, s] = await Promise.all([
                getPoliticsVolunteers(tenantId, searchQuery),
                getPoliticsStats(tenantId).catch(() => null)
            ]);
            setVolunteers(data);
            setStats(s);
        } catch (error) {
            console.error('Teşkilat kayıtları yüklenirken hata:', error);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        loadData();
    }, [tenantId, searchQuery]);

    const handleCreate = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!fullName || !phone || !tenantId) return;
        try {
            await createPoliticsVolunteer(tenantId, {
                full_name: fullName,
                phone: phone,
                city: city,
                district: district,
                neighborhood: neighborhood,
                ballot_box_number: ballotBoxNumber,
                role: role,
                notes: notes
            });
            setIsModalOpen(false);
            setFullName('');
            setPhone('');
            setCity('');
            setDistrict('');
            setNeighborhood('');
            setBallotBoxNumber('');
            setRole('member');
            setNotes('');
            loadData();
        } catch (error) {
            alert('Kayıt oluşturulurken bir hata oluştu.');
        }
    };

    const totalCount = stats?.total_members ?? volunteers.length;
    const ballotOfficersCount = stats?.ballot_officers ?? volunteers.filter(v => v.role === 'ballot_officer' || v.role === 'observer').length;
    const coveredBoxesCount = stats?.covered_boxes ?? new Set(volunteers.map(v => v.ballot_box_number).filter(Boolean)).size;

    return (
        <div className="p-6 space-y-6">
            <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h1 className="text-2xl font-bold text-gray-900 flex items-center gap-2">
                        <Flag className="w-7 h-7 text-indigo-600" />
                        Teşkilat ve Seçim Yönetimi
                    </h1>
                    <p className="text-sm text-gray-500 mt-1">
                        Siyasi parti ve teşkilatlar için üye, gönüllü, ilçe/mahalle ve sandık görevlisi koordinasyonu
                    </p>
                </div>
                <button
                    onClick={() => setIsModalOpen(true)}
                    className="flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium shadow-sm transition"
                >
                    <Plus className="w-4 h-4" />
                    Yeni Teşkilat / Sandık Görevlisi
                </button>
            </div>

            {/* İstatistikler */}
            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div className="bg-white p-5 rounded-xl border border-gray-100 shadow-sm flex items-center justify-between">
                    <div>
                        <div className="text-xs font-semibold uppercase tracking-wider text-gray-500">Toplam Teşkilat Mensubu</div>
                        <div className="text-2xl font-bold text-gray-900 mt-1">{totalCount}</div>
                    </div>
                    <div className="p-3 bg-indigo-50 rounded-lg text-indigo-600">
                        <Users className="w-6 h-6" />
                    </div>
                </div>

                <div className="bg-white p-5 rounded-xl border border-gray-100 shadow-sm flex items-center justify-between">
                    <div>
                        <div className="text-xs font-semibold uppercase tracking-wider text-gray-500">Sandık Müşahidi / Görevlisi</div>
                        <div className="text-2xl font-bold text-emerald-600 mt-1">{ballotOfficersCount}</div>
                    </div>
                    <div className="p-3 bg-emerald-50 rounded-lg text-emerald-600">
                        <Vote className="w-6 h-6" />
                    </div>
                </div>

                <div className="bg-white p-5 rounded-xl border border-gray-100 shadow-sm flex items-center justify-between">
                    <div>
                        <div className="text-xs font-semibold uppercase tracking-wider text-gray-500">Kapsanan Sandık Sayısı</div>
                        <div className="text-2xl font-bold text-gray-900 mt-1">{coveredBoxesCount}</div>
                    </div>
                    <div className="p-3 bg-purple-50 rounded-lg text-purple-600">
                        <MapPin className="w-6 h-6" />
                    </div>
                </div>
            </div>

            {/* Arama */}
            <div className="bg-white p-4 rounded-xl border border-gray-100 shadow-sm flex items-center gap-3">
                <Search className="w-4 h-4 text-gray-400" />
                <input
                    type="text"
                    placeholder="İsim, telefon veya sandık no ara..."
                    value={searchQuery}
                    onChange={(e) => setSearchQuery(e.target.value)}
                    className="border border-gray-300 rounded-lg px-3 py-1.5 text-sm bg-white focus:ring-2 focus:ring-indigo-500 outline-none w-full sm:w-80"
                />
            </div>

            {/* Liste Tablosu */}
            <div className="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
                <div className="p-4 border-b border-gray-100">
                    <h2 className="text-base font-semibold text-gray-800">Gönüllü ve Görevliler</h2>
                </div>
                {loading ? (
                    <div className="p-10 text-center text-gray-500">Yükleniyor...</div>
                ) : volunteers.length === 0 ? (
                    <div className="p-12 text-center text-gray-400">
                        Kayıt bulunamadı.
                    </div>
                ) : (
                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-sm">
                            <thead className="bg-gray-50 text-gray-600 text-xs uppercase font-medium">
                                <tr>
                                    <th className="px-6 py-3">Adı Soyadı</th>
                                    <th className="px-6 py-3">İletişim</th>
                                    <th className="px-6 py-3">Bölge (İl / İlçe / Mahalle)</th>
                                    <th className="px-6 py-3">Sandık No</th>
                                    <th className="px-6 py-3">Görevi</th>
                                    <th className="px-6 py-3">Kayıt Tarihi</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100">
                                {volunteers.map((item) => (
                                    <tr key={item.id} className="hover:bg-gray-50">
                                        <td className="px-6 py-4 font-semibold text-gray-900">
                                            {item.full_name}
                                        </td>
                                        <td className="px-6 py-4 text-gray-900">
                                            {item.phone || '-'}
                                        </td>
                                        <td className="px-6 py-4 text-gray-600">
                                            <div>{item.city ? `${item.city} / ${item.district || ''}` : item.district || '-'}</div>
                                            {item.neighborhood && (
                                                <div className="text-xs text-gray-400">{item.neighborhood}</div>
                                            )}
                                        </td>
                                        <td className="px-6 py-4">
                                            {item.ballot_box_number ? (
                                                <span className="font-mono font-bold text-indigo-700 bg-indigo-50 px-2.5 py-1 rounded">
                                                    #{item.ballot_box_number}
                                                </span>
                                            ) : (
                                                <span className="text-gray-400 text-xs">-</span>
                                            )}
                                        </td>
                                        <td className="px-6 py-4">
                                            <span className="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-100 text-indigo-800">
                                                {item.role === 'ballot_officer' ? 'Sandık Görevlisi' : item.role === 'observer' ? 'Müşahit' : item.role === 'coordinator' ? 'Koordinatör' : 'Üye / Gönüllü'}
                                            </span>
                                        </td>
                                        <td className="px-6 py-4 text-gray-500 text-xs">
                                            {new Date(item.created_at).toLocaleDateString('tr-TR')}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </div>

            {/* Yeni Görevli Ekleme Modalı */}
            {isModalOpen && (
                <div className="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4">
                    <div className="bg-white rounded-xl shadow-xl max-w-md w-full p-6 space-y-4">
                        <div className="flex justify-between items-center border-b pb-3">
                            <h3 className="text-lg font-bold text-gray-900">Yeni Teşkilat / Görevli Kaydı</h3>
                            <button
                                onClick={() => setIsModalOpen(false)}
                                className="text-gray-400 hover:text-gray-600 text-lg font-bold"
                            >
                                ✕
                            </button>
                        </div>
                        <form onSubmit={handleCreate} className="space-y-4">
                            <div>
                                <label className="block text-xs font-medium text-gray-700 mb-1">Ad Soyad *</label>
                                <input
                                    type="text"
                                    required
                                    value={fullName}
                                    onChange={(e) => setFullName(e.target.value)}
                                    placeholder="Örn: Mehmet Öz"
                                    className="w-full border border-gray-300 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-indigo-500 outline-none"
                                />
                            </div>
                            <div>
                                <label className="block text-xs font-medium text-gray-700 mb-1">Telefon *</label>
                                <input
                                    type="text"
                                    required
                                    value={phone}
                                    onChange={(e) => setPhone(e.target.value)}
                                    placeholder="05xx xxx xx xx"
                                    className="w-full border border-gray-300 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-indigo-500 outline-none"
                                />
                            </div>
                            <div className="grid grid-cols-2 gap-3">
                                <div>
                                    <label className="block text-xs font-medium text-gray-700 mb-1">Şehir</label>
                                    <input
                                        type="text"
                                        value={city}
                                        onChange={(e) => setCity(e.target.value)}
                                        placeholder="İstanbul"
                                        className="w-full border border-gray-300 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-indigo-500 outline-none"
                                    />
                                </div>
                                <div>
                                    <label className="block text-xs font-medium text-gray-700 mb-1">İlçe</label>
                                    <input
                                        type="text"
                                        value={district}
                                        onChange={(e) => setDistrict(e.target.value)}
                                        placeholder="Kadıköy"
                                        className="w-full border border-gray-300 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-indigo-500 outline-none"
                                    />
                                </div>
                            </div>
                            <div className="grid grid-cols-2 gap-3">
                                <div>
                                    <label className="block text-xs font-medium text-gray-700 mb-1">Sandık No</label>
                                    <input
                                        type="text"
                                        value={ballotBoxNumber}
                                        onChange={(e) => setBallotBoxNumber(e.target.value)}
                                        placeholder="1042"
                                        className="w-full border border-gray-300 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-indigo-500 outline-none"
                                    />
                                </div>
                                <div>
                                    <label className="block text-xs font-medium text-gray-700 mb-1">Görev Tanımı</label>
                                    <select
                                        value={role}
                                        onChange={(e) => setRole(e.target.value as any)}
                                        className="w-full border border-gray-300 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-indigo-500 outline-none bg-white"
                                    >
                                        <option value="member">Üye / Gönüllü</option>
                                        <option value="ballot_officer">Sandık Görevlisi</option>
                                        <option value="observer">Müşahit</option>
                                        <option value="coordinator">Koordinatör</option>
                                    </select>
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
                                <button
                                    type="submit"
                                    className="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium"
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
