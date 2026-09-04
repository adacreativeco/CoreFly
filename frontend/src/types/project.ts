export interface Project {
  id: string;
  tenant_id: string;
  code: string | null;
  name: string;
  description: string | null;
  type: 'internal' | 'customer' | 'r_and_d';
  status: 'planning' | 'active' | 'on_hold' | 'completed' | 'cancelled';
  priority: 'low' | 'medium' | 'high' | 'urgent';
  start_date: string | null;
  end_date: string | null;
  budget: number | null;
  currency: string;
  created_by: string;
  created_at: string;
  updated_at: string;
}

export interface CreateProjectData {
  name: string;
  code?: string;
  description?: string;
  type?: string;
  status?: string;
  priority?: string;
  start_date?: string;
  end_date?: string;
  budget?: number;
  currency?: string;
}

export interface Task {
  id: string;
  project_id: string;
  title: string;
  description: string | null;
  status: 'todo' | 'in_progress' | 'review' | 'done';
  priority: 'low' | 'medium' | 'high' | 'critical';
  assignee_id: string | null;
  due_date: string | null;
  created_at: string;
  updated_at: string;
}
