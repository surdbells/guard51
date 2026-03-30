import { Component, inject, signal, OnInit } from '@angular/core';
import { RouterLink } from '@angular/router';
import { LucideAngularModule, Shield, MapPin, AlertTriangle, Clock, Users, Plus, RefreshCw } from 'lucide-angular';
import { AuthStore } from '@core/services/auth.store';
import { ApiService } from '@core/services/api.service';
import { PageHeaderComponent } from '@shared/components/page-header/page-header.component';
import { StatsCardComponent } from '@shared/components/stats-card/stats-card.component';
import { BarChartComponent, BarChartData } from '@shared/components/charts/bar-chart.component';
import { LineChartComponent, LineChartSeries } from '@shared/components/charts/line-chart.component';
import { DonutChartComponent, DonutChartData } from '@shared/components/charts/donut-chart.component';
import { LoadingSpinnerComponent } from '@shared/components/loading-spinner/loading-spinner.component';

@Component({
  selector: 'g51-dashboard',
  standalone: true,
  imports: [
    RouterLink, LucideAngularModule, PageHeaderComponent, StatsCardComponent,
    BarChartComponent, LineChartComponent, DonutChartComponent, LoadingSpinnerComponent,
  ],
  template: `
    <g51-page-header [title]="greeting()" subtitle="Company overview and operational metrics">
      <button class="btn-secondary flex items-center gap-2" (click)="refresh()">
        <lucide-icon [img]="RefreshIcon" [size]="14" /> Refresh
      </button>
      <button class="btn-primary flex items-center gap-2" routerLink="/sites/new">
        <lucide-icon [img]="PlusIcon" [size]="16" /> Add Site
      </button>
    </g51-page-header>

    @if (loading()) {
      <g51-loading [fullPage]="false" />
    } @else {
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6 stagger-children">
        <g51-stats-card label="Total Guards" [value]="stats().total_guards" [icon]="ShieldIcon" />
        <g51-stats-card label="Active Guards" [value]="stats().active_guards" [icon]="UsersIcon" />
        <g51-stats-card label="Total Sites" [value]="stats().total_sites" [icon]="MapPinIcon" />
        <g51-stats-card label="Total Clients" [value]="stats().total_clients" [icon]="ClockIcon" />
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
        <div class="lg:col-span-2 card p-5">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-base font-semibold" [style.color]="'var(--text-primary)'">Attendance ({{ stats().attendance_rate }}%)</h3>
          </div>
          @if (snapshotSeries().length) {
            <g51-line-chart [seriesData]="snapshotSeries()" [labels]="snapshotLabels()" [height]="240" />
          } @else {
            <div class="h-60 flex items-center justify-center" [style.color]="'var(--text-tertiary)'">
              <p class="text-sm">No snapshot data yet. Data appears after the first day of operations.</p>
            </div>
          }
        </div>

        <div class="card p-5">
          <h3 class="text-base font-semibold mb-4" [style.color]="'var(--text-primary)'">Quick Stats</h3>
          <div class="space-y-3">
            <div class="flex items-center justify-between py-2 border-b" [style.borderColor]="'var(--border-default)'">
              <span class="text-xs" [style.color]="'var(--text-secondary)'">Guards on duty</span>
              <span class="text-sm font-bold" [style.color]="'var(--text-primary)'">{{ stats().active_guards }}</span>
            </div>
            <div class="flex items-center justify-between py-2 border-b" [style.borderColor]="'var(--border-default)'">
              <span class="text-xs" [style.color]="'var(--text-secondary)'">Attendance rate</span>
              <span class="text-sm font-bold" [style.color]="'var(--text-primary)'">{{ stats().attendance_rate }}%</span>
            </div>
            <div class="flex items-center justify-between py-2 border-b" [style.borderColor]="'var(--border-default)'">
              <span class="text-xs" [style.color]="'var(--text-secondary)'">Active sites</span>
              <span class="text-sm font-bold" [style.color]="'var(--text-primary)'">{{ stats().total_sites }}</span>
            </div>
            <div class="flex items-center justify-between py-2">
              <span class="text-xs" [style.color]="'var(--text-secondary)'">Clients served</span>
              <span class="text-sm font-bold" [style.color]="'var(--text-primary)'">{{ stats().total_clients }}</span>
            </div>
          </div>
        </div>
      </div>

      @if (todaySnapshot()) {
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
          <div class="card p-4"><p class="text-[10px]" [style.color]="'var(--text-tertiary)'">Clock-ins Today</p>
            <p class="text-xl font-bold" [style.color]="'var(--text-primary)'">{{ todaySnapshot().total_clock_ins || 0 }}</p></div>
          <div class="card p-4"><p class="text-[10px]" [style.color]="'var(--text-tertiary)'">Incidents Today</p>
            <p class="text-xl font-bold" [style.color]="'var(--color-danger)'">{{ todaySnapshot().total_incidents || 0 }}</p></div>
          <div class="card p-4"><p class="text-[10px]" [style.color]="'var(--text-tertiary)'">Tours Completed</p>
            <p class="text-xl font-bold" [style.color]="'var(--text-primary)'">{{ todaySnapshot().total_tours || 0 }}</p></div>
          <div class="card p-4"><p class="text-[10px]" [style.color]="'var(--text-tertiary)'">Reports Submitted</p>
            <p class="text-xl font-bold" [style.color]="'var(--text-primary)'">{{ todaySnapshot().total_reports || 0 }}</p></div>
        </div>
      }
    }
  `,
})
export class DashboardComponent implements OnInit {
  private auth = inject(AuthStore);
  private api = inject(ApiService);
  readonly ShieldIcon = Shield; readonly MapPinIcon = MapPin; readonly AlertTriangleIcon = AlertTriangle;
  readonly ClockIcon = Clock; readonly UsersIcon = Users; readonly PlusIcon = Plus; readonly RefreshIcon = RefreshCw;

