import { NgModule } from '@angular/core';
import { Routes } from '@angular/router';
import { NativeScriptRouterModule } from '@nativescript/angular';

const routes: Routes = [
  { path: '', redirectTo: '/login', pathMatch: 'full' },
  { path: 'login', loadChildren: () => import('./features/home/login.module').then(m => m.LoginModule) },
  { path: 'dashboard', loadChildren: () => import('./features/dashboard/dashboard.module').then(m => m.DashboardModule) },
  { path: 'tracking', loadChildren: () => import('./features/tracking/tracking.module').then(m => m.TrackingModule) },
  { path: 'reports', loadChildren: () => import('./features/reports/reports.module').then(m => m.ReportsModule) },
  { path: 'invoices', loadChildren: () => import('./features/invoices/invoices.module').then(m => m.InvoicesModule) },
  { path: 'notifications', loadChildren: () => import('./features/notifications/notifications.module').then(m => m.NotificationsModule) },
  { path: 'chat', loadChildren: () => import('./features/chat/chat.module').then(m => m.ChatModule) },
];

@NgModule({
  imports: [NativeScriptRouterModule.forRoot(routes)],
  exports: [NativeScriptRouterModule],
})
export class AppRoutingModule {}
