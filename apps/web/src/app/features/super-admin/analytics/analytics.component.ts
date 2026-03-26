import { Component } from '@angular/core';
import { LucideAngularModule, Users, MapPin, FileText, BarChart3 } from 'lucide-angular';
import { PageHeaderComponent } from '@shared/components/page-header/page-header.component';
import { StatsCardComponent } from '@shared/components/stats-card/stats-card.component';
import { AreaChartComponent, AreaChartSeries } from '@shared/components/charts/area-chart.component';
import { StackedBarChartComponent, StackedBarSeries } from '@shared/components/charts/stacked-bar-chart.component';
import { HeatmapChartComponent, HeatmapData } from '@shared/components/charts/heatmap-chart.component';

@Component({
  selector: 'g51-sa-analytics',
  standalone: true,
  imports: [LucideAngularModule, PageHeaderComponent, StatsCardComponent, AreaChartComponent, StackedBarChartComponent, HeatmapChartComponent],
  template: `
    <g51-page-header title="Usage Analytics" subtitle="Platform-wide usage metrics and per-tenant breakdown" />

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6 stagger-children">
      <g51-stats-card label="Total Guards" value="1,284" [icon]="UsersIcon" [trend]="8.3" trendLabel="from last month" />
      <g51-stats-card label="Total Sites" value="186" [icon]="MapPinIcon" [trend]="5.1" trendLabel="from last month" />
      <g51-stats-card label="Reports/Month" value="3,420" [icon]="FileTextIcon" [trend]="12" trendLabel="from last month" />
      <g51-stats-card label="API Calls/Day" value="48.2K" [icon]="BarChart3Icon" [trend]="18" trendLabel="from last month" />
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
      <div class="card p-5">
        <h3 class="text-base font-semibold mb-4" [style.color]="'var(--text-primary)'">Guard Growth</h3>
        <g51-area-chart [series]="guardGrowth" [labels]="months" [height]="240" />
      </div>
      <div class="card p-5">
        <h3 class="text-base font-semibold mb-4" [style.color]="'var(--text-primary)'">Reports by Type</h3>
        <g51-stacked-bar-chart [series]="reportTypes" [labels]="months" [height]="240" />
      </div>
    </div>

    <div class="card p-5">
      <h3 class="text-base font-semibold mb-4" [style.color]="'var(--text-primary)'">Activity Heatmap (Guards Active by Hour)</h3>
      <g51-heatmap-chart [data]="heatmapData" [rows]="days" [cols]="hours" [cellSize]="32" />
    </div>
  `,
})
export class AnalyticsComponent {
  readonly UsersIcon = Users; readonly MapPinIcon = MapPin; readonly FileTextIcon = FileText; readonly BarChart3Icon = BarChart3;

  months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul'];
  guardGrowth: AreaChartSeries[] = [
    { name: 'Guards', data: [680, 780, 890, 980, 1050, 1180, 1284], color: 'var(--color-brand-500)' },
    { name: 'Sites', data: [90, 105, 120, 135, 150, 168, 186], color: 'var(--color-accent-500)' },
  ];
  reportTypes: StackedBarSeries[] = [
    { name: 'DAR', data: [180, 210, 240, 260, 280, 310, 340], color: 'var(--color-brand-500)' },
    { name: 'Incidents', data: [30, 25, 35, 28, 32, 38, 42], color: 'var(--color-danger)' },
    { name: 'Patrol', data: [50, 65, 70, 80, 85, 90, 95], color: 'var(--color-success)' },
  ];

  days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
  hours = ['6am', '8am', '10am', '12pm', '2pm', '4pm', '6pm', '8pm', '10pm'];
  heatmapData: HeatmapData[] = this.generateHeatmap();

  private generateHeatmap(): HeatmapData[] {
    const data: HeatmapData[] = [];
    this.days.forEach(d => this.hours.forEach(h => {
      const isWeekday = !['Sat', 'Sun'].includes(d);
      const isPeak = ['8am', '10am', '2pm', '4pm', '6pm'].includes(h);
      data.push({ row: d, col: h, value: isWeekday && isPeak ? 60 + Math.floor(Math.random() * 40) : 10 + Math.floor(Math.random() * 30) });
    }));
    return data;
  }
}
