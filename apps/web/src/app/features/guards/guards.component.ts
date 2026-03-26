import { Component, inject, signal, OnInit } from '@angular/core';
import { RouterLink } from '@angular/router';
import { LucideAngularModule, Plus, Search, Shield, AlertTriangle } from 'lucide-angular';
import { PageHeaderComponent } from '@shared/components/page-header/page-header.component';
import { DataTableComponent, TableColumn } from '@shared/components/data-table/data-table.component';
import { StatsCardComponent } from '@shared/components/stats-card/stats-card.component';
import { ApiService } from '@core/services/api.service';

@Component({
  selector: 'g51-guards',
  standalone: true,
  imports: [RouterLink, LucideAngularModule, PageHeaderComponent, DataTableComponent, StatsCardComponent],
  template: `
    <g51-page-header title="Guard Directory" subtitle="Manage security guards, skills, and documents">
      <button class="btn-primary flex items-center gap-2" routerLink="new">
        <lucide-icon [img]="PlusIcon" [size]="16" /> Add Guard
      </button>
    </g51-page-header>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6 stagger-children">
      <g51-stats-card label="Total Guards" [value]="stats().total" [icon]="ShieldIcon" />
      <g51-stats-card label="Active" [value]="stats().active" [icon]="ShieldIcon" />
      <g51-stats-card label="Suspended" [value]="stats().suspended" [icon]="AlertTriangleIcon" />
      <g51-stats-card label="Docs Expiring" [value]="stats().docsExpiring" [icon]="AlertTriangleIcon" />
    </div>

    <div class="mb-4 flex items-center gap-3">
      <div class="relative flex-1 max-w-sm">
        <lucide-icon [img]="SearchIcon" [size]="16" class="absolute left-3 top-1/2 -translate-y-1/2" [style.color]="'var(--text-tertiary)'" />
        <input type="text" class="input-base w-full pl-9" placeholder="Search guards..." (input)="onSearch($event)" />
      </div>
    </div>

    <g51-data-table [columns]="columns" [rows]="filteredGuards()" [total]="filteredGuards().length" trackBy="id" />
  `,
})
export class GuardsComponent implements OnInit {
  private api = inject(ApiService);
  readonly PlusIcon = Plus; readonly SearchIcon = Search; readonly ShieldIcon = Shield; readonly AlertTriangleIcon = AlertTriangle;

  readonly guards = signal<any[]>([]);
  readonly searchTerm = signal('');
  readonly stats = signal({ total: 0, active: 0, suspended: 0, docsExpiring: 0 });

  columns: TableColumn[] = [
    { key: 'employee_number', label: 'Emp #', width: '90px' },
    { key: 'full_name', label: 'Name' },
    { key: 'phone', label: 'Phone' },
    { key: 'status_label', label: 'Status' },
    { key: 'hire_date', label: 'Hired' },
  ];

  filteredGuards = () => {
    const q = this.searchTerm().toLowerCase();
    if (!q) return this.guards();
    return this.guards().filter(g => g.full_name.toLowerCase().includes(q) || g.employee_number.includes(q));
  };

  ngOnInit(): void {
    this.api.get<{ guards: any[]; total: number }>('/guards').subscribe({
      next: res => {
        if (res.data) {
          const guards = res.data.guards;
          this.guards.set(guards);
          this.stats.set({
            total: guards.length,
            active: guards.filter((g: any) => g.status === 'active').length,
            suspended: guards.filter((g: any) => g.status === 'suspended').length,
            docsExpiring: 0,
          });
        }
      },
    });
  }

  onSearch(e: Event): void { this.searchTerm.set((e.target as HTMLInputElement).value); }
}
