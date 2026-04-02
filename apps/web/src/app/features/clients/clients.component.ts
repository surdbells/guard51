import { Component, inject, signal, OnInit } from '@angular/core';
import { RouterLink } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { NgClass, DecimalPipe } from '@angular/common';
import { LucideAngularModule, Building2, Plus, Search, Eye, Edit, Trash2, Download } from 'lucide-angular';
import { PageHeaderComponent } from '@shared/components/page-header/page-header.component';
import { StatsCardComponent } from '@shared/components/stats-card/stats-card.component';
import { EmptyStateComponent } from '@shared/components/empty-state/empty-state.component';
import { LoadingSpinnerComponent } from '@shared/components/loading-spinner/loading-spinner.component';
import { ApiService } from '@core/services/api.service';
import { ToastService } from '@core/services/toast.service';
import { exportToCsv } from '@core/utils/csv-export';

@Component({
  selector: 'g51-clients',
  standalone: true,
  imports: [RouterLink, FormsModule, NgClass, DecimalPipe, LucideAngularModule, PageHeaderComponent, StatsCardComponent, EmptyStateComponent, LoadingSpinnerComponent],
  template: `
    <g51-page-header title="Clients" subtitle="Manage client companies and contracts">
      <button class="btn-secondary flex items-center gap-2 text-xs" (click)="exportData()"><lucide-icon [img]="DownloadIcon" [size]="14" /> Export</button>
      <button class="btn-primary flex items-center gap-2" routerLink="new"><lucide-icon [img]="PlusIcon" [size]="16" /> Add Client</button>
    </g51-page-header>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-4 stagger-children">
      <g51-stats-card label="Total Clients" [value]="stats().total" [icon]="BuildingIcon" />
      <g51-stats-card label="Active" [value]="stats().active" [icon]="BuildingIcon" />
      <g51-stats-card label="Sites Covered" [value]="stats().sites" [icon]="BuildingIcon" />
      <g51-stats-card label="Outstanding (₦)" [value]="(stats().outstanding | number:'1.0-0') || '0'" [icon]="BuildingIcon" />
    </div>

    <div class="flex items-center gap-3 mb-4">
      <div class="relative flex-1 max-w-sm">
        <lucide-icon [img]="SearchIcon" [size]="14" class="absolute left-3 top-1/2 -translate-y-1/2" [style.color]="'var(--text-tertiary)'" />
        <input type="text" [(ngModel)]="search" (ngModelChange)="loadClients()" placeholder="Search clients..." class="input-base w-full pl-9" />
      </div>
      <select [(ngModel)]="statusFilter" (ngModelChange)="loadClients()" class="input-base text-xs py-2">
        <option value="">All Status</option><option value="active">Active</option><option value="inactive">Inactive</option><option value="prospect">Prospect</option>
      </select>
    </div>

    @if (loading()) { <g51-loading /> }
    @else if (!clients().length) { <g51-empty-state title="No Clients" message="Add your first client." [icon]="BuildingIcon" /> }
    @else {
      <div class="card overflow-hidden">
        <table class="w-full text-xs">
          <thead><tr [style.background]="'var(--surface-muted)'">
            <th class="text-left py-2.5 px-4 font-semibold" [style.color]="'var(--text-secondary)'">Client</th>
            <th class="text-left py-2.5 px-4 font-semibold" [style.color]="'var(--text-secondary)'">Contact</th>
            <th class="text-left py-2.5 px-4 font-semibold" [style.color]="'var(--text-secondary)'">Billing</th>
            <th class="text-left py-2.5 px-4 font-semibold" [style.color]="'var(--text-secondary)'">Sites</th>
            <th class="text-left py-2.5 px-4 font-semibold" [style.color]="'var(--text-secondary)'">Status</th>
            <th class="text-center py-2.5 px-4 font-semibold" [style.color]="'var(--text-secondary)'">Actions</th>
          </tr></thead>
          <tbody>
            @for (c of clients(); track c.id) {
              <tr class="border-t hover:bg-[var(--surface-hover)]" [style.borderColor]="'var(--border-default)'">
                <td class="py-2.5 px-4">
                  <a [routerLink]="[c.id]" class="font-medium hover:underline" [style.color]="'var(--text-primary)'">{{ c.company_name || c.name }}</a>
                  <p class="text-[10px]" [style.color]="'var(--text-tertiary)'">{{ c.industry || '' }}</p>
                </td>
                <td class="py-2.5 px-4">
                  <p [style.color]="'var(--text-primary)'">{{ c.contact_name || '—' }}</p>
                  <p class="text-[10px]" [style.color]="'var(--text-tertiary)'">{{ c.contact_email || c.email || '' }}</p>
                </td>
                <td class="py-2.5 px-4">
                  <p [style.color]="'var(--text-primary)'">{{ c.billing_type || 'per_guard' }}</p>
                  <p class="text-[10px]" [style.color]="'var(--text-tertiary)'">{{ c.contract_start ? c.contract_start + ' → ' + (c.contract_end || 'Ongoing') : '—' }}</p>
                </td>
                <td class="py-2.5 px-4 font-medium" [style.color]="'var(--text-primary)'">{{ c.site_count || 0 }}</td>
                <td class="py-2.5 px-4">
                  <span class="badge text-[10px]" [ngClass]="c.status === 'active' ? 'bg-emerald-50 text-emerald-600' : c.status === 'prospect' ? 'bg-blue-50 text-blue-600' : 'bg-gray-100 text-gray-500'">{{ c.status }}</span>
                </td>
                <td class="py-2.5 px-4 text-center">
                  <div class="flex justify-center gap-1">
                    <a [routerLink]="[c.id]" class="p-1 rounded hover:bg-[var(--surface-muted)]"><lucide-icon [img]="EyeIcon" [size]="14" [style.color]="'var(--text-tertiary)'" /></a>
                    <a [routerLink]="['edit', c.id]" class="p-1 rounded hover:bg-[var(--surface-muted)]"><lucide-icon [img]="EditIcon" [size]="14" [style.color]="'var(--text-tertiary)'" /></a>
                    <button (click)="confirmDelete(c)" class="p-1 rounded hover:bg-red-50"><lucide-icon [img]="TrashIcon" [size]="14" class="text-red-400" /></button>
                  </div>
                </td>
              </tr>
            }
          </tbody>
        </table>
      </div>
    }
  `,
})
export class ClientsComponent implements OnInit {
  private api = inject(ApiService); private toast = inject(ToastService);
  readonly BuildingIcon = Building2; readonly PlusIcon = Plus; readonly SearchIcon = Search;
  readonly EyeIcon = Eye; readonly EditIcon = Edit; readonly TrashIcon = Trash2; readonly DownloadIcon = Download;
  readonly clients = signal<any[]>([]); readonly loading = signal(true);
  readonly stats = signal<any>({ total: 0, active: 0, sites: 0, outstanding: 0 });
  search = ''; statusFilter = '';

  ngOnInit(): void { this.loadClients(); }
  loadClients(): void {
    this.loading.set(true);
    this.api.get<any>(`/clients?search=${this.search}&status=${this.statusFilter}`).subscribe({
      next: r => {
        const data = r.data?.clients || r.data?.items || r.data || [];
        this.clients.set(data);
        this.stats.set({
          total: data.length,
          active: data.filter((c: any) => c.status === 'active').length,
          sites: data.reduce((sum: number, c: any) => sum + (c.site_count || 0), 0),
          outstanding: 0,
        });
        this.loading.set(false);
      },
      error: () => this.loading.set(false),
    });
  }
  confirmDelete(c: any): void {
    if (confirm(`Delete ${c.company_name || c.name}?`)) {
      this.api.delete(`/clients/${c.id}`).subscribe({ next: () => { this.toast.success('Client deleted'); this.loadClients(); } });
    }
  }
  exportData(): void { exportToCsv('clients', this.clients(), [{ key: 'company_name', label: 'Company' }, { key: 'contact_name', label: 'Contact' }, { key: 'contact_email', label: 'Email' }, { key: 'billing_type', label: 'Billing' }, { key: 'status', label: 'Status' }]); }
}
