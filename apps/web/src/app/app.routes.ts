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
