import { Component, inject, signal, OnInit } from '@angular/core';
import { RouterLink } from '@angular/router';
import { LucideAngularModule, Plus, Search, MapPin, Eye } from 'lucide-angular';
import { PageHeaderComponent } from '@shared/components/page-header/page-header.component';
import { DataTableComponent, TableColumn } from '@shared/components/data-table/data-table.component';
import { StatsCardComponent } from '@shared/components/stats-card/stats-card.component';
import { ApiService } from '@core/services/api.service';

@Component({
  selector: 'g51-sites',
  standalone: true,
  imports: [RouterLink, LucideAngularModule, PageHeaderComponent, DataTableComponent, StatsCardComponent],
  template: `
    <g51-page-header title="Sites & Posts" subtitle="Manage security sites, geofences, and post orders">
      <button class="btn-primary flex items-center gap-2" routerLink="new">
        <lucide-icon [img]="PlusIcon" [size]="16" /> Add Site
      </button>
    </g51-page-header>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6 stagger-children">
      <g51-stats-card label="Total Sites" [value]="stats().total" [icon]="MapPinIcon" />
      <g51-stats-card label="Active" [value]="stats().active" [icon]="MapPinIcon" />
      <g51-stats-card label="With Geofence" [value]="stats().withGeofence" [icon]="MapPinIcon" />
      <g51-stats-card label="Post Orders" [value]="stats().postOrders" [icon]="EyeIcon" />
    </div>

    <div class="mb-4">
      <div class="relative max-w-sm">
        <lucide-icon [img]="SearchIcon" [size]="16" class="absolute left-3 top-1/2 -translate-y-1/2" [style.color]="'var(--text-tertiary)'" />
        <input type="text" class="input-base w-full pl-9" placeholder="Search sites..." (input)="onSearch($event)" />
      </div>
    </div>

    <g51-data-table [columns]="columns" [rows]="filteredSites()" [total]="filteredSites().length" trackBy="id" />
  `,
})
export class SitesComponent implements OnInit {
  private api = inject(ApiService);
  readonly PlusIcon = Plus; readonly SearchIcon = Search; readonly MapPinIcon = MapPin; readonly EyeIcon = Eye;

  readonly sites = signal<any[]>([]);
  readonly searchTerm = signal('');
  readonly stats = signal({ total: 0, active: 0, withGeofence: 0, postOrders: 0 });

  columns: TableColumn[] = [
    { key: 'name', label: 'Site Name' },
    { key: 'city', label: 'City' },
    { key: 'status', label: 'Status' },
    { key: 'geofence_type', label: 'Geofence' },
    { key: 'contact_name', label: 'Contact' },
  ];

  filteredSites = () => {
    const q = this.searchTerm().toLowerCase();
    if (!q) return this.sites();
    return this.sites().filter(s => s.name.toLowerCase().includes(q) || (s.city || '').toLowerCase().includes(q));
  };

  ngOnInit(): void {
    this.api.get<{ sites: any[]; total: number }>('/sites').subscribe({
      next: res => {
        if (res.data) {
          this.sites.set(res.data.sites);
          this.stats.set({
            total: res.data.total,
            active: res.data.sites.filter((s: any) => s.status === 'active').length,
            withGeofence: res.data.sites.filter((s: any) => s.latitude).length,
            postOrders: 0,
          });
        }
      },
    });
  }

  onSearch(e: Event): void { this.searchTerm.set((e.target as HTMLInputElement).value); }
}
