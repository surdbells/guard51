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
      // Dashboard (role-aware: super admin vs company admin)
      {
        path: 'dashboard',
        loadChildren: () => import('./features/dashboard/dashboard.routes').then(m => m.DASHBOARD_ROUTES),
      },

      // Phase 1: Core Operations
      {
        path: 'sites',
        loadChildren: () => import('./features/sites/sites.routes').then(m => m.SITES_ROUTES),
      },
      {
        path: 'guards',
        loadChildren: () => import('./features/guards/guards.routes').then(m => m.GUARDS_ROUTES),
      },
      {
        path: 'clients',
        loadChildren: () => import('./features/clients/clients.routes').then(m => m.CLIENTS_ROUTES),
      },

      // Phase 2: Scheduling & Attendance
      {
        path: 'scheduling',
        loadChildren: () => import('./features/scheduling/scheduling.routes').then(m => m.SCHEDULING_ROUTES),
      },
      {
        path: 'attendance',
        loadChildren: () => import('./features/attendance/attendance.routes').then(m => m.ATTENDANCE_ROUTES),
      },
      {
        path: 'passdowns',
        loadChildren: () => import('./features/passdowns/passdowns.routes').then(m => m.PASSDOWNS_ROUTES),
      },

      // Phase 3: Tracking, Tours & Panic
      {
        path: 'tracker',
        loadChildren: () => import('./features/tracker/tracker.routes').then(m => m.TRACKER_ROUTES),
      },
      {
        path: 'tours',
        loadChildren: () => import('./features/tours/tours.routes').then(m => m.TOURS_ROUTES),
      },
      {
        path: 'panic',
        loadChildren: () => import('./features/panic/panic.routes').then(m => m.PANIC_ROUTES),
      },

      // Phase 4: Reporting & Dispatch
      {
        path: 'reports',
        loadChildren: () => import('./features/reports/reports.routes').then(m => m.REPORTS_ROUTES),
      },
      {
        path: 'incidents',
        loadChildren: () => import('./features/incidents/incidents.routes').then(m => m.INCIDENTS_ROUTES),
      },
      {
        path: 'dispatch',
        loadChildren: () => import('./features/dispatch/dispatch.routes').then(m => m.DISPATCH_ROUTES),
      },
      {
        path: 'tasks',
        loadChildren: () => import('./features/tasks/tasks.routes').then(m => m.TASKS_ROUTES),
      },

      // Phase 5: Finance & Billing
      {
        path: 'invoices',
        loadChildren: () => import('./features/invoices/invoices.routes').then(m => m.INVOICES_ROUTES),
      },
      {
        path: 'payroll',
        loadChildren: () => import('./features/payroll/payroll.routes').then(m => m.PAYROLL_ROUTES),
      },

      // Phase 6: Client Experience & Communication
      {
        path: 'client-portal',
        loadChildren: () => import('./features/client-portal/client-portal.routes').then(m => m.CLIENT_PORTAL_ROUTES),
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

      // Default redirect
      { path: '', redirectTo: 'dashboard', pathMatch: 'full' },
    ],
  },

  // ── Fallback ─────────────────────────────────────
  { path: '**', redirectTo: 'auth/login' },
];
