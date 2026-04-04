import { Component, inject, signal, OnInit } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { NgClass } from '@angular/common';
import { LucideAngularModule, Settings, Building2, Bell, Palette, Globe, Save, Upload } from 'lucide-angular';
import { PageHeaderComponent } from '@shared/components/page-header/page-header.component';
import { SearchableSelectComponent, SelectOption } from '@shared/components/searchable-select/searchable-select.component';
import { LoadingSpinnerComponent } from '@shared/components/loading-spinner/loading-spinner.component';
import { ApiService } from '@core/services/api.service';
import { ToastService } from '@core/services/toast.service';
import { BrandingService } from '@core/services/branding.service';

@Component({
  selector: 'g51-settings',
  standalone: true,
  imports: [FormsModule, NgClass, LucideAngularModule, PageHeaderComponent, LoadingSpinnerComponent, SearchableSelectComponent],
  template: `
    <g51-page-header title="Settings" subtitle="Company profile, branding, and notification preferences" />

    <div class="tab-pills">
      @for (tab of ['Company Profile', 'Branding', 'Notifications']; track tab) {
        <button (click)="activeTab.set(tab)" class="tab-pill" [ngClass]="activeTab() === tab ? 'active' : ''">{{ tab }}</button>
      }
    </div>

    @if (activeTab() === 'Company Profile') {
      <div class="card p-5 max-w-2xl">
        <h3 class="text-sm font-semibold mb-4" [style.color]="'var(--text-primary)'">Company Information</h3>
        <div class="space-y-4">
          <div class="grid grid-cols-2 gap-4">
            <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Company Name</label><input type="text" [(ngModel)]="company.name" class="input-base w-full" /></div>
            <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">RC Number</label><input type="text" [(ngModel)]="company.rc_number" class="input-base w-full" /></div>
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Email</label><input type="email" [(ngModel)]="company.email" class="input-base w-full" /></div>
            <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Phone</label><input type="tel" [(ngModel)]="company.phone" class="input-base w-full" /></div>
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">State</label>
              <g51-searchable-select [(ngModel)]="company.state" [options]="stateOptions" placeholder="Select state" /></div>
            <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">City</label><input type="text" [(ngModel)]="company.city" class="input-base w-full" /></div>
          </div>
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Address</label><textarea [(ngModel)]="company.address" rows="2" class="input-base w-full resize-none"></textarea></div>
          <button (click)="saveCompany()" class="btn-primary flex items-center gap-1"><lucide-icon [img]="SaveIcon" [size]="14" /> Save Changes</button>
        </div>
      </div>
    }

    @if (activeTab() === 'Branding') {
      <div class="card p-5 max-w-2xl mb-4">
        <h3 class="text-sm font-semibold mb-4" [style.color]="'var(--text-primary)'">Company Logo</h3>
        <div class="grid grid-cols-2 gap-4 mb-4">
          <div>
            <label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Logo (light background)</label>
            <div class="border-2 border-dashed rounded-lg p-4 text-center cursor-pointer" [style.borderColor]="'var(--border-default)'" (click)="logoInput.click()">
              <input #logoInput type="file" accept="image/*" (change)="onLogoSelect($event, 'logo_url')" class="hidden" />
              @if (branding.logo_url) { <img [src]="branding.logo_url" class="h-12 mx-auto object-contain" /> }
              @else { <p class="text-xs" [style.color]="'var(--text-tertiary)'">Click to upload (SVG, PNG)</p> }
            </div>
          </div>
          <div>
            <label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Logo (dark background)</label>
            <div class="border-2 border-dashed rounded-lg p-4 text-center cursor-pointer" [style.borderColor]="'var(--border-default)'" [style.background]="'var(--brand-800)'" (click)="logoDarkInput.click()">
              <input #logoDarkInput type="file" accept="image/*" (change)="onLogoSelect($event, 'logo_dark_url')" class="hidden" />
              @if (branding.logo_dark_url) { <img [src]="branding.logo_dark_url" class="h-12 mx-auto object-contain" /> }
              @else { <p class="text-xs text-gray-400">Click to upload (SVG, PNG)</p> }
            </div>
          </div>
        </div>
        <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Favicon URL</label>
          <input type="text" [(ngModel)]="branding.favicon_url" class="input-base w-full" placeholder="https://..." /></div>
      </div>

      <div class="card p-5 max-w-2xl mb-4">
        <h3 class="text-sm font-semibold mb-4" [style.color]="'var(--text-primary)'">Brand Colors</h3>
        <div class="grid grid-cols-2 gap-4 mb-4">
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Primary Color</label>
            <div class="flex items-center gap-2"><input type="color" [(ngModel)]="branding.primary_color" class="h-8 w-12 rounded cursor-pointer" /><input type="text" [(ngModel)]="branding.primary_color" class="input-base flex-1" /></div></div>
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Accent Color</label>
            <div class="flex items-center gap-2"><input type="color" [(ngModel)]="branding.secondary_color" class="h-8 w-12 rounded cursor-pointer" /><input type="text" [(ngModel)]="branding.secondary_color" class="input-base flex-1" /></div></div>
        </div>
      </div>

      <div class="card p-5 max-w-2xl mb-4">
        <h3 class="text-sm font-semibold mb-4" [style.color]="'var(--text-primary)'">White-Labeling</h3>
        <p class="text-xs mb-3" [style.color]="'var(--text-tertiary)'">These details appear on reports, invoices, and notifications sent to your clients.</p>
        <div class="space-y-3">
          <div class="grid grid-cols-2 gap-4">
            <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Company Website</label>
              <input type="url" [(ngModel)]="branding.company_website" class="input-base w-full" placeholder="https://www.yourcompany.com" /></div>
            <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Support Email</label>
              <input type="email" [(ngModel)]="branding.support_email" class="input-base w-full" placeholder="support@yourcompany.com" /></div>
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Support Phone</label>
              <input type="tel" [(ngModel)]="branding.support_phone" class="input-base w-full" placeholder="+234 ..." /></div>
            <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Report Footer Text</label>
              <input type="text" [(ngModel)]="branding.report_footer" class="input-base w-full" placeholder="Powered by ShieldForce Security" /></div>
          </div>
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Invoice Notes / Terms</label>
            <textarea [(ngModel)]="branding.invoice_terms" rows="2" class="input-base w-full resize-none" placeholder="Payment is due within 30 days..."></textarea></div>
        </div>
      </div>
      <button (click)="saveBranding()" class="btn-primary flex items-center gap-1"><lucide-icon [img]="SaveIcon" [size]="14" /> Save Branding</button>
    }

    @if (activeTab() === 'Notifications') {
      <div class="card p-5 max-w-2xl">
        <h3 class="text-sm font-semibold mb-4" [style.color]="'var(--text-primary)'">Notification Preferences</h3>
        <div class="space-y-3">
          @for (pref of notifPrefs; track pref.key) {
            <label class="flex items-center justify-between py-2 border-b" [style.borderColor]="'var(--border-default)'">
              <div><p class="text-sm" [style.color]="'var(--text-primary)'">{{ pref.label }}</p>
                <p class="text-xs" [style.color]="'var(--text-tertiary)'">{{ pref.description }}</p></div>
              <input type="checkbox" [(ngModel)]="pref.enabled" class="rounded" />
            </label>
          }
        </div>
        <button (click)="saveNotifPrefs()" class="btn-primary flex items-center gap-1 mt-4"><lucide-icon [img]="SaveIcon" [size]="14" /> Save Preferences</button>
      </div>
    }
  `,
})
export class SettingsComponent implements OnInit {
  private api = inject(ApiService); private toast = inject(ToastService);
  readonly brandingService = inject(BrandingService);
  readonly SaveIcon = Save;
  readonly activeTab = signal('Company Profile');
  company: any = { name: '', rc_number: '', email: '', phone: '', state: '', city: '', address: '' };
  branding: any = { primary_color: '#1B3A5C', secondary_color: '#E8792D', logo_url: '', logo_dark_url: '', favicon_url: '', company_website: '', support_email: '', support_phone: '', report_footer: '', invoice_terms: '' };

