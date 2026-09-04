import client from '@/api/client';
import { RoleItem, AuditLogItem } from '@/types/rbac';

export const rbacService = {
  getRoles: async (tenantId: string): Promise<RoleItem[]> => {
    const res = await client.get<{ data: RoleItem[] }>(`/roles/${tenantId}`);
    return res.data.data;
  },
  createRole: async (tenantId: string, data: { name: string; description?: string; permissions?: string[] }): Promise<void> => {
    await client.post(`/roles/${tenantId}`, data);
  },
  updateRolePermissions: async (tenantId: string, roleId: string, permissions: string[]): Promise<void> => {
    await client.post(`/roles/${tenantId}/${roleId}/permissions`, { permissions });
  },
  deleteRole: async (tenantId: string, roleId: string): Promise<void> => {
    await client.delete(`/roles/${tenantId}/${roleId}`);
  },

  getAuditLogs: async (tenantId: string): Promise<AuditLogItem[]> => {
    const res = await client.get<{ data: AuditLogItem[] }>(`/audit-logs/${tenantId}`);
    return res.data.data;
  },
};
