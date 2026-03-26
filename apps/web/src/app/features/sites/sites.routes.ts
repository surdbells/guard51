import { Routes } from '@angular/router';

export const SITES_ROUTES: Routes = [
  { path: '', loadComponent: () => import('./sites.component').then(m => m.SitesComponent) },
  { path: 'new', loadComponent: () => import('./site-form.component').then(m => m.SiteFormComponent) },
  { path: 'edit/:id', loadComponent: () => import('./site-form.component').then(m => m.SiteFormComponent) },
  { path: ':id', loadComponent: () => import('./site-detail.component').then(m => m.SiteDetailComponent) },
];
