import client from '@/api/client';
import {
  FieldTask,
  TaskStatus,
  Donation,
  DonationStats,
  PoliticsVolunteer,
  PoliticsStats,
  TenantItem,
  RootStats,
} from '@/types/specialized';

// --- FIELD TASKS ---

export const getFieldTasks = async (tenantId: string, search?: string): Promise<FieldTask[]> => {
  const url = search ? `/field/${tenantId}/tasks?search=${encodeURIComponent(search)}` : `/field/${tenantId}/tasks`;
  const response = await client.get<{ data: FieldTask[] }>(url);
  return response.data.data;
};

export const createFieldTask = async (tenantId: string, data: Partial<FieldTask>): Promise<FieldTask> => {
  const response = await client.post<{ data: FieldTask }>(`/field/${tenantId}/tasks`, data);
  return response.data.data;
};

export const updateFieldTaskStatus = async (tenantId: string, taskId: string, status: TaskStatus): Promise<void> => {
  await client.patch(`/field/${tenantId}/tasks/${taskId}/status`, { status });
};

export const deleteFieldTask = async (tenantId: string, taskId: string): Promise<void> => {
  await client.delete(`/field/${tenantId}/tasks/${taskId}`);
};

// --- DONATIONS ---

export const getDonations = async (tenantId: string, search?: string): Promise<Donation[]> => {
  const url = search ? `/donations/${tenantId}?search=${encodeURIComponent(search)}` : `/donations/${tenantId}`;
  const response = await client.get<{ data: Donation[] }>(url);
  return response.data.data;
};

export const createDonation = async (tenantId: string, data: Partial<Donation>): Promise<Donation> => {
  const response = await client.post<{ data: Donation }>(`/donations/${tenantId}`, data);
  return response.data.data;
};

export const getDonationStats = async (tenantId: string): Promise<DonationStats> => {
  const response = await client.get<{ data: DonationStats }>(`/donations/${tenantId}/stats`);
  return response.data.data;
};

// --- POLITICS ---

export const getPoliticsVolunteers = async (tenantId: string, search?: string): Promise<PoliticsVolunteer[]> => {
  const url = search ? `/politics/${tenantId}/volunteers?search=${encodeURIComponent(search)}` : `/politics/${tenantId}/volunteers`;
  const response = await client.get<{ data: PoliticsVolunteer[] }>(url);
  return response.data.data;
};

export const createPoliticsVolunteer = async (tenantId: string, data: Partial<PoliticsVolunteer>): Promise<PoliticsVolunteer> => {
  const response = await client.post<{ data: PoliticsVolunteer }>(`/politics/${tenantId}/volunteers`, data);
  return response.data.data;
};

export const getPoliticsStats = async (tenantId: string): Promise<PoliticsStats> => {
  const response = await client.get<{ data: PoliticsStats }>(`/politics/${tenantId}/stats`);
  return response.data.data;
};

// --- ROOT SUPERADMIN ---

export const getTenants = async (): Promise<TenantItem[]> => {
  const response = await client.get<{ data: TenantItem[] }>('/root/tenants');
  return response.data.data;
};

export const createTenant = async (name: string): Promise<TenantItem> => {
  const response = await client.post<{ data: TenantItem }>('/root/tenants', { name });
  return response.data.data;
};

export const getRootStats = async (): Promise<RootStats> => {
  const response = await client.get<{ data: RootStats }>('/root/stats');
  return response.data.data;
};

export const specializedService = {
  getFieldTasks,
  createFieldTask,
  updateFieldTaskStatus,
  deleteFieldTask,
  getDonations,
  createDonation,
  getDonationStats,
  getPoliticsVolunteers,
  createPoliticsVolunteer,
  getPoliticsStats,
  getTenants,
  createTenant,
  getRootStats,
};

