import { Routes } from '@angular/router';
export const INCIDENTS_ROUTES: Routes = [
  { path: '', loadComponent: () => import('./incidents.component').then(m => m.IncidentsComponent) },
];
