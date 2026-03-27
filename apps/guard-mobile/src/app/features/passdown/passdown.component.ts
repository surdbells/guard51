import { Component } from '@angular/core';

@Component({
  selector: 'g51-passdown-mobile',
  template: `
    <ActionBar title="Passdown Log" class="action-bar">
      <NavigationButton text="Back" android.systemIcon="ic_menu_back"></NavigationButton>
    </ActionBar>
    <ScrollView>
      <StackLayout class="p-4">
        <Label text="Shift Handover Notes" class="text-base font-bold mb-3"></Label>
        <Label text="Write your handover notes for the incoming guard." class="text-xs text-muted mb-4"></Label>
        <TextView hint="What happened during your shift? Any issues to report? Key handover items..." class="input-area mb-4" height="200"></TextView>
        <Label text="Priority" class="text-xs text-muted mb-1"></Label>
        <StackLayout class="card mb-4">
          <GridLayout columns="*, *, *" class="gap-2">
            <Button col="0" text="Normal" class="btn-sm btn-primary"></Button>
            <Button col="1" text="Important" class="btn-sm btn-secondary"></Button>
            <Button col="2" text="Urgent" class="btn-sm btn-secondary"></Button>
          </GridLayout>
        </StackLayout>
        <Button text="📎 Attach Photo" class="btn-secondary text-center mb-3"></Button>
        <Button text="Submit Passdown" class="btn-primary text-center"></Button>
      </StackLayout>
    </ScrollView>
  `,
})
export class PassdownMobileComponent {}
