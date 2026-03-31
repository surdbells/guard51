import { inject } from '@angular/core';
import { CanActivateFn, ActivatedRouteSnapshot, Router } from '@angular/router';
import { AuthStore } from '../services/auth.store';

export const roleGuard: CanActivateFn = (route: ActivatedRouteSnapshot) => {
  const authStore = inject(AuthStore);
  const router = inject(Router);

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

  // Redirect to appropriate portal based on role instead of showing error
  if (userRole === 'guard') {
    router.navigate(['/portal']);
  } else if (userRole === 'client') {
    router.navigate(['/client-portal']);
  } else if (userRole === 'dispatcher') {
    router.navigate(['/dispatch']);
  } else {
    router.navigate(['/dashboard']);
  }
  return false;
};
