import React, { useState, useEffect } from 'react';
import { useAuthStore } from '@/store/authStore';
import client from '@/api/client';
import { Building2, Check, AlertCircle, CheckCircle2 } from 'lucide-react';

export const TenantSettings: React.FC = () => {
  const { user } = useAuthStore();
  const [tenantName, setTenantName] = useState('');
  const [loading, setLoading] = useState(true);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [message, setMessage] = useState<{ type: 'success' | 'error'; text: string } | null>(null);

  useEffect(() => {
    const fetchTenant = async () => {
      try {
        setLoading(true);
        const res = await client.get('/auth/tenant');
        if (res.data?.data?.name) {
          setTenantName(res.data.data.name);
        } else {
          setTenantName('CoreFly Kurumsal Teknoloji A.Ş.');
        }
      } catch (err) {
        setTenantName('CoreFly Kurumsal Teknoloji A.Ş.');
      } finally {
        setLoading(false);
      }
    };

    fetchTenant();
  }, []);

  const handleUpdate = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!tenantName.trim()) return;

    try {
      setIsSubmitting(true);
      setMessage(null);
      await client.put('/auth/tenant', { name: tenantName.trim() });
      setMessage({ type: 'success', text: 'Şirket adı ve yapılandırması başarıyla güncellendi.' });
    } catch (err: any) {
      console.error('Şirket ayarları güncelleme hatası:', err);
      setMessage({ 
        type: 'error', 
        text: err.response?.data?.error || 'Güncelleme yapılırken bir hata oluştu.' 
      });
    } finally {
      setIsSubmitting(false);
    }
  };

  const modules = [
    { name: 'Çalışma Alanı & Sosyal Akış', desc: 'Şirket içi anlık durum paylaşımları ve yorumlar' },
    { name: 'Projeler & Kanban Yönetimi', desc: 'Departman bazlı hedefler, bütçe ve aşama takibi' },
    { name: 'CRM & Müşteri Satış Hunisi', desc: 'Müşteri cari kartları, fırsatlar ve boru hattı' },
    { name: 'Ön Muhasebe & Kasa/Banka', desc: 'Faturalandırma, gelir-gider ve finansal hareketler' },
    { name: 'Envanter & Donanım Deposu', desc: 'Stok takibi, kritik miktar alarmları ve tedarikçiler' },
    { name: 'İnsan Kaynakları & Bordro', desc: 'Personel özlük, izin onayları ve dönemsel maaşlar' },
    { name: 'Saha Yönetimi & Ekipler', desc: 'Görev atama, GPS lokasyon ve saha personeli' },
    { name: 'Bağış & Fon Yönetimi (STK)', desc: 'Burs ve fon kampanyaları, bağış makbuzları' },
    { name: 'Teşkilat & Sandık Takibi', desc: 'Gönüllü koordinatörler, sandık ve seçim görevlileri' },
    { name: 'Takvim & Şirket Toplantıları', desc: 'İcra kurulu, departman toplantıları ve randevular' },
    { name: 'Duyurular & Şirket İletişimi', desc: 'Acil ve iğneli şirket genel bildirimleri' },
    { name: 'Destek Masası (Helpdesk)', desc: 'Donanım ve yazılım arıza biletleme sistemi' },
    { name: 'Mesajlaşma & Sohbet Odaları', desc: 'Departman kanalları ve doğrudan sohbetler' },
    { name: 'Dosyalar & Doküman Deposu', desc: 'Kurumsal evrak ve ek yükleme alanı' },
  ];

  if (loading) return <div className="p-8 text-center text-gray-500">Şirket yapılandırması yükleniyor...</div>;

  return (
    <div className="max-w-2xl bg-white p-6 rounded-2xl border border-gray-200 shadow-sm space-y-6">
      <div className="flex items-center space-x-3 pb-4 border-b border-gray-100">
        <div className="w-10 h-10 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center font-bold">
          <Building2 className="w-5 h-5" />
        </div>
        <div>
          <h3 className="text-lg font-bold text-gray-900">Şirket & Organizasyon Yapılandırması</h3>
          <p className="text-xs text-gray-500">Kurum unvanı ve lisanslı kurumsal modüller.</p>
        </div>
      </div>

      {message && (
        <div className={`p-4 rounded-xl flex items-center space-x-2 text-sm ${
          message.type === 'success' ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-rose-50 text-rose-800 border border-rose-200'
        }`}>
          {message.type === 'success' ? <Check className="w-4 h-4 flex-shrink-0" /> : <AlertCircle className="w-4 h-4 flex-shrink-0" />}
          <span>{message.text}</span>
        </div>
      )}

      <form onSubmit={handleUpdate} className="space-y-4">
        <div>
          <label className="block text-xs font-semibold text-gray-700 mb-1">Şirket / Organizasyon Unvanı</label>
          <input
            type="text"
            required
            className="block w-full border border-gray-300 rounded-lg shadow-sm py-2 px-3 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 text-sm"
            value={tenantName}
            onChange={(e) => setTenantName(e.target.value)}
          />
        </div>

        <div>
          <label className="block text-xs font-semibold text-gray-700 mb-1">Tenant ID (Kurumsal Kimlik Kodu)</label>
          <input
            type="text"
            disabled
            className="block w-full border border-gray-200 rounded-lg py-2 px-3 bg-gray-50 text-gray-500 text-sm font-mono cursor-not-allowed"
            value={user?.tenant_id || 'default-tenant'}
          />
          <p className="mt-1 text-[11px] text-gray-400">
            Çoklu kiracı (Multi-tenant) veri izolasyon anahtarıdır.
          </p>
        </div>

        <div className="pt-2">
          <button
            type="submit"
            disabled={isSubmitting}
            className="px-6 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg shadow-sm transition disabled:opacity-50"
          >
            {isSubmitting ? 'Kaydediliyor...' : 'Şirket Bilgilerini Güncelle'}
          </button>
        </div>
      </form>

      <div className="pt-4 border-t border-gray-100">
        <h4 className="text-sm font-bold text-gray-900 mb-3">Lisanslı & Aktif Enterprise Modülleri</h4>
        <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
          {modules.map((m) => (
            <div key={m.name} className="p-3 rounded-xl border border-gray-100 bg-gray-50/50 flex items-start space-x-3">
              <CheckCircle2 className="w-4 h-4 text-emerald-600 flex-shrink-0 mt-0.5" />
              <div>
                <p className="text-xs font-semibold text-gray-800">{m.name}</p>
                <p className="text-[10px] text-gray-500 mt-0.5 line-clamp-1">{m.desc}</p>
              </div>
            </div>
          ))}
        </div>
      </div>
    </div>
  );
};
