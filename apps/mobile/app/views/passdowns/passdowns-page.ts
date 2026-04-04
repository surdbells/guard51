import { EventData, Page, NavigatedData } from '@nativescript/core';
import { PassdownsViewModel } from './passdowns-view-model';
let vm: PassdownsViewModel;
export function onNavigatingTo(args: NavigatedData): void {
  const page = <Page>args.object;
  vm = new PassdownsViewModel();
  page.bindingContext = vm;
  vm.init();
}
export function goBack(args: EventData): void { (<any>args.object).page.frame.goBack(); }
