import { Component, inject, signal, OnInit } from '@angular/core';
import { RouterLink } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { NgClass } from '@angular/common';
import { LucideAngularModule, MapPin, Plus, Search, Eye, Edit, Trash2, Download, Shield, Users } from 'lucide-angular';
import { PageHeaderComponent } from '@shared/components/page-header/page-header.component';
import { StatsCardComponent } from '@shared/components/stats-card/stats-card.component';
import { EmptyStateComponent } from '@shared/components/empty-state/empty-state.component';
import { LoadingSpinnerComponent } from '@shared/components/loading-spinner/loading-spinner.component';
import { ApiService } from '@core/services/api.service';
import { ToastService } from '@core/services/toast.service';
import { exportToCsv } from '@core/utils/csv-export';

@Component({
  selector: 'g51-sites',
  standalone: true,
  imports: [RouterLink, FormsModule, NgClass, LucideAngularModule, PageHeaderComponent, StatsCardComponent, EmptyStateComponent, LoadingSpinnerComponent],
  template: `
    <g51-page-header title="Sites" subtitle="Manage security post locations">
      <button class="btn-secondary flex items-center gap-2 text-xs" (click)="exportData()"><lucide-icon [img]="DownloadIcon" [size]="14" /> Export</button>
      <button class="btn-primary flex items-center gap-2" routerLink="new"><lucide-icon [img]="PlusIcon" [size]="16" /> Add Site</button>
    </g51-page-header>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-4 stagger-children">
      <g51-stats-card label="Total Sites" [value]="stats().total" [icon]="MapPinIcon" />
      <g51-stats-card label="Active" [value]="stats().active" [icon]="MapPinIcon" />
      <g51-stats-card label="Guards Deployed" [value]="stats().guards" [icon]="ShieldIcon" />
      <g51-stats-card label="With Geofence" [value]="stats().geofenced" [icon]="MapPinIcon" />
    </div>

    <div class="flex items-center gap-3 mb-4">
      <div class="relative flex-1 max-w-sm">
        <lucide-icon [img]="SearchIcon" [size]="14" class="absolute left-3 top-1/2 -translate-y-1/2" [style.color]="'var(--text-tertiary)'" />
        <input type="text" [(ngModel)]="search" (ngModelChange)="loadSites()" placeholder="Search sites..." class="input-base w-full pl-9" />
      </div>
      <select [(ngModel)]="statusFilter" (ngModelChange)="loadSites()" class="input-base text-xs py-2">
        <option value="">All Status</option><option value="active">Active</option><option value="inactive">Inactive</option>
      </select>
    </div>

    @if (loading()) { <g51-loading /> }
    @else if (!sites().length) { <g51-empty-state title="No Sites" message="Add your first site." [icon]="MapPinIcon" /> }
    @else {
      <div class="card overflow-hidden">
        <table class="w-full text-xs">
          <thead><tr [style.background]="'var(--surface-muted)'">
            <th class="text-left py-2.5 px-4 font-semibold" [style.color]="'var(--text-secondary)'">Site</th>
            <th class="text-left py-2.5 px-4 font-semibold" [style.color]="'var(--text-secondary)'">Client</th>
            <th class="text-left py-2.5 px-4 font-semibold" [style.color]="'var(--text-secondary)'">Location</th>
            <th class="text-left py-2.5 px-4 font-semibold" [style.color]="'var(--text-secondary)'">Guards</th>
            <th class="text-left py-2.5 px-4 font-semibold" [style.color]="'var(--text-secondary)'">Geofence</th>
            <th class="text-left py-2.5 px-4 font-semibold" [style.color]="'var(--text-secondary)'">Status</th>
            <th class="text-center py-2.5 px-4 font-semibold" [style.color]="'var(--text-secondary)'">Actions</th>
          </tr></thead>
          <tbody>
            @for (s of sites(); track s.id) {
              <tr class="border-t hover:bg-[var(--surface-hover)]" [style.borderColor]="'var(--border-default)'">
                <td class="py-2.5 px-4">
                  <a [routerLink]="[s.id]" class="font-medium hover:underline" [style.color]="'var(--text-primary)'">{{ s.name }}</a>
                  <p class="text-[10px]" [style.color]="'var(--text-tertiary)'">{{ s.site_code || '' }}</p>
                </td>
                <td class="py-2.5 px-4" [style.color]="'var(--text-secondary)'">{{ s.client_name || '—' }}</td>
                <td class="py-2.5 px-4">
                  <p [style.color]="'var(--text-secondary)'">{{ s.address || '—' }}</p>
                  <p class="text-[10px]" [style.color]="'var(--text-tertiary)'">{{ s.city || '' }}{{ s.state ? ', ' + s.state : '' }}</p>
                </td>
                <td class="py-2.5 px-4 font-medium" [style.color]="'var(--text-primary)'">{{ s.guard_count || s.required_guards || 0 }}</td>
                <td class="py-2.5 px-4">
                  <span class="badge text-[10px]" [ngClass]="s.geofence_radius || s.latitude ? 'bg-emerald-50 text-emerald-600' : 'bg-gray-100 text-gray-400'">{{ s.geofence_radius || s.latitude ? 'Active' : 'None' }}</span>
                </td>
                <td class="py-2.5 px-4">
                  <span class="badge text-[10px]" [ngClass]="s.status === 'active' ? 'bg-emerald-50 text-emerald-600' : 'bg-gray-100 text-gray-500'">{{ s.status }}</span>
                </td>
                <td class="py-2.5 px-4 text-center">
                  <div class="flex justify-center gap-1">
                    <a [routerLink]="[s.id]" class="p-1 rounded hover:bg-[var(--surface-muted)]"><lucide-icon [img]="EyeIcon" [size]="14" [style.color]="'var(--text-tertiary)'" /></a>
                    <a [routerLink]="['edit', s.id]" class="p-1 rounded hover:bg-[var(--surface-muted)]"><lucide-icon [img]="EditIcon" [size]="14" [style.color]="'var(--text-tertiary)'" /></a>
                    <button (click)="confirmDelete(s)" class="p-1 rounded hover:bg-red-50"><lucide-icon [img]="TrashIcon" [size]="14" class="text-red-400" /></button>
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
export class SitesComponent implements OnInit {
  private api = inject(ApiService); private toast = inject(ToastService);
  readonly MapPinIcon = MapPin; readonly PlusIcon = Plus; readonly SearchIcon = Search;
  readonly EyeIcon = Eye; readonly EditIcon = Edit; readonly TrashIcon = Trash2;
  readonly DownloadIcon = Download; readonly ShieldIcon = Shield;
  readonly sites = signal<any[]>([]); readonly loading = signal(true);
  readonly stats = signal<any>({ total: 0, active: 0, guards: 0, geofenced: 0 });
  search = ''; statusFilter = '';

  ngOnInit(): void { this.loadSites(); }
  loadSites(): void {
    this.loading.set(true);
    this.api.get<any>(`/sites?search=${this.search}&status=${this.statusFilter}`).subscribe({
      next: r => {
        const data = r.data?.sites || r.data?.items || r.data || [];
        this.sites.set(data);
        this.stats.set({
          total: data.length,
          active: data.filter((s: any) => s.status === 'active').length,
          guards: data.reduce((sum: number, s: any) => sum + (s.guard_count || s.required_guards || 0), 0),
          geofenced: data.filter((s: any) => s.geofence_radius || s.latitude).length,
        });
        this.loading.set(false);
      },
      error: () => this.loading.set(false),
    });
  }
  confirmDelete(s: any): void {
    if (confirm(`Delete site ${s.name}?`)) {
      this.api.delete(`/sites/${s.id}`).subscribe({ next: () => { this.toast.success('Site deleted'); this.loadSites(); } });
    }
  }
  exportData(): void { exportToCsv('sites', this.sites(), [{ key: 'name', label: 'Name' }, { key: 'client_name', label: 'Client' }, { key: 'address', label: 'Address' }, { key: 'city', label: 'City' }, { key: 'state', label: 'State' }, { key: 'guard_count', label: 'Guards' }, { key: 'status', label: 'Status' }]); }
}
