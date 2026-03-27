import { Component } from '@angular/core';

@Component({
  selector: 'g51-tours-mobile',
  template: `
    <ActionBar title="Site Tour" class="action-bar">
      <NavigationButton text="Back" android.systemIcon="ic_menu_back"></NavigationButton>
    </ActionBar>
    <ScrollView>
      <StackLayout class="p-4">
        <StackLayout class="card mb-4">
          <Label text="Tour Progress" class="text-xs text-muted mb-2"></Label>
          <Progress value="40" maxValue="100" class="mb-1"></Progress>
          <Label text="2 of 5 checkpoints scanned" class="text-sm font-bold"></Label>
        </StackLayout>
        <Label text="Checkpoints" class="text-base font-bold mb-3"></Label>
        <StackLayout class="card mb-2 card-success">
          <GridLayout columns="auto, *">
            <Label col="0" text="✅" class="text-lg mr-3"></Label>
            <StackLayout col="1"><Label text="1. Main Gate" class="text-sm font-bold"></Label><Label text="QR Code • Scanned 06:05" class="text-xs text-muted"></Label></StackLayout>
          </GridLayout>
        </StackLayout>
        <StackLayout class="card mb-2 card-success">
          <GridLayout columns="auto, *">
            <Label col="0" text="✅" class="text-lg mr-3"></Label>
            <StackLayout col="1"><Label text="2. Parking Lot A" class="text-sm font-bold"></Label><Label text="Virtual GPS • Auto-scanned 06:12" class="text-xs text-muted"></Label></StackLayout>
          </GridLayout>
        </StackLayout>
        <StackLayout class="card mb-2 card-pending">
          <GridLayout columns="auto, *, auto">
            <Label col="0" text="⬜" class="text-lg mr-3"></Label>
            <StackLayout col="1"><Label text="3. Building B Entrance" class="text-sm font-bold"></Label><Label text="NFC Tag • Pending" class="text-xs text-muted"></Label></StackLayout>
            <Button col="2" text="Scan" class="btn-sm btn-primary"></Button>
          </GridLayout>
        </StackLayout>
        <StackLayout class="card mb-2 card-pending">
          <GridLayout columns="auto, *, auto">
            <Label col="0" text="⬜" class="text-lg mr-3"></Label>
            <StackLayout col="1"><Label text="4. Server Room" class="text-sm font-bold"></Label><Label text="NFC Tag • Pending" class="text-xs text-muted"></Label></StackLayout>
            <Button col="2" text="Scan" class="btn-sm btn-primary"></Button>
          </GridLayout>
        </StackLayout>
        <StackLayout class="card mb-2 card-pending">
          <GridLayout columns="auto, *, auto">
            <Label col="0" text="⬜" class="text-lg mr-3"></Label>
            <StackLayout col="1"><Label text="5. Back Gate" class="text-sm font-bold"></Label><Label text="QR Code • Pending" class="text-xs text-muted"></Label></StackLayout>
            <Button col="2" text="Scan" class="btn-sm btn-primary"></Button>
          </GridLayout>
        </StackLayout>
      </StackLayout>
    </ScrollView>
  `,
})
export class ToursMobileComponent {}
