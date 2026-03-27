import { Routes } from '@angular/router';
export const TRACKER_ROUTES: Routes = [
  { path: '', loadComponent: () => import('./tracker.component').then(m => m.TrackerComponent) },
];
