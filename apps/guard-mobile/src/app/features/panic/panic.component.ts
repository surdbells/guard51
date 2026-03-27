import { Component } from '@angular/core';

@Component({
  selector: 'g51-panic-mobile',
  template: `
    <ActionBar title="Emergency" class="action-bar action-bar-danger">
      <NavigationButton text="Back" android.systemIcon="ic_menu_back"></NavigationButton>
    </ActionBar>
    <StackLayout class="p-4 h-full" verticalAlignment="center">
      <Label text="🚨" class="text-6xl text-center mb-4"></Label>
      <Label text="PANIC BUTTON" class="text-xl text-center font-bold text-danger mb-2"></Label>
      <Label text="Press and hold to trigger emergency alert" class="text-sm text-center text-muted mb-8"></Label>
      <Button text="HOLD TO TRIGGER PANIC" class="btn-panic text-center"></Button>
      <Label text="Your GPS location will be sent immediately" class="text-xs text-center text-muted mt-4"></Label>
      <Label text="Dispatch, supervisor, and admin will be notified" class="text-xs text-center text-muted"></Label>
      <Button text="🎤 Record Voice Message" class="btn-secondary text-center mt-8"></Button>
    </StackLayout>
  `,
})
export class PanicMobileComponent {}
