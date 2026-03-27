import { NgModule } from '@angular/core';
import { Routes } from '@angular/router';
import { NativeScriptRouterModule } from '@nativescript/angular';

const routes: Routes = [
  { path: '', redirectTo: '/home', pathMatch: 'full' },
  { path: 'login', loadChildren: () => import('./features/home/login.module').then(m => m.LoginModule) },
  { path: 'home', loadChildren: () => import('./features/home/home.module').then(m => m.HomeModule) },
  { path: 'schedule', loadChildren: () => import('./features/schedule/schedule.module').then(m => m.ScheduleModule) },
  { path: 'clock', loadChildren: () => import('./features/clock/clock.module').then(m => m.ClockModule) },
  { path: 'post-orders', loadChildren: () => import('./features/post-orders/post-orders.module').then(m => m.PostOrdersModule) },
  { path: 'passdown', loadChildren: () => import('./features/passdown/passdown.module').then(m => m.PassdownModule) },
  { path: 'tours', loadChildren: () => import('./features/tours/tours.module').then(m => m.ToursModule) },
  { path: 'panic', loadChildren: () => import('./features/panic/panic.module').then(m => m.PanicModule) },
];

@NgModule({
  imports: [NativeScriptRouterModule.forRoot(routes)],
  exports: [NativeScriptRouterModule],
})
export class AppRoutingModule {}
