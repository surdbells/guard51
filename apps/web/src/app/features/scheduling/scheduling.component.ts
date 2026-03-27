import { Component, inject, signal, OnInit } from '@angular/core';
import { RouterLink } from '@angular/router';
import { NgClass } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { LucideAngularModule, Plus, Calendar, Clock, ChevronLeft, ChevronRight, Users, AlertTriangle } from 'lucide-angular';
import { PageHeaderComponent } from '@shared/components/page-header/page-header.component';
import { StatsCardComponent } from '@shared/components/stats-card/stats-card.component';
import { StackedBarChartComponent, StackedBarSeries } from '@shared/components/charts/stacked-bar-chart.component';
import { ApiService } from '@core/services/api.service';

@Component({
  selector: 'g51-scheduling',
  standalone: true,
  imports: [RouterLink, NgClass, FormsModule, LucideAngularModule, PageHeaderComponent, StatsCardComponent, StackedBarChartComponent],
  template: `
    <g51-page-header title="Shift Scheduling" subtitle="Manage shifts, templates, and open shift board">
      <a routerLink="open-shifts" class="btn-secondary flex items-center gap-2">
        <lucide-icon [img]="UsersIcon" [size]="16" /> Open Shifts
      </a>
      <a routerLink="swaps" class="btn-secondary flex items-center gap-2">
        <lucide-icon [img]="AlertTriangleIcon" [size]="16" /> Swaps
      </a>
      <a routerLink="templates" class="btn-secondary flex items-center gap-2">
        <lucide-icon [img]="ClockIcon" [size]="16" /> Templates
      </a>
      <a routerLink="bulk" class="btn-secondary flex items-center gap-2">
        <lucide-icon [img]="CalendarIcon" [size]="16" /> Bulk
      </a>
      <a routerLink="new" class="btn-primary flex items-center gap-2">
        <lucide-icon [img]="PlusIcon" [size]="16" /> New Shift
      </a>
    </g51-page-header>

    <!-- Filters -->
    <div class="flex flex-wrap gap-3 mb-6">
      <select [(ngModel)]="filterSiteId" (ngModelChange)="loadShifts()" class="input-base text-sm py-1.5 min-w-[180px]">
        <option value="">All Sites</option>
        @for (s of allSites(); track s.id) { <option [value]="s.id">{{ s.name }}</option> }
      </select>
      <select [(ngModel)]="filterGuardId" (ngModelChange)="loadShifts()" class="input-base text-sm py-1.5 min-w-[180px]">
        <option value="">All Guards</option>
        @for (g of allGuards(); track g.id) { <option [value]="g.id">{{ g.full_name }}</option> }
      </select>
    </div>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6 stagger-children">
      <g51-stats-card label="This Week's Shifts" [value]="stats().total" [icon]="CalendarIcon" />
      <g51-stats-card label="Confirmed" [value]="stats().confirmed" [icon]="CalendarIcon" />
      <g51-stats-card label="Open Shifts" [value]="stats().open" [icon]="UsersIcon" />
      <g51-stats-card label="Swap Requests" [value]="stats().swaps" [icon]="AlertTriangleIcon" />
    </div>

    <!-- Week navigator -->
    <div class="card p-4 mb-4">
      <div class="flex items-center justify-between mb-4">
        <div class="flex items-center gap-2">
          <button (click)="prevWeek()" class="p-1.5 rounded hover:bg-[var(--surface-hover)]" [style.color]="'var(--text-secondary)'">
            <lucide-icon [img]="ChevronLeftIcon" [size]="18" />
          </button>
          <h3 class="text-sm font-semibold" [style.color]="'var(--text-primary)'">{{ weekLabel() }}</h3>
          <button (click)="nextWeek()" class="p-1.5 rounded hover:bg-[var(--surface-hover)]" [style.color]="'var(--text-secondary)'">
            <lucide-icon [img]="ChevronRightIcon" [size]="18" />
          </button>
        </div>
        <div class="flex gap-1">
          @for (view of ['Week', 'Month']; track view) {
            <button class="px-3 py-1 rounded text-xs font-medium transition-colors"
              [ngClass]="activeView() === view ? 'bg-[var(--color-brand-500)] text-white' : 'bg-[var(--surface-muted)]'"
              [style.color]="activeView() !== view ? 'var(--text-secondary)' : ''"
              (click)="activeView.set(view)">{{ view }}</button>
          }
        </div>
      </div>

      <!-- Week grid -->
      <div class="grid grid-cols-7 gap-1">
        @for (day of weekDays; track day) {
          <div class="text-center text-xs font-medium py-1" [style.color]="'var(--text-tertiary)'">{{ day }}</div>
        }
        @for (date of weekDates(); track date.label) {
          <div class="min-h-[100px] rounded-lg border p-2" [style.borderColor]="'var(--border-default)'" [style.background]="date.isToday ? 'var(--surface-muted)' : 'transparent'">
            <span class="text-xs font-medium" [style.color]="date.isToday ? 'var(--color-brand-500)' : 'var(--text-tertiary)'">{{ date.label }}</span>
            @for (shift of date.shifts; track shift.id) {
              <div class="mt-1 px-2 py-1 rounded text-[10px] font-medium truncate"
                [ngClass]="shift.status === 'confirmed' ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-400'
                  : shift.status === 'published' ? 'bg-blue-50 text-blue-700 dark:bg-blue-950 dark:text-blue-400'
                  : shift.is_open ? 'bg-amber-50 text-amber-700 dark:bg-amber-950 dark:text-amber-400'
                  : 'bg-[var(--surface-muted)]'"
                [style.color]="shift.status === 'draft' ? 'var(--text-secondary)' : ''">
                {{ shift.start_label }} {{ shift.guard_name || 'Open' }}
              </div>
            }
          </div>
        }
      </div>
    </div>

    <!-- Shift status breakdown -->
    <div class="card p-5">
      <h3 class="text-sm font-semibold mb-3" [style.color]="'var(--text-primary)'">Shift Status Breakdown</h3>
      <g51-stacked-bar-chart [series]="chartSeries" [labels]="weekDays" [height]="200" />
    </div>
  `,
})
export class SchedulingComponent implements OnInit {
  private api = inject(ApiService);
  readonly PlusIcon = Plus; readonly CalendarIcon = Calendar; readonly ClockIcon = Clock;
  readonly ChevronLeftIcon = ChevronLeft; readonly ChevronRightIcon = ChevronRight;
  readonly UsersIcon = Users; readonly AlertTriangleIcon = AlertTriangle;

