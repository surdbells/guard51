import { Component } from '@angular/core';

@Component({
  selector: 'g51-home',
  template: `
    <ActionBar title="Guard51" class="action-bar"></ActionBar>
    <ScrollView>
      <StackLayout class="p-4">
        <StackLayout class="bg-primary rounded-lg p-4 mb-4">
          <Label text="Good morning, Musa" class="text-xl text-white font-bold"></Label>
          <Label text="Employee #G-001" class="text-sm text-white opacity-70"></Label>
        </StackLayout>
        <Button text="CLOCK IN" class="btn-primary-lg text-center mb-4"></Button>
        <StackLayout class="card mb-4">
          <Label text="Current Shift" class="text-xs text-muted mb-1"></Label>
          <Label text="Lekki Phase 1 Estate" class="text-base font-bold"></Label>
          <Label text="06:00 AM - 06:00 PM" class="text-sm text-muted"></Label>
        </StackLayout>
        <GridLayout columns="*, *" rows="auto, auto" class="gap-3 mb-4">
          <StackLayout col="0" row="0" class="card-action"><Label text="📋" class="text-2xl text-center"></Label><Label text="Post Orders" class="text-xs text-center mt-1"></Label></StackLayout>
          <StackLayout col="1" row="0" class="card-action"><Label text="📝" class="text-2xl text-center"></Label><Label text="Passdown" class="text-xs text-center mt-1"></Label></StackLayout>
          <StackLayout col="0" row="1" class="card-action"><Label text="🗺️" class="text-2xl text-center"></Label><Label text="Site Tour" class="text-xs text-center mt-1"></Label></StackLayout>
          <StackLayout col="1" row="1" class="card-action-panic"><Label text="🚨" class="text-2xl text-center"></Label><Label text="PANIC" class="text-xs text-center mt-1 text-danger font-bold"></Label></StackLayout>
        </GridLayout>
        <Label text="Upcoming Shifts" class="text-sm font-bold mb-2"></Label>
        <StackLayout class="card mb-2">
          <Label text="Tomorrow • 06:00 - 18:00" class="text-sm"></Label>
          <Label text="Lekki Phase 1 Estate" class="text-xs text-muted"></Label>
        </StackLayout>
      </StackLayout>
    </ScrollView>
  `,
})
export class HomeComponent {}
