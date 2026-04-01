import { Component, inject, signal, OnInit } from '@angular/core';
import { RouterLink } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { NgClass } from '@angular/common';
import { LucideAngularModule, Building2, Plus, Search, Trash2, Eye } from 'lucide-angular';
import { PageHeaderComponent } from '@shared/components/page-header/page-header.component';
import { EmptyStateComponent } from '@shared/components/empty-state/empty-state.component';
import { LoadingSpinnerComponent } from '@shared/components/loading-spinner/loading-spinner.component';
import { ApiService } from '@core/services/api.service';
import { ToastService } from '@core/services/toast.service';

@Component({
  selector: 'g51-clients',
  standalone: true,
  imports: [RouterLink, FormsModule, NgClass, LucideAngularModule, PageHeaderComponent, EmptyStateComponent, LoadingSpinnerComponent],
  template: `
    <g51-page-header title="Clients" subtitle="Manage client companies and contacts">
      <button class="btn-primary flex items-center gap-2" routerLink="new"><lucide-icon [img]="PlusIcon" [size]="16" /> Add Client</button>
    </g51-page-header>
    <div class="flex items-center gap-3 mb-4">
      <div class="relative flex-1 max-w-sm">
        <lucide-icon [img]="SearchIcon" [size]="14" class="absolute left-3 top-1/2 -translate-y-1/2" [style.color]="'var(--text-tertiary)'" />
        <input type="text" [(ngModel)]="search" (ngModelChange)="onSearch()" placeholder="Search clients..." class="input-base w-full pl-9" />
      </div>
      <select [(ngModel)]="statusFilter" (ngModelChange)="loadClients()" class="input-base text-xs py-2">
        <option value="">All Status</option><option value="active">Active</option><option value="inactive">Inactive</option>
      </select>
    </div>
    @if (loading()) { <g51-loading /> }
    @else if (!clients().length) { <g51-empty-state title="No Clients" message="Add your first client to get started." [icon]="BuildingIcon" /> }
    @else {
      <div class="space-y-2">
        @for (c of clients(); track c.id) {
          <div class="card p-4 card-hover">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-lg flex items-center justify-center" [style.background]="'var(--color-accent-50)'" [style.color]="'var(--color-accent-500)'"><lucide-icon [img]="BuildingIcon" [size]="18" /></div>
                <div>
                  <a [routerLink]="[c.id]" class="text-sm font-semibold hover:underline" [style.color]="'var(--text-primary)'">{{ c.company_name || c.name }}</a>
                  <p class="text-xs" [style.color]="'var(--text-tertiary)'">{{ c.contact_name || '' }} · {{ c.contact_email || c.email || '' }}</p>
                </div>
              </div>
              <div class="flex items-center gap-2">
                <span class="badge text-[10px]" [ngClass]="c.status === 'active' ? 'bg-emerald-50 text-emerald-600' : 'bg-gray-100 text-gray-500'">{{ c.status }}</span>
                <a [routerLink]="[c.id]" class="btn-secondary text-xs py-1 px-2"><lucide-icon [img]="EyeIcon" [size]="12" /></a>
                <a [routerLink]="['edit', c.id]" class="btn-secondary text-xs py-1 px-2">Edit</a>
                <button (click)="confirmDelete(c)" class="btn-secondary text-xs py-1 px-2 text-red-500"><lucide-icon [img]="TrashIcon" [size]="12" /></button>
              </div>
            </div>
          </div>
        }
      </div>
    }
  `,
})
export class ClientsComponent implements OnInit {
  private api = inject(ApiService); private toast = inject(ToastService);
  readonly BuildingIcon = Building2; readonly PlusIcon = Plus; readonly SearchIcon = Search;
  readonly TrashIcon = Trash2; readonly EyeIcon = Eye;
  readonly clients = signal<any[]>([]); readonly loading = signal(true);
  search = ''; statusFilter = '';
  ngOnInit(): void { this.loadClients(); }
  onSearch(): void { this.loadClients(); }
  loadClients(): void {
    this.loading.set(true);
    const p = new URLSearchParams();
    if (this.search) p.set('search', this.search);
    if (this.statusFilter) p.set('status', this.statusFilter);
    this.api.get<any>(`/clients?${p}`).subscribe({
      next: res => { this.clients.set(res.data?.clients || res.data?.items || res.data || []); this.loading.set(false); },
      error: () => this.loading.set(false),
    });
  }
  confirmDelete(c: any): void { if (confirm(`Delete client "${c.company_name || c.name}"?`)) { this.api.delete(`/clients/${c.id}`).subscribe({ next: () => { this.toast.success('Client deleted'); this.loadClients(); } }); } }
}
