import { Component } from '@angular/core';

@Component({
  selector: 'g51-client-reports',
  template: `
    <ActionBar title="Reports" class="action-bar"><NavigationButton text="Back" android.systemIcon="ic_menu_back"></NavigationButton></ActionBar>
    <ScrollView><StackLayout class="p-4">
      <Label text="Approved Reports" class="text-sm font-bold mb-3"></Label>
      <StackLayout class="card p-3 mb-2"><Label text="DAR — 2026-03-28" class="text-sm font-medium"></Label>
        <Label text="Routine patrol completed. All clear." class="text-xs text-muted mt-1" textWrap="true"></Label></StackLayout>
      <StackLayout class="card p-3 mb-2"><Label text="Night Shift — 2026-03-27" class="text-sm font-medium"></Label>
        <Label text="Quiet night. Visitor logs checked." class="text-xs text-muted mt-1" textWrap="true"></Label></StackLayout>
      <Label text="Incident Reports" class="text-sm font-bold mt-4 mb-3"></Label>
      <StackLayout class="card p-3 mb-2"><Label text="Suspicious vehicle near gate" class="text-sm font-medium"></Label>
        <Label text="Lekki Phase 1 • Medium • 2 hours ago" class="text-xs text-muted mt-1"></Label></StackLayout>
    </StackLayout></ScrollView>
  `,
})
export class ClientReportsComponent {}
