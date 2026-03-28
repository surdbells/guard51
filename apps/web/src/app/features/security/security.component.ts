import { Component, inject, signal, OnInit } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { NgClass } from '@angular/common';
import { LucideAngularModule, ShieldCheck, Key, ScrollText, Smartphone } from 'lucide-angular';
import { PageHeaderComponent } from '@shared/components/page-header/page-header.component';
import { ApiService } from '@core/services/api.service';
import { ToastService } from '@core/services/toast.service';

@Component({
  selector: 'g51-security',
  standalone: true,
  imports: [FormsModule, NgClass, LucideAngularModule, PageHeaderComponent],
  template: `
    <g51-page-header title="Security Settings" subtitle="Two-factor authentication and audit log" />
    <div class="flex gap-1 mb-6">
      @for (tab of ['Two-Factor Auth', 'Audit Log']; track tab) {
        <button (click)="activeTab.set(tab)" class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors"
          [ngClass]="activeTab() === tab ? 'bg-[var(--color-brand-500)] text-white' : 'bg-[var(--surface-muted)]'"
          [style.color]="activeTab() !== tab ? 'var(--text-secondary)' : ''">{{ tab }}</button>
      }
    </div>
    @if (activeTab() === 'Two-Factor Auth') {
      <div class="card p-6 max-w-lg">
        <div class="flex items-center gap-3 mb-4"><lucide-icon [img]="SmartphoneIcon" [size]="20" [style.color]="'var(--color-brand-500)'" />
          <div><h3 class="text-sm font-semibold" [style.color]="'var(--text-primary)'">Authenticator App (TOTP)</h3>
            <p class="text-xs" [style.color]="'var(--text-tertiary)'">Use Google Authenticator, Authy, or any TOTP app</p></div>
          <span class="ml-auto badge text-[10px]" [ngClass]="twoFaStatus().is_enabled ? 'bg-emerald-50 text-emerald-600 dark:bg-emerald-950 dark:text-emerald-400' : 'bg-[var(--surface-muted)]'">{{ twoFaStatus().is_enabled ? 'Enabled' : 'Disabled' }}</span>
        </div>
        @if (!twoFaStatus().is_enabled && !setupData()) {
          <button (click)="setup2FA()" class="btn-primary">Enable 2FA</button>
        }
        @if (setupData(); as s) {
          <div class="space-y-3">
            <p class="text-xs" [style.color]="'var(--text-secondary)'">Scan this QR code with your authenticator app, then enter the 6-digit code below.</p>
            <div class="p-4 bg-[var(--surface-muted)] rounded-lg text-center"><code class="text-xs break-all font-mono" [style.color]="'var(--text-primary)'">{{ s.secret }}</code>
              <p class="text-[10px] mt-1" [style.color]="'var(--text-tertiary)'">Or enter this key manually</p></div>
            <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Verification Code</label>
              <input type="text" [(ngModel)]="verifyCode" class="input-base w-full" maxlength="6" placeholder="000000" /></div>
            <button (click)="verify2FA()" class="btn-primary" [disabled]="verifyCode.length !== 6">Verify & Enable</button>
            <div class="mt-3 p-3 bg-amber-50 dark:bg-amber-950 rounded-lg"><p class="text-xs font-semibold text-amber-600 mb-1">Backup Codes — save these securely!</p>
              <div class="grid grid-cols-4 gap-1">@for (c of s.backup_codes; track c) { <code class="text-[10px] font-mono text-center" [style.color]="'var(--text-primary)'">{{ c }}</code> }</div></div>
          </div>
        }
        @if (twoFaStatus().is_enabled) {
          <div class="flex items-center gap-2 mt-3">
            <p class="text-xs" [style.color]="'var(--text-secondary)'">Backup codes remaining: {{ twoFaStatus().backup_codes_remaining }}</p>
            <button (click)="disable2FA()" class="btn-secondary text-xs ml-auto">Disable 2FA</button>
          </div>
        }
      </div>
    }
    @if (activeTab() === 'Audit Log') {
      <div class="card p-5"><h3 class="text-sm font-semibold mb-3" [style.color]="'var(--text-primary)'">Recent Activity</h3>
        <div class="space-y-1">
          @for (log of auditLogs(); track log.id) {
            <div class="flex items-center gap-3 py-2 border-b last:border-b-0" [style.borderColor]="'var(--border-default)'">
              <lucide-icon [img]="ScrollTextIcon" [size]="14" [style.color]="'var(--text-tertiary)'" />
              <div class="flex-1 min-w-0"><p class="text-xs" [style.color]="'var(--text-primary)'">{{ log.action_label }} — {{ log.resource_type }}</p>
                <p class="text-[10px]" [style.color]="'var(--text-tertiary)'">{{ log.description }} • {{ log.ip_address }}</p></div>
              <span class="text-[10px] shrink-0" [style.color]="'var(--text-tertiary)'">{{ log.created_at }}</span>
            </div>
          } @empty { <p class="text-xs text-center py-4" [style.color]="'var(--text-tertiary)'">No audit events recorded yet.</p> }
        </div>
      </div>
    }
  `,
})
export class SecurityComponent implements OnInit {
  private api = inject(ApiService); private toast = inject(ToastService);
  readonly ShieldCheckIcon = ShieldCheck; readonly KeyIcon = Key; readonly ScrollTextIcon = ScrollText; readonly SmartphoneIcon = Smartphone;
  readonly activeTab = signal('Two-Factor Auth');
  readonly twoFaStatus = signal<any>({ is_enabled: false, backup_codes_remaining: 0 });
  readonly setupData = signal<any>(null);
  readonly auditLogs = signal<any[]>([]);
  verifyCode = '';
  ngOnInit(): void {
    this.api.get<any>('/security/2fa/status').subscribe({ next: r => { if (r.data) this.twoFaStatus.set(r.data); } });
    this.api.get<any>('/security/audit-log').subscribe({ next: r => { if (r.data) this.auditLogs.set(r.data.logs || []); } });
  }
  setup2FA(): void { this.api.post<any>('/security/2fa/setup', {}).subscribe({ next: r => { if (r.data) this.setupData.set(r.data); } }); }
  verify2FA(): void { this.api.post<any>('/security/2fa/verify', { code: this.verifyCode }).subscribe({ next: r => { if (r.data?.verified) { this.toast.success('2FA enabled'); this.setupData.set(null); this.ngOnInit(); } else { this.toast.error('Invalid code'); } } }); }
  disable2FA(): void { this.api.post('/security/2fa/disable', {}).subscribe({ next: () => { this.toast.success('2FA disabled'); this.ngOnInit(); } }); }
}
