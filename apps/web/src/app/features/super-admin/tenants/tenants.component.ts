import { Component, inject, signal, OnInit } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { NgClass, DecimalPipe } from '@angular/common';
import { LucideAngularModule, Building2, Search, Plus, Eye, Ban, CheckCircle, Trash2 } from 'lucide-angular';
import { PageHeaderComponent } from '@shared/components/page-header/page-header.component';
import { ModalComponent } from '@shared/components/modal/modal.component';
import { EmptyStateComponent } from '@shared/components/empty-state/empty-state.component';
import { LoadingSpinnerComponent } from '@shared/components/loading-spinner/loading-spinner.component';
import { ApiService } from '@core/services/api.service';
import { ToastService } from '@core/services/toast.service';
import { exportToCsv } from '@core/utils/csv-export';

@Component({
  selector: 'g51-tenants',
  standalone: true,
  imports: [FormsModule, NgClass, DecimalPipe, LucideAngularModule, PageHeaderComponent, ModalComponent, EmptyStateComponent, LoadingSpinnerComponent],
  template: `
    <g51-page-header title="Companies" subtitle="Manage all tenant companies on the platform">
      <button (click)="exportTenants()" class="btn-secondary text-xs">Export CSV</button>
    </g51-page-header>

    <div class="flex items-center gap-3 mb-4 flex-wrap">
      <div class="relative flex-1 max-w-sm">
        <lucide-icon [img]="SearchIcon" [size]="14" class="absolute left-3 top-1/2 -translate-y-1/2" [style.color]="'var(--text-tertiary)'" />
        <input type="text" [(ngModel)]="search" (ngModelChange)="load()" placeholder="Search companies..." class="input-base w-full pl-9" />
      </div>
      <select [(ngModel)]="statusFilter" (ngModelChange)="load()" class="input-base text-xs py-2">
        <option value="">All Status</option><option value="active">Active</option><option value="trial">Trial</option>
        <option value="suspended">Suspended</option><option value="cancelled">Cancelled</option>
      </select>
      <select [(ngModel)]="typeFilter" (ngModelChange)="load()" class="input-base text-xs py-2">
        <option value="">All Types</option><option value="private_security">Private Security</option>
        <option value="neighborhood_watch">Neighborhood Watch</option><option value="government">Government</option>
      </select>
    </div>

    @if (loading()) { <g51-loading /> }
    @else if (!tenants().length) { <g51-empty-state title="No Companies" message="No companies match your filters." [icon]="BuildingIcon" /> }
    @else {
      <div class="card overflow-hidden">
        <table class="w-full text-xs">
          <thead><tr [style.background]="'var(--surface-muted)'">
            <th class="text-left py-2.5 px-4 font-semibold" [style.color]="'var(--text-secondary)'">Company</th>
            <th class="text-left py-2.5 px-4 font-semibold" [style.color]="'var(--text-secondary)'">Type</th>
            <th class="text-left py-2.5 px-4 font-semibold" [style.color]="'var(--text-secondary)'">Status</th>
            <th class="text-left py-2.5 px-4 font-semibold" [style.color]="'var(--text-secondary)'">Guards</th>
            <th class="text-left py-2.5 px-4 font-semibold" [style.color]="'var(--text-secondary)'">Plan</th>
            <th class="text-left py-2.5 px-4 font-semibold" [style.color]="'var(--text-secondary)'">Created</th>
            <th class="text-right py-2.5 px-4 font-semibold" [style.color]="'var(--text-secondary)'">Actions</th>
          </tr></thead>
          <tbody>
            @for (t of tenants(); track t.id) {
              <tr class="border-t hover:bg-[var(--surface-hover)] transition-colors" [style.borderColor]="'var(--border-default)'">
                <td class="py-3 px-4">
                  <p class="font-semibold" [style.color]="'var(--text-primary)'">{{ t.name }}</p>
                  <p class="text-[10px]" [style.color]="'var(--text-tertiary)'">{{ t.email }}</p>
                </td>
                <td class="py-3 px-4" [style.color]="'var(--text-secondary)'">{{ t.tenant_type || '—' }}</td>
                <td class="py-3 px-4">
                  <span class="badge text-[10px]" [ngClass]="t.status === 'active' ? 'bg-emerald-50 text-emerald-600' : t.status === 'trial' ? 'bg-blue-50 text-blue-600' : t.status === 'suspended' ? 'bg-red-50 text-red-600' : 'bg-gray-100 text-gray-500'">{{ t.status }}</span>
                </td>
                <td class="py-3 px-4" [style.color]="'var(--text-primary)'">{{ t.guard_count || 0 }}</td>
                <td class="py-3 px-4" [style.color]="'var(--text-secondary)'">{{ t.plan_name || 'None' }}</td>
                <td class="py-3 px-4" [style.color]="'var(--text-tertiary)'">{{ t.created_at?.slice(0, 10) }}</td>
                <td class="py-3 px-4 text-right">
                  <div class="flex items-center justify-end gap-1">
                    <button (click)="showDetail(t)" class="p-1.5 rounded hover:bg-[var(--surface-hover)]" title="View">
                      <lucide-icon [img]="EyeIcon" [size]="14" [style.color]="'var(--text-tertiary)'" /></button>
                    @if (t.status === 'active') {
                      <button (click)="suspendTenant(t)" class="p-1.5 rounded hover:bg-red-50" title="Suspend">
                        <lucide-icon [img]="BanIcon" [size]="14" [style.color]="'var(--color-danger)'" /></button>
                    } @else if (t.status === 'suspended') {
                      <button (click)="activateTenant(t)" class="p-1.5 rounded hover:bg-emerald-50" title="Activate">
                        <lucide-icon [img]="CheckIcon" [size]="14" [style.color]="'var(--color-success)'" /></button>
                    }
                  </div>
                </td>
              </tr>
            }
          </tbody>
        </table>
      </div>
      @if (total() > perPage) {
        <div class="flex items-center justify-between mt-4">
          <p class="text-xs" [style.color]="'var(--text-tertiary)'">Showing {{ (page()-1)*perPage+1 }}–{{ Math.min(page()*perPage, total()) }} of {{ total() }}</p>
          <div class="flex gap-1">
            <button (click)="prevPage()" [disabled]="page()<=1" class="btn-secondary text-xs py-1 px-2">Prev</button>
            <button (click)="nextPage()" [disabled]="page()*perPage>=total()" class="btn-secondary text-xs py-1 px-2">Next</button>
          </div>
        </div>
      }
    }

    <!-- Tenant Detail Modal -->
    <g51-modal [open]="showDetailModal()" [title]="selectedTenant()?.name || 'Company'" maxWidth="600px" (closed)="showDetailModal.set(false)">
      @if (selectedTenant(); as t) {
        <div class="grid grid-cols-2 gap-y-3 gap-x-6 text-xs">
          <div><span [style.color]="'var(--text-tertiary)'">Company Name</span><p class="font-medium" [style.color]="'var(--text-primary)'">{{ t.name }}</p></div>
          <div><span [style.color]="'var(--text-tertiary)'">Email</span><p class="font-medium" [style.color]="'var(--text-primary)'">{{ t.email }}</p></div>
          <div><span [style.color]="'var(--text-tertiary)'">Phone</span><p class="font-medium" [style.color]="'var(--text-primary)'">{{ t.phone || '—' }}</p></div>
          <div><span [style.color]="'var(--text-tertiary)'">RC Number</span><p class="font-medium" [style.color]="'var(--text-primary)'">{{ t.rc_number || '—' }}</p></div>
          <div><span [style.color]="'var(--text-tertiary)'">Type</span><p class="font-medium" [style.color]="'var(--text-primary)'">{{ t.tenant_type }}</p></div>
          <div><span [style.color]="'var(--text-tertiary)'">Status</span><span class="badge text-[10px]" [ngClass]="t.status === 'active' ? 'bg-emerald-50 text-emerald-600' : 'bg-red-50 text-red-600'">{{ t.status }}</span></div>
          <div><span [style.color]="'var(--text-tertiary)'">Address</span><p class="font-medium" [style.color]="'var(--text-primary)'">{{ t.address || '—' }}</p></div>
          <div><span [style.color]="'var(--text-tertiary)'">City / State</span><p class="font-medium" [style.color]="'var(--text-primary)'">{{ t.city || '' }} {{ t.state || '' }}</p></div>
          <div><span [style.color]="'var(--text-tertiary)'">Guards</span><p class="font-medium" [style.color]="'var(--text-primary)'">{{ t.guard_count || 0 }}</p></div>
          <div><span [style.color]="'var(--text-tertiary)'">Sites</span><p class="font-medium" [style.color]="'var(--text-primary)'">{{ t.site_count || 0 }}</p></div>
          <div><span [style.color]="'var(--text-tertiary)'">Plan</span><p class="font-medium" [style.color]="'var(--text-primary)'">{{ t.plan_name || 'None' }}</p></div>
          <div><span [style.color]="'var(--text-tertiary)'">Created</span><p class="font-medium" [style.color]="'var(--text-primary)'">{{ t.created_at?.slice(0, 10) }}</p></div>
        </div>
      }
    </g51-modal>
  `,
})
export class TenantsComponent implements OnInit {
  private api = inject(ApiService); private toast = inject(ToastService);
  readonly BuildingIcon = Building2; readonly SearchIcon = Search; readonly EyeIcon = Eye;
  readonly BanIcon = Ban; readonly CheckIcon = CheckCircle;
  readonly Math = Math;

