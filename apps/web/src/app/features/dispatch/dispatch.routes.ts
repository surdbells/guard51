import { Routes } from '@angular/router';
export const DISPATCH_ROUTES: Routes = [
  { path: '', loadComponent: () => import('./dispatch.component').then(m => m.DispatchComponent) },
];
