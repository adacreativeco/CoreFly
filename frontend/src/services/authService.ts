import client from '@/api/client';
import { useAuthStore } from '@/store/authStore';
import { RegisterData } from '@/types/auth';

export const login = async (email: string, password: string) => {
  const response = await client.post('/auth/login', { email, password });
  const { token, user } = response.data;
  useAuthStore.getState().setAuth(token, user);
  return user;
};

export const register = async (data: RegisterData) => {
  const response = await client.post('/auth/register', data);
  const { token, user } = response.data;
  useAuthStore.getState().setAuth(token, user);
  return user;
};

export const logout = () => {
  useAuthStore.getState().logout();
};
