import { Routes } from '@angular/router';
export const INVOICES_ROUTES: Routes = [
  { path: '', loadComponent: () => import('./invoices.component').then(m => m.InvoicesComponent) },
  { path: ':id', loadComponent: () => import('./invoice-detail.component').then(m => m.InvoiceDetailComponent) },
];
