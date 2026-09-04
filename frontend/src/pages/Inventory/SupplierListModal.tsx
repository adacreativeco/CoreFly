import React, { useState, useEffect } from 'react';
import { hrExtensionService } from '@/services/hrExtensionService';
import { InventorySupplier } from '@/types/hrExtensions';
import { Building, Plus, Trash2, Phone, Mail } from 'lucide-react';

interface SupplierListModalProps {
  tenantId: string;
  isOpen: boolean;
  onClose: () => void;
}

export const SupplierListModal: React.FC<SupplierListModalProps> = ({ tenantId, isOpen, onClose }) => {
  const [suppliers, setSuppliers] = useState<InventorySupplier[]>([]);
  const [loading, setLoading] = useState(true);
  const [showAddForm, setShowAddForm] = useState(false);

  const [name, setName] = useState('');
  const [contactPerson, setContactPerson] = useState('');
  const [phone, setPhone] = useState('');
  const [email, setEmail] = useState('');

  const loadSuppliers = async () => {
    try {
      setLoading(true);
      const data = await hrExtensionService.getSuppliers(tenantId);
      setSuppliers(data);
    } catch (err) {
      console.error('Tedarikçiler yüklenemedi:', err);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    if (isOpen && tenantId) {
      loadSuppliers();
    }
  }, [isOpen, tenantId]);

  const handleCreate = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!name) return;
    try {
      await hrExtensionService.createSupplier(tenantId, {
        name,
        contact_person: contactPerson,
        phone,
        email,
      });
      setShowAddForm(false);
      setName('');
      setContactPerson('');
      setPhone('');
      setEmail('');
      loadSuppliers();
    } catch (err) {
      alert('Tedarikçi eklenemedi.');
    }
  };

  const handleDelete = async (id: string) => {
    if (!window.confirm('Bu tedarikçiyi silmek istediğinize emin misiniz?')) return;
    try {
      await hrExtensionService.deleteSupplier(tenantId, id);
      loadSuppliers();
    } catch (err) {
      alert('Silinemedi.');
    }
  };

  if (!isOpen) return null;

  return (
    <div className="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4">
      <div className="bg-white rounded-xl shadow-xl max-w-2xl w-full p-6 space-y-4 max-h-[85vh] flex flex-col">
        <div className="flex justify-between items-center border-b pb-3">
          <h3 className="text-lg font-bold text-gray-900 flex items-center gap-2">
            <Building className="w-5 h-5 text-indigo-600" />
            Envanter Tedarikçi Firmaları
          </h3>
          <button onClick={onClose} className="text-gray-400 hover:text-gray-600 text-lg font-bold">
            ✕
          </button>
        </div>

        <div className="flex justify-end">
          <button
            onClick={() => setShowAddForm(!showAddForm)}
            className="flex items-center gap-1.5 px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-medium transition"
          >
            <Plus className="w-4 h-4" />
            {showAddForm ? 'Listeye Dön' : 'Yeni Tedarikçi Ekle'}
          </button>
        </div>

        {showAddForm ? (
          <form onSubmit={handleCreate} className="space-y-3 bg-gray-50 p-4 rounded-xl border border-gray-200">
            <h4 className="text-sm font-semibold text-gray-800">Tedarikçi Firma Kaydı</h4>
            <div>
              <label className="block text-xs font-medium text-gray-700 mb-1">Firma / Tedarikçi Adı *</label>
              <input
                type="text"
                required
                value={name}
                onChange={(e) => setName(e.target.value)}
                placeholder="Örn: ABC Elektronik Dağıtım"
                className="w-full border border-gray-300 rounded-lg p-2 text-sm bg-white focus:ring-2 focus:ring-indigo-500 outline-none"
              />
            </div>
            <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
              <div>
                <label className="block text-xs font-medium text-gray-700 mb-1">İlgili Kişi</label>
                <input
                  type="text"
                  value={contactPerson}
                  onChange={(e) => setContactPerson(e.target.value)}
                  placeholder="Ali Yılmaz"
                  className="w-full border border-gray-300 rounded-lg p-2 text-sm bg-white focus:ring-2 focus:ring-indigo-500 outline-none"
                />
              </div>
              <div>
                <label className="block text-xs font-medium text-gray-700 mb-1">Telefon</label>
                <input
                  type="text"
                  value={phone}
                  onChange={(e) => setPhone(e.target.value)}
                  placeholder="0212 xxx xx xx"
                  className="w-full border border-gray-300 rounded-lg p-2 text-sm bg-white focus:ring-2 focus:ring-indigo-500 outline-none"
                />
              </div>
              <div>
                <label className="block text-xs font-medium text-gray-700 mb-1">E-posta</label>
                <input
                  type="email"
                  value={email}
                  onChange={(e) => setEmail(e.target.value)}
                  placeholder="info@tedarikci.com"
                  className="w-full border border-gray-300 rounded-lg p-2 text-sm bg-white focus:ring-2 focus:ring-indigo-500 outline-none"
                />
              </div>
            </div>
            <div className="flex justify-end gap-2 pt-1">
              <button
                type="button"
                onClick={() => setShowAddForm(false)}
                className="px-3 py-1.5 border border-gray-300 text-xs text-gray-600 rounded-lg hover:bg-gray-100"
              >
                İptal
              </button>
              <button type="submit" className="px-4 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-medium">
                Kaydet
              </button>
            </div>
          </form>
        ) : (
          <div className="flex-1 overflow-y-auto">
            {loading ? (
              <div className="py-8 text-center text-sm text-gray-500">Yükleniyor...</div>
            ) : suppliers.length === 0 ? (
              <div className="py-8 text-center text-sm text-gray-400">Henüz tedarikçi eklenmemiş.</div>
            ) : (
              <div className="divide-y divide-gray-100">
                {suppliers.map((s) => (
                  <div key={s.id} className="py-3 flex items-center justify-between hover:bg-gray-50 px-2 rounded-lg transition">
                    <div>
                      <div className="font-semibold text-gray-900 text-sm">{s.name}</div>
                      <div className="text-xs text-gray-500 flex items-center gap-3 mt-1">
                        {s.contact_person && <span>Yetkili: {s.contact_person}</span>}
                        {s.phone && (
                          <span className="flex items-center gap-1">
                            <Phone className="w-3 h-3 text-gray-400" />
                            {s.phone}
                          </span>
                        )}
                        {s.email && (
                          <span className="flex items-center gap-1">
                            <Mail className="w-3 h-3 text-gray-400" />
                            {s.email}
                          </span>
                        )}
                      </div>
                    </div>
                    <button
                      onClick={() => handleDelete(s.id)}
                      className="p-1.5 text-gray-300 hover:text-red-600 rounded transition"
                      title="Sil"
                    >
                      <Trash2 className="w-4 h-4" />
                    </button>
                  </div>
                ))}
              </div>
            )}
          </div>
        )}
      </div>
    </div>
  );
};
