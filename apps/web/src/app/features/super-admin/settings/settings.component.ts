import { Component, inject, signal, OnInit } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { NgClass } from '@angular/common';
import { LucideAngularModule, Settings, Save, Key, Mail, MessageSquare, CreditCard } from 'lucide-angular';
import { PageHeaderComponent } from '@shared/components/page-header/page-header.component';
import { LoadingSpinnerComponent } from '@shared/components/loading-spinner/loading-spinner.component';
import { ApiService } from '@core/services/api.service';
import { ToastService } from '@core/services/toast.service';

@Component({
  selector: 'g51-sa-settings',
  standalone: true,
  imports: [FormsModule, NgClass, LucideAngularModule, PageHeaderComponent, LoadingSpinnerComponent],
  template: `
    <g51-page-header title="Platform Settings" subtitle="API keys, integrations, and configuration" />
    <div class="tab-pills">
      @for (tab of ['API Keys', 'Email', 'SMS', 'Payment', 'General']; track tab) {
        <button (click)="activeTab.set(tab)" class="tab-pill" [ngClass]="activeTab() === tab ? 'active' : ''">{{ tab }}</button>
      }
    </div>

    @if (activeTab() === 'API Keys') {
      <div class="card p-5 max-w-2xl">
        <h3 class="text-sm font-semibold mb-4" [style.color]="'var(--text-primary)'">API Configuration</h3>
        <div class="space-y-4">
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">JWT Secret</label>
            <input type="password" [(ngModel)]="settings.jwt_secret" class="input-base w-full font-mono" placeholder="Auto-generated" /></div>
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Encryption Key</label>
            <input type="password" [(ngModel)]="settings.encryption_key" class="input-base w-full font-mono" placeholder="Base64-encoded 32 bytes" /></div>
          <p class="text-[10px]" [style.color]="'var(--text-tertiary)'">These are loaded from .env on the server. Changes here update the database config only.</p>
        </div>
      </div>
    }

    @if (activeTab() === 'Email') {
      <div class="card p-5 max-w-2xl">
        <h3 class="text-sm font-semibold mb-4" [style.color]="'var(--text-primary)'">ZeptoMail Configuration</h3>
        <div class="space-y-4">
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">ZeptoMail API Key</label>
            <input type="password" [(ngModel)]="settings.zeptomail_key" class="input-base w-full font-mono" /></div>
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">From Email</label>
            <input type="email" [(ngModel)]="settings.email_from" class="input-base w-full" placeholder="noreply@guard51.com" /></div>
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">From Name</label>
            <input type="text" [(ngModel)]="settings.email_from_name" class="input-base w-full" placeholder="Guard51" /></div>
          <button (click)="saveSection('email')" class="btn-primary flex items-center gap-1"><lucide-icon [img]="SaveIcon" [size]="14" /> Save</button>
        </div>
      </div>
    }

    @if (activeTab() === 'SMS') {
      <div class="card p-5 max-w-2xl">
        <h3 class="text-sm font-semibold mb-4" [style.color]="'var(--text-primary)'">Termii SMS Configuration</h3>
        <div class="space-y-4">
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Termii API Key</label>
            <input type="password" [(ngModel)]="settings.termii_key" class="input-base w-full font-mono" /></div>
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Sender ID</label>
            <input type="text" [(ngModel)]="settings.termii_sender" class="input-base w-full" placeholder="Guard51" /></div>
          <button (click)="saveSection('sms')" class="btn-primary flex items-center gap-1"><lucide-icon [img]="SaveIcon" [size]="14" /> Save</button>
        </div>
      </div>
    }

    @if (activeTab() === 'Payment') {
      <div class="card p-5 max-w-2xl">
        <h3 class="text-sm font-semibold mb-4" [style.color]="'var(--text-primary)'">Paystack Configuration</h3>
        <div class="space-y-4">
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Paystack Public Key</label>
            <input type="text" [(ngModel)]="settings.paystack_public" class="input-base w-full font-mono" /></div>
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Paystack Secret Key</label>
            <input type="password" [(ngModel)]="settings.paystack_secret" class="input-base w-full font-mono" /></div>
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Webhook Secret</label>
            <input type="password" [(ngModel)]="settings.paystack_webhook" class="input-base w-full font-mono" /></div>
          <button (click)="saveSection('payment')" class="btn-primary flex items-center gap-1"><lucide-icon [img]="SaveIcon" [size]="14" /> Save</button>
        </div>
      </div>
    }

    @if (activeTab() === 'General') {
      <div class="card p-5 max-w-2xl">
        <h3 class="text-sm font-semibold mb-4" [style.color]="'var(--text-primary)'">General Platform Settings</h3>
        <div class="space-y-4">
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Platform Name</label>
            <input type="text" [(ngModel)]="settings.platform_name" class="input-base w-full" placeholder="Guard51" /></div>
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Default Trial Days</label>
            <input type="number" [(ngModel)]="settings.default_trial_days" class="input-base w-full" /></div>
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Support Email</label>
            <input type="email" [(ngModel)]="settings.support_email" class="input-base w-full" placeholder="support@guard51.com" /></div>
          <label class="flex items-center gap-2 text-xs"><input type="checkbox" [(ngModel)]="settings.registration_open" /> Allow new company registration</label>
          <label class="flex items-center gap-2 text-xs"><input type="checkbox" [(ngModel)]="settings.maintenance_mode" /> Maintenance mode</label>
          <button (click)="saveSection('general')" class="btn-primary flex items-center gap-1"><lucide-icon [img]="SaveIcon" [size]="14" /> Save</button>
        </div>
      </div>
    }
  `,
})
export class SaSettingsComponent implements OnInit {
  private api = inject(ApiService); private toast = inject(ToastService);
  readonly SaveIcon = Save;
  readonly activeTab = signal('API Keys');
  settings: any = { jwt_secret: '', encryption_key: '', zeptomail_key: '', email_from: 'noreply@guard51.com', email_from_name: 'Guard51', termii_key: '', termii_sender: 'Guard51', paystack_public: '', paystack_secret: '', paystack_webhook: '', platform_name: 'Guard51', default_trial_days: 14, support_email: 'support@guard51.com', registration_open: true, maintenance_mode: false };

  ngOnInit(): void {
    this.api.get<any>('/admin/settings').subscribe({ next: r => { if (r.data) this.settings = { ...this.settings, ...r.data }; } });
  }
  saveSection(section: string): void { this.api.put('/admin/settings', { section, ...this.settings }).subscribe({ next: () => this.toast.success(`${section} settings saved`), error: () => this.toast.error('Failed to save') }); }
}
