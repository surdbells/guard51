import { Component, inject, signal, OnInit } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { NgClass } from '@angular/common';
import { LucideAngularModule, User, Save, Key, Shield, Camera } from 'lucide-angular';
import { PageHeaderComponent } from '@shared/components/page-header/page-header.component';
import { LoadingSpinnerComponent } from '@shared/components/loading-spinner/loading-spinner.component';
import { ApiService } from '@core/services/api.service';
import { AuthStore } from '@core/services/auth.store';
import { ToastService } from '@core/services/toast.service';

@Component({
  selector: 'g51-profile',
  standalone: true,
  imports: [FormsModule, NgClass, LucideAngularModule, PageHeaderComponent, LoadingSpinnerComponent],
  template: `
    <g51-page-header title="My Profile" subtitle="Manage your personal information and security" />

    <div class="tab-pills">
      @for (tab of ['Personal Info', 'Security', 'Preferences']; track tab) {
        <button (click)="activeTab.set(tab)" class="tab-pill" [ngClass]="activeTab() === tab ? 'active' : ''">{{ tab }}</button>
      }
    </div>

    @if (activeTab() === 'Personal Info') {
      <div class="card p-5 max-w-2xl">
        <div class="flex items-center gap-4 mb-6">
          <div class="h-16 w-16 rounded-full flex items-center justify-center text-xl font-bold text-white" [style.background]="'var(--color-brand-500)'">
            {{ profile.first_name?.charAt(0) || '' }}{{ profile.last_name?.charAt(0) || '' }}
          </div>
          <div>
            <p class="text-base font-semibold" [style.color]="'var(--text-primary)'">{{ profile.first_name }} {{ profile.last_name }}</p>
            <p class="text-xs" [style.color]="'var(--text-tertiary)'">{{ profile.email }} · {{ auth.userRole() }}</p>
          </div>
        </div>
        <div class="space-y-4">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">First Name</label>
              <input type="text" [(ngModel)]="profile.first_name" class="input-base w-full" /></div>
            <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Last Name</label>
              <input type="text" [(ngModel)]="profile.last_name" class="input-base w-full" /></div>
          </div>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Email</label>
              <input type="email" [(ngModel)]="profile.email" class="input-base w-full" disabled /></div>
            <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Phone</label>
              <input type="tel" [(ngModel)]="profile.phone" class="input-base w-full" /></div>
          </div>
          <button (click)="saveProfile()" class="btn-primary flex items-center gap-1"><lucide-icon [img]="SaveIcon" [size]="14" /> Save Changes</button>
        </div>
      </div>
    }

    @if (activeTab() === 'Security') {
      <div class="card p-5 max-w-2xl">
        <h3 class="text-sm font-semibold mb-4" [style.color]="'var(--text-primary)'">Change Password</h3>
        <div class="space-y-4">
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Current Password</label>
            <input type="password" [(ngModel)]="passwords.current" class="input-base w-full" /></div>
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">New Password</label>
            <input type="password" [(ngModel)]="passwords.newPass" class="input-base w-full" />
            <p class="text-[10px] mt-1" [style.color]="'var(--text-tertiary)'">Min 10 chars, 1 uppercase, 1 number, 1 special character</p></div>
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Confirm New Password</label>
            <input type="password" [(ngModel)]="passwords.confirm" class="input-base w-full" /></div>
          <button (click)="changePassword()" class="btn-primary flex items-center gap-1"><lucide-icon [img]="KeyIcon" [size]="14" /> Update Password</button>
        </div>

        <div class="mt-6 pt-6 border-t" [style.borderColor]="'var(--border-default)'">
          <h3 class="text-sm font-semibold mb-3" [style.color]="'var(--text-primary)'">Two-Factor Authentication</h3>
          <div class="flex items-center justify-between">
            <div>
              <p class="text-xs" [style.color]="'var(--text-secondary)'">Add an extra layer of security to your account</p>
              <span class="badge text-[10px] mt-1" [ngClass]="twoFaEnabled() ? 'bg-emerald-50 text-emerald-600' : 'bg-gray-100 text-gray-500'">{{ twoFaEnabled() ? 'Enabled' : 'Disabled' }}</span>
            </div>
            <button (click)="toggle2FA()" class="btn-secondary text-xs">{{ twoFaEnabled() ? 'Disable' : 'Enable' }} 2FA</button>
          </div>
        </div>
      </div>
    }

    @if (activeTab() === 'Preferences') {
      <div class="card p-5 max-w-2xl">
        <h3 class="text-sm font-semibold mb-4" [style.color]="'var(--text-primary)'">Notification Preferences</h3>
        <div class="space-y-3">
          @for (pref of notifPrefs; track pref.key) {
            <label class="flex items-center justify-between py-2 border-b" [style.borderColor]="'var(--border-default)'">
              <div><p class="text-sm" [style.color]="'var(--text-primary)'">{{ pref.label }}</p>
                <p class="text-xs" [style.color]="'var(--text-tertiary)'">{{ pref.desc }}</p></div>
              <input type="checkbox" [(ngModel)]="pref.enabled" class="rounded" />
            </label>
          }
        </div>
        <button (click)="toast.success('Preferences saved')" class="btn-primary flex items-center gap-1 mt-4"><lucide-icon [img]="SaveIcon" [size]="14" /> Save Preferences</button>
      </div>
    }
  `,
})
export class ProfileComponent implements OnInit {
  readonly auth = inject(AuthStore);
  private api = inject(ApiService);
  readonly toast = inject(ToastService);
  readonly SaveIcon = Save; readonly KeyIcon = Key;
  readonly activeTab = signal('Personal Info');
  readonly twoFaEnabled = signal(false);
  profile: any = { first_name: '', last_name: '', email: '', phone: '' };
  passwords = { current: '', newPass: '', confirm: '' };
  notifPrefs = [
    { key: 'email_alerts', label: 'Email Alerts', desc: 'Receive important alerts via email', enabled: true },
    { key: 'push_alerts', label: 'Push Notifications', desc: 'Receive mobile push notifications', enabled: true },
    { key: 'sms_alerts', label: 'SMS Alerts', desc: 'Receive critical alerts via SMS', enabled: false },
    { key: 'shift_reminders', label: 'Shift Reminders', desc: 'Reminders before shift starts', enabled: true },
  ];

