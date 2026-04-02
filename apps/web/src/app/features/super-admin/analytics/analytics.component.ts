import { Component, inject, signal, OnInit } from '@angular/core';
import { NgClass, DecimalPipe } from '@angular/common';
import { LucideAngularModule, BarChart3, TrendingUp, Users, Building2, Shield } from 'lucide-angular';
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
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6 stagger-children">
        <g51-stats-card label="Total Companies" [value]="stats().total_tenants" [icon]="BuildingIcon" />
        <g51-stats-card label="Total Guards" [value]="stats().total_guards" [icon]="ShieldIcon" />
        <g51-stats-card label="Total Users" [value]="stats().total_users" [icon]="UsersIcon" />
        <g51-stats-card label="MRR (₦)" [value]="(stats().mrr | number:'1.0-0') || '0'" [icon]="TrendingIcon" />
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
        <div class="card p-5">
          <h3 class="text-sm font-semibold font-heading mb-3" [style.color]="'var(--text-primary)'">Tenant Breakdown</h3>
          <div class="space-y-3">
            @for (item of tenantBreakdown(); track item.label) {
              <div>
                <div class="flex justify-between text-xs mb-1">
                  <span [style.color]="'var(--text-secondary)'">{{ item.label }}</span>
                  <span class="font-medium" [style.color]="'var(--text-primary)'">{{ item.count }}</span>
                </div>
                <div class="w-full bg-gray-100 rounded-full h-2">
                  <div class="h-2 rounded-full transition-all" [style.width.%]="stats().total_tenants ? (item.count / stats().total_tenants * 100) : 0" [style.background]="item.color"></div>
                </div>
              </div>
            }
          </div>
        </div>

        <div class="card p-5">
          <h3 class="text-sm font-semibold font-heading mb-3" [style.color]="'var(--text-primary)'">Platform Summary</h3>
          <div class="grid grid-cols-2 gap-y-3 text-xs">
            <div><span [style.color]="'var(--text-tertiary)'">Total Sites</span><p class="text-lg font-bold" [style.color]="'var(--text-primary)'">{{ stats().total_sites }}</p></div>
            <div><span [style.color]="'var(--text-tertiary)'">Total Clients</span><p class="text-lg font-bold" [style.color]="'var(--text-primary)'">{{ stats().total_clients }}</p></div>
            <div><span [style.color]="'var(--text-tertiary)'">Active Subscriptions</span><p class="text-lg font-bold" [style.color]="'var(--color-success)'">{{ stats().active_subscriptions }}</p></div>
            <div><span [style.color]="'var(--text-tertiary)'">Active Companies</span><p class="text-lg font-bold" [style.color]="'var(--color-success)'">{{ stats().active }}</p></div>
            <div><span [style.color]="'var(--text-tertiary)'">Trial Companies</span><p class="text-lg font-bold" [style.color]="'var(--color-info)'">{{ stats().trial }}</p></div>
            <div><span [style.color]="'var(--text-tertiary)'">Suspended</span><p class="text-lg font-bold" [style.color]="'var(--color-danger)'">{{ stats().suspended }}</p></div>
          </div>
        </div>
      </div>
    }
  `,
})
export class AnalyticsComponent implements OnInit {
  private api = inject(ApiService);
  readonly BuildingIcon = Building2; readonly ShieldIcon = Shield; readonly UsersIcon = Users; readonly TrendingIcon = TrendingUp;
  readonly loading = signal(true);
  readonly stats = signal<any>({ total_tenants: 0, total_guards: 0, total_users: 0, mrr: 0, total_sites: 0, total_clients: 0, active_subscriptions: 0, active: 0, trial: 0, suspended: 0, cancelled: 0 });
  readonly tenantBreakdown = signal<{ label: string; count: number; color: string }[]>([]);

  ngOnInit(): void {
    this.api.get<any>('/admin/stats').subscribe({
      next: r => {
        if (r.data) {
          this.stats.set(r.data);
          this.tenantBreakdown.set([
            { label: 'Active', count: r.data.active || 0, color: 'var(--color-success)' },
            { label: 'Trial', count: r.data.trial || 0, color: 'var(--color-info)' },
            { label: 'Suspended', count: r.data.suspended || 0, color: 'var(--color-danger)' },
            { label: 'Cancelled', count: r.data.cancelled || 0, color: 'var(--text-tertiary)' },
          ]);
        }
        this.loading.set(false);
      },
      error: () => this.loading.set(false),
    });
  }
}
