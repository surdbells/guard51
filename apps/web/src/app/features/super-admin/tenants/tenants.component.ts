import { Component } from '@angular/core';
import { LucideAngularModule, Plus, Search } from 'lucide-angular';
import { PageHeaderComponent } from '@shared/components/page-header/page-header.component';
import { DataTableComponent, TableColumn } from '@shared/components/data-table/data-table.component';

@Component({
  selector: 'g51-tenants',
  standalone: true,
  imports: [LucideAngularModule, PageHeaderComponent, DataTableComponent],
  template: `
    <g51-page-header title="Tenant Management" subtitle="Manage all registered organizations">
      <button class="btn-primary flex items-center gap-2">
        <lucide-icon [img]="PlusIcon" [size]="16" /> Add Tenant
      </button>
    </g51-page-header>

    <!-- Search -->
    <div class="mb-4 flex items-center gap-3">
      <div class="relative flex-1 max-w-sm">
        <lucide-icon [img]="SearchIcon" [size]="16" class="absolute left-3 top-1/2 -translate-y-1/2" [style.color]="'var(--text-tertiary)'" />
        <input type="text" class="input-base w-full pl-9" placeholder="Search tenants..." />
      </div>
    </div>

    <g51-data-table [columns]="columns" [rows]="rows" [total]="rows.length" [trackBy]="'id'" />
  `,
})
export class TenantsComponent {
  readonly PlusIcon = Plus;
  readonly SearchIcon = Search;

  columns: TableColumn[] = [
    { key: 'name', label: 'Organization' },
    { key: 'type', label: 'Type' },
    { key: 'plan', label: 'Plan' },
    { key: 'guards', label: 'Guards', align: 'center' },
    { key: 'status', label: 'Status' },
    { key: 'created', label: 'Joined' },
  ];

  rows = [
    { id: '1', name: 'ShieldForce Security', type: 'Private Security', plan: 'Business', guards: 85, status: 'Active', created: '2024-11-15' },
    { id: '2', name: 'Fortress Guard Services', type: 'Private Security', plan: 'Professional', guards: 42, status: 'Active', created: '2024-12-02' },
    { id: '3', name: 'Ikeja Neighborhood Watch', type: 'Neighborhood Watch', plan: 'Starter', guards: 15, status: 'Trial', created: '2025-01-10' },
    { id: '4', name: 'Eagle Eye Protection', type: 'Private Security', plan: 'Starter', guards: 20, status: 'Active', created: '2025-02-01' },
    { id: '5', name: 'Lagos State Police HQ', type: 'State Police', plan: 'Enterprise', guards: 350, status: 'Active', created: '2025-03-01' },
  ];
}
