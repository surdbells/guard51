import { NgModule } from '@angular/core';
import { Routes } from '@angular/router';
import { NativeScriptRouterModule } from '@nativescript/angular';
const routes: Routes = [
  { path: '', redirectTo: '/login', pathMatch: 'full' },
  { path: 'login', loadChildren: () => import('./features/home/login.module').then(m => m.LoginModule) },
  { path: 'console', loadChildren: () => import('./features/console/console.module').then(m => m.ConsoleModule) },
  { path: 'map', loadChildren: () => import('./features/map/map.module').then(m => m.MapModule) },
  { path: 'panic', loadChildren: () => import('./features/panic/panic.module').then(m => m.PanicModule) },
  { path: 'history', loadChildren: () => import('./features/history/history.module').then(m => m.HistoryModule) },
  { path: 'reports', loadChildren: () => import('./features/reports/reports.module').then(m => m.ReportsModule) },
];
@NgModule({ imports: [NativeScriptRouterModule.forRoot(routes)], exports: [NativeScriptRouterModule] })
export class AppRoutingModule {}
