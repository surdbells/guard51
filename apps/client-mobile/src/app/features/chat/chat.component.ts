import { Component } from '@angular/core';

@Component({
  selector: 'g51-client-chat',
  template: `
    <ActionBar title="Messages" class="action-bar"><NavigationButton text="Back" android.systemIcon="ic_menu_back"></NavigationButton></ActionBar>
    <GridLayout rows="*, auto">
      <ScrollView row="0"><StackLayout class="p-4">
        <Label text="Conversations" class="text-sm font-bold mb-3"></Label>
        <StackLayout class="card p-3 mb-2"><GridLayout columns="auto, *, auto">
          <Label col="0" text="🏢" class="text-lg mr-2"></Label>
          <StackLayout col="1"><Label text="Security Operations" class="text-sm font-medium"></Label><Label text="Your dedicated team" class="text-xs text-muted"></Label></StackLayout>
          <Label col="2" text="2" class="badge text-[10px]"></Label>
        </GridLayout></StackLayout>
        <StackLayout class="card p-3 mb-2"><GridLayout columns="auto, *">
          <Label col="0" text="#" class="text-lg mr-2"></Label>
          <StackLayout col="1"><Label text="Lekki Phase 1" class="text-sm font-medium"></Label><Label text="Site updates" class="text-xs text-muted"></Label></StackLayout>
        </GridLayout></StackLayout>
      </StackLayout></ScrollView>
      <GridLayout row="1" columns="*, auto" class="p-3 border-t">
        <TextField col="0" hint="Type a message..." class="input mr-2"></TextField>
        <Button col="1" text="Send" class="btn-primary btn-sm"></Button>
      </GridLayout>
    </GridLayout>
  `,
})
export class ClientChatComponent {}
