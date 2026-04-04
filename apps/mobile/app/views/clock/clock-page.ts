import { EventData, Page, NavigatedData } from '@nativescript/core';
import { ClockViewModel } from './clock-view-model';

let vm: ClockViewModel;

export function onNavigatingTo(args: NavigatedData): void {
  const page = <Page>args.object;
  vm = new ClockViewModel();
  page.bindingContext = vm;
  vm.init();
}

export function goBack(args: EventData): void {
  const page = (<any>args.object).page;
  page.frame.goBack();
}
