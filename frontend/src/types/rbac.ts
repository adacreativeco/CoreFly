export interface RoleItem {
  id: string;
  tenant_id: string;
  name: string;
  description?: string;
  is_system: number | boolean;
  permissions?: string[];
  created_at: string;
}

export interface AuditLogItem {
  id: string;
  tenant_id: string;
  user_id?: string;
  user_name?: string;
  user_email?: string;
  action: string;
  entity_type: string;
  entity_id: string;
  details?: string;
  ip_address?: string;
  created_at: string;
}
