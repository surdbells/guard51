import { EventData, Page } from '@nativescript/core';
import { IncidentViewModel } from './incident-view-model';

export function onLoaded(args: EventData) {
  const page = args.object as Page;
  const vm = new IncidentViewModel();
  page.bindingContext = vm;
  vm.init();
}
