import { Routes } from '@angular/router';

export const CLIENTS_ROUTES: Routes = [
  { path: '', loadComponent: () => import('./clients.component').then(m => m.ClientsComponent) },
  { path: 'new', loadComponent: () => import('./client-form.component').then(m => m.ClientFormComponent) },
  { path: 'edit/:id', loadComponent: () => import('./client-form.component').then(m => m.ClientFormComponent) },
  { path: ':id', loadComponent: () => import('./client-detail.component').then(m => m.ClientDetailComponent) },
];
