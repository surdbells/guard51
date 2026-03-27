import { Injectable } from '@angular/core';
import { BehaviorSubject } from 'rxjs';

const API_URL = 'https://api.guard51.com/api/v1';

@Injectable({ providedIn: 'root' })
export class AuthService {
  private tokenSubject = new BehaviorSubject<string | null>(null);
  token$ = this.tokenSubject.asObservable();
  get isLoggedIn(): boolean { return !!this.tokenSubject.value; }
  get token(): string | null { return this.tokenSubject.value; }

  async login(email: string, password: string): Promise<any> {
    const res = await fetch(`${API_URL}/auth/login`, {
      method: 'POST', headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ email, password }),
    });
    const data = await res.json();
    if (data.data?.access_token) this.tokenSubject.next(data.data.access_token);
    return data;
  }

  logout(): void { this.tokenSubject.next(null); }
}
