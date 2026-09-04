import client from '@/api/client';
import { LeaveRequest, Payroll, CompanyTaskItem, InventorySupplier } from '@/types/hrExtensions';

export const hrExtensionService = {
  // Leaves
  getLeaves: async (tenantId: string): Promise<LeaveRequest[]> => {
    const res = await client.get<{ data: LeaveRequest[] }>(`/hr/${tenantId}/leave-requests`);
    return res.data.data;
  },
  createLeave: async (tenantId: string, data: Partial<LeaveRequest>): Promise<void> => {
    await client.post(`/hr/${tenantId}/leave-requests`, data);
  },
  updateLeaveStatus: async (tenantId: string, leaveId: string, status: 'approved' | 'rejected'): Promise<void> => {
    await client.patch(`/hr/${tenantId}/leave-requests/${leaveId}/status`, { status });
  },
  deleteLeave: async (tenantId: string, leaveId: string): Promise<void> => {
    await client.delete(`/hr/${tenantId}/leave-requests/${leaveId}`);
  },

  // Payrolls
  getPayrolls: async (tenantId: string): Promise<Payroll[]> => {
    const res = await client.get<{ data: Payroll[] }>(`/hr/${tenantId}/payrolls`);
    return res.data.data;
  },
  createPayroll: async (tenantId: string, data: Partial<Payroll>): Promise<void> => {
    await client.post(`/hr/${tenantId}/payrolls`, data);
  },
  updatePayrollStatus: async (tenantId: string, payrollId: string, status: 'paid' | 'pending'): Promise<void> => {
    await client.patch(`/hr/${tenantId}/payrolls/${payrollId}/status`, { status });
  },
  deletePayroll: async (tenantId: string, payrollId: string): Promise<void> => {
    await client.delete(`/hr/${tenantId}/payrolls/${payrollId}`);
  },

  // Company Tasks
  getTasks: async (tenantId: string): Promise<CompanyTaskItem[]> => {
    const res = await client.get<{ data: CompanyTaskItem[] }>(`/tasks/${tenantId}`);
    return res.data.data;
  },
  createTask: async (tenantId: string, data: Partial<CompanyTaskItem>): Promise<void> => {
    await client.post(`/tasks/${tenantId}`, data);
  },
  updateTask: async (tenantId: string, taskId: string, data: Partial<CompanyTaskItem>): Promise<void> => {
    await client.patch(`/tasks/${tenantId}/${taskId}`, data);
  },
  deleteTask: async (tenantId: string, taskId: string): Promise<void> => {
    await client.delete(`/tasks/${tenantId}/${taskId}`);
  },

  // Password / Security
  changePassword: async (currentPassword: string, newPassword: string): Promise<void> => {
    await client.post('/auth/change-password', {
      current_password: currentPassword,
      new_password: newPassword,
    });
  },

  // Suppliers
  getSuppliers: async (tenantId: string): Promise<InventorySupplier[]> => {
    const res = await client.get<{ data: InventorySupplier[] }>(`/inventory/${tenantId}/suppliers`);
    return res.data.data;
  },
  createSupplier: async (tenantId: string, data: Partial<InventorySupplier>): Promise<void> => {
    await client.post(`/inventory/${tenantId}/suppliers`, data);
  },
  deleteSupplier: async (tenantId: string, supplierId: string): Promise<void> => {
    await client.delete(`/inventory/${tenantId}/suppliers/${supplierId}`);
  },
};
