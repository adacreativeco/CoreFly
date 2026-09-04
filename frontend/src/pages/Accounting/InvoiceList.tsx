import React, { useState } from 'react';
import { AccountingInvoice, InvoiceStatus, InvoiceType } from '@/types/accounting';
import {
  Plus,
  Search,
  FileText,
  CheckCircle,
  Clock,
  Trash2,
  XCircle,
  Send,
  Eye,
  Download,
  RefreshCw,
  Printer,
  X,
  Building2,
  ShieldCheck,
} from 'lucide-react';
import { Pagination } from '@/components/common/Pagination';

interface InvoiceListProps {
  tenantId?: string;
  invoices: AccountingInvoice[];
  onCreateInvoice: (data: Partial<AccountingInvoice>) => Promise<void>;
  onUpdateStatus: (invoiceId: string, status: InvoiceStatus) => Promise<void>;
  onDeleteInvoice: (invoiceId: string) => Promise<void>;
  onSendEInvoice?: (invoiceId: string) => Promise<void>;
  onCheckStatus?: (invoiceId: string) => Promise<void>;
  onSearch: (query: string) => void;
  onFilterType: (type: string) => void;
  onFilterStatus: (status: string) => void;
}

export const InvoiceList: React.FC<InvoiceListProps> = ({
  tenantId,
  invoices,
  onCreateInvoice,
  onUpdateStatus,
  onDeleteInvoice,
  onSendEInvoice,
  onCheckStatus,
  onSearch,
  onFilterType,
  onFilterStatus,
}) => {
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [previewInvoiceId, setPreviewInvoiceId] = useState<string | null>(null);
  const [sendingId, setSendingId] = useState<string | null>(null);
  const [checkingId, setCheckingId] = useState<string | null>(null);
  const [currentPage, setCurrentPage] = useState(1);
  const itemsPerPage = 8;

  const paginatedInvoices = invoices.slice((currentPage - 1) * itemsPerPage, currentPage * itemsPerPage);
  const [form, setForm] = useState({
    title: '',
    customer_name: '',
    type: 'sale' as InvoiceType,
    issue_date: new Date().toISOString().split('T')[0],
    due_date: '',
    currency: 'TRY',
    item_description: 'Danışmanlık / Hizmet Bedeli',
    item_quantity: 1,
    item_unit_price: 0,
    item_tax_rate: 20,
    notes: '',
  });

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!form.title) return;

    await onCreateInvoice({
      title: form.title,
      customer_name: form.customer_name,
      type: form.type,
      issue_date: form.issue_date,
      due_date: form.due_date || undefined,
      currency: form.currency,
      notes: form.notes,
      items: [
        {
          description: form.item_description,
          quantity: form.item_quantity,
          unit_price: form.item_unit_price,
          tax_rate: form.item_tax_rate,
        },
      ],
    });

    setIsModalOpen(false);
    setForm({
      title: '',
      customer_name: '',
      type: 'sale',
      issue_date: new Date().toISOString().split('T')[0],
      due_date: '',
      currency: 'TRY',
      item_description: 'Danışmanlık / Hizmet Bedeli',
      item_quantity: 1,
      item_unit_price: 0,
      item_tax_rate: 20,
      notes: '',
    });
  };

  return (
    <div className="space-y-4">
      {/* Controls Bar */}
      <div className="flex flex-col sm:flex-row justify-between gap-3 items-stretch sm:items-center">
        <div className="flex flex-1 flex-col sm:flex-row gap-3">
          <div className="relative flex-1">
            <Search className="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" />
            <input
              type="text"
              placeholder="Fatura no, başlık veya müşteri ara..."
              onChange={(e) => onSearch(e.target.value)}
              className="w-full pl-9 pr-4 py-2 border border-gray-200 dark:border-gray-700 rounded-lg text-sm bg-white dark:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-indigo-500"
            />
          </div>

          <select
            onChange={(e) => onFilterType(e.target.value)}
            className="px-3 py-2 border border-gray-200 dark:border-gray-700 rounded-lg text-sm bg-white dark:bg-gray-800 focus:outline-none"
          >
            <option value="">Tüm Fatura Tipleri</option>
            <option value="sale">Satış Faturaları</option>
            <option value="purchase">Alış Faturaları</option>
          </select>

          <select
            onChange={(e) => onFilterStatus(e.target.value)}
            className="px-3 py-2 border border-gray-200 dark:border-gray-700 rounded-lg text-sm bg-white dark:bg-gray-800 focus:outline-none"
          >
            <option value="">Tüm Durumlar</option>
            <option value="draft">Taslak</option>
            <option value="sent">Gönderildi (Açık)</option>
            <option value="paid">Ödendi / Tahsil Edildi</option>
            <option value="overdue">Vadesi Geçmiş</option>
            <option value="cancelled">İptal Edilmiş</option>
          </select>
        </div>

        <button
          onClick={() => setIsModalOpen(true)}
          className="flex items-center justify-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors shadow-sm"
        >
          <Plus className="w-4 h-4" />
          Yeni Fatura Kes
        </button>
      </div>

      {/* Table */}
      <div className="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full text-left text-sm">
            <thead className="bg-gray-50 dark:bg-gray-900/50 text-gray-500 font-medium border-b border-gray-200 dark:border-gray-700">
              <tr>
                <th className="px-6 py-3">Fatura No & Başlık</th>
                <th className="px-6 py-3">Müşteri / Cari</th>
                <th className="px-6 py-3">Tarih / Vade</th>
                <th className="px-6 py-3">Toplam Tutar</th>
                <th className="px-6 py-3">Durum</th>
                <th className="px-6 py-3 text-right">İşlemler</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-200 dark:divide-gray-700">
              {invoices.length === 0 ? (
                <tr>
                  <td colSpan={6} className="text-center py-12 text-gray-400">
                    <FileText className="w-8 h-8 mx-auto mb-2 opacity-40" />
                    Kayıtlı fatura bulunamadı.
                  </td>
                </tr>
              ) : (
                paginatedInvoices.map((inv) => (
                  <tr key={inv.id} className="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                    <td className="px-6 py-4">
                      <div className="font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <span className={`text-[10px] uppercase font-bold px-1.5 py-0.5 rounded ${
                          inv.type === 'sale' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800'
                        }`}>
                          {inv.type === 'sale' ? 'Satış' : 'Alış'}
                        </span>
                        {inv.title}
                      </div>
                      <div className="flex items-center gap-2 mt-1">
                        <span className="text-xs text-gray-400 font-mono">{inv.number}</span>
                        {inv.ettn ? (
                          <span className="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                            <ShieldCheck className="w-3 h-3" />
                            GİB {inv.einvoice_type === 'efatura' ? 'e-Fatura' : 'e-Arşiv'} (Durum: {inv.gib_status_code || '1300'})
                          </span>
                        ) : inv.type === 'sale' ? (
                          <span className="text-[10px] px-1.5 py-0.5 rounded bg-amber-50 text-amber-700 dark:bg-amber-950/30 dark:text-amber-300">
                            GİB Gönderilmedi
                          </span>
                        ) : null}
                      </div>
                      {inv.ettn && (
                        <div className="text-[10px] text-gray-400 font-mono mt-0.5 truncate max-w-xs" title={`ETTN: ${inv.ettn}`}>
                          ETTN: {inv.ettn}
                        </div>
                      )}
                    </td>
                    <td className="px-6 py-4 text-xs font-medium text-gray-700 dark:text-gray-300">
                      {inv.customer_name || 'Genel Cari'}
                    </td>
                    <td className="px-6 py-4 text-xs text-gray-500">
                      <div>Düzenleme: {inv.issue_date}</div>
                      {inv.due_date && <div className="text-gray-400">Vade: {inv.due_date}</div>}
                    </td>
                    <td className="px-6 py-4 font-bold text-gray-900 dark:text-white">
                      {Number(inv.total).toLocaleString('tr-TR')} {inv.currency}
                      <div className="text-[11px] text-gray-400 font-normal">KDV Dahil</div>
                    </td>
                    <td className="px-6 py-4">
                      {inv.status === 'paid' ? (
                        <span className="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-200">
                          <CheckCircle className="w-3.5 h-3.5" /> Ödendi
                        </span>
                      ) : inv.status === 'sent' ? (
                        <span className="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-200">
                          <Clock className="w-3.5 h-3.5" /> Açık / Bekliyor
                        </span>
                      ) : inv.status === 'overdue' ? (
                        <span className="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-rose-100 text-rose-800 dark:bg-rose-900/50 dark:text-rose-200">
                          Vadesi Geçti
                        </span>
                      ) : (
                        <span className="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300">
                          {inv.status === 'draft' ? 'Taslak' : 'İptal'}
                        </span>
                      )}
                    </td>
                    <td className="px-6 py-4 text-right">
                      <div className="flex items-center justify-end gap-1.5 flex-wrap">
                        {/* GİB E-Invoice Actions */}
                        {inv.ettn ? (
                          <>
                            <button
                              type="button"
                              onClick={() => setPreviewInvoiceId(inv.id)}
                              className="inline-flex items-center gap-1 text-xs bg-indigo-50 hover:bg-indigo-100 text-indigo-700 dark:bg-indigo-950/40 dark:text-indigo-300 px-2 py-1 rounded transition-colors"
                              title="Resmi GİB Görsel Fatura Önizle"
                            >
                              <Eye className="w-3.5 h-3.5" />
                              Görsel
                            </button>

                            {tenantId && (
                              <a
                                href={`/api/accounting/${tenantId}/invoices/${inv.id}/ubl-xml`}
                                target="_blank"
                                rel="noreferrer"
                                download={`${inv.number || 'gib-fatura'}.xml`}
                                className="inline-flex items-center gap-1 text-xs bg-gray-100 hover:bg-gray-200 text-gray-700 dark:bg-gray-800 dark:text-gray-300 px-2 py-1 rounded transition-colors"
                                title="GİB UBL-TR 2.1 XML İndir"
                              >
                                <Download className="w-3.5 h-3.5" />
                                XML
                              </a>
                            )}

                            {onCheckStatus && (
                              <button
                                type="button"
                                disabled={checkingId === inv.id}
                                onClick={async () => {
                                  setCheckingId(inv.id);
                                  try {
                                    await onCheckStatus(inv.id);
                                  } finally {
                                    setCheckingId(null);
                                  }
                                }}
                                className="text-gray-400 hover:text-indigo-600 p-1 rounded transition-colors"
                                title="GİB Durumunu Sorgula"
                              >
                                <RefreshCw className={`w-3.5 h-3.5 ${checkingId === inv.id ? 'animate-spin text-indigo-600' : ''}`} />
                              </button>
                            )}
                          </>
                        ) : inv.type === 'sale' && onSendEInvoice ? (
                          <button
                            type="button"
                            disabled={sendingId === inv.id}
                            onClick={async () => {
                              if (!confirm(`"${inv.title}" faturası Gelir İdaresi Başkanlığı'na (GİB) iletilecektir. Onaylıyor musunuz?`)) return;
                              setSendingId(inv.id);
                              try {
                                await onSendEInvoice(inv.id);
                              } finally {
                                setSendingId(null);
                              }
                            }}
                            className="inline-flex items-center gap-1 text-xs bg-indigo-600 hover:bg-indigo-700 text-white font-medium px-2.5 py-1 rounded-md transition-colors shadow-sm disabled:opacity-50"
                            title="GİB e-Fatura / e-Arşiv Olarak Gönder"
                          >
                            <Send className={`w-3.5 h-3.5 ${sendingId === inv.id ? 'animate-pulse' : ''}`} />
                            {sendingId === inv.id ? 'İletiliyor...' : 'GİB Gönder'}
                          </button>
                        ) : null}

                        {inv.status !== 'paid' && (
                          <button
                            type="button"
                            onClick={() => onUpdateStatus(inv.id, 'paid')}
                            className="text-xs bg-emerald-50 hover:bg-emerald-100 text-emerald-700 px-2 py-1 rounded transition-colors"
                            title="Ödendi Olarak İşaretle"
                          >
                            Ödendi Yap
                          </button>
                        )}
                        {inv.status !== 'cancelled' && (
                          <button
                            type="button"
                            onClick={() => onUpdateStatus(inv.id, 'cancelled')}
                            className="text-xs text-gray-400 hover:text-gray-600 p-1"
                            title="İptal Et"
                          >
                            <XCircle className="w-4 h-4" />
                          </button>
                        )}
                        <button
                          type="button"
                          onClick={() => onDeleteInvoice(inv.id)}
                          className="text-gray-400 hover:text-rose-600 p-1 rounded transition-colors ml-1"
                          title="Faturayı Sil"
                        >
                          <Trash2 className="w-4 h-4" />
                        </button>
                      </div>
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>

        {/* Pagination Component */}
        <Pagination
          currentPage={currentPage}
          totalItems={invoices.length}
          itemsPerPage={itemsPerPage}
          onPageChange={setCurrentPage}
        />
      </div>

      {/* New Invoice Modal */}
      {isModalOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
          <div className="bg-white dark:bg-gray-800 rounded-2xl w-full max-w-lg p-6 shadow-xl border border-gray-200 dark:border-gray-700 max-h-[90vh] overflow-y-auto">
            <h3 className="text-lg font-bold text-gray-900 dark:text-white mb-4">
              Yeni Fatura Oluştur
            </h3>
            <form onSubmit={handleSubmit} className="space-y-3">
              <div>
                <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                  Fatura Açıklaması / Başlığı *
                </label>
                <input
                  type="text"
                  required
                  value={form.title}
                  onChange={(e) => setForm({ ...form, title: e.target.value })}
                  placeholder="Örn: 2026 Q3 Yazılım Bakım Bedeli"
                  className="w-full px-3 py-2 border rounded-lg text-sm bg-gray-50 dark:bg-gray-700 border-gray-300 dark:border-gray-600"
                />
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Fatura Türü
                  </label>
                  <select
                    value={form.type}
                    onChange={(e) => setForm({ ...form, type: e.target.value as any })}
                    className="w-full px-3 py-2 border rounded-lg text-sm bg-gray-50 dark:bg-gray-700 border-gray-300 dark:border-gray-600"
                  >
                    <option value="sale">Satış Faturası (Gelir)</option>
                    <option value="purchase">Alış Faturası (Gider)</option>
                  </select>
                </div>
                <div>
                  <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Müşteri / Cari Adı
                  </label>
                  <input
                    type="text"
                    value={form.customer_name}
                    onChange={(e) => setForm({ ...form, customer_name: e.target.value })}
                    placeholder="Örn: Turkcell A.Ş."
                    className="w-full px-3 py-2 border rounded-lg text-sm bg-gray-50 dark:bg-gray-700 border-gray-300 dark:border-gray-600"
                  />
                </div>
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Fatura Tarihi *
                  </label>
                  <input
                    type="date"
                    required
                    value={form.issue_date}
                    onChange={(e) => setForm({ ...form, issue_date: e.target.value })}
                    className="w-full px-3 py-2 border rounded-lg text-sm bg-gray-50 dark:bg-gray-700 border-gray-300 dark:border-gray-600"
                  />
                </div>
                <div>
                  <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Son Ödeme (Vade) Tarihi
                  </label>
                  <input
                    type="date"
                    value={form.due_date}
                    onChange={(e) => setForm({ ...form, due_date: e.target.value })}
                    className="w-full px-3 py-2 border rounded-lg text-sm bg-gray-50 dark:bg-gray-700 border-gray-300 dark:border-gray-600"
                  />
                </div>
              </div>

              <div className="p-3 bg-gray-50 dark:bg-gray-750 rounded-xl border border-gray-200 dark:border-gray-700 space-y-2">
                <div className="text-xs font-bold text-gray-700 dark:text-gray-200">Hizmet / Kalem Bilgisi</div>
                <div>
                  <input
                    type="text"
                    value={form.item_description}
                    onChange={(e) => setForm({ ...form, item_description: e.target.value })}
                    placeholder="Hizmet veya ürün açıklaması"
                    className="w-full px-3 py-1.5 border rounded-lg text-sm bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600"
                  />
                </div>
                <div className="grid grid-cols-3 gap-2">
                  <div>
                    <label className="text-[11px] text-gray-500">Miktar</label>
                    <input
                      type="number"
                      min="1"
                      value={form.item_quantity}
                      onChange={(e) => setForm({ ...form, item_quantity: parseFloat(e.target.value) || 1 })}
                      className="w-full px-2 py-1 border rounded text-sm bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600"
                    />
                  </div>
                  <div>
                    <label className="text-[11px] text-gray-500">Birim Fiyat (₺)</label>
                    <input
                      type="number"
                      min="0"
                      step="0.01"
                      value={form.item_unit_price}
                      onChange={(e) => setForm({ ...form, item_unit_price: parseFloat(e.target.value) || 0 })}
                      className="w-full px-2 py-1 border rounded text-sm bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600"
                    />
                  </div>
                  <div>
                    <label className="text-[11px] text-gray-500">KDV Oranı (%)</label>
                    <select
                      value={form.item_tax_rate}
                      onChange={(e) => setForm({ ...form, item_tax_rate: parseFloat(e.target.value) || 20 })}
                      className="w-full px-2 py-1 border rounded text-sm bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600"
                    >
                      <option value="0">%0</option>
                      <option value="1">%1</option>
                      <option value="10">%10</option>
                      <option value="20">%20</option>
                    </select>
                  </div>
                </div>
              </div>

              <div>
                <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                  Fatura Notları
                </label>
                <textarea
                  rows={2}
                  value={form.notes}
                  onChange={(e) => setForm({ ...form, notes: e.target.value })}
                  placeholder="Banka IBAN vb. açıklamalar..."
                  className="w-full px-3 py-2 border rounded-lg text-sm bg-gray-50 dark:bg-gray-700 border-gray-300 dark:border-gray-600"
                />
              </div>

              <div className="flex justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                <button
                  type="button"
                  onClick={() => setIsModalOpen(false)}
                  className="px-4 py-2 text-sm text-gray-600 hover:text-gray-800 dark:text-gray-400"
                >
                  İptal
                </button>
                <button
                  type="submit"
                  className="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium transition-colors"
                >
                  Faturayı Kaydet ve Kes
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* Official GİB E-Invoice Visual HTML Preview Modal */}
      {previewInvoiceId && tenantId && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
          <div className="bg-white dark:bg-gray-900 rounded-2xl w-full max-w-4xl h-[90vh] shadow-2xl border border-gray-200 dark:border-gray-800 flex flex-col overflow-hidden">
            {/* Modal Header */}
            <div className="px-6 py-4 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between bg-gray-50 dark:bg-gray-800/60">
              <div className="flex items-center gap-2">
                <ShieldCheck className="w-5 h-5 text-emerald-600" />
                <h3 className="font-bold text-gray-900 dark:text-white">
                  Resmi Gelir İdaresi Başkanlığı (GİB) Fatura Görüntüsü
                </h3>
              </div>
              <div className="flex items-center gap-2">
                <button
                  type="button"
                  onClick={() => {
                    const iframe = document.getElementById('einvoice-preview-frame') as HTMLIFrameElement;
                    if (iframe && iframe.contentWindow) {
                      iframe.contentWindow.print();
                    }
                  }}
                  className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-50 transition-colors shadow-sm"
                >
                  <Printer className="w-3.5 h-3.5" />
                  Yazdır
                </button>
                <a
                  href={`/api/accounting/${tenantId}/invoices/${previewInvoiceId}/ubl-xml`}
                  target="_blank"
                  rel="noreferrer"
                  download="gib-ubl-fatura.xml"
                  className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium bg-indigo-50 dark:bg-indigo-950/50 text-indigo-700 dark:text-indigo-300 hover:bg-indigo-100 transition-colors"
                >
                  <Download className="w-3.5 h-3.5" />
                  UBL XML
                </a>
                <button
                  type="button"
                  onClick={() => setPreviewInvoiceId(null)}
                  className="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 p-1.5 rounded-lg"
                >
                  <X className="w-5 h-5" />
                </button>
              </div>
            </div>

            {/* Modal Body: Iframe */}
            <div className="flex-1 w-full bg-gray-100 dark:bg-gray-950 p-2 sm:p-4 overflow-hidden">
              <iframe
                id="einvoice-preview-frame"
                src={`/api/accounting/${tenantId}/invoices/${previewInvoiceId}/preview-html`}
                className="w-full h-full rounded-xl border border-gray-300 dark:border-gray-800 shadow bg-white"
                title="Resmi GİB Fatura Önizlemesi"
              />
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

