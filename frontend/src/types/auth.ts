export interface RegisterData {
  name: string;
  email: string;
  password: string;
  password_confirmation: string;
}

export interface User {
  id: string;
  tenant_id: string;
  name: string;
  full_name?: string;
  email: string;
  role?: string;
  avatar_url?: string;
  created_at: string;
  updated_at: string;
}

export interface AuthResponse {
  token: string;
  user: User;
}

export interface ApiError {
  response?: {
    data?: {
      error?: string;
    };
  };
}
