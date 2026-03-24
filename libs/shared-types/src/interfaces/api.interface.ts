export interface ApiResponse<T = unknown> {
  success: boolean;
  data?: T;
  message?: string;
  errors?: Record<string, string[]>;
}

export interface PaginatedResponse<T = unknown> {
  success: boolean;
  data: T[];
  meta: {
    total: number;
    page: number;
    per_page: number;
    last_page: number;
  };
}

export interface AuthTokens {
  access_token: string;
  refresh_token: string;
  expires_in: number;
  token_type: 'Bearer';
}

export interface User {
  id: string;
  email: string;
  first_name: string;
  last_name: string;
  role: string;
  tenant_id: string;
  is_active: boolean;
  created_at: string;
}

export interface Tenant {
  id: string;
  name: string;
  tenant_type: string;
  status: string;
  created_at: string;
}

export interface GpsLocation {
  guard_id: string;
  tenant_id: string;
  lat: number;
  lng: number;
  accuracy: number;
  battery_level?: number;
  speed?: number;
  recorded_at: string;
}
