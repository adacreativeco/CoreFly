export type TaskStatus = 'planned' | 'in_progress' | 'completed' | 'cancelled';

export interface FieldTask {
  id: string;
  tenant_id: string;
  title: string;
  description?: string;
  team_name?: string;
  location?: string;
  status: TaskStatus;
  priority: 'low' | 'medium' | 'high' | 'urgent';
  assigned_to?: string;
  task_date?: string;
  created_at: string;
}

export interface Donation {
  id: string;
  tenant_id: string;
  donor_name: string;
  donor_email?: string;
  donor_phone?: string;
  amount: number;
  currency: string;
  campaign_name: string;
  payment_method: 'bank' | 'credit_card' | 'cash';
  notes?: string;
  created_at: string;
}

export interface DonationStats {
  total_amount: number;
  total_donors: number;
  total_donations: number;
}

export interface PoliticsVolunteer {
  id: string;
  tenant_id: string;
  full_name: string;
  phone?: string;
  city?: string;
  district?: string;
  neighborhood?: string;
  ballot_box_number?: string;
  role: 'member' | 'ballot_officer' | 'coordinator' | 'observer';
  notes?: string;
  created_at: string;
}

export interface PoliticsStats {
  total_members: number;
  ballot_officers: number;
  covered_boxes: number;
}

export interface TenantItem {
  id: string;
  name: string;
  status: string;
  user_count?: number;
  created_at: string;
}

export interface RootStats {
  total_tenants: number;
  active_tenants?: number;
  total_users: number;
}
