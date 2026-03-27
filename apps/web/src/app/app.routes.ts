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
