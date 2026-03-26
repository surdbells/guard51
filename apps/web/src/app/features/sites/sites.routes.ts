import { Routes } from '@angular/router';

export const SITES_ROUTES: Routes = [
  {
    path: '',
    loadComponent: () => import('./sites.component').then(m => m.SitesComponent),
  },
];
