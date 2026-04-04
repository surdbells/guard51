import { EventData, Page, Frame } from '@nativescript/core';

function navigate(page: Page, moduleName: string): void {
  const frame = page.getViewById<Frame>('contentFrame');
  if (frame) frame.navigate({ moduleName, clearHistory: false });
}

export function onNavigatingTo(args: any): void {}
export function navDashboard(args: EventData): void { navigate((<any>args.object).page, 'app/views/dashboard/dashboard-page'); }
export function navClock(args: EventData): void { navigate((<any>args.object).page, 'app/views/clock/clock-page'); }
export function navPanic(args: EventData): void { navigate((<any>args.object).page, 'app/views/panic/panic-page'); }
export function navTours(args: EventData): void { navigate((<any>args.object).page, 'app/views/tours/tours-page'); }
export function navChat(args: EventData): void { navigate((<any>args.object).page, 'app/views/chat/chat-page'); }
