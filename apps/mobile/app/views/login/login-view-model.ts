import { Observable, Frame, NavigationEntry } from '@nativescript/core';
import { ApiService } from '../../services/api.service';
import { SecureStorage } from '../../services/secure-storage.service';

export class LoginViewModel extends Observable {
  email = '';
  password = '';
  loading = false;
  errorMessage = '';

  async onLogin() {
    if (!this.email || !this.password) {
      this.set('errorMessage', 'Enter email and password');
      return;
    }

    this.set('loading', true);
    this.set('errorMessage', '');

    try {
      const res = await ApiService.post('/auth/login', {
        email: this.email, password: this.password,
      });

      if (res.data?.tokens?.access_token) {
        ApiService.setToken(res.data.tokens.access_token);
        if (res.data.tokens.refresh_token) {
          SecureStorage.set('refresh_token', res.data.tokens.refresh_token);
        }
        SecureStorage.set('user', JSON.stringify(res.data.user));

        Frame.topmost().navigate({
          moduleName: 'app/views/dashboard/dashboard-page',
          clearHistory: true,
        } as NavigationEntry);
      }
    } catch (e: any) {
      this.set('errorMessage', e.message || 'Login failed');
    } finally {
      this.set('loading', false);
    }
  }
}
