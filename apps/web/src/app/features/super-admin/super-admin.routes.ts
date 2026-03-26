import { Routes } from '@angular/router';

export const SUPER_ADMIN_ROUTES: Routes = [
  {
    path: 'dashboard',
    loadComponent: () => import('./dashboard/sa-dashboard.component').then(m => m.SaDashboardComponent),
  },
  {
    path: 'tenants',
    loadComponent: () => import('./tenants/tenants.component').then(m => m.TenantsComponent),
  },
  { path: '', redirectTo: 'dashboard', pathMatch: 'full' },
];
