import { inject } from '@angular/core';
import { CanActivateFn, ActivatedRouteSnapshot, Router } from '@angular/router';
import { AuthStore } from '../services/auth.store';
import { ToastService } from '../services/toast.service';

export const roleGuard: CanActivateFn = (route: ActivatedRouteSnapshot) => {
  const authStore = inject(AuthStore);
  const router = inject(Router);
  const toast = inject(ToastService);

  const requiredRoles = route.data['roles'] as string[] | undefined;

  if (!requiredRoles || requiredRoles.length === 0) {
    return true;
  }

  const userRole = authStore.userRole();

  // Super admin always passes
  if (userRole === 'super_admin') {
    return true;
  }

  if (userRole && requiredRoles.includes(userRole)) {
    return true;
  }

  toast.warning('Access Denied', 'You do not have permission to access this page.');
  router.navigate(['/dashboard']);
  return false;
};
