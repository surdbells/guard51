import { HttpInterceptorFn, HttpErrorResponse } from '@angular/common/http';
import { inject } from '@angular/core';
import { catchError, switchMap, throwError } from 'rxjs';
import { AuthStore } from '../services/auth.store';
import { AuthService } from '../services/auth.service';

let isRefreshing = false;

export const authInterceptor: HttpInterceptorFn = (req, next) => {
  const authStore = inject(AuthStore);
  const authService = inject(AuthService);

  const token = authStore.accessToken();

  // Skip auth for public endpoints
  if (req.url.includes('/auth/login') ||
      req.url.includes('/auth/register') ||
      req.url.includes('/auth/refresh') ||
      req.url.includes('/auth/forgot-password') ||
      req.url.includes('/auth/reset-password') ||
      req.url.includes('/apps/check-update') ||
      req.url.includes('/apps/heartbeat') ||
      req.url.includes('/subscriptions/plans') ||
      req.url.includes('/invitations/accept')) {
    return next(req);
  }

  if (token) {
    req = req.clone({
      setHeaders: { Authorization: `Bearer ${token}` },
    });
  }

  return next(req).pipe(
    catchError((error: HttpErrorResponse) => {
      if (error.status === 401 && !isRefreshing && !req.url.includes('/auth/')) {
        isRefreshing = true;
        return authService.refreshToken().pipe(
          switchMap(res => {
            isRefreshing = false;
            if (res.success && res.data) {
              const newReq = req.clone({
                setHeaders: { Authorization: `Bearer ${res.data.tokens.access_token}` },
              });
              return next(newReq);
            }
            authService.logout();
            return throwError(() => error);
          }),
          catchError(refreshErr => {
            isRefreshing = false;
            authService.logout();
            return throwError(() => refreshErr);
          }),
        );
      }
      return throwError(() => error);
    }),
  );
};
