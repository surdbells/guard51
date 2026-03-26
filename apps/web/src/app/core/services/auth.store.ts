import { Injectable, signal, computed } from '@angular/core';

export interface AuthUser {
  id: string;
  email: string;
  first_name: string;
  last_name: string;
  full_name: string;
  phone: string | null;
  photo_url: string | null;
  role: string;
  tenant_id: string | null;
  is_active: boolean;
}

export interface AuthTokens {
  access_token: string;
  refresh_token: string;
  token_type: string;
  expires_in: number;
}

export interface TenantInfo {
  id: string;
  name: string;
  tenant_type: string;
  status: string;
  logo_url: string | null;
  branding: Record<string, string>;
}

const STORAGE_KEYS = {
  accessToken: 'g51_access_token',
  refreshToken: 'g51_refresh_token',
  user: 'g51_user',
  tenant: 'g51_tenant',
};

@Injectable({ providedIn: 'root' })
export class AuthStore {
  readonly user = signal<AuthUser | null>(null);
  readonly tenant = signal<TenantInfo | null>(null);
  readonly accessToken = signal<string | null>(null);

  readonly isAuthenticated = computed(() => !!this.accessToken() && !!this.user());
  readonly userRole = computed(() => this.user()?.role ?? null);
  readonly isSuperAdmin = computed(() => this.userRole() === 'super_admin');
  readonly isCompanyAdmin = computed(() => this.userRole() === 'company_admin');
  readonly tenantType = computed(() => this.tenant()?.tenant_type ?? 'private_security');
  readonly userInitials = computed(() => {
    const u = this.user();
    if (!u) return '';
    return `${u.first_name.charAt(0)}${u.last_name.charAt(0)}`.toUpperCase();
  });

  constructor() {
    this.loadFromStorage();
  }

  setAuth(user: AuthUser, tokens: AuthTokens, tenant?: TenantInfo): void {
    this.user.set(user);
    this.accessToken.set(tokens.access_token);
    if (tenant) this.tenant.set(tenant);

    localStorage.setItem(STORAGE_KEYS.accessToken, tokens.access_token);
    localStorage.setItem(STORAGE_KEYS.refreshToken, tokens.refresh_token);
    localStorage.setItem(STORAGE_KEYS.user, JSON.stringify(user));
    if (tenant) localStorage.setItem(STORAGE_KEYS.tenant, JSON.stringify(tenant));
  }

  updateTokens(tokens: AuthTokens): void {
    this.accessToken.set(tokens.access_token);
    localStorage.setItem(STORAGE_KEYS.accessToken, tokens.access_token);
    localStorage.setItem(STORAGE_KEYS.refreshToken, tokens.refresh_token);
  }

  updateUser(user: AuthUser): void {
    this.user.set(user);
    localStorage.setItem(STORAGE_KEYS.user, JSON.stringify(user));
  }

  updateTenant(tenant: TenantInfo): void {
    this.tenant.set(tenant);
    localStorage.setItem(STORAGE_KEYS.tenant, JSON.stringify(tenant));
  }

  getRefreshToken(): string | null {
    return localStorage.getItem(STORAGE_KEYS.refreshToken);
  }

  clearAuth(): void {
    this.user.set(null);
    this.tenant.set(null);
    this.accessToken.set(null);
    Object.values(STORAGE_KEYS).forEach(key => localStorage.removeItem(key));
  }

  private loadFromStorage(): void {
    const token = localStorage.getItem(STORAGE_KEYS.accessToken);
    const userJson = localStorage.getItem(STORAGE_KEYS.user);
    const tenantJson = localStorage.getItem(STORAGE_KEYS.tenant);

    if (token && userJson) {
      try {
        this.accessToken.set(token);
        this.user.set(JSON.parse(userJson));
        if (tenantJson) this.tenant.set(JSON.parse(tenantJson));
      } catch {
        this.clearAuth();
      }
    }
  }
}
