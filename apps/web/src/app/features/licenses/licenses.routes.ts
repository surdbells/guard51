import { Routes } from '@angular/router';
export const LICENSES_ROUTES: Routes = [
  { path: '', loadComponent: () => import('./licenses.component').then(m => m.LicensesComponent) },
];
