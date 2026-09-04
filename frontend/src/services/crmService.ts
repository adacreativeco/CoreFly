import client from '@/api/client';
import { CrmCustomer, CrmDeal, CrmActivity, CrmStats, DealStage } from '@/types/crm';

export const getCrmStats = async (tenantId: string): Promise<CrmStats> => {
  const response = await client.get<{ data: CrmStats }>(`/crm/${tenantId}/stats`);
  return response.data.data;
};

export const getCustomers = async (tenantId: string, search?: string): Promise<CrmCustomer[]> => {
  const url = search ? `/crm/${tenantId}/customers?search=${encodeURIComponent(search)}` : `/crm/${tenantId}/customers`;
  const response = await client.get<{ data: CrmCustomer[] }>(url);
  return response.data.data;
};

export const createCustomer = async (tenantId: string, data: Partial<CrmCustomer>): Promise<CrmCustomer> => {
  const response = await client.post<{ data: CrmCustomer }>(`/crm/${tenantId}/customers`, data);
  return response.data.data;
};

export const deleteCustomer = async (tenantId: string, customerId: string): Promise<void> => {
  await client.delete(`/crm/${tenantId}/customers/${customerId}`);
};

export const getDeals = async (tenantId: string): Promise<CrmDeal[]> => {
  const response = await client.get<{ data: CrmDeal[] }>(`/crm/${tenantId}/deals`);
  return response.data.data;
};

export const createDeal = async (tenantId: string, data: Partial<CrmDeal>): Promise<CrmDeal> => {
  const response = await client.post<{ data: CrmDeal }>(`/crm/${tenantId}/deals`, data);
  return response.data.data;
};

export const updateDealStage = async (tenantId: string, dealId: string, stage: DealStage): Promise<void> => {
  await client.patch(`/crm/${tenantId}/deals/${dealId}/stage`, { stage });
};

export const deleteDeal = async (tenantId: string, dealId: string): Promise<void> => {
  await client.delete(`/crm/${tenantId}/deals/${dealId}`);
};

export const getActivities = async (tenantId: string): Promise<CrmActivity[]> => {
  const response = await client.get<{ data: CrmActivity[] }>(`/crm/${tenantId}/activities`);
  return response.data.data;
};

export const createActivity = async (tenantId: string, data: Partial<CrmActivity>): Promise<void> => {
  await client.post(`/crm/${tenantId}/activities`, data);
};
