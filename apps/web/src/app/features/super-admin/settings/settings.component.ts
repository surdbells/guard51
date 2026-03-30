import { Component, inject, signal, OnInit } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { NgClass } from '@angular/common';
import { LucideAngularModule, Settings, Upload, X } from 'lucide-angular';
import { PageHeaderComponent } from '@shared/components/page-header/page-header.component';
import { ApiService } from '@core/services/api.service';
import { ToastService } from '@core/services/toast.service';

@Component({
  selector: 'g51-sa-settings',
  standalone: true,
  imports: [FormsModule, NgClass, LucideAngularModule, PageHeaderComponent],
  template: `
    <g51-page-header title="Platform Settings" subtitle="Guard51 SaaS configuration" />
    <div class="flex gap-1 mb-6">
      @for (tab of ['General', 'Branding', 'Email', 'Security']; track tab) {
        <button (click)="activeTab.set(tab)" class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors"
          [ngClass]="activeTab() === tab ? 'bg-[var(--color-brand-500)] text-white' : 'bg-[var(--surface-muted)]'" [style.color]="activeTab() !== tab ? 'var(--text-secondary)' : ''">{{ tab }}</button>
      }
    </div>
    @if (activeTab() === 'General') {
      <div class="card p-6 max-w-2xl space-y-4">
        <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Platform Name</label><input type="text" [(ngModel)]="form.platform_name" class="input-base w-full" /></div>
        <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Support Email</label><input type="email" [(ngModel)]="form.support_email" class="input-base w-full" /></div>
        <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Default Trial Days</label><input type="number" [(ngModel)]="form.trial_days" class="input-base w-full" /></div>
        <div class="flex items-center gap-2">
          <input type="checkbox" [(ngModel)]="form.registration_open" class="rounded" />
          <label class="text-xs" [style.color]="'var(--text-secondary)'">Allow new company registration</label>
        </div>
        <div class="flex items-center gap-2">
          <input type="checkbox" [(ngModel)]="form.maintenance_mode" class="rounded" />
          <label class="text-xs" [style.color]="'var(--text-secondary)'">Maintenance mode</label>
        </div>
        <button (click)="saveSettings()" class="btn-primary">Save</button>
      </div>
    }
    @if (activeTab() === 'Branding') {
      <div class="card p-6 max-w-2xl space-y-4">
        <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Platform Logo</label>
          @if (logoPreview()) {
            <div class="relative inline-block mb-2"><img [src]="logoPreview()" class="h-16 rounded border" [style.borderColor]="'var(--border-default)'" />
              <button (click)="removeLogo()" class="absolute -top-2 -right-2 h-5 w-5 rounded-full bg-red-500 text-white flex items-center justify-center"><lucide-icon [img]="XIcon" [size]="10" /></button></div>
          }
          <label class="btn-secondary inline-flex items-center gap-2 cursor-pointer text-xs">
            <lucide-icon [img]="UploadIcon" [size]="14" /> Upload Logo
            <input type="file" accept="image/*" (change)="onLogoSelect($event)" class="hidden" />
          </label>
        </div>
        <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Primary Brand Color</label>
          <input type="color" [(ngModel)]="form.brand_color" class="h-10 w-20 rounded cursor-pointer" /></div>
        <button (click)="saveSettings()" class="btn-primary">Save Branding</button>
      </div>
    }
    @if (activeTab() === 'Email') {
      <div class="card p-6 max-w-2xl space-y-4">
        <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">ZeptoMail API Key</label><input type="password" [(ngModel)]="form.zeptomail_key" class="input-base w-full" /></div>
        <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">From Email</label><input type="email" [(ngModel)]="form.zeptomail_from" class="input-base w-full" /></div>
        <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Termii API Key</label><input type="password" [(ngModel)]="form.termii_key" class="input-base w-full" /></div>
        <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Termii Sender ID</label><input type="text" [(ngModel)]="form.termii_sender" class="input-base w-full" /></div>
        <button (click)="saveSettings()" class="btn-primary">Save Email Config</button>
      </div>
    }
    @if (activeTab() === 'Security') {
      <div class="card p-6 max-w-2xl space-y-4">
        <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Paystack Public Key</label><input type="text" [(ngModel)]="form.paystack_public" class="input-base w-full" /></div>
        <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Paystack Secret Key</label><input type="password" [(ngModel)]="form.paystack_secret" class="input-base w-full" /></div>
        <button (click)="saveSettings()" class="btn-primary">Save</button>
      </div>
    }
  `,
})
export class SaSettingsComponent implements OnInit {
  private api = inject(ApiService); private toast = inject(ToastService);
  readonly SettingsIcon = Settings; readonly UploadIcon = Upload; readonly XIcon = X;
  readonly activeTab = signal('General'); readonly logoPreview = signal<string | null>(null);
  private logoFile: File | null = null;
  form: any = { platform_name: 'Guard51', support_email: '', trial_days: 14, registration_open: true, maintenance_mode: false, brand_color: '#1B3A5C', zeptomail_key: '', zeptomail_from: '', termii_key: '', termii_sender: '', paystack_public: '', paystack_secret: '' };
  ngOnInit(): void {
    this.api.get<any>('/admin/settings').subscribe({ next: res => { if (res.data) Object.assign(this.form, res.data); if (res.data?.logo_url) this.logoPreview.set(res.data.logo_url); }, error: () => {} });
  }
  onLogoSelect(e: Event): void { const f = (e.target as HTMLInputElement).files?.[0]; if (f) { this.logoFile = f; const r = new FileReader(); r.onload = () => this.logoPreview.set(r.result as string); r.readAsDataURL(f); } }
  removeLogo(): void { this.logoPreview.set(null); this.logoFile = null; }
  saveSettings(): void { this.api.put('/admin/settings', this.form).subscribe({ next: () => this.toast.success('Settings saved'), error: () => this.toast.error('Failed to save') }); }
}
