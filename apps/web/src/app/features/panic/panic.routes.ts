import { Routes } from '@angular/router';
export const PANIC_ROUTES: Routes = [
  { path: '', loadComponent: () => import('./panic.component').then(m => m.PanicComponent) },
];
