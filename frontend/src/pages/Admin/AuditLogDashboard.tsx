import React, { useState, useEffect } from 'react';
import { useAuthStore } from '@/store/authStore';
import { rbacService } from '@/services/rbacService';
import { AuditLogItem } from '@/types/rbac';
import { ShieldAlert, Search, RefreshCw, Calendar, Terminal } from 'lucide-react';

export const AuditLogDashboard: React.FC = () => {
  const user = useAuthStore((state) => state.user);
  const tenantId = user?.tenant_id || '';

  const [logs, setLogs] = useState<AuditLogItem[]>([]);
  const [loading, setLoading] = useState(true);
  const [search, setSearch] = useState('');

  const loadLogs = async () => {
    if (!tenantId) return;
    try {
      setLoading(true);
      const data = await rbacService.getAuditLogs(tenantId);
      setLogs(data || []);
    } catch (err) {
      console.error('Denetim logları yüklenemedi:', err);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    loadLogs();
  }, [tenantId]);

  const filteredLogs = logs.filter(
    (l) =>
      l.action.toLowerCase().includes(search.toLowerCase()) ||
      l.entity_type.toLowerCase().includes(search.toLowerCase()) ||
      (l.user_email || '').toLowerCase().includes(search.toLowerCase()) ||
      (l.ip_address || '').includes(search)
  );

  return (
    <div className="p-6 space-y-6">
      <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
          <h1 className="text-2xl font-bold text-gray-900 flex items-center gap-2">
            <ShieldAlert className="w-7 h-7 text-purple-600" />
            Sistem Denetim ve Güvenlik Günlükleri (Audit Logs)
          </h1>
          <p className="text-sm text-gray-500 mt-1">
            Kullanıcı aktiviteleri, veri değişiklikleri ve sistem güvenlik hareketlerinin anlık kaydı
          </p>
        </div>
        <button
          onClick={loadLogs}
          disabled={loading}
          className="flex items-center gap-2 px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-50 transition"
        >
          <RefreshCw className={`w-4 h-4 ${loading ? 'animate-spin' : ''}`} />
          Yenile
        </button>
      </div>

      {/* Arama */}
      <div className="bg-white p-4 rounded-xl border border-gray-100 shadow-sm flex items-center gap-3">
        <Search className="w-4 h-4 text-gray-400" />
        <input
          type="text"
          placeholder="Eylem, nesne türü, kullanıcı e-posta veya IP ara..."
          value={search}
          onChange={(e) => setSearch(e.target.value)}
          className="w-full sm:w-96 border border-gray-300 rounded-lg px-3 py-1.5 text-sm bg-white focus:ring-2 focus:ring-purple-500 outline-none"
        />
      </div>

      {/* Tablo */}
      <div className="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <div className="p-4 border-b border-gray-100 flex justify-between items-center">
          <h2 className="text-base font-semibold text-gray-800">Son 100 Güvenlik ve İşlem Günlüğü</h2>
          <span className="text-xs text-gray-400">Canlı Veritabanı Kaydı</span>
        </div>
        {loading ? (
          <div className="p-12 text-center text-gray-500">Yükleniyor...</div>
        ) : filteredLogs.length === 0 ? (
          <div className="p-12 text-center text-gray-400">Henüz kayıtlı işlem günlüğü yok.</div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-left text-sm">
              <thead className="bg-gray-50 text-gray-600 text-xs uppercase font-medium">
                <tr>
                  <th className="px-6 py-3">Zaman</th>
                  <th className="px-6 py-3">Kullanıcı</th>
                  <th className="px-6 py-3">Eylem</th>
                  <th className="px-6 py-3">Etkilenen Nesne</th>
                  <th className="px-6 py-3">IP Adresi</th>
                  <th className="px-6 py-3">Detaylar</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-100">
                {filteredLogs.map((item) => (
                  <tr key={item.id} className="hover:bg-gray-50 font-mono text-xs">
                    <td className="px-6 py-3.5 text-gray-500 flex items-center gap-1.5">
                      <Calendar className="w-3.5 h-3.5 text-gray-400" />
                      {new Date(item.created_at).toLocaleString('tr-TR')}
                    </td>
                    <td className="px-6 py-3.5 font-sans">
                      <div className="font-semibold text-gray-900">{item.user_name || item.user_email || 'Sistem'}</div>
                    </td>
                    <td className="px-6 py-3.5">
                      <span
                        className={`px-2 py-0.5 rounded text-[11px] font-semibold uppercase ${
                          item.action === 'delete'
                            ? 'bg-red-100 text-red-800'
                            : item.action === 'create'
                            ? 'bg-emerald-100 text-emerald-800'
                            : item.action === 'update'
                            ? 'bg-blue-100 text-blue-800'
                            : 'bg-purple-100 text-purple-800'
                        }`}
                      >
                        {item.action}
                      </span>
                    </td>
                    <td className="px-6 py-3.5 text-gray-700 font-sans">
                      <span className="font-medium text-gray-900">{item.entity_type}</span>
                      <span className="text-gray-400 ml-1 text-xs">#{item.entity_id}</span>
                    </td>
                    <td className="px-6 py-3.5 text-gray-500">{item.ip_address || '127.0.0.1'}</td>
                    <td className="px-6 py-3.5 text-gray-600 truncate max-w-xs" title={item.details}>
                      {item.details || '-'}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </div>
    </div>
  );
};
