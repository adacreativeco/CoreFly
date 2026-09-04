export interface Employee {
  id: string;
  tenant_id: string;
  user_id: string;
  full_name?: string;
  email?: string;
  department_name?: string;
  position_title?: string;
  employee_number: string | null;
  hire_date: string;
  termination_date: string | null;
  department_id: string | null;
  position_id: string | null;
  manager_id: string | null;
  employment_type: 'full_time' | 'part_time' | 'contract' | 'intern';
  salary: number | null;
  currency: string;
  status: 'active' | 'on_leave' | 'terminated';
  created_at: string;
  updated_at: string;
}

export interface Department {
  id: string;
  tenant_id: string;
  name: string;
  code: string | null;
  description: string | null;
  parent_id: string | null;
  manager_id: string | null;
  location: string | null;
  created_at: string;
  updated_at: string;
}

export interface CreateEmployeeData {
  user_id?: string;
  full_name?: string;
  email?: string;
  employee_number?: string;
  hire_date?: string;
  department_id?: string;
  position_id?: string;
  manager_id?: string;
  employment_type?: string;
  salary?: number;
  currency?: string;
  status?: string;
}

export interface CreateDepartmentData {
  name: string;
  code?: string;
  description?: string;
  parent_id?: string;
  manager_id?: string;
  location?: string;
}
