import { Routes } from '@angular/router';
export const VEHICLE_PATROL_ROUTES: Routes = [
  { path: '', loadComponent: () => import('./vehicle-patrol.component').then(m => m.VehiclePatrolComponent) },
];