  readonly tenants = signal<any[]>([]); readonly loading = signal(true);
  readonly total = signal(0); readonly page = signal(1); readonly perPage = 20;
  readonly showDetailModal = signal(false); readonly selectedTenant = signal<any>(null);
  search = ''; statusFilter = ''; typeFilter = '';

  ngOnInit(): void { this.load(); }

  load(): void {
    this.loading.set(true);
    const p = new URLSearchParams();
    p.set('page', String(this.page())); p.set('per_page', String(this.perPage));
    if (this.search) p.set('search', this.search);
    if (this.statusFilter) p.set('status', this.statusFilter);
    if (this.typeFilter) p.set('type', this.typeFilter);
    this.api.get<any>(`/admin/tenants?${p}`).subscribe({
      next: res => { this.tenants.set(res.data?.tenants || res.data || []); this.total.set(res.data?.total || this.tenants().length); this.loading.set(false); },
      error: () => this.loading.set(false),
    });
  }

  showDetail(t: any): void { this.selectedTenant.set(t); this.showDetailModal.set(true); }
  suspendTenant(t: any): void {
    if (confirm(`Suspend ${t.name}? Users will lose access.`)) {
      this.api.post(`/admin/tenants/${t.id}/suspend`, {}).subscribe({ next: () => { this.toast.success('Suspended'); this.load(); } });
    }
  }
  activateTenant(t: any): void {
    this.api.post(`/admin/tenants/${t.id}/activate`, {}).subscribe({ next: () => { this.toast.success('Activated'); this.load(); } });
  }
  prevPage(): void { this.page.update(p => Math.max(1, p - 1)); this.load(); }
  nextPage(): void { this.page.update(p => p + 1); this.load(); }
  exportTenants(): void {
    exportToCsv('companies', this.tenants(), [
      { key: 'name', label: 'Company' }, { key: 'email', label: 'Email' },
      { key: 'tenant_type', label: 'Type' }, { key: 'status', label: 'Status' },
      { key: 'guard_count', label: 'Guards' }, { key: 'plan_name', label: 'Plan' },
      { key: 'created_at', label: 'Created' },
    ]);
  }
}
