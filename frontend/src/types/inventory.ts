export interface InventoryCategory {
  id: string;
  tenant_id: string;
  name: string;
  description?: string;
  created_at: string;
}

export interface InventoryProduct {
  id: string;
  tenant_id: string;
  category_id?: string;
  category_name?: string;
  name: string;
  sku?: string;
  barcode?: string;
  description?: string;
  quantity: number;
  min_quantity: number;
  unit: string;
  unit_cost: number;
  unit_price: number;
  location?: string;
  status: 'active' | 'inactive' | 'discontinued';
  created_at: string;
}

export type MovementType = 'in' | 'out' | 'adjustment';

export interface InventoryMovement {
  id: string;
  tenant_id: string;
  product_id: string;
  product_name?: string;
  product_sku?: string;
  product_unit?: string;
  movement_type: MovementType;
  quantity: number;
  previous_quantity: number;
  new_quantity: number;
  reference_number?: string;
  reason?: string;
  created_at: string;
}

export interface InventoryStats {
  total_products: number;
  total_quantity: number;
  total_value: number;
  low_stock_count: number;
}
