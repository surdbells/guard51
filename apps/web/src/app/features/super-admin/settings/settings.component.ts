import { Component, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { LucideAngularModule, Save, Mail, MessageSquare, CreditCard, Building2, Clock, Globe } from 'lucide-angular';
import { PageHeaderComponent } from '@shared/components/page-header/page-header.component';

@Component({
  selector: 'g51-sa-settings',
  standalone: true,
  imports: [FormsModule, LucideAngularModule, PageHeaderComponent],
  template: `
    <g51-page-header title="Platform Settings" subtitle="Configure Guard51 platform services and integrations" />

    <div class="space-y-6 max-w-3xl">
      <!-- ZeptoMail -->
      <div class="card p-5">
        <div class="flex items-center gap-3 mb-4">
          <div class="h-9 w-9 rounded-lg flex items-center justify-center" [style.background]="'var(--surface-muted)'">
            <lucide-icon [img]="MailIcon" [size]="18" [style.color]="'var(--text-secondary)'" />
          </div>
          <div>
            <h3 class="text-sm font-semibold" [style.color]="'var(--text-primary)'">ZeptoMail (Transactional Email)</h3>
            <p class="text-xs" [style.color]="'var(--text-tertiary)'">Email API for welcome, reset, invitation emails</p>
          </div>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">API Key</label>
            <input type="password" class="input-base w-full text-sm" placeholder="Zoho-enczapikey-..." [(ngModel)]="settings.zeptomail_key" /></div>
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">From Email</label>
            <input type="email" class="input-base w-full text-sm" placeholder="noreply@guard51.com" [(ngModel)]="settings.zeptomail_from" /></div>
        </div>
      </div>

      <!-- Termii SMS -->
      <div class="card p-5">
        <div class="flex items-center gap-3 mb-4">
          <div class="h-9 w-9 rounded-lg flex items-center justify-center" [style.background]="'var(--surface-muted)'">
            <lucide-icon [img]="MessageSquareIcon" [size]="18" [style.color]="'var(--text-secondary)'" />
          </div>
          <div>
            <h3 class="text-sm font-semibold" [style.color]="'var(--text-primary)'">Termii (SMS Alerts)</h3>
            <p class="text-xs" [style.color]="'var(--text-tertiary)'">SMS gateway for guard alerts and OTP</p>
          </div>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">API Key</label>
            <input type="password" class="input-base w-full text-sm" [(ngModel)]="settings.termii_key" /></div>
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Sender ID</label>
            <input type="text" class="input-base w-full text-sm" placeholder="Guard51" [(ngModel)]="settings.termii_sender" /></div>
        </div>
      </div>

      <!-- Paystack -->
      <div class="card p-5">
        <div class="flex items-center gap-3 mb-4">
          <div class="h-9 w-9 rounded-lg flex items-center justify-center" [style.background]="'var(--surface-muted)'">
            <lucide-icon [img]="CreditCardIcon" [size]="18" [style.color]="'var(--text-secondary)'" />
          </div>
          <div>
            <h3 class="text-sm font-semibold" [style.color]="'var(--text-primary)'">Paystack (Payments)</h3>
            <p class="text-xs" [style.color]="'var(--text-tertiary)'">Payment processing for subscriptions</p>
          </div>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Public Key</label>
            <input type="text" class="input-base w-full text-sm" placeholder="pk_live_..." [(ngModel)]="settings.paystack_public" /></div>
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Secret Key</label>
            <input type="password" class="input-base w-full text-sm" placeholder="sk_live_..." [(ngModel)]="settings.paystack_secret" /></div>
        </div>
      </div>

      <!-- Platform Bank Account -->
      <div class="card p-5">
        <div class="flex items-center gap-3 mb-4">
          <div class="h-9 w-9 rounded-lg flex items-center justify-center" [style.background]="'var(--surface-muted)'">
            <lucide-icon [img]="BuildingIcon" [size]="18" [style.color]="'var(--text-secondary)'" />
          </div>
          <div>
            <h3 class="text-sm font-semibold" [style.color]="'var(--text-primary)'">Platform Bank Account</h3>
            <p class="text-xs" [style.color]="'var(--text-tertiary)'">Displayed on invoices for manual bank transfer payments</p>
          </div>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Bank Name</label>
            <input type="text" class="input-base w-full text-sm" [(ngModel)]="settings.bank_name" /></div>
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Account Number</label>
            <input type="text" class="input-base w-full text-sm" [(ngModel)]="settings.bank_account" /></div>
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Account Name</label>
            <input type="text" class="input-base w-full text-sm" [(ngModel)]="settings.bank_name_holder" /></div>
        </div>
      </div>

      <!-- Trial & Global Flags -->
      <div class="card p-5">
        <div class="flex items-center gap-3 mb-4">
          <div class="h-9 w-9 rounded-lg flex items-center justify-center" [style.background]="'var(--surface-muted)'">
            <lucide-icon [img]="ClockIcon" [size]="18" [style.color]="'var(--text-secondary)'" />
          </div>
          <div>
            <h3 class="text-sm font-semibold" [style.color]="'var(--text-primary)'">Trial & Global Settings</h3>
            <p class="text-xs" [style.color]="'var(--text-tertiary)'">Default trial duration and platform flags</p>
          </div>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Trial Days</label>
            <input type="number" class="input-base w-full text-sm" [(ngModel)]="settings.trial_days" /></div>
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Maintenance Mode</label>
            <select class="input-base w-full text-sm" [(ngModel)]="settings.maintenance_mode">
              <option value="false">Off</option><option value="true">On</option>
            </select></div>
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Registration Open</label>
            <select class="input-base w-full text-sm" [(ngModel)]="settings.registration_open">
              <option value="true">Yes</option><option value="false">No</option>
            </select></div>
        </div>
      </div>

      <button class="btn-primary flex items-center gap-2">
        <lucide-icon [img]="SaveIcon" [size]="16" /> Save Settings
      </button>
    </div>
  `,
})
export class SettingsComponent {
  readonly MailIcon = Mail; readonly MessageSquareIcon = MessageSquare; readonly CreditCardIcon = CreditCard;
  readonly BuildingIcon = Building2; readonly ClockIcon = Clock; readonly GlobalIcon = Globe; readonly SaveIcon = Save;

  settings: Record<string, any> = {
    zeptomail_key: '', zeptomail_from: 'noreply@guard51.com',
    termii_key: '', termii_sender: 'Guard51',
    paystack_public: '', paystack_secret: '',
    bank_name: 'Guaranty Trust Bank', bank_account: '0123456789', bank_name_holder: 'DOSTHQ Limited',
    trial_days: 14, maintenance_mode: 'false', registration_open: 'true',
  };
}
