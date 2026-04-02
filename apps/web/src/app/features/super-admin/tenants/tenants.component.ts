import { Component, inject, signal, OnInit } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { NgClass, DecimalPipe } from '@angular/common';
import { LucideAngularModule, Building2, Search, Eye, Ban, Play, Download, UserPlus, CreditCard, ArrowUpDown } from 'lucide-angular';
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
    <g51-page-header title="Company Management" subtitle="All registered security companies">
      <button (click)="exportAll()" class="btn-secondary text-xs flex items-center gap-1 mr-2"><lucide-icon [img]="DownloadIcon" [size]="14" /> Export</button>
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
    </div>

    @if (loading()) { <g51-loading /> }
    @else if (!tenants().length) { <g51-empty-state title="No Companies" message="No companies match your filter." [icon]="BuildingIcon" /> }
    @else {
      <div class="card overflow-hidden">
        <table class="w-full text-xs">
          <thead><tr [style.background]="'var(--surface-muted)'">
            <th class="text-left py-2.5 px-4 font-semibold" [style.color]="'var(--text-secondary)'">Company</th>
            <th class="text-left py-2.5 px-4 font-semibold" [style.color]="'var(--text-secondary)'">Plan</th>
            <th class="text-left py-2.5 px-4 font-semibold" [style.color]="'var(--text-secondary)'">Guards</th>
            <th class="text-left py-2.5 px-4 font-semibold" [style.color]="'var(--text-secondary)'">Status</th>
            <th class="text-left py-2.5 px-4 font-semibold" [style.color]="'var(--text-secondary)'">Created</th>
            <th class="text-center py-2.5 px-4 font-semibold" [style.color]="'var(--text-secondary)'">Actions</th>
          </tr></thead>
          <tbody>
            @for (t of tenants(); track t.id) {
              <tr class="border-t hover:bg-[var(--surface-hover)]" [style.borderColor]="'var(--border-default)'">
                <td class="py-2.5 px-4">
                  <p class="font-medium" [style.color]="'var(--text-primary)'">{{ t.company_name || t.name }}</p>
                  <p class="text-[10px]" [style.color]="'var(--text-tertiary)'">{{ t.admin_email || t.email || '' }}</p>
                </td>
                <td class="py-2.5 px-4" [style.color]="'var(--text-secondary)'">{{ t.plan_name || t.subscription_plan || 'None' }}</td>
                <td class="py-2.5 px-4" [style.color]="'var(--text-primary)'">{{ t.guard_count || 0 }}</td>
                <td class="py-2.5 px-4">
                  <span class="badge text-[10px]" [ngClass]="t.status === 'active' ? 'bg-emerald-50 text-emerald-600' : t.status === 'trial' ? 'bg-blue-50 text-blue-600' : t.status === 'suspended' ? 'bg-amber-50 text-amber-600' : 'bg-red-50 text-red-600'">{{ t.status }}</span>
                </td>
                <td class="py-2.5 px-4" [style.color]="'var(--text-tertiary)'">{{ t.created_at?.slice(0, 10) || '' }}</td>
                <td class="py-2.5 px-4 text-center">
                  <div class="flex justify-center gap-1">
                    <button (click)="viewDetail(t)" class="p-1 rounded hover:bg-[var(--surface-muted)]" title="View"><lucide-icon [img]="EyeIcon" [size]="14" [style.color]="'var(--text-tertiary)'" /></button>
                    <button (click)="openSubscription(t)" class="p-1 rounded hover:bg-[var(--surface-muted)]" title="Subscription"><lucide-icon [img]="CreditCardIcon" [size]="14" [style.color]="'var(--text-tertiary)'" /></button>
                    @if (t.status === 'active' || t.status === 'trial') {
                      <button (click)="suspend(t)" class="p-1 rounded hover:bg-amber-50" title="Suspend"><lucide-icon [img]="BanIcon" [size]="14" class="text-amber-500" /></button>
                    } @else if (t.status === 'suspended') {
                      <button (click)="activate(t)" class="p-1 rounded hover:bg-emerald-50" title="Reactivate"><lucide-icon [img]="PlayIcon" [size]="14" class="text-emerald-500" /></button>
                    }
                  </div>
                </td>
              </tr>
            }
          </tbody>
        </table>
      </div>
      @if (totalPages() > 1) {
        <div class="flex justify-center gap-1 mt-4">
          @for (p of pages(); track p) {
            <button (click)="page.set(p); load()" class="px-3 py-1 rounded text-xs"
              [ngClass]="page() === p ? 'bg-[var(--color-brand-500)] text-white' : 'bg-[var(--surface-muted)]'">{{ p }}</button>
          }
        </div>
      }
    }

    <!-- Tenant Detail Modal -->
    <g51-modal [open]="showDetail()" [title]="selectedTenant()?.company_name || 'Company Detail'" maxWidth="600px" (closed)="showDetail.set(false)">
      @if (selectedTenant(); as t) {
        <div class="space-y-4">
          <div class="grid grid-cols-2 gap-3">
            <div><p class="text-[10px] uppercase tracking-wide" [style.color]="'var(--text-tertiary)'">Company</p><p class="text-sm font-semibold" [style.color]="'var(--text-primary)'">{{ t.company_name || t.name }}</p></div>
            <div><p class="text-[10px] uppercase tracking-wide" [style.color]="'var(--text-tertiary)'">Status</p><span class="badge text-[10px]" [ngClass]="t.status === 'active' ? 'bg-emerald-50 text-emerald-600' : 'bg-gray-100 text-gray-500'">{{ t.status }}</span></div>
            <div><p class="text-[10px] uppercase tracking-wide" [style.color]="'var(--text-tertiary)'">Admin Email</p><p class="text-sm" [style.color]="'var(--text-primary)'">{{ t.admin_email || t.email || '—' }}</p></div>
            <div><p class="text-[10px] uppercase tracking-wide" [style.color]="'var(--text-tertiary)'">Phone</p><p class="text-sm" [style.color]="'var(--text-primary)'">{{ t.phone || '—' }}</p></div>
            <div><p class="text-[10px] uppercase tracking-wide" [style.color]="'var(--text-tertiary)'">Plan</p><p class="text-sm" [style.color]="'var(--text-primary)'">{{ t.plan_name || 'None' }}</p></div>
            <div><p class="text-[10px] uppercase tracking-wide" [style.color]="'var(--text-tertiary)'">Created</p><p class="text-sm" [style.color]="'var(--text-primary)'">{{ t.created_at }}</p></div>
          </div>
          <div class="grid grid-cols-4 gap-2">
            <div class="card p-2 text-center"><p class="text-lg font-bold" [style.color]="'var(--text-primary)'">{{ detailStats().guards }}</p><p class="text-[10px]" [style.color]="'var(--text-tertiary)'">Guards</p></div>
            <div class="card p-2 text-center"><p class="text-lg font-bold" [style.color]="'var(--text-primary)'">{{ detailStats().sites }}</p><p class="text-[10px]" [style.color]="'var(--text-tertiary)'">Sites</p></div>
            <div class="card p-2 text-center"><p class="text-lg font-bold" [style.color]="'var(--text-primary)'">{{ detailStats().users }}</p><p class="text-[10px]" [style.color]="'var(--text-tertiary)'">Users</p></div>
            <div class="card p-2 text-center"><p class="text-lg font-bold" [style.color]="'var(--text-primary)'">{{ detailStats().clients }}</p><p class="text-[10px]" [style.color]="'var(--text-tertiary)'">Clients</p></div>
          </div>
        </div>
      }
    </g51-modal>

    <!-- Subscription Modal -->
    <g51-modal [open]="showSub()" title="Manage Subscription" maxWidth="500px" (closed)="showSub.set(false)">
      @if (selectedTenant(); as t) {
        <div class="space-y-3">
          <p class="text-sm font-medium" [style.color]="'var(--text-primary)'">{{ t.company_name || t.name }}</p>
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Select Plan</label>
            <select [(ngModel)]="subForm.plan_id" class="input-base w-full">
              <option value="">No Plan</option>
              @for (p of allPlans(); track p.id) { <option [value]="p.id">{{ p.name }} — ₦{{ p.monthly_price | number:'1.0-0' }}/mo</option> }
            </select></div>
          <div class="grid grid-cols-2 gap-3">
            <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Start Date</label><input type="date" [(ngModel)]="subForm.start_date" class="input-base w-full" /></div>
            <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Status</label>
              <select [(ngModel)]="subForm.status" class="input-base w-full"><option value="active">Active</option><option value="trialing">Trial</option><option value="cancelled">Cancelled</option><option value="past_due">Past Due</option></select></div>
          </div>
        </div>
        <div modal-footer><button (click)="showSub.set(false)" class="btn-secondary">Cancel</button>
          <button (click)="saveSub()" class="btn-primary">Update Subscription</button></div>
      }
    </g51-modal>
  `,
})
export class TenantsComponent implements OnInit {
  private api = inject(ApiService); private toast = inject(ToastService);
  readonly BuildingIcon = Building2; readonly SearchIcon = Search; readonly EyeIcon = Eye;
  readonly BanIcon = Ban; readonly PlayIcon = Play; readonly DownloadIcon = Download;
  readonly CreditCardIcon = CreditCard;
  readonly loading = signal(true); readonly showDetail = signal(false); readonly showSub = signal(false);
  readonly tenants = signal<any[]>([]); readonly allPlans = signal<any[]>([]);
  readonly selectedTenant = signal<any>(null); readonly detailStats = signal<any>({ guards: 0, sites: 0, users: 0, clients: 0 });
  readonly page = signal(1); readonly totalPages = signal(1);
  search = ''; statusFilter = '';
  subForm: any = { plan_id: '', start_date: new Date().toISOString().slice(0, 10), status: 'active' };

  pages() { return Array.from({ length: Math.min(this.totalPages(), 10) }, (_, i) => i + 1); }

  ngOnInit(): void { this.load(); this.api.get<any>('/admin/plans').subscribe({ next: r => this.allPlans.set(r.data?.plans || r.data || []) }); }
  load(): void {
    this.loading.set(true);
    this.api.get<any>(`/admin/tenants?page=${this.page()}&per_page=20&search=${this.search}&status=${this.statusFilter}`).subscribe({
      next: r => {
        this.tenants.set(r.data?.items || r.data || []);
        this.totalPages.set(r.data?.total_pages || 1);
        this.loading.set(false);
      },
      error: () => this.loading.set(false),
    });
  }
  viewDetail(t: any): void {
    this.selectedTenant.set(t);
    this.showDetail.set(true);
    this.api.get<any>(`/admin/tenants/${t.id}`).subscribe({
      next: r => {
        const d = r.data || {};
        this.detailStats.set({ guards: d.guard_count || d.usage?.total_guards || 0, sites: d.site_count || d.usage?.total_sites || 0, users: d.user_count || 0, clients: d.client_count || 0 });
        if (d.tenant) this.selectedTenant.set({ ...t, ...d.tenant });
      },
    });
  }
  openSubscription(t: any): void {
    this.selectedTenant.set(t);
    this.subForm = { plan_id: t.plan_id || '', start_date: new Date().toISOString().slice(0, 10), status: 'active' };
    this.showSub.set(true);
  }
  saveSub(): void {
    this.api.post(`/admin/tenants/${this.selectedTenant()?.id}/subscription`, this.subForm).subscribe({
      next: () => { this.showSub.set(false); this.toast.success('Subscription updated'); this.load(); },
      error: () => this.toast.error('Failed to update subscription'),
    });
  }
  suspend(t: any): void { this.api.post(`/admin/tenants/${t.id}/suspend`, {}).subscribe({ next: () => { this.toast.success('Suspended'); this.load(); } }); }
  activate(t: any): void { this.api.post(`/admin/tenants/${t.id}/activate`, {}).subscribe({ next: () => { this.toast.success('Activated'); this.load(); } }); }
  exportAll(): void {
    exportToCsv('tenants', this.tenants(), [
      { key: 'company_name', label: 'Company' }, { key: 'admin_email', label: 'Email' },
      { key: 'plan_name', label: 'Plan' }, { key: 'status', label: 'Status' }, { key: 'created_at', label: 'Created' },
    ]);
  }
}
