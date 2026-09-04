import React, { useState, useEffect } from 'react';
import { useAuthStore } from '@/store/authStore';
import { rbacService } from '@/services/rbacService';
import { RoleItem } from '@/types/rbac';
import { ShieldCheck, Plus, Trash2, Check, Lock } from 'lucide-react';

const AVAILABLE_PERMISSIONS = [
  { id: 'crm:*', label: 'CRM & Satış Yönetimi (Tam Erişim)' },
  { id: 'accounting:*', label: 'Ön Muhasebe & Faturalar (Tam Erişim)' },
  { id: 'inventory:*', label: 'Envanter & Stok Yönetimi (Tam Erişim)' },
  { id: 'hr:*', label: 'İnsan Kaynakları & Bordrolar (Tam Erişim)' },
  { id: 'projects:*', label: 'Projeler & Görevler (Tam Erişim)' },
  { id: 'field:*', label: 'Saha Yönetimi (Tam Erişim)' },
  { id: 'donations:*', label: 'Bağış & STK Modülü (Tam Erişim)' },
  { id: 'politics:*', label: 'Teşkilat & Seçim Modülü (Tam Erişim)' },
  { id: 'admin:users', label: 'Kullanıcı Yönetimi' },
  { id: 'admin:roles', label: 'Rol & Yetki Yönetimi' },
  { id: 'admin:audit', label: 'Denetim Logları Görüntüleme' },
];

