import { Component, inject, signal, OnInit } from '@angular/core';
import { RouterLink } from '@angular/router';
import { LucideAngularModule, Plus, Search, Building2 } from 'lucide-angular';
import { PageHeaderComponent } from '@shared/components/page-header/page-header.component';
import { DataTableComponent, TableColumn } from '@shared/components/data-table/data-table.component';
import { StatsCardComponent } from '@shared/components/stats-card/stats-card.component';
import { ApiService } from '@core/services/api.service';

@Component({
  selector: 'g51-clients',
  standalone: true,
  imports: [RouterLink, LucideAngularModule, PageHeaderComponent, DataTableComponent, StatsCardComponent],
  template: `
    <g51-page-header title="Client Directory" subtitle="Manage clients, contacts, and contracts">
      <button class="btn-primary flex items-center gap-2" routerLink="new">
        <lucide-icon [img]="PlusIcon" [size]="16" /> Add Client
      </button>
    </g51-page-header>

    <div class="grid grid-cols-2 lg:grid-cols-3 gap-4 mb-6 stagger-children">
      <g51-stats-card label="Total Clients" [value]="stats().total" [icon]="BuildingIcon" />
      <g51-stats-card label="Active" [value]="stats().active" [icon]="BuildingIcon" />
      <g51-stats-card label="With Contract" [value]="stats().withContract" [icon]="BuildingIcon" />
    </div>

    <div class="mb-4">
      <div class="relative max-w-sm">
        <lucide-icon [img]="SearchIcon" [size]="16" class="absolute left-3 top-1/2 -translate-y-1/2" [style.color]="'var(--text-tertiary)'" />
        <input type="text" class="input-base w-full pl-9" placeholder="Search clients..." (input)="onSearch($event)" />
      </div>
    </div>

    <g51-data-table [columns]="columns" [rows]="filteredClients()" [total]="filteredClients().length" trackBy="id" />
  `,
})
export class ClientsComponent implements OnInit {
  private api = inject(ApiService);
  readonly PlusIcon = Plus; readonly SearchIcon = Search; readonly BuildingIcon = Building2;

  readonly clients = signal<any[]>([]);
  readonly searchTerm = signal('');
  readonly stats = signal({ total: 0, active: 0, withContract: 0 });

  columns: TableColumn[] = [
    { key: 'company_name', label: 'Company' },
    { key: 'contact_name', label: 'Contact' },
    { key: 'contact_phone', label: 'Phone' },
    { key: 'status_label', label: 'Status' },
    { key: 'billing_type', label: 'Billing' },
  ];

  filteredClients = () => {
    const q = this.searchTerm().toLowerCase();
    if (!q) return this.clients();
    return this.clients().filter(c => c.company_name.toLowerCase().includes(q));
  };

  ngOnInit(): void {
    this.api.get<{ clients: any[]; total: number }>('/clients').subscribe({
      next: res => {
        if (res.data) {
          const clients = res.data.clients;
          this.clients.set(clients);
          this.stats.set({
            total: clients.length,
            active: clients.filter((c: any) => c.status === 'active').length,
            withContract: clients.filter((c: any) => c.contract_start).length,
          });
        }
      },
    });
  }

  onSearch(e: Event): void { this.searchTerm.set((e.target as HTMLInputElement).value); }
}
