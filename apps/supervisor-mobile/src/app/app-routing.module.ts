import { NgModule } from '@angular/core';
import { Routes } from '@angular/router';
import { NativeScriptRouterModule } from '@nativescript/angular';

const routes: Routes = [
  { path: '', redirectTo: '/login', pathMatch: 'full' },
  { path: 'login', loadChildren: () => import('./features/home/login.module').then(m => m.LoginModule) },
  { path: 'dashboard', loadChildren: () => import('./features/dashboard/dashboard.module').then(m => m.DashboardModule) },
  { path: 'tracking', loadChildren: () => import('./features/tracking/tracking.module').then(m => m.TrackingModule) },
  { path: 'attendance', loadChildren: () => import('./features/attendance/attendance.module').then(m => m.AttendanceModule) },
  { path: 'incidents', loadChildren: () => import('./features/incidents/incidents.module').then(m => m.IncidentsModule) },
  { path: 'reports', loadChildren: () => import('./features/reports/reports.module').then(m => m.ReportsModule) },
  { path: 'dispatch', loadChildren: () => import('./features/dispatch/dispatch.module').then(m => m.DispatchModule) },
  { path: 'performance', loadChildren: () => import('./features/performance/performance.module').then(m => m.PerformanceModule) },
];

@NgModule({ imports: [NativeScriptRouterModule.forRoot(routes)], exports: [NativeScriptRouterModule] })
export class AppRoutingModule {}
