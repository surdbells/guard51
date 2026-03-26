import { Routes } from '@angular/router';

export const GUARDS_ROUTES: Routes = [
  {
    path: '',
    loadComponent: () => import('./guards.component').then(m => m.GuardsComponent),
  },
];
