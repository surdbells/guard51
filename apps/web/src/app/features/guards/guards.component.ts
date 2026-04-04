import { Component, inject, signal, OnInit } from '@angular/core';
import { RouterLink } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { NgClass } from '@angular/common';
import { LucideAngularModule, Shield, Plus, Search, Trash2, UserCheck, UserX, Eye, MoreVertical, Edit } from 'lucide-angular';
import { PageHeaderComponent } from '@shared/components/page-header/page-header.component';
import { EmptyStateComponent } from '@shared/components/empty-state/empty-state.component';
import { LoadingSpinnerComponent } from '@shared/components/loading-spinner/loading-spinner.component';
import { ApiService } from '@core/services/api.service';
import { AuthStore } from '@core/services/auth.store';
import { ToastService } from '@core/services/toast.service';
import { exportToCsv } from '@core/utils/csv-export';
import { ConfirmService } from '@core/services/confirm.service';

@Component({
  selector: 'g51-guards',
  standalone: true,
  imports: [RouterLink, FormsModule, NgClass, LucideAngularModule, PageHeaderComponent, EmptyStateComponent, LoadingSpinnerComponent],
  template: `
    <g51-page-header title="Guards" subtitle="Manage your security personnel">
      @if (auth.isAdmin()) {
        <button class="btn-secondary flex items-center gap-2 text-xs" (click)="exportGuards()">Export CSV</button>
        <button class="btn-primary flex items-center gap-2 text-xs" routerLink="new"><lucide-icon [img]="PlusIcon" [size]="14" /> Add Guard</button>
      }
    </g51-page-header>

    <div class="flex items-center gap-3 mb-4">
      <div class="relative flex-1 max-w-sm">
        <lucide-icon [img]="SearchIcon" [size]="14" class="absolute left-3 top-1/2 -translate-y-1/2" [style.color]="'var(--text-tertiary)'" />
        <input type="text" [(ngModel)]="search" (ngModelChange)="onSearch()" placeholder="Search guards..." class="input-base w-full pl-9" />
      </div>
      <select [(ngModel)]="statusFilter" (ngModelChange)="loadGuards()" class="input-base text-xs py-2">
        <option value="">All Status</option><option value="active">Active</option><option value="inactive">Inactive</option><option value="suspended">Suspended</option>
      </select>
    </div>

    @if (loading()) { <g51-loading /> }
    @else if (!guards().length) { <g51-empty-state title="No Guards" message="Add your first guard to get started." [icon]="ShieldIcon" /> }
    @else {
      <div class="space-y-2">
        @for (g of guards(); track g.id) {
          <div class="card p-4 card-hover">
            <div class="flex items-start justify-between gap-2">
              <a [routerLink]="[g.id]" class="flex items-center gap-3 flex-1 min-w-0">
                <div class="h-10 w-10 rounded-full flex items-center justify-center text-sm font-bold text-white shrink-0" [style.background]="'var(--color-brand-500)'">{{ g.first_name?.charAt(0) }}{{ g.last_name?.charAt(0) }}</div>
                <div class="min-w-0">
                  <p class="text-sm font-semibold truncate" [style.color]="'var(--text-primary)'">{{ g.first_name }} {{ g.last_name }}</p>
                  <p class="text-xs truncate" [style.color]="'var(--text-tertiary)'">{{ g.employee_number }} · {{ g.phone || 'No phone' }}</p>
                </div>
              </a>
              <div class="flex items-center gap-1.5 shrink-0">
                <span class="badge text-[10px]" [ngClass]="g.status === 'active' ? 'bg-emerald-50 text-emerald-600' : g.status === 'suspended' ? 'bg-red-50 text-red-600' : 'bg-gray-100 text-gray-500'">{{ g.status }}</span>
                <!-- Desktop actions -->
                @if (auth.isAdmin()) {
                  <div class="hidden sm:flex items-center gap-1">
                    <a [routerLink]="[g.id]" class="btn-secondary text-xs py-1 px-2"><lucide-icon [img]="EyeIcon" [size]="12" /></a>
                    <a [routerLink]="['edit', g.id]" class="btn-secondary text-xs py-1 px-2">Edit</a>
                    @if (g.status === 'active') {
                      <button (click)="suspend(g)" class="btn-secondary text-xs py-1 px-2 text-amber-600"><lucide-icon [img]="UserXIcon" [size]="12" /></button>
                    } @else if (g.status === 'suspended' || g.status === 'inactive') {
                      <button (click)="activate(g)" class="btn-secondary text-xs py-1 px-2 text-emerald-600"><lucide-icon [img]="UserCheckIcon" [size]="12" /></button>
                    }
                    <button (click)="confirmDelete(g)" class="btn-secondary text-xs py-1 px-2 text-red-500"><lucide-icon [img]="TrashIcon" [size]="12" /></button>
                  </div>
                }
                <!-- Mobile action menu -->
                @if (auth.isAdmin()) {
                  <div class="relative sm:hidden">
                    <button (click)="toggleMenu(g.id)" class="p-1.5 rounded-lg hover:bg-[var(--surface-muted)]"><lucide-icon [img]="MoreIcon" [size]="16" [style.color]="'var(--text-tertiary)'" /></button>
                    @if (openMenuId() === g.id) {
                      <div class="absolute right-0 top-full mt-1 w-36 rounded-xl border py-1 z-20 animate-scale-in"
                        [style.background]="'var(--surface-card)'" [style.borderColor]="'var(--border-default)'" style="box-shadow:var(--shadow-lg)">
                        <a [routerLink]="[g.id]" class="flex items-center gap-2 px-3 py-2 text-xs hover:bg-[var(--surface-hover)]" [style.color]="'var(--text-primary)'"><lucide-icon [img]="EyeIcon" [size]="13" /> View</a>
                        <a [routerLink]="['edit', g.id]" class="flex items-center gap-2 px-3 py-2 text-xs hover:bg-[var(--surface-hover)]" [style.color]="'var(--text-primary)'"><lucide-icon [img]="EditIcon" [size]="13" /> Edit</a>
                        @if (g.status === 'active') {
                          <button (click)="suspend(g); openMenuId.set(null)" class="w-full flex items-center gap-2 px-3 py-2 text-xs hover:bg-[var(--surface-hover)] text-amber-600"><lucide-icon [img]="UserXIcon" [size]="13" /> Suspend</button>
                        } @else {
                          <button (click)="activate(g); openMenuId.set(null)" class="w-full flex items-center gap-2 px-3 py-2 text-xs hover:bg-[var(--surface-hover)] text-emerald-600"><lucide-icon [img]="UserCheckIcon" [size]="13" /> Activate</button>
                        }
                        <div class="my-1 h-px" [style.background]="'var(--border-default)'"></div>
                        <button (click)="confirmDelete(g); openMenuId.set(null)" class="w-full flex items-center gap-2 px-3 py-2 text-xs hover:bg-red-50 text-red-500"><lucide-icon [img]="TrashIcon" [size]="13" /> Delete</button>
                      </div>
                    }
                  </div>
                }
              </div>
            </div>
          </div>
        }
      </div>
      @if (totalPages() > 1) {
        <div class="flex justify-center gap-1 mt-4">
          @for (p of pages(); track p) {
            <button (click)="goToPage(p)" class="px-3 py-1 rounded text-xs" [ngClass]="p === page() ? 'bg-[var(--color-brand-500)] text-white' : 'bg-[var(--surface-muted)]'" [style.color]="p !== page() ? 'var(--text-secondary)' : ''">{{ p }}</button>
          }
        </div>
      }
    }
  `,
})
export class GuardsComponent implements OnInit {
  private api = inject(ApiService);
  readonly auth = inject(AuthStore);
  private toast = inject(ToastService);
  private confirmSvc = inject(ConfirmService);
  readonly ShieldIcon = Shield; readonly PlusIcon = Plus; readonly SearchIcon = Search;
  readonly MoreIcon = MoreVertical; readonly EditIcon = Edit;
  readonly openMenuId = signal<string | null>(null);

