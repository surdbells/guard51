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
  {
    path: 'subscriptions',
    loadComponent: () => import('./subscriptions/subscriptions.component').then(m => m.SubscriptionsComponent),
  },
  {
    path: 'payments',
    loadComponent: () => import('./payments/payments.component').then(m => m.PaymentsComponent),
  },
  {
    path: 'features',
    loadComponent: () => import('./features/features.component').then(m => m.FeaturesComponent),
  },
  {
    path: 'apps',
    loadComponent: () => import('./apps/apps.component').then(m => m.AppsComponent),
  },
  {
    path: 'analytics',
    loadComponent: () => import('./analytics/analytics.component').then(m => m.AnalyticsComponent),
  },
  {
    path: 'settings',
    loadComponent: () => import('./settings/settings.component').then(m => m.SaSettingsComponent),
  },
  { path: '', redirectTo: 'dashboard', pathMatch: 'full' },
];
