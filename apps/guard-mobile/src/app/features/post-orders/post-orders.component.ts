import { Component } from '@angular/core';

@Component({
  selector: 'g51-post-orders-mobile',
  template: `
    <ActionBar title="Post Orders" class="action-bar">
      <NavigationButton text="Back" android.systemIcon="ic_menu_back"></NavigationButton>
    </ActionBar>
    <ScrollView>
      <StackLayout class="p-4">
        <Label text="Lekki Phase 1 Estate" class="text-base font-bold mb-3"></Label>
        <StackLayout class="card mb-3">
          <Label text="General Standing Orders" class="text-sm font-bold mb-1"></Label>
          <Label text="All visitors must sign in at the guardhouse. Vehicle number plates must be recorded. No unauthorized vehicles past 10PM." class="text-sm text-muted" textWrap="true"></Label>
        </StackLayout>
        <StackLayout class="card mb-3">
          <Label text="Emergency Procedures" class="text-sm font-bold mb-1"></Label>
          <Label text="In case of fire: sound alarm, call fire service (01-7907040), evacuate residents. In case of robbery: trigger panic button, do not engage." class="text-sm text-muted" textWrap="true"></Label>
        </StackLayout>
        <StackLayout class="card mb-3">
          <Label text="Access Control" class="text-sm font-bold mb-1"></Label>
          <Label text="Resident vehicles: auto-entry with sticker. Delivery vehicles: guardhouse verification. Contractor vehicles: require written authorization." class="text-sm text-muted" textWrap="true"></Label>
        </StackLayout>
      </StackLayout>
    </ScrollView>
  `,
})
export class PostOrdersMobileComponent {}
