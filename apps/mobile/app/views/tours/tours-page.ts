import { EventData, Page, NavigatedData } from '@nativescript/core';
import { ToursViewModel } from './tours-view-model';
let vm: ToursViewModel;
export function onNavigatingTo(args: NavigatedData): void {
  const page = <Page>args.object;
  vm = new ToursViewModel();
  page.bindingContext = vm;
  vm.init();
}
export function goBack(args: EventData): void { (<any>args.object).page.frame.goBack(); }
