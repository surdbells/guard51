import { Routes } from '@angular/router';
export const INCIDENTS_ROUTES: Routes = [
  { path: '', loadComponent: () => import('./incidents.component').then(m => m.IncidentsComponent) },
  { path: ':id', loadComponent: () => import('./incident-detail.component').then(m => m.IncidentDetailComponent) },
];
