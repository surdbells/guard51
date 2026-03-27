import { Routes } from '@angular/router';

export const PASSDOWNS_ROUTES: Routes = [
  { path: '', loadComponent: () => import('./passdowns.component').then(m => m.PassdownsComponent) },
];
