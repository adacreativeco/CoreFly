import client from '@/api/client';
import {
  AccountingAccount,
  AccountingInvoice,
  AccountingTransaction,
  AccountingStats,
  InvoiceStatus,
} from '@/types/accounting';

export const getAccountingStats = async (tenantId: string): Promise<AccountingStats> => {
  const response = await client.get<{ data: AccountingStats }>(`/accounting/${tenantId}/stats`);
  return response.data.data;
};

export const getInvoices = async (
  tenantId: string,
  params?: { search?: string; type?: string; status?: string }
): Promise<AccountingInvoice[]> => {
  const query = new URLSearchParams();
  if (params?.search) query.append('search', params.search);
  if (params?.type) query.append('type', params.type);
  if (params?.status) query.append('status', params.status);

  const response = await client.get<{ data: AccountingInvoice[] }>(
    `/accounting/${tenantId}/invoices?${query.toString()}`
  );
  return response.data.data;
};

export const createInvoice = async (
  tenantId: string,
  data: Partial<AccountingInvoice>
): Promise<AccountingInvoice> => {
  const response = await client.post<{ data: AccountingInvoice }>(`/accounting/${tenantId}/invoices`, data);
  return response.data.data;
};

export const updateInvoiceStatus = async (
  tenantId: string,
  invoiceId: string,
  status: InvoiceStatus
): Promise<void> => {
  await client.patch(`/accounting/${tenantId}/invoices/${invoiceId}/status`, { status });
};

export const deleteInvoice = async (tenantId: string, invoiceId: string): Promise<void> => {
  await client.delete(`/accounting/${tenantId}/invoices/${invoiceId}`);
};

export const getTransactions = async (tenantId: string): Promise<AccountingTransaction[]> => {
  const response = await client.get<{ data: AccountingTransaction[] }>(`/accounting/${tenantId}/transactions`);
  return response.data.data;
};

export const createTransaction = async (
  tenantId: string,
  data: Partial<AccountingTransaction>
): Promise<void> => {
  await client.post(`/accounting/${tenantId}/transactions`, data);
};

export const getAccounts = async (tenantId: string): Promise<AccountingAccount[]> => {
  const response = await client.get<{ data: AccountingAccount[] }>(`/accounting/${tenantId}/accounts`);
  return response.data.data;
};

export const createAccount = async (
  tenantId: string,
  data: Partial<AccountingAccount>
): Promise<AccountingAccount> => {
  const response = await client.post<{ data: AccountingAccount }>(`/accounting/${tenantId}/accounts`, data);
  return response.data.data;
};

export const sendEInvoice = async (
  tenantId: string,
  invoiceId: string
): Promise<{ success: boolean; data: { ettn: string; einvoice_type: string; profile_id: string; gib_status_code: string; gib_status_description: string; envelope_id: string } }> => {
  const response = await client.post(`/accounting/${tenantId}/invoices/${invoiceId}/send-einvoice`);
  return response.data;
};

export const getUblXmlUrl = (tenantId: string, invoiceId: string): string => {
  return `/api/accounting/${tenantId}/invoices/${invoiceId}/ubl-xml`;
};

export const getPreviewHtmlUrl = (tenantId: string, invoiceId: string): string => {
  return `/api/accounting/${tenantId}/invoices/${invoiceId}/preview-html`;
};

export const checkEInvoiceStatus = async (
  tenantId: string,
  invoiceId: string
): Promise<{ success: boolean; data: { ettn: string; gib_status_code: string; gib_status_description: string } }> => {
  const response = await client.post(`/accounting/${tenantId}/invoices/${invoiceId}/check-einvoice-status`);
  return response.data;
};
