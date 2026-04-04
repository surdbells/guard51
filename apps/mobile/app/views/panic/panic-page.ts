import { EventData, Page, NavigatedData } from '@nativescript/core';
import { PanicViewModel } from './panic-view-model';

let vm: PanicViewModel;

export function onNavigatingTo(args: NavigatedData): void {
  const page = <Page>args.object;
  vm = new PanicViewModel();
  page.bindingContext = vm;
  vm.init();
}
