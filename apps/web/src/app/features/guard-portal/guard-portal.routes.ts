import { Routes } from '@angular/router';

export const GUARD_PORTAL_ROUTES: Routes = [
  {
    path: '',
    loadComponent: () => import('./guard-portal.component').then(m => m.GuardPortalComponent),
  },
];
