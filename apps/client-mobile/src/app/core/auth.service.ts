import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';

@Injectable({ providedIn: 'root' })
export class ClientAuthService {
  private baseUrl = 'https://api.guard51.com/api/v1';
  private token: string | null = null;
  private clientId: string | null = null;

  constructor(private http: HttpClient) {}

  login(email: string, password: string) {
    return this.http.post<any>(`${this.baseUrl}/auth/login`, { email, password });
  }

  setToken(token: string, clientId: string) { this.token = token; this.clientId = clientId; }
  getToken(): string | null { return this.token; }
  getClientId(): string | null { return this.clientId; }
  isLoggedIn(): boolean { return !!this.token; }
  logout(): void { this.token = null; this.clientId = null; }
}
