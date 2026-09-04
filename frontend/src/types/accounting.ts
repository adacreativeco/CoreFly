export type AccountType = 'cash' | 'bank' | 'customer' | 'supplier';

export interface AccountingAccount {
  id: string;
  tenant_id: string;
  code?: string;
  name: string;
  type: AccountType;
  balance: number;
  currency: string;
  bank_name?: string;
  iban?: string;
  created_at: string;
}

export type InvoiceStatus = 'draft' | 'sent' | 'paid' | 'overdue' | 'cancelled';
export type InvoiceType = 'sale' | 'purchase';

export interface AccountingInvoiceItem {
  id?: string;
  description: string;
  quantity: number;
  unit_price: number;
  tax_rate: number;
  total?: number;
}

export interface AccountingInvoice {
  id: string;
  tenant_id: string;
  account_id?: string;
  account_name?: string;
  number: string;
  type: InvoiceType;
  title: string;
  customer_name?: string;
  issue_date: string;
  due_date?: string;
  subtotal: number;
  tax_total: number;
  total: number;
  currency: string;
  status: InvoiceStatus;
  notes?: string;
  items?: AccountingInvoiceItem[];
  ettn?: string;
  einvoice_type?: 'efatura' | 'earsiv';
  profile_id?: string;
  gib_status_code?: string;
  gib_status_description?: string;
  sent_at?: string;
  integrator?: string;
  created_at: string;
}

export interface AccountingTransaction {
  id: string;
  tenant_id: string;
  account_id: string;
  account_name?: string;
  invoice_id?: string;
  type: 'income' | 'expense' | 'transfer';
  amount: number;
  currency: string;
  date: string;
  category?: string;
  description?: string;
  payment_method: 'cash' | 'bank' | 'credit_card';
  created_at: string;
}

export interface AccountingStats {
  total_income: number;
  total_expense: number;
  net_profit: number;
  total_receivables: number;
  total_cash_balance: number;
  net_balance?: number;
  pending_receivables?: number;
}
