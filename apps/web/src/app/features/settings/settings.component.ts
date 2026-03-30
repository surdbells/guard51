import { Component, inject, signal, OnInit } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { NgClass } from '@angular/common';
import { LucideAngularModule, Settings, Building, Palette, Bell, Shield, Globe } from 'lucide-angular';
import { PageHeaderComponent } from '@shared/components/page-header/page-header.component';
import { ApiService } from '@core/services/api.service';
import { ToastService } from '@core/services/toast.service';

@Component({
  selector: 'g51-settings',
  standalone: true,
  imports: [FormsModule, NgClass, LucideAngularModule, PageHeaderComponent],
  template: `
    <g51-page-header title="Company Settings" subtitle="Manage your organization, branding, and preferences" />
    <div class="flex gap-1 mb-6">
      @for (tab of ['General', 'Branding', 'Notifications']; track tab) {
        <button (click)="activeTab.set(tab)" class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors"
          [ngClass]="activeTab() === tab ? 'bg-[var(--color-brand-500)] text-white' : 'bg-[var(--surface-muted)]'"
          [style.color]="activeTab() !== tab ? 'var(--text-secondary)' : ''">{{ tab }}</button>
      }
    </div>
    @if (activeTab() === 'General') {
      <div class="card p-6 max-w-2xl space-y-4">
        <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Company Name</label>
          <input type="text" [(ngModel)]="form.company_name" class="input-base w-full" /></div>
        <div class="grid grid-cols-2 gap-4">
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Contact Email</label>
            <input type="email" [(ngModel)]="form.contact_email" class="input-base w-full" /></div>
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Contact Phone</label>
            <input type="tel" [(ngModel)]="form.contact_phone" class="input-base w-full" /></div>
        </div>
        <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Address</label>
          <textarea [(ngModel)]="form.address" rows="2" class="input-base w-full resize-none"></textarea></div>
        <div class="grid grid-cols-2 gap-4">
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">City</label>
            <input type="text" [(ngModel)]="form.city" class="input-base w-full" /></div>
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">State</label>
            <input type="text" [(ngModel)]="form.state" class="input-base w-full" /></div>
        </div>
        <div class="grid grid-cols-2 gap-4">
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Bank Name</label>
            <input type="text" [(ngModel)]="form.bank_name" class="input-base w-full" /></div>
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Account Number</label>
            <input type="text" [(ngModel)]="form.bank_account" class="input-base w-full" /></div>
        </div>
        <button (click)="saveGeneral()" class="btn-primary">Save Changes</button>
      </div>
    }
    @if (activeTab() === 'Branding') {
      <div class="card p-6 max-w-2xl space-y-4">
        <p class="text-xs" [style.color]="'var(--text-secondary)'">Customize your company branding. These settings affect client-facing portals and generated PDFs.</p>
        <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Primary Color</label>
          <input type="color" [(ngModel)]="form.brand_color" class="h-10 w-20 rounded cursor-pointer" /></div>
        <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Company Logo</label>
          @if (logoPreview) {
            <div class="relative inline-block mb-2"><img [src]="logoPreview" class="h-14 rounded border" [style.borderColor]="'var(--border-default)'" />
              <button (click)="logoPreview = null" class="absolute -top-2 -right-2 h-5 w-5 rounded-full bg-red-500 text-white flex items-center justify-center text-[10px]">✕</button></div>
          }
          <label class="btn-secondary inline-flex items-center gap-2 cursor-pointer text-xs">
            Upload Logo
            <input type="file" accept="image/*" (change)="onLogoSelect($event)" class="hidden" />
          </label></div>
        <button (click)="saveBranding()" class="btn-primary">Save Branding</button>
      </div>
    }
    @if (activeTab() === 'Notifications') {
      <div class="card p-6 max-w-2xl space-y-4">
        <p class="text-xs" [style.color]="'var(--text-secondary)'">Configure notification channels and preferences.</p>
        <div class="space-y-2">
          @for (n of notifPrefs; track n.key) {
            <div class="flex items-center justify-between py-2 border-b" [style.borderColor]="'var(--border-default)'">
              <span class="text-sm" [style.color]="'var(--text-primary)'">{{ n.label }}</span>
              <div class="flex gap-3">@for (ch of ['Push', 'SMS', 'Email']; track ch) {
                <label class="flex items-center gap-1 text-[10px]" [style.color]="'var(--text-tertiary)'">
                  <input type="checkbox" class="rounded" /> {{ ch }}</label>
              }</div></div>
          }
        </div>
      </div>
    }
    @if (activeTab() === 'Integrations') {
      <div class="card p-6 max-w-2xl space-y-4">
        <p class="text-xs" [style.color]="'var(--text-secondary)'">API keys for third-party integrations.</p>
        <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Paystack Secret Key</label>
          <input type="password" [(ngModel)]="form.paystack_secret" class="input-base w-full" /></div>
        <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">ZeptoMail API Key</label>
          <input type="password" [(ngModel)]="form.zeptomail_key" class="input-base w-full" /></div>
        <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Termii API Key</label>
          <input type="password" [(ngModel)]="form.termii_key" class="input-base w-full" /></div>
        <button (click)="saveIntegrations()" class="btn-primary">Save Integrations</button>
      </div>
    }
  `,
})
export class SettingsComponent implements OnInit {
  private api = inject(ApiService); private toast = inject(ToastService);
  readonly SettingsIcon = Settings;
  readonly activeTab = signal('General');
  form: any = {};
  logoPreview: string | null = null;
  onLogoSelect(e: Event): void { const f = (e.target as HTMLInputElement).files?.[0]; if (f) { const r = new FileReader(); r.onload = () => this.logoPreview = r.result as string; r.readAsDataURL(f); } }
  notifPrefs = [
    { key: 'shift_assigned', label: 'Shift Assigned' }, { key: 'clock_reminder', label: 'Clock-in Reminder' },
    { key: 'incident', label: 'New Incident' }, { key: 'panic', label: 'Panic Alert' },
    { key: 'report', label: 'Report Submitted' }, { key: 'invoice', label: 'Invoice Status' },
  ];
  ngOnInit(): void { this.api.get<any>('/onboarding/status').subscribe({ next: r => { if (r.data) this.form = r.data; } }); }
  saveGeneral(): void { this.api.put('/onboarding/company', this.form).subscribe({ next: () => this.toast.success('Settings saved') }); }
  saveBranding(): void { this.toast.success('Branding saved'); }
  saveIntegrations(): void { this.toast.success('Integrations saved'); }
}
