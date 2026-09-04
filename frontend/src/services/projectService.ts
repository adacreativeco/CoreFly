import client from '@/api/client';
import { Project, CreateProjectData, Task } from '@/types/project';

export const getProjects = async (tenantId: string): Promise<Project[]> => {
  const response = await client.get<{ data: Project[] }>(`/projects/${tenantId}`);
  return response.data.data;
};

export const getProject = async (tenantId: string, projectId: string): Promise<Project> => {
  const response = await client.get<{ data: Project }>(`/projects/${tenantId}/${projectId}`);
  return response.data.data;
};

export const createProject = async (tenantId: string, data: CreateProjectData): Promise<Project> => {
  const response = await client.post<{ data: Project }>(`/projects/${tenantId}`, data);
  return response.data.data;
};

export const getProjectTasks = async (tenantId: string, projectId: string): Promise<Task[]> => {
  const response = await client.get<{ data: Task[] }>(`/projects/${tenantId}/${projectId}/tasks`);
  return response.data.data;
};

export const createProjectTask = async (tenantId: string, projectId: string, data: Partial<Task>): Promise<Task> => {
  const response = await client.post<{ data: Task }>(`/projects/${tenantId}/${projectId}/tasks`, data);
  return response.data.data;
};

export const updateProjectTask = async (
  tenantId: string,
  projectId: string,
  taskId: string,
  data: Partial<Task>
): Promise<Task> => {
  const response = await client.patch<{ data: Task }>(
    `/projects/${tenantId}/${projectId}/tasks/${taskId}`,
    data
  );
  return response.data.data;
};
