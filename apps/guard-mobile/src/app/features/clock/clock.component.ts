import { Component } from '@angular/core';

@Component({
  selector: 'g51-clock',
  template: `
    <ActionBar title="Time Clock" class="action-bar">
      <NavigationButton text="Back" android.systemIcon="ic_menu_back"></NavigationButton>
    </ActionBar>
    <StackLayout class="p-4">
      <StackLayout class="card mb-4">
        <Label text="📍 GPS Status" class="text-xs text-muted mb-1"></Label>
        <Label text="Lat: 6.4281, Lng: 3.4219" class="text-sm"></Label>
        <Label text="Accuracy: 8m • ✅ Inside Geofence" class="text-xs text-success mt-1"></Label>
      </StackLayout>
      <StackLayout class="card mb-4">
        <Label text="Assigned Site" class="text-xs text-muted mb-1"></Label>
        <Label text="Lekki Phase 1 Estate" class="text-base font-bold"></Label>
        <Label text="Shift: 06:00 - 18:00" class="text-sm text-muted"></Label>
      </StackLayout>
      <Button text="CLOCK IN" class="btn-primary-xl text-center mb-4"></Button>
      <Button text="📷 Take Selfie (Optional)" class="btn-secondary text-center"></Button>
    </StackLayout>
  `,
})
export class ClockComponent {}
