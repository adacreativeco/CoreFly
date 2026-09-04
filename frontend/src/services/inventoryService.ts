import client from '@/api/client';
import { InventoryProduct, InventoryCategory, InventoryMovement, InventoryStats, MovementType } from '@/types/inventory';

export const getInventoryStats = async (tenantId: string): Promise<InventoryStats> => {
  const response = await client.get<{ data: InventoryStats }>(`/inventory/${tenantId}/stats`);
  return response.data.data;
};

export const getProducts = async (
  tenantId: string,
  params?: { search?: string; low_stock?: boolean; category_id?: string }
): Promise<InventoryProduct[]> => {
  const query = new URLSearchParams();
  if (params?.search) query.append('search', params.search);
  if (params?.low_stock) query.append('low_stock', 'true');
  if (params?.category_id) query.append('category_id', params.category_id);

  const response = await client.get<{ data: InventoryProduct[] }>(
    `/inventory/${tenantId}/products?${query.toString()}`
  );
  return response.data.data;
};

export const createProduct = async (
  tenantId: string,
  data: Partial<InventoryProduct>
): Promise<InventoryProduct> => {
  const response = await client.post<{ data: InventoryProduct }>(`/inventory/${tenantId}/products`, data);
  return response.data.data;
};

export const adjustStock = async (
  tenantId: string,
  productId: string,
  data: { type: MovementType; quantity: number; reason: string }
): Promise<void> => {
  await client.post(`/inventory/${tenantId}/products/${productId}/adjust`, data);
};

export const deleteProduct = async (tenantId: string, productId: string): Promise<void> => {
  await client.delete(`/inventory/${tenantId}/products/${productId}`);
};

export const getMovements = async (tenantId: string): Promise<InventoryMovement[]> => {
  const response = await client.get<{ data: InventoryMovement[] }>(`/inventory/${tenantId}/movements`);
  return response.data.data;
};

export const getCategories = async (tenantId: string): Promise<InventoryCategory[]> => {
  const response = await client.get<{ data: InventoryCategory[] }>(`/inventory/${tenantId}/categories`);
  return response.data.data;
};

export const createCategory = async (
  tenantId: string,
  data: { name: string; description?: string }
): Promise<InventoryCategory> => {
  const response = await client.post<{ data: InventoryCategory }>(`/inventory/${tenantId}/categories`, data);
  return response.data.data;
};
