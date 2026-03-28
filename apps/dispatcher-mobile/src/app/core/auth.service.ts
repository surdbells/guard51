import { Injectable } from '@angular/core';
@Injectable({ providedIn: 'root' })
export class DispatcherAuthService { private token: string | null = null; isLoggedIn(): boolean { return !!this.token; } setToken(t: string): void { this.token = t; } }
