import { Component, inject, signal, OnInit } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { NgClass } from '@angular/common';
import { LucideAngularModule, Shield, Clock, Search, Filter, Download, Eye, AlertTriangle, User, FileText, Settings } from 'lucide-angular';
import { PageHeaderComponent } from '@shared/components/page-header/page-header.component';
import { LoadingSpinnerComponent } from '@shared/components/loading-spinner/loading-spinner.component';
import { EmptyStateComponent } from '@shared/components/empty-state/empty-state.component';
import { ApiService } from '@core/services/api.service';
import { ToastService } from '@core/services/toast.service';
import { exportToCsv } from '@core/utils/csv-export';

@Component({
  selector: 'g51-security',
  standalone: true,
  imports: [FormsModule, NgClass, LucideAngularModule, PageHeaderComponent, LoadingSpinnerComponent, EmptyStateComponent],
  template: `
    <g51-page-header title="Security & Audit" subtitle="Audit log, activity tracking, and security settings">
      <button (click)="exportAudit()" class="btn-secondary text-xs">Export CSV</button>
    </g51-page-header>

    <div class="tab-pills">
      @for (tab of ['Audit Log', 'Two-Factor Auth']; track tab) {
        <button (click)="activeTab.set(tab); loadTab()" class="tab-pill" [ngClass]="activeTab() === tab ? 'active' : ''">{{ tab }}</button>
      }
    </div>

    @if (activeTab() === 'Audit Log') {
      <div class="flex items-center gap-3 mb-4 flex-wrap">
        <div class="relative flex-1 max-w-sm">
          <lucide-icon [img]="SearchIcon" [size]="14" class="absolute left-3 top-1/2 -translate-y-1/2" [style.color]="'var(--text-tertiary)'" />
          <input type="text" [(ngModel)]="search" (ngModelChange)="loadAudit()" placeholder="Search actions..." class="input-base w-full pl-9" />
        </div>
        <select [(ngModel)]="actionFilter" (ngModelChange)="loadAudit()" class="input-base text-xs py-2">
          <option value="">All Actions</option><option value="create">Create</option><option value="update">Update</option>
          <option value="delete">Delete</option><option value="login">Login</option><option value="logout">Logout</option>
        </select>
        <select [(ngModel)]="resourceFilter" (ngModelChange)="loadAudit()" class="input-base text-xs py-2">
          <option value="">All Resources</option><option value="Guard">Guard</option><option value="Site">Site</option>
          <option value="Client">Client</option><option value="Shift">Shift</option><option value="Invoice">Invoice</option>
          <option value="User">User</option><option value="Incident">Incident</option>
        </select>
      </div>

      @if (loading()) { <g51-loading /> }
      @else if (!auditLogs().length) { <g51-empty-state title="No Audit Events" message="No activity has been recorded yet." [icon]="ShieldIcon" /> }
      @else {
        <div class="space-y-1">
          @for (log of auditLogs(); track log.id) {
            <div class="card p-3 flex items-start gap-3">
              <div class="h-8 w-8 rounded-lg flex items-center justify-center shrink-0"
                [ngClass]="log.action === 'create' ? 'bg-emerald-50' : log.action === 'delete' ? 'bg-red-50' : log.action === 'login' ? 'bg-blue-50' : 'bg-amber-50'">
                <lucide-icon [img]="getActionIcon(log.action)" [size]="14"
                  [style.color]="log.action === 'create' ? 'var(--color-success)' : log.action === 'delete' ? 'var(--color-danger)' : 'var(--color-warning)'" />
              </div>
              <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2">
                  <span class="text-xs font-semibold" [style.color]="'var(--text-primary)'">{{ log.action_label || log.action }}</span>
                  <span class="badge text-[10px] bg-gray-100 text-gray-500">{{ log.resource_type }}</span>
                </div>
                <p class="text-xs" [style.color]="'var(--text-secondary)'">{{ log.description }}</p>
                <p class="text-[10px] mt-0.5" [style.color]="'var(--text-tertiary)'">{{ log.user_email || 'System' }} · {{ log.ip_address || '' }} · {{ log.created_at }}</p>
              </div>
            </div>
          }
        </div>
        @if (hasMore()) {
          <button (click)="loadMore()" class="btn-secondary w-full mt-3 text-xs">Load More</button>
        }
      }
    }

    @if (activeTab() === 'Two-Factor Auth') {
      <div class="card p-5 max-w-lg">
        <h3 class="text-sm font-semibold mb-3" [style.color]="'var(--text-primary)'">Two-Factor Authentication</h3>
        <p class="text-xs mb-4" [style.color]="'var(--text-secondary)'">Add an extra layer of security to your account by enabling two-factor authentication via authenticator app.</p>
        @if (twoFaEnabled()) {
          <div class="flex items-center gap-2 p-3 rounded-lg bg-emerald-50 mb-4">
            <lucide-icon [img]="ShieldIcon" [size]="16" style="color: var(--color-success)" />
            <span class="text-xs font-semibold text-emerald-700">2FA is enabled</span>
          </div>
          <button (click)="disable2fa()" class="btn-danger text-xs">Disable 2FA</button>
        } @else {
          <button (click)="enable2fa()" class="btn-primary text-xs">Enable 2FA</button>
        }
      </div>
    }
  `,
})
export class SecurityComponent implements OnInit {
  private api = inject(ApiService); private toast = inject(ToastService);
  readonly ShieldIcon = Shield; readonly SearchIcon = Search; readonly ClockIcon = Clock;
  readonly UserIcon = User; readonly FileTextIcon = FileText; readonly SettingsIcon = Settings; readonly AlertTriangleIcon = AlertTriangle;

