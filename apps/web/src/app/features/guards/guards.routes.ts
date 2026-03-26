import { Routes } from '@angular/router';

export const GUARDS_ROUTES: Routes = [
  { path: '', loadComponent: () => import('./guards.component').then(m => m.GuardsComponent) },
  { path: 'new', loadComponent: () => import('./guard-form.component').then(m => m.GuardFormComponent) },
  { path: 'edit/:id', loadComponent: () => import('./guard-form.component').then(m => m.GuardFormComponent) },
  { path: ':id', loadComponent: () => import('./guard-detail.component').then(m => m.GuardDetailComponent) },
];
