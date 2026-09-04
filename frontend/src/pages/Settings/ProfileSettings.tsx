import React, { useState } from 'react';
import { useAuthStore } from '@/store/authStore';
import client from '@/api/client';
import { User, Check, AlertCircle } from 'lucide-react';

export const ProfileSettings: React.FC = () => {
  const { user, token, setAuth } = useAuthStore();
  const [fullName, setFullName] = useState(user?.name || user?.full_name || '');
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [message, setMessage] = useState<{ type: 'success' | 'error'; text: string } | null>(null);

  const handleUpdate = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!fullName.trim()) {
      setMessage({ type: 'error', text: 'Lütfen geçerli bir Ad Soyad girin.' });
      return;
    }

    try {
      setIsSubmitting(true);
      setMessage(null);
      const res = await client.post('/auth/profile', { full_name: fullName.trim() });
      if (res.data && res.data.user && token) {
        setAuth(token, res.data.user);
        setMessage({ type: 'success', text: 'Profil bilgileriniz başarıyla güncellendi.' });
      }
    } catch (err: any) {
      console.error('Profil güncelleme hatası:', err);
      setMessage({ 
        type: 'error', 
        text: err.response?.data?.error || 'Profil güncellenirken bir hata oluştu.' 
      });
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <div className="max-w-xl bg-white p-6 rounded-2xl border border-gray-200 shadow-sm">
      <div className="flex items-center space-x-3 mb-6 pb-4 border-b border-gray-100">
        <div className="w-10 h-10 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center font-bold">
          <User className="w-5 h-5" />
        </div>
        <div>
          <h3 className="text-lg font-bold text-gray-900">Profil Ayarları</h3>
          <p className="text-xs text-gray-500">Kişisel bilgilerinizi buradan güncelleyebilirsiniz.</p>
        </div>
      </div>

      {message && (
        <div className={`mb-6 p-4 rounded-xl flex items-center space-x-2 text-sm ${
          message.type === 'success' ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-rose-50 text-rose-800 border border-rose-200'
        }`}>
          {message.type === 'success' ? <Check className="w-4 h-4 flex-shrink-0" /> : <AlertCircle className="w-4 h-4 flex-shrink-0" />}
          <span>{message.text}</span>
        </div>
      )}

      <form onSubmit={handleUpdate} className="space-y-5">
        <div>
          <label className="block text-xs font-semibold text-gray-700 mb-1">Ad Soyad</label>
          <input
            type="text"
            required
            className="block w-full border border-gray-300 rounded-lg shadow-sm py-2 px-3 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 text-sm"
            value={fullName}
            onChange={(e) => setFullName(e.target.value)}
          />
        </div>

        <div>
          <label className="block text-xs font-semibold text-gray-700 mb-1">E-Posta Adresi</label>
          <input
            type="email"
            disabled
            className="block w-full border border-gray-200 rounded-lg py-2 px-3 bg-gray-50 text-gray-500 text-sm cursor-not-allowed"
            value={user?.email || ''}
          />
          <p className="text-[11px] text-gray-400 mt-1">E-posta adresi güvenlik nedeniyle değiştirilemez.</p>
        </div>

        <div>
          <label className="block text-xs font-semibold text-gray-700 mb-1">Kullanıcı Rolü</label>
          <input
            type="text"
            disabled
            className="block w-full border border-gray-200 rounded-lg py-2 px-3 bg-gray-50 text-gray-500 text-sm uppercase font-mono cursor-not-allowed"
            value={user?.role || 'member'}
          />
        </div>

        <div className="pt-3">
          <button
            type="submit"
            disabled={isSubmitting}
            className="w-full sm:w-auto px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg shadow-sm transition disabled:opacity-50"
          >
            {isSubmitting ? 'Kaydediliyor...' : 'Değişiklikleri Kaydet'}
          </button>
        </div>
      </form>
    </div>
  );
};
