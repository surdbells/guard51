import { Component } from '@angular/core';

@Component({
  selector: 'g51-client-dashboard',
  template: `
    <ActionBar title="Dashboard" class="action-bar"></ActionBar>
    <ScrollView>
      <StackLayout class="p-4">
        <GridLayout columns="*, *" class="gap-3 mb-4">
          <StackLayout col="0" class="card p-3">
            <Label text="Guards On Duty" class="text-[10px] text-muted"></Label>
            <Label text="4" class="text-2xl font-bold"></Label>
          </StackLayout>
          <StackLayout col="1" class="card p-3">
            <Label text="Incidents Today" class="text-[10px] text-muted"></Label>
            <Label text="1" class="text-2xl font-bold"></Label>
          </StackLayout>
        </GridLayout>
        <Label text="Guards On Duty" class="text-sm font-bold mb-2"></Label>
        <StackLayout class="card mb-3">
          <StackLayout class="p-3 border-b"><Label text="Musa Ibrahim" class="text-sm font-medium"></Label>
            <Label text="Lekki Phase 1 • Since 06:00 AM" class="text-xs text-muted"></Label></StackLayout>
          <StackLayout class="p-3 border-b"><Label text="Chika Nwosu" class="text-sm font-medium"></Label>
            <Label text="V.I. Office • Since 06:15 AM" class="text-xs text-muted"></Label></StackLayout>
          <StackLayout class="p-3"><Label text="Adebayo O." class="text-sm font-medium"></Label>
            <Label text="Lekki Phase 1 • Since 18:00 PM" class="text-xs text-muted"></Label></StackLayout>
        </StackLayout>
        <GridLayout columns="*, *, *" class="gap-2">
          <StackLayout col="0" class="card p-3 text-center"><Label text="📍" class="text-lg mb-1"></Label><Label text="Live Map" class="text-[10px]"></Label></StackLayout>
          <StackLayout col="1" class="card p-3 text-center"><Label text="📄" class="text-lg mb-1"></Label><Label text="Reports" class="text-[10px]"></Label></StackLayout>
          <StackLayout col="2" class="card p-3 text-center"><Label text="💬" class="text-lg mb-1"></Label><Label text="Messages" class="text-[10px]"></Label></StackLayout>
        </GridLayout>
      </StackLayout>
    </ScrollView>
  `,
})
export class ClientDashboardComponent {}
