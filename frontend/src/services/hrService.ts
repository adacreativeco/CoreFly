import client from '@/api/client';
import { Employee, Department, CreateEmployeeData, CreateDepartmentData } from '@/types/hr';

export const getEmployees = async (tenantId: string): Promise<Employee[]> => {
  const response = await client.get<{ data: Employee[] }>(`/hr/${tenantId}/employees`);
  return response.data.data;
};

export const createEmployee = async (tenantId: string, data: CreateEmployeeData): Promise<Employee> => {
  const response = await client.post<{ data: Employee }>(`/hr/${tenantId}/employees`, data);
  return response.data.data;
};

export const getDepartments = async (tenantId: string): Promise<Department[]> => {
  const response = await client.get<{ data: Department[] }>(`/hr/${tenantId}/departments`);
  return response.data.data;
};

export const createDepartment = async (tenantId: string, data: CreateDepartmentData): Promise<Department> => {
  const response = await client.post<{ data: Department }>(`/hr/${tenantId}/departments`, data);
  return response.data.data;
};
