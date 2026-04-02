import { Http, HttpResponse } from '@nativescript/core';
import { SecureStorage } from './secure-storage.service';

const API_URL = 'https://api.guard51.com/api/v1';
const WS_URL = 'wss://api.guard51.com/ws';

export class ApiService {
  private static token: string | null = null;

  static setToken(token: string): void { this.token = token; SecureStorage.set('access_token', token); }
  static getToken(): string | null { return this.token || SecureStorage.get('access_token'); }
  static clearToken(): void { this.token = null; SecureStorage.remove('access_token'); }

  static async get<T = any>(path: string): Promise<T> {
    const res = await Http.request({
      url: `${API_URL}${path}`,
      method: 'GET',
      headers: this.headers(),
      timeout: 15000,
    });
    return this.handleResponse(res);
  }

  static async post<T = any>(path: string, body: any = {}): Promise<T> {
    const res = await Http.request({
      url: `${API_URL}${path}`,
      method: 'POST',
      headers: { ...this.headers(), 'Content-Type': 'application/json' },
      content: JSON.stringify(body),
      timeout: 15000,
    });
    return this.handleResponse(res);
  }

  private static headers(): Record<string, string> {
    const h: Record<string, string> = {};
    const t = this.getToken();
    if (t) h['Authorization'] = `Bearer ${t}`;
    return h;
  }

  private static handleResponse(res: HttpResponse): any {
    if (res.statusCode === 401) {
      this.clearToken();
      throw new Error('Unauthorized');
    }
    const data = res.content?.toJSON?.() || JSON.parse(res.content?.toString() || '{}');
    if (!data.success && res.statusCode >= 400) {
      throw new Error(data.message || `HTTP ${res.statusCode}`);
    }
    return data;
  }

  static getWsUrl(): string {
    return `${WS_URL}?token=${this.getToken() || ''}`;
  }
}
