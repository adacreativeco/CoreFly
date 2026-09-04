export interface NotificationItem {
  id: string;
  tenant_id: string;
  user_id?: string;
  type: string;
  title: string;
  message: string;
  read: boolean | number;
  data?: any;
  created_at: string;
}
