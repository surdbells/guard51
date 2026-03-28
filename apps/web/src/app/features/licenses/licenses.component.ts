import { Component, inject, signal, OnInit } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { NgClass, DatePipe } from '@angular/common';
import { LucideAngularModule, Award, Plus, AlertTriangle, Clock } from 'lucide-angular';
import { PageHeaderComponent } from '@shared/components/page-header/page-header.component';
import { StatsCardComponent } from '@shared/components/stats-card/stats-card.component';
import { ModalComponent } from '@shared/components/modal/modal.component';
import { EmptyStateComponent } from '@shared/components/empty-state/empty-state.component';
import { ApiService } from '@core/services/api.service';
import { ToastService } from '@core/services/toast.service';

@Component({
  selector: 'g51-licenses',
  standalone: true,
  imports: [FormsModule, NgClass, DatePipe, LucideAngularModule, PageHeaderComponent, StatsCardComponent, ModalComponent, EmptyStateComponent],
  template: `
    <g51-page-header title="Guard Licenses" subtitle="Certification tracking, expiry alerts, and compliance">
      <button (click)="showCreate.set(true)" class="btn-primary flex items-center gap-2"><lucide-icon [img]="PlusIcon" [size]="16" /> Add License</button>
    </g51-page-header>
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6 stagger-children">
      <g51-stats-card label="Active Licenses" [value]="stats().active" [icon]="AwardIcon" />
      <g51-stats-card label="Expiring (30d)" [value]="expiring().length" [icon]="ClockIcon" />
      <g51-stats-card label="Expired" [value]="expired().length" [icon]="AlertTriangleIcon" />
      <g51-stats-card label="Compliance Rate" [value]="stats().compliance + '%'" [icon]="AwardIcon" />
    </div>
    <div class="flex gap-1 mb-6">
      @for (tab of ['Expiring Soon', 'Expired', 'All Licenses']; track tab) {
        <button (click)="activeTab.set(tab)" class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors"
          [ngClass]="activeTab() === tab ? 'bg-[var(--color-brand-500)] text-white' : 'bg-[var(--surface-muted)]'"
          [style.color]="activeTab() !== tab ? 'var(--text-secondary)' : ''">{{ tab }}
          @if (tab === 'Expiring Soon' && expiring().length) { <span class="ml-1 bg-amber-500 text-white text-[9px] px-1 py-0.5 rounded-full">{{ expiring().length }}</span> }
          @if (tab === 'Expired' && expired().length) { <span class="ml-1 bg-red-500 text-white text-[9px] px-1 py-0.5 rounded-full">{{ expired().length }}</span> }
        </button>
      }
    </div>
    @if (activeTab() === 'Expiring Soon') {
      @for (l of expiring(); track l.id) {
        <div class="card p-4 mb-2" style="border-left: 3px solid #f59e0b;">
          <div class="flex items-center justify-between"><div><span class="text-sm font-semibold" [style.color]="'var(--text-primary)'">{{ l.name }}</span>
            <p class="text-xs" [style.color]="'var(--text-tertiary)'">{{ l.license_type_label }} • #{{ l.license_number }} • {{ l.issuing_authority }}</p>
            <p class="text-xs font-medium text-amber-600">Expires {{ l.expiry_date }} ({{ l.days_until_expiry }} days)</p></div></div>
        </div>
      } @empty { <g51-empty-state title="All Clear" message="No licenses expiring within 30 days." [icon]="AwardIcon" /> }
    }
    @if (activeTab() === 'Expired') {
      @for (l of expired(); track l.id) {
        <div class="card p-4 mb-2" style="border-left: 3px solid #ef4444;">
          <div class="flex items-center justify-between"><div><span class="text-sm font-semibold" [style.color]="'var(--text-primary)'">{{ l.name }}</span>
            <p class="text-xs" [style.color]="'var(--text-tertiary)'">{{ l.license_type_label }} • Expired {{ l.expiry_date }}</p></div>
            <span class="badge text-[10px] bg-red-50 text-red-600">EXPIRED</span></div>
        </div>
      } @empty { <g51-empty-state title="No Expired" message="All licenses are current." [icon]="AwardIcon" /> }
    }
    @if (activeTab() === 'All Licenses') {
      <g51-empty-state title="All Licenses" message="Select a guard to view their licenses." [icon]="AwardIcon" />
    }
    <g51-modal [open]="showCreate()" title="Add License" maxWidth="520px" (closed)="showCreate.set(false)">
      <div class="space-y-3">
        <div class="grid grid-cols-2 gap-3">
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">License Type *</label>
            <select [(ngModel)]="form.license_type" class="input-base w-full"><option value="security_license">Security License</option><option value="firearms_permit">Firearms Permit</option><option value="first_aid">First Aid</option><option value="cpr">CPR</option><option value="fire_safety">Fire Safety</option><option value="drivers_license">Drivers License</option><option value="custom">Custom</option></select></div>
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Name *</label><input type="text" [(ngModel)]="form.name" class="input-base w-full" /></div>
        </div>
        <div class="grid grid-cols-2 gap-3">
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">License Number</label><input type="text" [(ngModel)]="form.license_number" class="input-base w-full" /></div>
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Issuing Authority</label><input type="text" [(ngModel)]="form.issuing_authority" class="input-base w-full" /></div>
        </div>
        <div class="grid grid-cols-2 gap-3">
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Issue Date *</label><input type="date" [(ngModel)]="form.issue_date" class="input-base w-full" /></div>
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Expiry Date *</label><input type="date" [(ngModel)]="form.expiry_date" class="input-base w-full" /></div>
        </div>
      </div>
      <div modal-footer><button (click)="showCreate.set(false)" class="btn-secondary">Cancel</button><button (click)="onCreate()" class="btn-primary">Add License</button></div>
    </g51-modal>
  `,
})
export class LicensesComponent implements OnInit {
  private api = inject(ApiService); private toast = inject(ToastService);
  readonly AwardIcon = Award; readonly PlusIcon = Plus; readonly AlertTriangleIcon = AlertTriangle; readonly ClockIcon = Clock;
  readonly activeTab = signal('Expiring Soon');
  readonly showCreate = signal(false);
  readonly expiring = signal<any[]>([]);
  readonly expired = signal<any[]>([]);
  readonly stats = signal({ active: 0, compliance: 100 });
  form: any = { license_type: 'security_license', name: '', license_number: '', issuing_authority: '', issue_date: '', expiry_date: '', guard_id: '' };
  ngOnInit(): void {
    this.api.get<any>('/licenses/expiring').subscribe({ next: r => { if (r.data) this.expiring.set(r.data.licenses || []); } });
    this.api.get<any>('/licenses/expired').subscribe({ next: r => { if (r.data) this.expired.set(r.data.licenses || []); } });
  }
  onCreate(): void { this.api.post('/licenses', this.form).subscribe({ next: () => { this.showCreate.set(false); this.toast.success('License added'); this.ngOnInit(); } }); }
}
