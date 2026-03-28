import { Component } from '@angular/core';

@Component({
  selector: 'g51-client-tracking',
  template: `
    <ActionBar title="Live Tracking" class="action-bar"><NavigationButton text="Back" android.systemIcon="ic_menu_back"></NavigationButton></ActionBar>
    <GridLayout rows="2*, *">
      <StackLayout row="0" class="bg-gray-200"><Label text="📍 Live Map" class="text-center p-8 text-muted"></Label></StackLayout>
      <ScrollView row="1"><StackLayout class="p-3">
        <Label text="Active Guards" class="text-sm font-bold mb-2"></Label>
        <StackLayout class="card p-3 mb-2"><Label text="Musa Ibrahim" class="text-sm font-medium"></Label><Label text="Lekki Phase 1 • Moving • 78%" class="text-xs text-muted"></Label></StackLayout>
        <StackLayout class="card p-3 mb-2"><Label text="Chika Nwosu" class="text-sm font-medium"></Label><Label text="V.I. Office • Stationary • 92%" class="text-xs text-muted"></Label></StackLayout>
      </StackLayout></ScrollView>
    </GridLayout>
  `,
})
export class ClientTrackingComponent {}
