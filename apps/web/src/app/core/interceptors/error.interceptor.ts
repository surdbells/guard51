import { HttpInterceptorFn, HttpErrorResponse } from '@angular/common/http';
import { inject } from '@angular/core';
import { catchError, throwError } from 'rxjs';
import { ToastService } from '../services/toast.service';

export const errorInterceptor: HttpInterceptorFn = (req, next) => {
  const toast = inject(ToastService);

  return next(req).pipe(
    catchError((error: HttpErrorResponse) => {
      // Skip toast for auth-related errors (handled by auth interceptor)
      if (error.status === 401) {
        return throwError(() => error);
      }

      const body = error.error;
      const message = body?.message || getDefaultMessage(error.status);

      if (error.status === 422 && body?.errors) {
        // Validation errors — show first error
        const firstField = Object.keys(body.errors)[0];
        const firstError = body.errors[firstField]?.[0];
        toast.error('Validation Error', firstError || message);
      } else if (error.status === 429) {
        toast.warning('Too Many Requests', message);
      } else if (error.status >= 500) {
        toast.error('Server Error', 'Something went wrong. Please try again later.');
      } else if (error.status === 403) {
        toast.warning('Access Denied', message);
      } else if (error.status !== 404) {
        toast.error('Error', message);
      }

      return throwError(() => error);
    }),
  );
};

function getDefaultMessage(status: number): string {
  switch (status) {
    case 400: return 'Bad request.';
    case 403: return 'You do not have permission to perform this action.';
    case 404: return 'Resource not found.';
    case 409: return 'Conflict — this resource already exists.';
    case 429: return 'Too many requests. Please wait and try again.';
    case 500: return 'Internal server error.';
    default: return 'An unexpected error occurred.';
  }
}
