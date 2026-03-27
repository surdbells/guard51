import { Component } from '@angular/core';

@Component({
  selector: 'g51-tasks-mobile',
  template: `
    <ActionBar title="My Tasks" class="action-bar">
      <NavigationButton text="Back" android.systemIcon="ic_menu_back"></NavigationButton>
    </ActionBar>
    <ScrollView>
      <StackLayout class="p-4">
        <Label text="Active Tasks" class="text-base font-bold mb-3"></Label>
        <StackLayout class="card mb-3">
          <GridLayout columns="auto, *, auto">
            <Label col="0" text="⬜" class="text-lg mr-3"></Label>
            <StackLayout col="1">
              <Label text="Check fire extinguisher expiry" class="text-sm font-bold"></Label>
              <Label text="Building A, Floor 2 • Due today" class="text-xs text-muted"></Label>
            </StackLayout>
            <Button col="2" text="Done" class="btn-sm btn-primary"></Button>
          </GridLayout>
        </StackLayout>
        <StackLayout class="card mb-3">
          <GridLayout columns="auto, *, auto">
            <Label col="0" text="⬜" class="text-lg mr-3"></Label>
            <StackLayout col="1">
              <Label text="Verify visitor logs" class="text-sm font-bold"></Label>
              <Label text="Main Gate • Due 6:00 PM" class="text-xs text-muted"></Label>
            </StackLayout>
            <Button col="2" text="Done" class="btn-sm btn-primary"></Button>
          </GridLayout>
        </StackLayout>
        <Label text="Completed" class="text-base font-bold mt-4 mb-3"></Label>
        <StackLayout class="card mb-3 opacity-60">
          <GridLayout columns="auto, *">
            <Label col="0" text="✅" class="text-lg mr-3"></Label>
            <StackLayout col="1">
              <Label text="Morning perimeter walk" class="text-sm font-bold"></Label>
              <Label text="Completed 06:15 AM" class="text-xs text-muted"></Label>
            </StackLayout>
          </GridLayout>
        </StackLayout>
      </StackLayout>
    </ScrollView>
  `,
})
export class TasksMobileComponent {}
