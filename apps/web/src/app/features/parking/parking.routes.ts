import { Routes } from '@angular/router';
export const PARKING_ROUTES: Routes = [
  { path: '', loadComponent: () => import('./parking.component').then(m => m.ParkingComponent) },
];