  ngOnInit(): void {
    const u = this.auth.user();
    if (u) this.profile = { first_name: u.first_name || '', last_name: u.last_name || '', email: u.email || '', phone: u.phone || '' };
    this.api.get<any>('/auth/2fa/status').subscribe({ next: r => this.twoFaEnabled.set(r.data?.enabled || false), error: () => {} });
  }
  saveProfile(): void { this.api.put('/auth/profile', this.profile).subscribe({ next: () => this.toast.success('Profile updated'), error: () => this.toast.error('Failed to save') }); }
  changePassword(): void {
    if (this.passwords.newPass !== this.passwords.confirm) { this.toast.error('Passwords do not match'); return; }
    if (this.passwords.newPass.length < 10) { this.toast.error('Password must be at least 10 characters'); return; }
    this.api.post('/auth/change-password', { current_password: this.passwords.current, new_password: this.passwords.newPass }).subscribe({
      next: () => { this.toast.success('Password changed'); this.passwords = { current: '', newPass: '', confirm: '' }; },
      error: (e: any) => this.toast.error(e.error?.message || 'Failed'),
    });
  }
  toggle2FA(): void {
    const endpoint = this.twoFaEnabled() ? '/auth/2fa/disable' : '/auth/2fa/enable';
    this.api.post(endpoint, {}).subscribe({ next: () => { this.twoFaEnabled.update(v => !v); this.toast.success(this.twoFaEnabled() ? '2FA enabled' : '2FA disabled'); } });
  }
}