  toggleMenu(id: string): void { this.openMenuId.update(v => v === id ? null : id); }
  readonly TrashIcon = Trash2; readonly UserCheckIcon = UserCheck; readonly UserXIcon = UserX; readonly EyeIcon = Eye;

  readonly guards = signal<any[]>([]);
  readonly loading = signal(true);
  readonly page = signal(1);
  readonly totalPages = signal(1);
  readonly pages = signal<number[]>([]);
  search = ''; statusFilter = '';

  ngOnInit(): void { this.loadGuards(); }

  onSearch(): void { this.page.set(1); this.loadGuards(); }

  loadGuards(): void {
    this.loading.set(true);
    const params = new URLSearchParams();
    params.set('page', String(this.page()));
    if (this.search) params.set('search', this.search);
    if (this.statusFilter) params.set('status', this.statusFilter);
    this.api.get<any>(`/guards?${params}`).subscribe({
      next: res => { this.guards.set(res.data?.guards || res.data?.items || res.data || []); this.totalPages.set(res.data?.last_page || 1); this.pages.set(Array.from({length: this.totalPages()}, (_,i) => i+1)); this.loading.set(false); },
      error: () => this.loading.set(false),
    });
  }

  goToPage(p: number): void { this.page.set(p); this.loadGuards(); }
  async suspend(g: any): Promise<void> { const ok = await this.confirmSvc.suspend(`${g.first_name} ${g.last_name}`); if (ok) this.api.post(`/guards/${g.id}/suspend`, {}).subscribe({ next: () => { this.toast.success('Guard suspended'); this.loadGuards(); } }); }
  async activate(g: any): Promise<void> { const ok = await this.confirmSvc.show({ title: 'Activate Guard?', message: `Reactivate ${g.first_name} ${g.last_name}? They will regain access to the system.`, confirmText: 'Activate', variant: 'success' }); if (ok) this.api.post(`/guards/${g.id}/activate`, {}).subscribe({ next: () => { this.toast.success('Guard activated'); this.loadGuards(); } }); }
  async confirmDelete(g: any): Promise<void> {
    const ok = await this.confirmSvc.delete(`${g.first_name} ${g.last_name}`); if (ok) {
      this.api.delete(`/guards/${g.id}`).subscribe({ next: () => { this.toast.success('Guard deleted'); this.loadGuards(); } });
    }
  }
  exportGuards(): void {
    exportToCsv('guards', this.guards(), [
      { key: 'employee_number', label: 'Employee #' }, { key: 'first_name', label: 'First Name' },
      { key: 'last_name', label: 'Last Name' }, { key: 'phone', label: 'Phone' },
      { key: 'email', label: 'Email' }, { key: 'status', label: 'Status' },
      { key: 'state', label: 'State' }, { key: 'hire_date', label: 'Hire Date' },
    ]);
  }
}