  readonly greeting = signal('');
  readonly loading = signal(true);
  readonly stats = signal<any>({ total_guards: 0, active_guards: 0, total_sites: 0, total_clients: 0, attendance_rate: 0 });
  readonly todaySnapshot = signal<any>(null);
  readonly snapshotSeries = signal<LineChartSeries[]>([]);
  readonly snapshotLabels = signal<string[]>([]);

  ngOnInit(): void {
    const name = this.auth.user()?.first_name || 'there';
    const hour = new Date().getHours();
    const prefix = hour < 12 ? 'Good morning' : hour < 18 ? 'Good afternoon' : 'Good evening';
    this.greeting.set(`${prefix}, ${name}`);
    this.loadData();
  }

  refresh(): void { this.loadData(); }

  private loadData(): void {
    this.loading.set(true);

    this.api.get<any>('/dashboard/stats').subscribe({
      next: res => { if (res.data) this.stats.set(res.data); this.loading.set(false); },
      error: () => this.loading.set(false),
    });

    this.api.get<any>('/dashboard/today').subscribe({
      next: res => { if (res.data?.snapshot) this.todaySnapshot.set(res.data.snapshot); },
    });

    this.api.get<any>('/dashboard/snapshots?days=14').subscribe({
      next: res => {
        if (res.data?.snapshots?.length) {
          const snaps = res.data.snapshots;
          this.snapshotLabels.set(snaps.map((s: any) => new Date(s.date).toLocaleDateString('en', { month: 'short', day: 'numeric' })));
          this.snapshotSeries.set([
            { name: 'Guards Active', data: snaps.map((s: any) => s.active_guards || 0), color: 'var(--color-brand-500)' },
            { name: 'Clock-ins', data: snaps.map((s: any) => s.total_clock_ins || 0), color: 'var(--color-accent-500)' },
          ]);
        }
      },
    });
  }
}
