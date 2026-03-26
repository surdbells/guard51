import { Component } from '@angular/core';
import { LucideAngularModule, Building2, Users, CreditCard, TrendingUp, AlertCircle, Download } from 'lucide-angular';
import { PageHeaderComponent } from '@shared/components/page-header/page-header.component';
import { StatsCardComponent } from '@shared/components/stats-card/stats-card.component';
import { LineChartComponent, LineChartSeries } from '@shared/components/charts/line-chart.component';
import { DonutChartComponent, DonutChartData } from '@shared/components/charts/donut-chart.component';
import { BarChartComponent, BarChartData } from '@shared/components/charts/bar-chart.component';

@Component({
  selector: 'g51-sa-dashboard',
  standalone: true,
  imports: [LucideAngularModule, PageHeaderComponent, StatsCardComponent, LineChartComponent, DonutChartComponent, BarChartComponent],
  template: `
    <g51-page-header title="Platform Dashboard" subtitle="Guard51 platform overview and metrics" />

    <!-- Stats Row -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6 stagger-children">
      <g51-stats-card label="Total Tenants" value="47" [icon]="BuildingIcon" [trend]="12.5" trendLabel="from last month" />
      <g51-stats-card label="Total Guards" value="1,284" [icon]="UsersIcon" [trend]="8.3" trendLabel="from last month" />
      <g51-stats-card label="Monthly Revenue" value="₦3.75M" [icon]="CreditCardIcon" [trend]="15.2" trendLabel="from last month" />
      <g51-stats-card label="App Downloads" value="892" [icon]="DownloadIcon" [trend]="23.1" trendLabel="from last month" />
    </div>

    <!-- Charts -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
      <div class="lg:col-span-2 card p-5">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-base font-semibold" [style.color]="'var(--text-primary)'">Revenue Trend</h3>
          <div class="flex items-center gap-3 text-xs" [style.color]="'var(--text-secondary)'">
            <span class="flex items-center gap-1"><span class="h-2 w-2 rounded-full" style="background: var(--color-brand-500)"></span> Revenue</span>
            <span class="flex items-center gap-1"><span class="h-2 w-2 rounded-full" style="background: var(--color-accent-500)"></span> Expenses</span>
          </div>
        </div>
        <g51-line-chart [seriesData]="revenueSeries" [labels]="months" [height]="260" />
      </div>

      <div class="card p-5">
        <h3 class="text-base font-semibold mb-4" [style.color]="'var(--text-primary)'">Revenue by Plan</h3>
        <g51-donut-chart [data]="revenueByPlan" [size]="130" [strokeWidth]="20" centerValue="₦3.75M" centerLabel="Total MRR" />
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
      <div class="card p-5">
        <h3 class="text-base font-semibold mb-4" [style.color]="'var(--text-primary)'">Signups by Month</h3>
        <g51-bar-chart [data]="signupData" [height]="220" />
      </div>

      <div class="card p-5">
        <h3 class="text-base font-semibold mb-4" [style.color]="'var(--text-primary)'">Pending Actions</h3>
        <div class="space-y-3">
          @for (item of pendingActions; track item.label) {
            <div class="flex items-center justify-between py-2.5 border-b last:border-b-0" [style.borderColor]="'var(--border-default)'">
              <div class="flex items-center gap-3">
                <div class="h-8 w-8 rounded-lg flex items-center justify-center" [style.background]="'var(--surface-muted)'">
                  <lucide-icon [img]="AlertCircleIcon" [size]="16" [style.color]="item.color" />
                </div>
                <span class="text-sm" [style.color]="'var(--text-primary)'">{{ item.label }}</span>
              </div>
              <span class="badge" [style.background]="item.color + '15'" [style.color]="item.color">{{ item.count }}</span>
            </div>
          }
        </div>
      </div>
    </div>
  `,
})
export class SaDashboardComponent {
  readonly BuildingIcon = Building2; readonly UsersIcon = Users; readonly CreditCardIcon = CreditCard;
  readonly TrendingUpIcon = TrendingUp; readonly AlertCircleIcon = AlertCircle; readonly DownloadIcon = Download;

  months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul'];
  revenueSeries: LineChartSeries[] = [
    { name: 'Revenue', data: [1800000, 2100000, 2400000, 2750000, 3100000, 3400000, 3750000], color: 'var(--color-brand-500)' },
    { name: 'Expenses', data: [500000, 520000, 540000, 560000, 580000, 600000, 620000], color: 'var(--color-accent-500)' },
  ];

  revenueByPlan: DonutChartData[] = [
    { label: 'Business', value: 1800000, color: 'var(--color-brand-500)' },
    { label: 'Professional', value: 1200000, color: 'var(--color-accent-500)' },
    { label: 'Starter', value: 500000, color: 'var(--color-success)' },
    { label: 'Enterprise', value: 250000, color: 'var(--color-warning)' },
  ];

  signupData: BarChartData[] = [
    { label: 'Jan', value: 3 }, { label: 'Feb', value: 5 }, { label: 'Mar', value: 7 },
    { label: 'Apr', value: 6 }, { label: 'May', value: 9 }, { label: 'Jun', value: 8 }, { label: 'Jul', value: 9 },
  ];

  pendingActions = [
    { label: 'Pending Bank Transfers', count: 3, color: 'var(--color-warning)' },
    { label: 'Trial Expiring (7 days)', count: 5, color: 'var(--color-danger)' },
    { label: 'Support Tickets', count: 2, color: 'var(--color-info)' },
    { label: 'Failed Payments', count: 1, color: 'var(--color-danger)' },
  ];
}
