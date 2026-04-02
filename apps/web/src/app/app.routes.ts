import { Routes } from '@angular/router';
import { authGuard } from './core/guards/auth.guard';
import { roleGuard } from './core/guards/role.guard';

export const routes: Routes = [
  // ── Public: Auth ─────────────────────────────────
  {
    path: 'auth',
    loadChildren: () => import('./features/auth/auth.routes').then(m => m.AUTH_ROUTES),
  },

  // ── Authenticated: Shell Layout ──────────────────
  {
    path: '',
    loadComponent: () => import('./shared/layouts/shell/shell.component').then(m => m.ShellComponent),
    canActivate: [authGuard],
    children: [
      // Dashboard — all authenticated users
      {
        path: 'dashboard',
        loadChildren: () => import('./features/dashboard/dashboard.routes').then(m => m.DASHBOARD_ROUTES),
        canActivate: [roleGuard], data: { roles: ['company_admin', 'supervisor', 'dispatcher'] },
      },

      // Core Operations — admin + supervisor
      {
        path: 'sites',
        loadChildren: () => import('./features/sites/sites.routes').then(m => m.SITES_ROUTES),
        canActivate: [roleGuard], data: { roles: ['company_admin', 'supervisor'] },
      },
      {
        path: 'guards',
        loadChildren: () => import('./features/guards/guards.routes').then(m => m.GUARDS_ROUTES),
        canActivate: [roleGuard], data: { roles: ['company_admin', 'supervisor'] },
      },
      {
        path: 'clients',
        loadChildren: () => import('./features/clients/clients.routes').then(m => m.CLIENTS_ROUTES),
        canActivate: [roleGuard], data: { roles: ['company_admin'] },
      },

      // Scheduling & Attendance — admin + supervisor
      {
        path: 'scheduling',
        loadChildren: () => import('./features/scheduling/scheduling.routes').then(m => m.SCHEDULING_ROUTES),
        canActivate: [roleGuard], data: { roles: ['company_admin', 'supervisor'] },
      },
      {
        path: 'attendance',
        loadChildren: () => import('./features/attendance/attendance.routes').then(m => m.ATTENDANCE_ROUTES),
        canActivate: [roleGuard], data: { roles: ['company_admin', 'supervisor'] },
      },
      {
        path: 'passdowns',
        loadChildren: () => import('./features/passdowns/passdowns.routes').then(m => m.PASSDOWNS_ROUTES),
      },

      // Tracking, Tours & Panic
      {
        path: 'tracker',
        loadChildren: () => import('./features/tracker/tracker.routes').then(m => m.TRACKER_ROUTES),
        canActivate: [roleGuard], data: { roles: ['company_admin', 'supervisor', 'dispatcher'] },
      },
      {
        path: 'tours',
        loadChildren: () => import('./features/tours/tours.routes').then(m => m.TOURS_ROUTES),
        canActivate: [roleGuard], data: { roles: ['company_admin', 'supervisor'] },
      },
      {
        path: 'panic',
        loadChildren: () => import('./features/panic/panic.routes').then(m => m.PANIC_ROUTES),
        canActivate: [roleGuard], data: { roles: ['company_admin', 'supervisor', 'dispatcher'] },
      },

      // Reporting & Dispatch
      {
        path: 'reports',
        loadChildren: () => import('./features/reports/reports.routes').then(m => m.REPORTS_ROUTES),
        canActivate: [roleGuard], data: { roles: ['company_admin', 'supervisor', 'guard'] },
      },
      {
        path: 'incidents',
        loadChildren: () => import('./features/incidents/incidents.routes').then(m => m.INCIDENTS_ROUTES),
        canActivate: [roleGuard], data: { roles: ['company_admin', 'supervisor', 'guard'] },
      },
      {
        path: 'dispatch',
        loadChildren: () => import('./features/dispatch/dispatch.routes').then(m => m.DISPATCH_ROUTES),
        canActivate: [roleGuard], data: { roles: ['company_admin', 'dispatcher'] },
      },
      {
        path: 'tasks',
        loadChildren: () => import('./features/tasks/tasks.routes').then(m => m.TASKS_ROUTES),
      },

      // Finance & Billing — admin only
      {
        path: 'invoices',
        loadChildren: () => import('./features/invoices/invoices.routes').then(m => m.INVOICES_ROUTES),
        canActivate: [roleGuard], data: { roles: ['company_admin'] },
      },
      {
        path: 'payroll',
        loadChildren: () => import('./features/payroll/payroll.routes').then(m => m.PAYROLL_ROUTES),
        canActivate: [roleGuard], data: { roles: ['company_admin'] },
      },

      // Client Experience & Communication
      {
        path: 'client-portal',
        loadChildren: () => import('./features/client-portal/client-portal.routes').then(m => m.CLIENT_PORTAL_ROUTES),
        canActivate: [roleGuard], data: { roles: ['client'] },
      },
      {
        path: 'chat',
        loadChildren: () => import('./features/chat/chat.routes').then(m => m.CHAT_ROUTES),
      },
      {
        path: 'notifications',
        loadChildren: () => import('./features/notifications/notifications.routes').then(m => m.NOTIFICATIONS_ROUTES),
      },

      // Phase 7: Operations & Extended
      {
        path: 'vehicle-patrol',
        loadChildren: () => import('./features/vehicle-patrol/vehicle-patrol.routes').then(m => m.VEHICLE_PATROL_ROUTES),
      },
      {
        path: 'visitors',
        loadChildren: () => import('./features/visitors/visitors.routes').then(m => m.VISITORS_ROUTES),
      },
      {
        path: 'parking',
        loadChildren: () => import('./features/parking/parking.routes').then(m => m.PARKING_ROUTES),
      },

      // Phase 8: Advanced Features
      {
        path: 'analytics',
        loadChildren: () => import('./features/analytics/analytics.routes').then(m => m.ANALYTICS_ROUTES),
      },
      {
        path: 'licenses',
        loadChildren: () => import('./features/licenses/licenses.routes').then(m => m.LICENSES_ROUTES),
      },
      {
        path: 'security',
        loadChildren: () => import('./features/security/security.routes').then(m => m.SECURITY_ROUTES),
      },

      // SaaS Management
      {
        path: 'settings',
        loadChildren: () => import('./features/settings/settings.routes').then(m => m.SETTINGS_ROUTES),
      },
      {
        path: 'billing',
        loadChildren: () => import('./features/billing/billing.routes').then(m => m.BILLING_ROUTES),
      },
      {
        path: 'users',
        loadChildren: () => import('./features/users/users.routes').then(m => m.USERS_ROUTES),
      },

      // Guard Web Portal (guard role only)
      {
        path: 'portal',
        canActivate: [roleGuard],
        data: { roles: ['guard'] },
        loadChildren: () => import('./features/guard-portal/guard-portal.routes').then(m => m.GUARD_PORTAL_ROUTES),
      },

      // Super Admin routes
      {
        path: 'admin',
        canActivate: [roleGuard],
        data: { roles: ['super_admin'] },
        loadChildren: () => import('./features/super-admin/super-admin.routes').then(m => m.SUPER_ADMIN_ROUTES),
      },

      // Support & Help
      {
        path: 'support',
        loadChildren: () => import('./features/support/support.routes').then(m => m.SUPPORT_ROUTES),
      },
      {
        path: 'help',
        loadChildren: () => import('./features/help-center/help-center.routes').then(m => m.HELP_CENTER_ROUTES),
      },

      // Onboarding wizard
      {
        path: 'onboarding',
        loadChildren: () => import('./features/onboarding/onboarding.routes').then(m => m.ONBOARDING_ROUTES),
        canActivate: [roleGuard], data: { roles: ['company_admin'] },
      },

      // Default redirect
      { path: '', redirectTo: 'dashboard', pathMatch: 'full' },
    ],
  },

  // ── Fallback ─────────────────────────────────────
  { path: '**', redirectTo: 'auth/login' },
];
