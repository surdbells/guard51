import { Component } from '@angular/core';

@Component({
  selector: 'g51-client-invoices',
  template: `
    <ActionBar title="Invoices" class="action-bar"><NavigationButton text="Back" android.systemIcon="ic_menu_back"></NavigationButton></ActionBar>
    <ScrollView><StackLayout class="p-4">
      <Label text="Your Invoices" class="text-sm font-bold mb-3"></Label>
      <StackLayout class="card p-3 mb-2">
        <GridLayout columns="*, auto"><StackLayout col="0"><Label text="INV-00012" class="text-sm font-bold font-mono"></Label>
          <Label text="March 2026 • Due Apr 15" class="text-xs text-muted"></Label></StackLayout>
          <StackLayout col="1" class="text-right"><Label text="₦2,150,000" class="text-sm font-bold"></Label>
            <Label text="Sent" class="text-xs text-blue"></Label></StackLayout></GridLayout>
      </StackLayout>
      <StackLayout class="card p-3 mb-2">
        <GridLayout columns="*, auto"><StackLayout col="0"><Label text="INV-00011" class="text-sm font-bold font-mono"></Label>
          <Label text="February 2026 • Paid Mar 5" class="text-xs text-muted"></Label></StackLayout>
          <StackLayout col="1" class="text-right"><Label text="₦2,150,000" class="text-sm font-bold"></Label>
            <Label text="Paid" class="text-xs text-success"></Label></StackLayout></GridLayout>
      </StackLayout>
    </StackLayout></ScrollView>
  `,
})
export class ClientInvoicesComponent {}