  readonly activeView = signal('Week');
  readonly weekOffset = signal(0);
  readonly stats = signal({ total: 0, confirmed: 0, open: 0, swaps: 0 });
  readonly shifts = signal<any[]>([]);
  readonly allSites = signal<any[]>([]);
  readonly allGuards = signal<any[]>([]);
  filterSiteId = '';
  filterGuardId = '';

  weekDays = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
  chartSeries: StackedBarSeries[] = [
    { name: 'Confirmed', data: [8, 10, 9, 8, 10, 4, 3], color: 'var(--color-success)' },
    { name: 'Open', data: [2, 0, 1, 2, 0, 2, 1], color: 'var(--color-warning)' },
    { name: 'Missed', data: [0, 1, 0, 0, 1, 0, 0], color: 'var(--color-danger)' },
  ];

  weekLabel = () => {
    const d = new Date();
    d.setDate(d.getDate() + this.weekOffset() * 7);
    const mon = new Date(d); mon.setDate(d.getDate() - d.getDay() + 1);
    const sun = new Date(mon); sun.setDate(mon.getDate() + 6);
    return `${mon.toLocaleDateString('en-NG', { month: 'short', day: 'numeric' })} — ${sun.toLocaleDateString('en-NG', { month: 'short', day: 'numeric', year: 'numeric' })}`;
  };

  weekDates = () => {
    const d = new Date();
    d.setDate(d.getDate() + this.weekOffset() * 7);
    const mon = new Date(d); mon.setDate(d.getDate() - d.getDay() + 1);
    const today = new Date().toDateString();
    return Array.from({ length: 7 }, (_, i) => {
      const date = new Date(mon); date.setDate(mon.getDate() + i);
      return { label: date.getDate().toString(), isToday: date.toDateString() === today, shifts: [] as any[] };
    });
  };

  prevWeek(): void { this.weekOffset.update(v => v - 1); }
  nextWeek(): void { this.weekOffset.update(v => v + 1); }

  ngOnInit(): void {
    this.api.get<any>('/sites').subscribe({ next: res => { if (res.data) this.allSites.set(res.data.sites || []); } });
    this.api.get<any>('/guards').subscribe({ next: res => { if (res.data) this.allGuards.set(res.data.guards || []); } });
    this.loadShifts();
  }

  loadShifts(): void {
    let url = '/shifts?';
    if (this.filterSiteId) url += `site_id=${this.filterSiteId}&`;
    if (this.filterGuardId) url += `guard_id=${this.filterGuardId}&`;
    this.api.get<any>(url).subscribe({
      next: res => { if (res.data) { this.shifts.set(res.data.shifts || []); this.stats.set({ total: res.data.total || 0, confirmed: 0, open: 0, swaps: 0 }); } }
    });
  }
}