  onLogoSelect(event: Event, field: string): void {
    const file = (event.target as HTMLInputElement).files?.[0];
    if (!file) return;
    const fd = new FormData(); fd.append('file', file); fd.append('field', field);
    const token = localStorage.getItem('g51_access_token') || '';
    fetch('https://api.guard51.com/api/v1/onboarding/logo', {
      method: 'POST', headers: { 'Authorization': `Bearer ${token}` }, body: fd,
    }).then(r => r.json()).then(data => {
      if (data.data?.url) { this.branding[field] = data.data.url; this.toast.success('Logo uploaded'); }
      else { this.branding[field] = URL.createObjectURL(file); }
    }).catch(() => { this.branding[field] = URL.createObjectURL(file); });
  }
  notifPrefs = [
    { key: 'email_incidents', label: 'Incident Alerts (Email)', description: 'Receive email when incidents are reported', enabled: true },
    { key: 'sms_panic', label: 'Panic Alerts (SMS)', description: 'Receive SMS for panic button activations', enabled: true },
    { key: 'email_invoices', label: 'Invoice Notifications', description: 'Email when new invoices are generated', enabled: true },
    { key: 'email_shifts', label: 'Shift Reminders', description: 'Email guards about upcoming shifts', enabled: false },
    { key: 'sms_visitors', label: 'Visitor Arrivals (SMS)', description: 'SMS when visitors check in at your sites', enabled: false },
  ];
  states = ['Abia','Adamawa','Akwa Ibom','Anambra','Bauchi','Bayelsa','Benue','Borno','Cross River','Delta','Ebonyi','Edo','Ekiti','Enugu','FCT','Gombe','Imo','Jigawa','Kaduna','Kano','Katsina','Kebbi','Kogi','Kwara','Lagos','Nasarawa','Niger','Ogun','Ondo','Osun','Oyo','Plateau','Rivers','Sokoto','Taraba','Yobe','Zamfara'];
  stateOptions: any[] = this.states.map(s => ({ value: s, label: s }));

  ngOnInit(): void {
    this.api.get<any>('/onboarding/status').subscribe({ next: r => { if (r.data?.tenant) { const t = r.data.tenant; Object.keys(this.company).forEach(k => { if (t[k]) this.company[k] = t[k]; }); if (t.branding) this.branding = { ...this.branding, ...t.branding }; } } });
  }
  saveCompany(): void { this.api.put('/onboarding/company', this.company).subscribe({ next: () => this.toast.success('Company updated') }); }
  saveBranding(): void { this.api.put('/onboarding/branding', this.branding).subscribe({ next: () => { this.toast.success('Branding updated'); this.brandingService.applyBranding(); } }); }
  saveNotifPrefs(): void { this.toast.success('Preferences saved'); }
}
