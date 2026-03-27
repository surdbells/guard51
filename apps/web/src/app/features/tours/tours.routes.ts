import { Routes } from '@angular/router';
export const TOURS_ROUTES: Routes = [
  { path: '', loadComponent: () => import('./tours.component').then(m => m.ToursComponent) },
];
