import { Routes } from '@angular/router';

export const SCHEDULING_ROUTES: Routes = [
  { path: '', loadComponent: () => import('./scheduling.component').then(m => m.SchedulingComponent) },
  { path: 'templates', loadComponent: () => import('./templates.component').then(m => m.TemplatesComponent) },
  { path: 'new', loadComponent: () => import('./shift-form.component').then(m => m.ShiftFormComponent) },
  { path: 'bulk', loadComponent: () => import('./bulk-wizard.component').then(m => m.BulkWizardComponent) },
  { path: 'open-shifts', loadComponent: () => import('./open-shifts.component').then(m => m.OpenShiftsComponent) },
  { path: 'swaps', loadComponent: () => import('./swap-requests.component').then(m => m.SwapRequestsComponent) },
];
