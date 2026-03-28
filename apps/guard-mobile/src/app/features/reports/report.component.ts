import { Component } from '@angular/core';

@Component({
  selector: 'g51-report-mobile',
  template: `
    <ActionBar title="Activity Report" class="action-bar">
      <NavigationButton text="Back" android.systemIcon="ic_menu_back"></NavigationButton>
    </ActionBar>
    <ScrollView>
      <StackLayout class="p-4">
        <Label text="Daily Activity Report" class="text-base font-bold mb-3"></Label>
        <Label text="Document your shift activities, incidents, and observations." class="text-xs text-muted mb-4"></Label>

        <!-- Auto-filled header -->
        <StackLayout class="card mb-4">
          <Label text="Report Details" class="text-xs text-muted mb-2"></Label>
          <GridLayout columns="*, *" rows="auto, auto" class="gap-2">
            <StackLayout col="0" row="0">
              <Label text="Date" class="text-[10px] text-muted"></Label>
              <Label text="2026-03-28" class="text-sm font-medium"></Label>
            </StackLayout>
            <StackLayout col="1" row="0">
              <Label text="Weather" class="text-[10px] text-muted"></Label>
              <TextField hint="Clear skies" class="input text-sm"></TextField>
            </StackLayout>
            <StackLayout col="0" row="1">
              <Label text="Site" class="text-[10px] text-muted"></Label>
              <Label text="Lekki Phase 1" class="text-sm font-medium"></Label>
            </StackLayout>
            <StackLayout col="1" row="1">
              <Label text="Shift" class="text-[10px] text-muted"></Label>
              <Label text="Day (06:00-18:00)" class="text-sm font-medium"></Label>
            </StackLayout>
          </GridLayout>
        </StackLayout>

        <!-- Report content -->
        <Label text="Report Content *" class="text-xs text-muted mb-1"></Label>
        <TextView hint="Describe patrol activities, incidents observed, visitor interactions, key handover notes..." class="input-area mb-4" height="200"></TextView>

        <!-- Attachments -->
        <Label text="Attachments" class="text-xs text-muted mb-2"></Label>
        <GridLayout columns="*, *" class="gap-2 mb-4">
          <Button col="0" text="📷 Take Photo" class="btn-secondary text-center"></Button>
          <Button col="1" text="🎥 Record Video" class="btn-secondary text-center"></Button>
        </GridLayout>

        <!-- Watch mode shortcut -->
        <StackLayout class="card-highlight mb-4">
          <Label text="📸 Watch Mode" class="text-sm font-bold mb-1"></Label>
          <Label text="Quick-capture photos that auto-upload to the admin dashboard" class="text-xs text-muted" textWrap="true"></Label>
          <Button text="Open Watch Mode" class="btn-sm btn-secondary mt-2"></Button>
        </StackLayout>

        <!-- Actions -->
        <GridLayout columns="*, *" class="gap-3">
          <Button col="0" text="Save Draft" class="btn-secondary text-center"></Button>
          <Button col="1" text="Submit Report" class="btn-primary text-center"></Button>
        </GridLayout>

        <!-- Draft indicator -->
        <Label text="💾 Auto-saved 2 minutes ago" class="text-[10px] text-muted text-center mt-3"></Label>
      </StackLayout>
    </ScrollView>
  `,
})
export class ReportMobileComponent {}
