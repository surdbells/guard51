import { Component, inject, signal, OnInit } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { NgClass } from '@angular/common';
import { LucideAngularModule, FileCheck, AlertTriangle, Clock, Plus, Upload } from 'lucide-angular';
import { PageHeaderComponent } from '@shared/components/page-header/page-header.component';
import { SearchableSelectComponent, SelectOption } from '@shared/components/searchable-select/searchable-select.component';
import { StatsCardComponent } from '@shared/components/stats-card/stats-card.component';
import { ModalComponent } from '@shared/components/modal/modal.component';
import { EmptyStateComponent } from '@shared/components/empty-state/empty-state.component';
import { LoadingSpinnerComponent } from '@shared/components/loading-spinner/loading-spinner.component';
import { ApiService } from '@core/services/api.service';
import { ToastService } from '@core/services/toast.service';

@Component({
  selector: 'g51-licenses',
  standalone: true,
  imports: [FormsModule, NgClass, LucideAngularModule, PageHeaderComponent, StatsCardComponent, ModalComponent, EmptyStateComponent, LoadingSpinnerComponent, SearchableSelectComponent],
  template: `
    <g51-page-header title="Licenses & Certifications" subtitle="Guard license tracking and expiry alerts">
      <button (click)="showCreate.set(true)" class="btn-primary flex items-center gap-2"><lucide-icon [img]="PlusIcon" [size]="16" /> Add License</button>
    </g51-page-header>

    <div class="grid grid-cols-3 gap-4 mb-4 stagger-children">
      <g51-stats-card label="Active" [value]="stats().active" [icon]="FileCheckIcon" />
      <g51-stats-card label="Expiring Soon" [value]="stats().expiring_soon" [icon]="ClockIcon" />
      <g51-stats-card label="Expired" [value]="stats().expired" [icon]="AlertTriangleIcon" />
    </div>

    <div class="tab-pills">
      @for (tab of ['All', 'Expiring Soon', 'Expired']; track tab) {
        <button (click)="activeTab.set(tab); loadLicenses()" class="tab-pill" [ngClass]="activeTab() === tab ? 'active' : ''">{{ tab }}</button>
      }
    </div>
    @if (loading()) { <g51-loading /> }
    @else if (!licenses().length) { <g51-empty-state title="No Licenses" message="No licenses in this category." [icon]="FileCheckIcon" /> }
    @else {
      <div class="space-y-2">
        @for (l of licenses(); track l.id) {
          <div class="card p-4">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm font-semibold" [style.color]="'var(--text-primary)'">{{ l.license_type || l.type }}</p>
                <p class="text-xs" [style.color]="'var(--text-tertiary)'">{{ l.guard_name || '' }} · #{{ l.license_number || '—' }}</p>
                <p class="text-xs" [style.color]="'var(--text-tertiary)'">Issued: {{ l.issue_date || '—' }} · Expires: {{ l.expiry_date || 'N/A' }}</p>
              </div>
              <span class="badge text-[10px]" [ngClass]="l.is_expired ? 'bg-red-50 text-red-600' : l.is_expiring_soon ? 'bg-amber-50 text-amber-600' : 'bg-emerald-50 text-emerald-600'">{{ l.is_expired ? 'Expired' : l.is_expiring_soon ? 'Expiring' : 'Active' }}</span>
            </div>
          </div>
        }
      </div>
    }

    <g51-modal [open]="showCreate()" title="Add License" maxWidth="480px" (closed)="showCreate.set(false)">
      <div class="space-y-3">
        <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Guard *</label>
          <g51-searchable-select [(ngModel)]="form.guard_id" [options]="guardOptions()" placeholder="Select guard" /></div>
        <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">License Type *</label>
          <select [(ngModel)]="form.license_type" class="input-base w-full">
            <option value="security_license">Security License</option><option value="first_aid">First Aid</option>
            <option value="fire_safety">Fire Safety</option><option value="drivers_license">Driver's License</option><option value="other">Other</option>
          </select></div>
        <div class="grid grid-cols-2 gap-3">
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">License #</label><input type="text" [(ngModel)]="form.license_number" class="input-base w-full" /></div>
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Expiry Date *</label><input type="date" [(ngModel)]="form.expiry_date" class="input-base w-full" /></div>
        </div>
      </div>
      <div modal-footer><button (click)="showCreate.set(false)" class="btn-secondary">Cancel</button>
        <button (click)="createLicense()" class="btn-primary">Add License</button></div>
    </g51-modal>
  `,
})
export class LicensesComponent implements OnInit {
  private api = inject(ApiService); private toast = inject(ToastService);
  readonly FileCheckIcon = FileCheck; readonly AlertTriangleIcon = AlertTriangle; readonly ClockIcon = Clock; readonly PlusIcon = Plus;
  readonly activeTab = signal('All'); readonly loading = signal(true); readonly showCreate = signal(false);
  readonly licenses = signal<any[]>([]); readonly guards = signal<any[]>([]);
  readonly guardOptions = signal<SelectOption[]>([]);
  readonly stats = signal<any>({ active: 0, expiring_soon: 0, expired: 0 });
  form: any = { guard_id: '', license_type: 'security_license', license_number: '', expiry_date: '' };

  ngOnInit(): void { this.loadLicenses(); this.api.get<any>('/guards').subscribe({ next: r => { const g = r.data?.guards || r.data || []; this.guards.set(g); this.guardOptions.set(g.map((x: any) => ({ value: x.id, label: (x.first_name || '') + ' ' + (x.last_name || ''), sublabel: x.employee_number || '' }))); } }); }
  loadLicenses(): void {
    this.loading.set(true);
    const endpoint = this.activeTab() === 'Expiring Soon' ? '/licenses/expiring' : this.activeTab() === 'Expired' ? '/licenses/expired' : '/licenses/guard/all';
    this.api.get<any>(endpoint).subscribe({
      next: r => {
        const data = r.data?.licenses || r.data || [];
        this.licenses.set(data);
        this.loading.set(false);
      },
      error: () => this.loading.set(false),
    });
    // Load stats
    this.api.get<any>('/licenses/expiring').subscribe({ next: r => this.stats.update(s => ({ ...s, expiring_soon: (r.data?.licenses || r.data || []).length })) });
    this.api.get<any>('/licenses/expired').subscribe({ next: r => this.stats.update(s => ({ ...s, expired: (r.data?.licenses || r.data || []).length })) });
  }
  createLicense(): void {
    this.api.post('/licenses', this.form).subscribe({ next: () => { this.showCreate.set(false); this.toast.success('License added'); this.form = { guard_id: '', license_type: 'security_license', license_number: '', expiry_date: '' }; this.loadLicenses(); } });
  }
}
