import { Component } from '@angular/core';

@Component({
  selector: 'g51-client-notifications',
  template: `
    <ActionBar title="Notifications" class="action-bar"><NavigationButton text="Back" android.systemIcon="ic_menu_back"></NavigationButton></ActionBar>
    <ScrollView><StackLayout class="p-4">
      <StackLayout class="card p-3 mb-2" style="border-left: 3px solid #3b82f6;">
        <Label text="Guard Clocked In" class="text-sm font-medium"></Label><Label text="Musa Ibrahim at Lekki Phase 1" class="text-xs text-muted"></Label>
        <Label text="06:02 AM" class="text-[10px] text-muted mt-1"></Label></StackLayout>
      <StackLayout class="card p-3 mb-2" style="border-left: 3px solid #f59e0b;">
        <Label text="New Incident" class="text-sm font-medium"></Label><Label text="Suspicious vehicle near gate" class="text-xs text-muted"></Label>
        <Label text="2 hours ago" class="text-[10px] text-muted mt-1"></Label></StackLayout>
      <StackLayout class="card p-3 mb-2">
        <Label text="Report Available" class="text-sm font-medium"></Label><Label text="DAR approved for your review" class="text-xs text-muted"></Label>
        <Label text="3 hours ago" class="text-[10px] text-muted mt-1"></Label></StackLayout>
      <StackLayout class="card p-3 mb-2">
        <Label text="Invoice Sent" class="text-sm font-medium"></Label><Label text="INV-00012 • ₦2,150,000" class="text-xs text-muted"></Label>
        <Label text="Yesterday" class="text-[10px] text-muted mt-1"></Label></StackLayout>
    </StackLayout></ScrollView>
  `,
})
export class ClientNotificationsComponent {}
