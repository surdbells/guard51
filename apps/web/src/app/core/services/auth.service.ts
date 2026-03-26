import { Injectable, inject } from '@angular/core';
import { Router } from '@angular/router';
import { Observable, tap, map } from 'rxjs';
import { ApiService, ApiResponse } from './api.service';
import { AuthStore, AuthUser, AuthTokens, TenantInfo } from './auth.store';

interface LoginResponse {
  user: AuthUser;
  tokens: AuthTokens;
}

interface RegisterResponse {
  user: AuthUser;
  tenant: TenantInfo;
  tokens: AuthTokens;
}

interface MeResponse extends AuthUser {
  tenant?: TenantInfo;
}

@Injectable({ providedIn: 'root' })
export class AuthService {
  private api = inject(ApiService);
  private store = inject(AuthStore);
  private router = inject(Router);

  login(email: string, password: string): Observable<ApiResponse<LoginResponse>> {
    return this.api.post<LoginResponse>('/auth/login', { email, password }).pipe(
      tap(res => {
        if (res.success && res.data) {
          this.store.setAuth(res.data.user, res.data.tokens);
        }
      }),
    );
  }

  register(data: {
    company_name: string;
    first_name: string;
    last_name: string;
    email: string;
    password: string;
    phone?: string;
    tenant_type?: string;
  }): Observable<ApiResponse<RegisterResponse>> {
    return this.api.post<RegisterResponse>('/auth/register', data).pipe(
      tap(res => {
        if (res.success && res.data) {
          this.store.setAuth(res.data.user, res.data.tokens, res.data.tenant);
        }
      }),
    );
  }

  refreshToken(): Observable<ApiResponse<LoginResponse>> {
    const refreshToken = this.store.getRefreshToken();
    return this.api.post<LoginResponse>('/auth/refresh', { refresh_token: refreshToken }).pipe(
      tap(res => {
        if (res.success && res.data) {
          this.store.setAuth(res.data.user, res.data.tokens);
        }
      }),
    );
  }

  me(): Observable<ApiResponse<MeResponse>> {
    return this.api.get<MeResponse>('/auth/me').pipe(
      tap(res => {
        if (res.success && res.data) {
          const { tenant, ...user } = res.data;
          this.store.updateUser(user as AuthUser);
          if (tenant) this.store.updateTenant(tenant);
        }
      }),
    );
  }

  forgotPassword(email: string): Observable<ApiResponse<{ message: string }>> {
    return this.api.post('/auth/forgot-password', { email });
  }

  resetPassword(token: string, email: string, password: string): Observable<ApiResponse<{ message: string }>> {
    return this.api.post('/auth/reset-password', { token, email, password });
  }

  logout(): void {
    this.api.post('/auth/logout').subscribe({ error: () => {} });
    this.store.clearAuth();
    this.router.navigate(['/auth/login']);
  }
}
