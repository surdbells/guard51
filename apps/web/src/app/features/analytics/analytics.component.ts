import { Component, inject, signal, OnInit } from '@angular/core';
import { NgClass, DecimalPipe } from '@angular/common';
import { LucideAngularModule, BarChart3, Users, Clock, AlertTriangle, TrendingUp, Shield, MapPin } from 'lucide-angular';
import { PageHeaderComponent } from '@shared/components/page-header/page-header.component';
import { StatsCardComponent } from '@shared/components/stats-card/stats-card.component';
import { BarChartComponent, BarChartData } from '@shared/components/charts/bar-chart.component';
import { LineChartComponent, LineChartSeries } from '@shared/components/charts/line-chart.component';
import { DonutChartComponent, DonutChartData } from '@shared/components/charts/donut-chart.component';
import { LoadingSpinnerComponent } from '@shared/components/loading-spinner/loading-spinner.component';
import { ApiService } from '@core/services/api.service';
import { exportToCsv } from '@core/utils/csv-export';

@Component({
  selector: 'g51-analytics',
  standalone: true,
  imports: [NgClass, DecimalPipe, LucideAngularModule, PageHeaderComponent, StatsCardComponent, BarChartComponent, LineChartComponent, DonutChartComponent, LoadingSpinnerComponent],
  template: `
    <g51-page-header title="Analytics & Reporting" subtitle="Operational insights and performance metrics">
      <button (click)="exportKpis()" class="btn-secondary text-xs">Export Report</button>
    </g51-page-header>

    @if (loading()) { <g51-loading /> } @else {
      <!-- KPI Cards -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6 stagger-children">
        <g51-stats-card label="Active Guards" [value]="kpis().active_guards" [icon]="ShieldIcon" />
        <g51-stats-card label="Active Sites" [value]="kpis().active_sites" [icon]="MapPinIcon" />
        <g51-stats-card label="Punctuality Rate" [value]="kpis().guard_punctuality_rate + '%'" [icon]="ClockIcon" />
        <g51-stats-card label="Open Incidents" [value]="kpis().open_incidents" [icon]="AlertTriangleIcon" />
      </div>

      <!-- Secondary KPIs -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <g51-stats-card label="Avg Response Time" [value]="kpis().avg_response_time_min + ' min'" [icon]="ClockIcon" />
        <g51-stats-card label="Tour Compliance" [value]="kpis().tour_compliance_rate + '%'" [icon]="MapPinIcon" />
        <g51-stats-card label="Incident Resolution" [value]="kpis().incident_resolution_hours + ' hrs'" [icon]="AlertTriangleIcon" />
        <g51-stats-card label="Overdue Tasks" [value]="kpis().overdue_tasks" [icon]="TrendingUpIcon" />
      </div>

      <!-- Charts Row 1 -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
        <div class="lg:col-span-2 card p-5">
          <h3 class="text-sm font-semibold mb-3" [style.color]="'var(--text-primary)'">Attendance Trend (Last 14 Days)</h3>
          @if (attendanceSeries().length) { <g51-line-chart [seriesData]="attendanceSeries()" [labels]="attendanceLabels()" [height]="220" /> }
          @else { <p class="text-xs py-12 text-center" [style.color]="'var(--text-tertiary)'">No attendance data yet</p> }
        </div>
        <div class="card p-5">
          <h3 class="text-sm font-semibold mb-3" [style.color]="'var(--text-primary)'">Incidents by Type</h3>
          @if (incidentDonut().length) { <g51-donut-chart [data]="incidentDonut()" [size]="120" [strokeWidth]="18" [centerValue]="String(kpis().incidents_this_month)" centerLabel="Total" /> }
          @else { <p class="text-xs py-12 text-center" [style.color]="'var(--text-tertiary)'">No incidents</p> }
        </div>
      </div>

      <!-- Charts Row 2 -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
        <div class="card p-5">
          <h3 class="text-sm font-semibold mb-3" [style.color]="'var(--text-primary)'">Guard Hours This Week</h3>
          @if (hoursData().length) { <g51-bar-chart [data]="hoursData()" [height]="200" /> }
          @else { <p class="text-xs py-12 text-center" [style.color]="'var(--text-tertiary)'">No hours data</p> }
        </div>
        <div class="card p-5">
          <h3 class="text-sm font-semibold mb-3" [style.color]="'var(--text-primary)'">Revenue by Client</h3>
          @if (revenueData().length) { <g51-bar-chart [data]="revenueData()" [height]="200" /> }
          @else { <p class="text-xs py-12 text-center" [style.color]="'var(--text-tertiary)'">No revenue data</p> }
        </div>
      </div>

      <!-- Top Guards Performance -->
      <div class="card p-5">
        <h3 class="text-sm font-semibold mb-3" [style.color]="'var(--text-primary)'">Guard Performance Index (GPI)</h3>
        @if (!topGuards().length) { <p class="text-xs" [style.color]="'var(--text-tertiary)'">No performance data calculated yet.</p> }
        @else {
          <div class="space-y-2">
            @for (g of topGuards(); track g.guard_id; let i = $index) {
              <div class="flex items-center gap-3 py-2 border-b" [style.borderColor]="'var(--border-default)'">
                <span class="text-xs font-bold w-6 text-center" [style.color]="i < 3 ? 'var(--color-brand-500)' : 'var(--text-tertiary)'">#{{ i + 1 }}</span>
                <div class="flex-1">
                  <p class="text-sm font-medium" [style.color]="'var(--text-primary)'">{{ g.guard_name || 'Guard' }}</p>
                  <div class="flex gap-3 mt-0.5">
                    <span class="text-[10px]" [style.color]="'var(--text-tertiary)'">Punctuality: {{ g.punctuality_score }}%</span>
                    <span class="text-[10px]" [style.color]="'var(--text-tertiary)'">Reports: {{ g.report_completion_score }}%</span>
                    <span class="text-[10px]" [style.color]="'var(--text-tertiary)'">Tours: {{ g.tour_compliance_score }}%</span>
                  </div>
                </div>
                <div class="text-right">
                  <p class="text-lg font-bold" [style.color]="'var(--color-brand-500)'">{{ g.overall_score }}%</p>
                  <p class="text-[10px]" [style.color]="'var(--text-tertiary)'">Overall GPI</p>
                </div>
              </div>
            }
          </div>
        }
      </div>
    }
  `,
})
export class AnalyticsComponent implements OnInit {
  private api = inject(ApiService);
  readonly BarChartIcon = BarChart3; readonly ShieldIcon = Shield; readonly MapPinIcon = MapPin;
  readonly ClockIcon = Clock; readonly AlertTriangleIcon = AlertTriangle; readonly TrendingUpIcon = TrendingUp;
  readonly UsersIcon = Users;
  readonly String = String;

