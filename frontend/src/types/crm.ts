export interface CrmCustomer {
  id: string;
  tenant_id: string;
  name: string;
  email?: string;
  phone?: string;
  company?: string;
  address?: string;
  city?: string;
  country?: string;
  status: 'lead' | 'active' | 'passive';
  notes?: string;
  created_at: string;
}

export type DealStage = 'lead' | 'proposal' | 'negotiation' | 'won' | 'lost';

export interface CrmDeal {
  id: string;
  tenant_id: string;
  customer_id: string;
  customer_name?: string;
  customer_company?: string;
  title: string;
  value: number;
  currency: string;
  stage: DealStage;
  probability: number;
  expected_close_date?: string;
  notes?: string;
  created_at: string;
}

export interface CrmActivity {
  id: string;
  tenant_id: string;
  related_to_type: 'customer' | 'deal';
  related_to_id: string;
  type: 'call' | 'meeting' | 'email' | 'note';
  subject: string;
  description?: string;
  date: string;
  status: 'planned' | 'completed';
  created_at: string;
}

export interface CrmStats {
  total_customers: number;
  total_deals: number;
  pipeline_value: number;
  won_value: number;
}
