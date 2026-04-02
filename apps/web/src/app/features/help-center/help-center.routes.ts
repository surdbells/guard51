import { Routes } from '@angular/router';
export const HELP_CENTER_ROUTES: Routes = [
  { path: '', loadComponent: () => import('./help-center.component').then(m => m.HelpCenterComponent) },
];
