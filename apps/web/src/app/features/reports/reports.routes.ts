import { Routes } from '@angular/router';
export const REPORTS_ROUTES: Routes = [
  { path: '', loadComponent: () => import('./reports.component').then(m => m.ReportsComponent) },
  { path: 'dar/:id', loadComponent: () => import('./dar-detail.component').then(m => m.DarDetailComponent) },
];