export const RoleManagement: React.FC = () => {
  const user = useAuthStore((state) => state.user);
  const tenantId = user?.tenant_id || '';

  const [roles, setRoles] = useState<RoleItem[]>([]);
  const [loading, setLoading] = useState(true);
  const [selectedRole, setSelectedRole] = useState<RoleItem | null>(null);
  const [editPermissions, setEditPermissions] = useState<string[]>([]);
  const [isModalOpen, setIsModalOpen] = useState(false);

  // New role form
  const [name, setName] = useState('');
  const [description, setDescription] = useState('');

  const loadRoles = async () => {
    if (!tenantId) return;
    try {
      setLoading(true);
      const data = await rbacService.getRoles(tenantId);
      setRoles(data);
      if (data.length > 0 && !selectedRole) {
        setSelectedRole(data[0]);
        setEditPermissions(data[0].permissions || []);
      }
    } catch (err) {
      console.error('Roller yüklenemedi:', err);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    loadRoles();
  }, [tenantId]);

  const handleSelectRole = (role: RoleItem) => {
    setSelectedRole(role);
    setEditPermissions(role.permissions || []);
  };

  const handleTogglePermission = (permId: string) => {
    setEditPermissions((prev) =>
      prev.includes(permId) ? prev.filter((p) => p !== permId) : [...prev, permId]
    );
  };

  const handleSavePermissions = async () => {
    if (!selectedRole) return;
    try {
      await rbacService.updateRolePermissions(tenantId, selectedRole.id, editPermissions);
      alert('İzinler başarıyla güncellendi.');
      loadRoles();
    } catch (err) {
      alert('İzinler güncellenirken hata oluştu.');
    }
  };

  const handleCreateRole = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!name.trim()) return;

    try {
      await rbacService.createRole(tenantId, {
        name,
        description,
        permissions: ['projects:*', 'crm:*'],
      });
      setIsModalOpen(false);
      setName('');
      setDescription('');
      loadRoles();
    } catch (err) {
      alert('Rol oluşturulamadı.');
    }
  };

  const handleDeleteRole = async (id: string) => {
    if (!window.confirm('Bu rolü silmek istediğinize emin misiniz?')) return;
    try {
      await rbacService.deleteRole(tenantId, id);
      if (selectedRole?.id === id) setSelectedRole(null);
      loadRoles();
    } catch (err) {
      alert('Rol silinemedi.');
    }
  };

  return (
    <div className="p-6 space-y-6">
      <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
          <h1 className="text-2xl font-bold text-gray-900 flex items-center gap-2">
            <ShieldCheck className="w-7 h-7 text-indigo-600" />
            Rol ve Yetkilendirme Yönetimi (RBAC)
          </h1>
          <p className="text-sm text-gray-500 mt-1">
            Kullanıcı grupları, departman yetkileri ve modül bazlı erişim matrisi
          </p>
        </div>
        <button
          onClick={() => setIsModalOpen(true)}
          className="flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium shadow-sm transition"
        >
          <Plus className="w-4 h-4" />
          Yeni Rol Tanımla
        </button>
      </div>

      {loading ? (
        <div className="p-12 text-center text-gray-500">Yükleniyor...</div>
      ) : (
        <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
          {/* Sol Kolon: Roller Listesi */}
          <div className="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden flex flex-col">
            <div className="p-4 border-b border-gray-100 font-semibold text-gray-800 text-sm">
              Tanımlı Roller ({roles.length})
            </div>
            <div className="divide-y divide-gray-50 flex-1 overflow-y-auto max-h-[600px]">
              {roles.map((r) => (
                <div
                  key={r.id}
                  onClick={() => handleSelectRole(r)}
                  className={`p-4 cursor-pointer transition flex items-center justify-between ${
                    selectedRole?.id === r.id ? 'bg-indigo-50/70 border-l-4 border-indigo-600' : 'hover:bg-gray-50'
                  }`}
                >
                  <div>
                    <div className="font-semibold text-gray-900 text-sm flex items-center gap-1.5">
                      {r.name}
                      {Boolean(r.is_system) && (
                        <span className="text-[10px] bg-gray-200 text-gray-700 px-1.5 py-0.5 rounded flex items-center gap-0.5">
                          <Lock className="w-2.5 h-2.5" /> Sistem
                        </span>
                      )}
                    </div>
                    <p className="text-xs text-gray-500 mt-0.5">{r.description || 'Açıklama yok'}</p>
                  </div>
                  {!Boolean(r.is_system) && (
                    <button
                      onClick={(e) => {
                        e.stopPropagation();
                        handleDeleteRole(r.id);
                      }}
                      className="text-gray-300 hover:text-red-600 transition p-1"
                      title="Sil"
                    >
                      <Trash2 className="w-4 h-4" />
                    </button>
                  )}
                </div>
              ))}
            </div>
          </div>

          {/* Sağ Kolon: İzin Matrisi */}
          <div className="md:col-span-2 bg-white rounded-xl border border-gray-100 shadow-sm p-6 space-y-6">
            {selectedRole ? (
              <>
                <div className="flex justify-between items-center border-b border-gray-100 pb-4">
                  <div>
                    <h2 className="text-lg font-bold text-gray-900">{selectedRole.name} - Erişim İzinleri</h2>
                    <p className="text-xs text-gray-500">
                      Bu role sahip kullanıcıların sistemde hangi alanları görebileceğini belirleyin
                    </p>
                  </div>
                  <button
                    onClick={handleSavePermissions}
                    className="flex items-center gap-1.5 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-semibold shadow-sm transition"
                  >
                    <Check className="w-4 h-4" />
                    İzinleri Kaydet
                  </button>
                </div>

                <div className="space-y-3">
                  {AVAILABLE_PERMISSIONS.map((perm) => {
                    const isChecked = editPermissions.includes(perm.id) || editPermissions.includes('*');
                    return (
                      <label
                        key={perm.id}
                        className="flex items-center gap-3 p-3 rounded-lg border border-gray-100 hover:bg-gray-50 cursor-pointer transition"
                      >
                        <input
                          type="checkbox"
                          checked={isChecked}
                          onChange={() => handleTogglePermission(perm.id)}
                          className="w-4 h-4 text-indigo-600 rounded border-gray-300 focus:ring-indigo-500"
                        />
                        <span className="text-sm font-medium text-gray-800">{perm.label}</span>
                      </label>
                    );
                  })}
                </div>
              </>
            ) : (
              <div className="p-12 text-center text-gray-400">Yetkilerini düzenlemek için bir rol seçin.</div>
            )}
          </div>
        </div>
      )}

      {/* Yeni Rol Modalı */}
      {isModalOpen && (
        <div className="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4">
          <div className="bg-white rounded-xl shadow-xl max-w-md w-full p-6 space-y-4">
            <div className="flex justify-between items-center border-b pb-3">
              <h3 className="text-lg font-bold text-gray-900">Yeni Rol Oluştur</h3>
              <button onClick={() => setIsModalOpen(false)} className="text-gray-400 hover:text-gray-600 text-lg font-bold">
                ✕
              </button>
            </div>
            <form onSubmit={handleCreateRole} className="space-y-4">
              <div>
                <label className="block text-xs font-medium text-gray-700 mb-1">Rol Adı *</label>
                <input
                  type="text"
                  required
                  value={name}
                  onChange={(e) => setName(e.target.value)}
                  placeholder="Örn: Muhasebe Sorumlusu"
                  className="w-full border border-gray-300 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-indigo-500 outline-none"
                />
              </div>
              <div>
                <label className="block text-xs font-medium text-gray-700 mb-1">Açıklama</label>
                <textarea
                  value={description}
                  onChange={(e) => setDescription(e.target.value)}
                  rows={2}
                  placeholder="Bu rolün görev tanımı..."
                  className="w-full border border-gray-300 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-indigo-500 outline-none"
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
                <button type="submit" className="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium">
                  Rolü Kaydet
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
};
