import { Component } from '@angular/core';

@Component({
  selector: 'g51-schedule',
  template: `
    <ActionBar title="My Schedule" class="action-bar">
      <NavigationButton text="Back" android.systemIcon="ic_menu_back"></NavigationButton>
    </ActionBar>
    <ScrollView>
      <StackLayout class="p-4">
        <Label text="This Week" class="text-base font-bold mb-3"></Label>
        <StackLayout class="card mb-3">
          <GridLayout columns="*, auto">
            <StackLayout col="0">
              <Label text="Monday, Mar 27" class="text-sm font-bold"></Label>
              <Label text="06:00 - 18:00 • Day Shift" class="text-xs text-muted"></Label>
              <Label text="Lekki Phase 1 Estate" class="text-xs text-muted"></Label>
            </StackLayout>
            <Label col="1" text="✅ Confirmed" class="text-xs text-success"></Label>
          </GridLayout>
        </StackLayout>
        <StackLayout class="card mb-3">
          <GridLayout columns="*, auto">
            <StackLayout col="0">
              <Label text="Tuesday, Mar 28" class="text-sm font-bold"></Label>
              <Label text="06:00 - 18:00 • Day Shift" class="text-xs text-muted"></Label>
            </StackLayout>
            <Button col="1" text="Confirm" class="btn-sm btn-primary"></Button>
          </GridLayout>
        </StackLayout>
        <Label text="Open Shifts" class="text-base font-bold mt-4 mb-3"></Label>
        <StackLayout class="card-highlight mb-3">
          <Label text="Wednesday, Mar 29 • 18:00 - 06:00" class="text-sm font-bold"></Label>
          <Label text="Victoria Island HQ" class="text-xs text-muted"></Label>
          <Button text="Claim Shift" class="btn-sm btn-warning mt-2"></Button>
        </StackLayout>
      </StackLayout>
    </ScrollView>
  `,
})
export class ScheduleComponent {}
