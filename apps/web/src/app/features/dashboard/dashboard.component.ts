import { Component, inject, signal, OnInit } from '@angular/core';
import { TranslateModule } from '@ngx-translate/core';
import { LucideAngularModule, Shield, MapPin, AlertTriangle, Clock, Users, Building2, TrendingUp, Plus } from 'lucide-angular';
import { AuthStore } from '@core/services/auth.store';
import { PageHeaderComponent } from '@shared/components/page-header/page-header.component';
import { StatsCardComponent } from '@shared/components/stats-card/stats-card.component';
import { BarChartComponent, BarChartData } from '@shared/components/charts/bar-chart.component';
import { LineChartComponent, LineChartSeries } from '@shared/components/charts/line-chart.component';
import { DonutChartComponent, DonutChartData } from '@shared/components/charts/donut-chart.component';
import { SparklineComponent } from '@shared/components/charts/sparkline.component';

@Component({
  selector: 'g51-dashboard',
  standalone: true,
  imports: [
    TranslateModule, LucideAngularModule, PageHeaderComponent, StatsCardComponent,
    BarChartComponent, LineChartComponent, DonutChartComponent, SparklineComponent,
  ],
  template: `
    <g51-page-header [title]="greeting()" [subtitle]="'dashboard.overview' | translate">
      <button class="btn-primary flex items-center gap-2">
        <lucide-icon [img]="PlusIcon" [size]="16" /> Add Site
      </button>
    </g51-page-header>

    <!-- Stats Row -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6 stagger-children">
      <g51-stats-card label="{{ 'dashboard.guards_on_duty' | translate }}" value="24" [icon]="ShieldIcon" [trend]="4.2" [trendLabel]="'dashboard.from_last_month' | translate" />
      <g51-stats-card label="{{ 'dashboard.total_sites' | translate }}" value="12" [icon]="MapPinIcon" [trend]="1.2" [trendLabel]="'dashboard.from_last_month' | translate" />
      <g51-stats-card label="{{ 'dashboard.incidents_today' | translate }}" value="2" [icon]="AlertTriangleIcon" [trend]="-15" [trendLabel]="'dashboard.from_last_month' | translate" />
      <g51-stats-card label="{{ 'dashboard.attendance_rate' | translate }}" value="96.4%" [icon]="ClockIcon" [trend]="2.1" [trendLabel]="'dashboard.from_last_month' | translate" />
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
      <!-- Attendance Trend (2/3) -->
      <div class="lg:col-span-2 card p-5">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-base font-semibold" [style.color]="'var(--text-primary)'">Attendance Trend</h3>
          <div class="flex items-center gap-1 text-xs">
            <button class="px-3 py-1 rounded-md font-medium" [style.background]="'var(--surface-muted)'" [style.color]="'var(--text-primary)'">Last 7 days</button>
          </div>
        </div>
        <g51-line-chart [seriesData]="attendanceSeries" [labels]="attendanceLabels" [height]="240" />
      </div>

      <!-- Incidents by Category (1/3) -->
      <div class="card p-5">
        <h3 class="text-base font-semibold mb-4" [style.color]="'var(--text-primary)'">Incidents by Category</h3>
        <g51-donut-chart [data]="incidentData" [size]="130" [strokeWidth]="20" centerValue="14" centerLabel="Total" />
      </div>
    </div>

    <!-- Bottom Row -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
      <!-- Guard Hours -->
      <div class="card p-5">
        <h3 class="text-base font-semibold mb-4" [style.color]="'var(--text-primary)'">Guard Hours This Week</h3>
        <g51-bar-chart [data]="guardHoursData" [height]="220" />
      </div>

      <!-- Recent Activity -->
      <div class="card p-5">
        <h3 class="text-base font-semibold mb-4" [style.color]="'var(--text-primary)'">Recent Activity</h3>
        <div class="space-y-3">
          @for (item of recentActivity; track item.id) {
            <div class="flex items-start gap-3 py-2 border-b last:border-b-0" [style.borderColor]="'var(--border-default)'">
              <div class="h-8 w-8 rounded-full flex items-center justify-center shrink-0 text-xs font-medium"
                [style.background]="'var(--surface-muted)'" [style.color]="'var(--text-secondary)'">
                {{ item.initials }}
              </div>
              <div class="min-w-0 flex-1">
                <p class="text-sm" [style.color]="'var(--text-primary)'">{{ item.text }}</p>
                <p class="text-xs mt-0.5" [style.color]="'var(--text-tertiary)'">{{ item.time }}</p>
              </div>
            </div>
          }
        </div>
      </div>
    </div>
  `,
})
export class DashboardComponent implements OnInit {
  private auth = inject(AuthStore);
  readonly ShieldIcon = Shield; readonly MapPinIcon = MapPin; readonly AlertTriangleIcon = AlertTriangle;
  readonly ClockIcon = Clock; readonly PlusIcon = Plus;

  greeting = signal('');

  ngOnInit(): void {
    const name = this.auth.user()?.first_name || 'there';
    const hour = new Date().getHours();
    const prefix = hour < 12 ? 'Good morning' : hour < 18 ? 'Good afternoon' : 'Good evening';
    this.greeting.set(`${prefix}, ${name}`);
  }

  // Demo data
  attendanceLabels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
  attendanceSeries: LineChartSeries[] = [
    { name: 'Present', data: [22, 24, 21, 23, 24, 18, 16], color: 'var(--color-brand-500)' },
    { name: 'Expected', data: [24, 24, 24, 24, 24, 20, 18], color: 'var(--color-accent-500)' },
  ];

  incidentData: DonutChartData[] = [
    { label: 'Unauthorized Access', value: 5, color: 'var(--color-danger)' },
    { label: 'Equipment Issue', value: 4, color: 'var(--color-warning)' },
    { label: 'Suspicious Activity', value: 3, color: 'var(--color-brand-500)' },
    { label: 'Other', value: 2, color: 'var(--color-info)' },
  ];

  guardHoursData: BarChartData[] = [
    { label: 'Mon', value: 168 }, { label: 'Tue', value: 192 },
    { label: 'Wed', value: 156 }, { label: 'Thu', value: 180 },
    { label: 'Fri', value: 192 }, { label: 'Sat', value: 128 }, { label: 'Sun', value: 104 },
  ];

  recentActivity = [
    { id: 1, initials: 'MI', text: 'Musa Ibrahim clocked in at Lekki Phase 1', time: '5 minutes ago' },
    { id: 2, initials: 'CN', text: 'Chika Nwosu submitted daily activity report', time: '18 minutes ago' },
    { id: 3, initials: 'FA', text: 'Funmi Adeyemi dispatched patrol to Victoria Island', time: '34 minutes ago' },
    { id: 4, initials: 'AO', text: 'Adebayo Okonkwo approved shift swap request', time: '1 hour ago' },
    { id: 5, initials: 'KE', text: 'Kelechi Eze reported incident at Ikeja site', time: '2 hours ago' },
  ];
}
