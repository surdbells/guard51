import { EventData, Page, NavigationEntry } from '@nativescript/core';
import { LoginViewModel } from './login-view-model';

export function onNavigatingTo(args: EventData) {
  const page = args.object as Page;
  page.bindingContext = new LoginViewModel();
}
