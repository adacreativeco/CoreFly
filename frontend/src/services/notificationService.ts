import client from '@/api/client';
import { NotificationItem } from '@/types/notification';

export const notificationService = {
  getNotifications: async (tenantId: string): Promise<NotificationItem[]> => {
    const res = await client.get<{ data: NotificationItem[] }>(`/notifications/${tenantId}`);
    return res.data.data;
  },
  markAsRead: async (tenantId: string, notificationId: string): Promise<void> => {
    await client.patch(`/notifications/${tenantId}/${notificationId}/read`);
  },
  createTest: async (tenantId: string, title: string, message: string): Promise<void> => {
    await client.post(`/notifications/${tenantId}/test`, { title, message });
  },
};
