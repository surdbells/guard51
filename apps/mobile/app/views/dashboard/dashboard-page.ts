import { EventData, Page } from '@nativescript/core';
import { DashboardViewModel } from './dashboard-view-model';

export function onLoaded(args: EventData) {
  const page = args.object as Page;
  const vm = new DashboardViewModel();
  page.bindingContext = vm;
  vm.load();
}