  readonly loading = signal(true);
  readonly kpis = signal<any>({ active_guards: 0, active_sites: 0, open_incidents: 0, overdue_tasks: 0, avg_response_time_min: 0, tour_compliance_rate: 0, incident_resolution_hours: 0, guard_punctuality_rate: 0 });
  readonly attendanceSeries = signal<LineChartSeries[]>([]);
  readonly attendanceLabels = signal<string[]>([]);
  readonly incidentDonut = signal<DonutChartData[]>([]);
  readonly hoursData = signal<BarChartData[]>([]);
  readonly revenueData = signal<BarChartData[]>([]);
  readonly topGuards = signal<any[]>([]);

  ngOnInit(): void {
    this.api.get<any>('/analytics/kpis').subscribe({
      next: res => {
        if (res.data) {
          this.kpis.set(res.data);
          // Build charts from KPI data
          if (res.data.attendance_by_day) {
            this.attendanceLabels.set(res.data.attendance_by_day.map((d: any) => d.label || d.date));
            this.attendanceSeries.set([{ name: 'Present', data: res.data.attendance_by_day.map((d: any) => d.present || d.value || 0), color: 'var(--color-brand-500)' }]);
          }
          if (res.data.incidents_by_type) {
            const colors = ['var(--color-danger)', 'var(--color-warning)', 'var(--color-brand-500)', 'var(--color-info)', 'var(--color-success)'];
            this.incidentDonut.set(res.data.incidents_by_type.map((d: any, i: number) => ({ label: d.type || d.label, value: d.count || d.value, color: colors[i % colors.length] })));
          }
          if (res.data.hours_by_day) {
            this.hoursData.set(res.data.hours_by_day.map((d: any) => ({ label: d.label || d.day, value: d.hours || d.value || 0 })));
          }
          if (res.data.revenue_by_client) {
            this.revenueData.set(res.data.revenue_by_client.map((d: any) => ({ label: d.client || d.label, value: d.amount || d.value || 0 })));
          }
          if (res.data.top_guards) { this.topGuards.set(res.data.top_guards); }
        }
        this.loading.set(false);
      },
      error: () => this.loading.set(false),
    });
  }

  exportKpis(): void {
    const k = this.kpis();
    exportToCsv('analytics-report', [k], [
      { key: 'total_guards', label: 'Total Guards' }, { key: 'total_sites', label: 'Total Sites' },
      { key: 'attendance_rate', label: 'Attendance Rate %' }, { key: 'incidents_this_month', label: 'Incidents This Month' },
    ]);
  }
}
