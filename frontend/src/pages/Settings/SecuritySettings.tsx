import React, { useState } from 'react';
import { hrExtensionService } from '@/services/hrExtensionService';
import { Shield, KeyRound, CheckCircle2, AlertCircle } from 'lucide-react';

export const SecuritySettings: React.FC = () => {
  const [currentPassword, setCurrentPassword] = useState('');
  const [newPassword, setNewPassword] = useState('');
  const [confirmPassword, setConfirmPassword] = useState('');
  const [loading, setLoading] = useState(false);
  const [message, setMessage] = useState<{ type: 'success' | 'error'; text: string } | null>(null);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setMessage(null);

    if (newPassword !== confirmPassword) {
      setMessage({ type: 'error', text: 'Yeni şifreler birbiriyle eşleşmiyor.' });
      return;
    }

    if (newPassword.length < 6) {
      setMessage({ type: 'error', text: 'Yeni şifre en az 6 karakter olmalıdır.' });
      return;
    }

    try {
      setLoading(true);
      await hrExtensionService.changePassword(currentPassword, newPassword);
      setMessage({ type: 'success', text: 'Şifreniz başarıyla güncellendi!' });
      setCurrentPassword('');
      setNewPassword('');
      setConfirmPassword('');
    } catch (err: any) {
      const errMsg = err.response?.data?.error || 'Şifre güncellenirken bir hata oluştu.';
      setMessage({ type: 'error', text: errMsg });
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="bg-white p-6 rounded-xl border border-gray-100 shadow-sm max-w-xl">
      <div className="flex items-center gap-3 border-b border-gray-100 pb-4 mb-6">
        <div className="p-2.5 bg-indigo-50 text-indigo-600 rounded-lg">
          <KeyRound className="w-6 h-6" />
        </div>
        <div>
          <h2 className="text-lg font-bold text-gray-900">Güvenlik ve Şifre Ayarları</h2>
          <p className="text-xs text-gray-500">Hesap giriş şifrenizi güncelleyin ve güvenliğinizi sağlayın</p>
        </div>
      </div>

      {message && (
        <div
          className={`p-3.5 rounded-lg mb-5 flex items-center gap-2 text-sm ${
            message.type === 'success' ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-rose-50 text-rose-800 border border-rose-200'
          }`}
        >
          {message.type === 'success' ? <CheckCircle2 className="w-5 h-5 flex-shrink-0" /> : <AlertCircle className="w-5 h-5 flex-shrink-0" />}
          <span>{message.text}</span>
        </div>
      )}

      <form onSubmit={handleSubmit} className="space-y-4">
        <div>
          <label className="block text-xs font-medium text-gray-700 mb-1">Mevcut Şifre *</label>
          <input
            type="password"
            required
            value={currentPassword}
            onChange={(e) => setCurrentPassword(e.target.value)}
            placeholder="••••••••"
            className="w-full border border-gray-300 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-indigo-500 outline-none"
          />
        </div>

        <div>
          <label className="block text-xs font-medium text-gray-700 mb-1">Yeni Şifre *</label>
          <input
            type="password"
            required
            value={newPassword}
            onChange={(e) => setNewPassword(e.target.value)}
            placeholder="••••••••"
            className="w-full border border-gray-300 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-indigo-500 outline-none"
          />
        </div>

        <div>
          <label className="block text-xs font-medium text-gray-700 mb-1">Yeni Şifre (Tekrar) *</label>
          <input
            type="password"
            required
            value={confirmPassword}
            onChange={(e) => setConfirmPassword(e.target.value)}
            placeholder="••••••••"
            className="w-full border border-gray-300 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-indigo-500 outline-none"
          />
        </div>

        <div className="pt-2">
          <button
            type="submit"
            disabled={loading}
            className="w-full sm:w-auto px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white rounded-lg text-sm font-medium transition"
          >
            {loading ? 'Güncelleniyor...' : 'Şifreyi Değiştir'}
          </button>
        </div>
      </form>

      <div className="mt-8 pt-6 border-t border-gray-100 flex items-start gap-3 text-xs text-gray-500">
        <Shield className="w-5 h-5 text-gray-400 flex-shrink-0 mt-0.5" />
        <p>
          Hesabınızın güvenliği için güçlü, tahmin edilmesi güç parolalar kullanınız. Büyük-küçük harf, rakam ve özel karakter kombinasyonları önerilir.
        </p>
      </div>
    </div>
  );
};
