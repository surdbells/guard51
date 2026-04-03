import { Component, inject, signal, OnInit } from '@angular/core';
import { NgClass, DecimalPipe } from '@angular/common';
import { LucideAngularModule, BarChart3, TrendingUp, Users, Building2, Shield, MapPin, DollarSign, AlertTriangle, Clock } from 'lucide-angular';
import { PageHeaderComponent } from '@shared/components/page-header/page-header.component';
import { StatsCardComponent } from '@shared/components/stats-card/stats-card.component';
import { LoadingSpinnerComponent } from '@shared/components/loading-spinner/loading-spinner.component';
import { ApiService } from '@core/services/api.service';

@Component({
  selector: 'g51-sa-analytics',
  standalone: true,
  imports: [NgClass, DecimalPipe, LucideAngularModule, PageHeaderComponent, StatsCardComponent, LoadingSpinnerComponent],
  template: `
    <g51-page-header title="Platform Analytics" subtitle="Revenue, growth, and usage metrics across all tenants" />

    @if (loading()) { <g51-loading /> }
    @else {
      <!-- Key Metrics -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6 stagger-children">
        <g51-stats-card label="Total Companies" [value]="stats().total_tenants" [icon]="BuildingIcon" />
        <g51-stats-card label="Monthly Revenue" [value]="'₦' + (stats().mrr | number:'1.0-0')" [icon]="DollarIcon" />
        <g51-stats-card label="Total Guards" [value]="stats().total_guards" [icon]="ShieldIcon" />
        <g51-stats-card label="Total Sites" [value]="stats().total_sites" [icon]="MapPinIcon" />
      </div>

      <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <g51-stats-card label="Total Users" [value]="stats().total_users" [icon]="UsersIcon" />
        <g51-stats-card label="Total Clients" [value]="stats().total_clients" [icon]="BuildingIcon" />
        <g51-stats-card label="Signups (30d)" [value]="stats().recent_signups_30d" [icon]="TrendingUpIcon" />
        <g51-stats-card label="Open Tickets" [value]="stats().open_tickets" [icon]="AlertTriangleIcon" />
      </div>

      <!-- Revenue & Growth -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
        <div class="card p-5">
          <h3 class="text-sm font-semibold mb-4 font-heading" [style.color]="'var(--text-primary)'">Company Distribution</h3>
          <div class="space-y-3">
            @for (row of statusBreakdown(); track row.label) {
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                  <span class="h-3 w-3 rounded-full" [style.background]="row.color"></span>
                  <span class="text-xs" [style.color]="'var(--text-secondary)'">{{ row.label }}</span>
                </div>
                <div class="flex items-center gap-3">
                  <span class="text-sm font-bold tabular-nums" [style.color]="'var(--text-primary)'">{{ row.count }}</span>
                  <div class="w-32 h-2.5 rounded-full" [style.background]="'var(--surface-muted)'">
                    <div class="h-2.5 rounded-full transition-all" [style.width.%]="stats().total_tenants ? (row.count / stats().total_tenants * 100) : 0" [style.background]="row.color"></div>
                  </div>
                  <span class="text-[10px] tabular-nums w-10 text-right" [style.color]="'var(--text-tertiary)'">{{ stats().total_tenants ? (row.count / stats().total_tenants * 100 | number:'1.0-0') : 0 }}%</span>
                </div>
              </div>
            }
          </div>
        </div>

        <div class="card p-5">
          <h3 class="text-sm font-semibold mb-4 font-heading" [style.color]="'var(--text-primary)'">Platform Health</h3>
          <div class="grid grid-cols-2 gap-3">
            <div class="rounded-lg p-3" [style.background]="'var(--surface-muted)'">
              <p class="text-[10px] uppercase tracking-wide mb-1" [style.color]="'var(--text-tertiary)'">Subscription Rate</p>
              <p class="text-xl font-bold tabular-nums" [style.color]="'var(--text-primary)'">{{ stats().total_tenants ? ((stats().active_subscriptions / stats().total_tenants * 100) | number:'1.0-0') : 0 }}%</p>
            </div>
            <div class="rounded-lg p-3" [style.background]="'var(--surface-muted)'">
              <p class="text-[10px] uppercase tracking-wide mb-1" [style.color]="'var(--text-tertiary)'">Avg Guards/Company</p>
              <p class="text-xl font-bold tabular-nums" [style.color]="'var(--text-primary)'">{{ stats().active ? ((stats().total_guards / stats().active) | number:'1.0-0') : 0 }}</p>
            </div>
            <div class="rounded-lg p-3" [style.background]="'var(--surface-muted)'">
              <p class="text-[10px] uppercase tracking-wide mb-1" [style.color]="'var(--text-tertiary)'">Incidents (Month)</p>
              <p class="text-xl font-bold tabular-nums" [style.color]="'var(--text-primary)'">{{ stats().monthly_incidents || 0 }}</p>
            </div>
            <div class="rounded-lg p-3" [style.background]="'var(--surface-muted)'">
              <p class="text-[10px] uppercase tracking-wide mb-1" [style.color]="'var(--text-tertiary)'">ARR (Projected)</p>
              <p class="text-xl font-bold tabular-nums" [style.color]="'var(--text-primary)'">₦{{ ((stats().mrr || 0) * 12) | number:'1.0-0' }}</p>
            </div>
          </div>
        </div>
      </div>
    }
  `,
})
export class AnalyticsComponent implements OnInit {
  private api = inject(ApiService);
  readonly BuildingIcon = Building2; readonly ShieldIcon = Shield; readonly UsersIcon = Users;
  readonly MapPinIcon = MapPin; readonly DollarIcon = DollarSign; readonly TrendingUpIcon = TrendingUp;
  readonly AlertTriangleIcon = AlertTriangle;
  readonly loading = signal(true);
  readonly stats = signal<any>({});
  readonly statusBreakdown = signal<{ label: string; count: number; color: string }[]>([]);

  ngOnInit(): void {
    this.api.get<any>('/admin/stats').subscribe({
      next: r => {
        const d = r.data || {};
        this.stats.set(d);
        this.statusBreakdown.set([
          { label: 'Active', count: d.active || 0, color: '#10B981' },
          { label: 'Trial', count: d.trial || 0, color: '#3B82F6' },
          { label: 'Suspended', count: d.suspended || 0, color: '#F59E0B' },
          { label: 'Cancelled', count: d.cancelled || 0, color: '#EF4444' },
        ]);
        this.loading.set(false);
      },
      error: () => this.loading.set(false),
    });
  }
}