  readonly activeTab = signal('Audit Log');
  readonly auditLogs = signal<any[]>([]); readonly loading = signal(true);
  readonly twoFaEnabled = signal(false); readonly hasMore = signal(false);
  search = ''; actionFilter = ''; resourceFilter = '';
  private auditPage = 1;

  ngOnInit(): void { this.loadTab(); }

  loadTab(): void {
    if (this.activeTab() === 'Audit Log') { this.auditPage = 1; this.loadAudit(); }
    else { this.api.get<any>('/auth/2fa/status').subscribe({ next: res => this.twoFaEnabled.set(res.data?.enabled || false), error: () => {} }); }
  }

  loadAudit(): void {
    this.loading.set(true);
    const p = new URLSearchParams(); p.set('page', String(this.auditPage)); p.set('per_page', '50');
    if (this.search) p.set('search', this.search);
    if (this.actionFilter) p.set('action', this.actionFilter);
    if (this.resourceFilter) p.set('resource_type', this.resourceFilter);
    this.api.get<any>(`/audit-log?${p}`).subscribe({
      next: res => { const logs = res.data?.logs || res.data || []; this.auditLogs.set(this.auditPage === 1 ? logs : [...this.auditLogs(), ...logs]); this.hasMore.set(logs.length >= 50); this.loading.set(false); },
      error: () => this.loading.set(false),
    });
  }

  loadMore(): void { this.auditPage++; this.loadAudit(); }

  getActionIcon(action: string): any {
    if (action === 'login' || action === 'logout') return this.UserIcon;
    if (action === 'delete') return this.AlertTriangleIcon;
    return this.FileTextIcon;
  }

  exportAudit(): void {
    exportToCsv('audit-log', this.auditLogs(), [
      { key: 'created_at', label: 'Timestamp' }, { key: 'action', label: 'Action' },
      { key: 'resource_type', label: 'Resource' }, { key: 'description', label: 'Description' },
      { key: 'user_email', label: 'User' }, { key: 'ip_address', label: 'IP' },
    ]);
  }

  enable2fa(): void { this.api.post('/auth/2fa/enable', {}).subscribe({ next: () => { this.twoFaEnabled.set(true); this.toast.success('2FA enabled'); } }); }
  disable2fa(): void { this.api.post('/auth/2fa/disable', {}).subscribe({ next: () => { this.twoFaEnabled.set(false); this.toast.success('2FA disabled'); } }); }
}
