import { ErrorHandler, Injectable } from '@angular/core';
import { environment } from '@env/environment';

@Injectable()
export class GlobalErrorHandler implements ErrorHandler {
  handleError(error: any): void {
    // Log to console in all environments
    console.error('[Guard51]', error);

    // In production, send to error tracking service (Sentry, etc.)
    if (environment.production && (window as any).__SENTRY__) {
      try {
        (window as any).Sentry?.captureException(error.originalError || error);
      } catch {}
    }

    // Could also POST to /api/v1/errors endpoint for self-hosted tracking
    if (environment.production) {
      try {
        const payload = {
          message: error.message || String(error),
          stack: error.stack?.substring(0, 2000),
          url: window.location.href,
          timestamp: new Date().toISOString(),
        };
        navigator.sendBeacon?.(environment.apiUrl + '/errors/client', JSON.stringify(payload));
      } catch {}
    }
  }
}
