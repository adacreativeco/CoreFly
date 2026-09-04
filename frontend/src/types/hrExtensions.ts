export interface LeaveRequest {
  id: string;
  tenant_id: string;
  employee_id: string;
  first_name?: string;
  last_name?: string;
  employee_email?: string;
  department_name?: string;
  leave_type: string;
  start_date: string;
  end_date: string;
  days: number;
  reason?: string;
  status: 'pending' | 'approved' | 'rejected';
  created_at: string;
}

export interface Payroll {
  id: string;
  tenant_id: string;
  employee_id: string;
  first_name?: string;
  last_name?: string;
  employee_email?: string;
  department_name?: string;
  period: string;
  base_salary: number;
  bonus: number;
  deductions: number;
  net_salary: number;
  status: 'draft' | 'pending' | 'paid';
  payment_date?: string;
  created_at: string;
}

export interface CompanyTaskItem {
  id: string;
  tenant_id: string;
  title: string;
  description?: string;
  assigned_to?: string;
  assigned_user_name?: string;
  priority: 'low' | 'medium' | 'high' | 'urgent';
  status: 'todo' | 'in_progress' | 'review' | 'done';
  due_date?: string;
  created_at: string;
}

export type CompanyTask = CompanyTaskItem;

export interface InventorySupplier {
  id: string;
  tenant_id: string;
  name: string;
  contact_person?: string;
  phone?: string;
  email?: string;
  address?: string;
  tax_number?: string;
  created_at: string;
}
