import { Component, inject, signal, OnInit } from '@angular/core';
import { RouterLink } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { NgClass } from '@angular/common';
import { LucideAngularModule, Shield, Plus, Search, Trash2, UserCheck, UserX, Eye } from 'lucide-angular';
import { PageHeaderComponent } from '@shared/components/page-header/page-header.component';
import { EmptyStateComponent } from '@shared/components/empty-state/empty-state.component';
import { LoadingSpinnerComponent } from '@shared/components/loading-spinner/loading-spinner.component';
import { ApiService } from '@core/services/api.service';
import { ToastService } from '@core/services/toast.service';

@Component({
  selector: 'g51-guards',
  standalone: true,
  imports: [RouterLink, FormsModule, NgClass, LucideAngularModule, PageHeaderComponent, EmptyStateComponent, LoadingSpinnerComponent],
  template: `
    <g51-page-header title="Guards" subtitle="Manage your security personnel">
      <button class="btn-primary flex items-center gap-2" routerLink="new"><lucide-icon [img]="PlusIcon" [size]="16" /> Add Guard</button>
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
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-full flex items-center justify-center text-sm font-bold text-white" [style.background]="'var(--color-brand-500)'">{{ g.first_name?.charAt(0) }}{{ g.last_name?.charAt(0) }}</div>
                <div>
                  <a [routerLink]="[g.id]" class="text-sm font-semibold hover:underline" [style.color]="'var(--text-primary)'">{{ g.first_name }} {{ g.last_name }}</a>
                  <p class="text-xs" [style.color]="'var(--text-tertiary)'">{{ g.employee_number }} · {{ g.phone || 'No phone' }}</p>
                </div>
              </div>
              <div class="flex items-center gap-2">
                <span class="badge text-[10px]" [ngClass]="g.status === 'active' ? 'bg-emerald-50 text-emerald-600' : g.status === 'suspended' ? 'bg-red-50 text-red-600' : 'bg-gray-100 text-gray-500'">{{ g.status }}</span>
                <a [routerLink]="[g.id]" class="btn-secondary text-xs py-1 px-2"><lucide-icon [img]="EyeIcon" [size]="12" /></a>
                <a [routerLink]="[g.id, 'edit']" class="btn-secondary text-xs py-1 px-2">Edit</a>
                @if (g.status === 'active') {
                  <button (click)="suspend(g)" class="btn-secondary text-xs py-1 px-2 text-amber-600"><lucide-icon [img]="UserXIcon" [size]="12" /></button>
                } @else if (g.status === 'suspended' || g.status === 'inactive') {
                  <button (click)="activate(g)" class="btn-secondary text-xs py-1 px-2 text-emerald-600"><lucide-icon [img]="UserCheckIcon" [size]="12" /></button>
                }
                <button (click)="confirmDelete(g)" class="btn-secondary text-xs py-1 px-2 text-red-500"><lucide-icon [img]="TrashIcon" [size]="12" /></button>
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
  private toast = inject(ToastService);
  readonly ShieldIcon = Shield; readonly PlusIcon = Plus; readonly SearchIcon = Search;
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
  suspend(g: any): void { this.api.post(`/guards/${g.id}/suspend`, {}).subscribe({ next: () => { this.toast.success('Guard suspended'); this.loadGuards(); } }); }
  activate(g: any): void { this.api.post(`/guards/${g.id}/activate`, {}).subscribe({ next: () => { this.toast.success('Guard activated'); this.loadGuards(); } }); }
  confirmDelete(g: any): void {
    if (confirm(`Delete guard ${g.first_name} ${g.last_name}? This cannot be undone.`)) {
      this.api.delete(`/guards/${g.id}`).subscribe({ next: () => { this.toast.success('Guard deleted'); this.loadGuards(); } });
    }
  }
}
