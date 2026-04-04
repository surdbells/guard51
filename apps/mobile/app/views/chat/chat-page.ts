import { EventData, Page, NavigatedData } from '@nativescript/core';
import { ChatViewModel } from './chat-view-model';
let vm: ChatViewModel;
export function onNavigatingTo(args: NavigatedData): void {
  const page = <Page>args.object;
  vm = new ChatViewModel();
  page.bindingContext = vm;
  vm.init();
}
export function goBack(args: EventData): void { (<any>args.object).page.frame.goBack(); }
