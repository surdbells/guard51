import { Injectable } from '@angular/core';
@Injectable({ providedIn: 'root' })
export class SupervisorAuthService {
  private token: string | null = null;
  isLoggedIn(): boolean { return !!this.token; }
  setToken(t: string): void { this.token = t; }
  logout(): void { this.token = null; }
}
