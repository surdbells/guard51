import { Component, inject, signal, OnInit } from '@angular/core';
import { NgClass } from '@angular/common';
import { LucideAngularModule, BarChart3, Clock, Shield, FileText, TrendingUp, Users } from 'lucide-angular';
import { PageHeaderComponent } from '@shared/components/page-header/page-header.component';
import { StatsCardComponent } from '@shared/components/stats-card/stats-card.component';
import { ApiService } from '@core/services/api.service';

@Component({
  selector: 'g51-analytics',
  standalone: true,
  imports: [NgClass, LucideAngularModule, PageHeaderComponent, StatsCardComponent],
  template: `
    <g51-page-header title="Analytics & Performance" subtitle="KPIs, guard performance index, and operational insights" />
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6 stagger-children">
      <g51-stats-card label="Avg Response Time" [value]="kpis().avg_response_time_min + ' min'" [icon]="ClockIcon" />
      <g51-stats-card label="Tour Compliance" [value]="kpis().tour_compliance_rate + '%'" [icon]="ShieldIcon" />
      <g51-stats-card label="Guard Punctuality" [value]="kpis().guard_punctuality_rate + '%'" [icon]="UsersIcon" />
      <g51-stats-card label="Incident Resolution" [value]="kpis().incident_resolution_hours + 'h'" [icon]="TrendingUpIcon" />
    </div>
    <div class="flex gap-1 mb-6">
      @for (tab of ['Overview', 'Guard Performance', 'Site Analytics']; track tab) {
        <button (click)="activeTab.set(tab)" class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors"
          [ngClass]="activeTab() === tab ? 'bg-[var(--color-brand-500)] text-white' : 'bg-[var(--surface-muted)]'"
          [style.color]="activeTab() !== tab ? 'var(--text-secondary)' : ''">{{ tab }}</button>
      }
    </div>
    @if (activeTab() === 'Overview') {
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <div class="card p-5"><h3 class="text-sm font-semibold mb-3" [style.color]="'var(--text-primary)'">Operational Trends (30d)</h3>
          <div class="h-48 flex items-center justify-center" [style.color]="'var(--text-tertiary)'"><lucide-icon [img]="BarChart3Icon" [size]="32" /><span class="ml-2 text-sm">Chart renders with live data</span></div></div>
        <div class="card p-5"><h3 class="text-sm font-semibold mb-3" [style.color]="'var(--text-primary)'">Compliance Summary</h3>
          @for (m of complianceMetrics; track m.label) {
            <div class="flex items-center justify-between py-2 border-b last:border-b-0" [style.borderColor]="'var(--border-default)'">
              <span class="text-xs" [style.color]="'var(--text-secondary)'">{{ m.label }}</span>
              <div class="flex items-center gap-2"><div class="w-24 h-1.5 rounded-full bg-[var(--surface-muted)] overflow-hidden"><div class="h-full rounded-full" [style.width]="m.value + '%'" [style.background]="m.value >= 90 ? '#10b981' : m.value >= 70 ? '#f59e0b' : '#ef4444'"></div></div>
                <span class="text-xs font-mono tabular-nums w-10 text-right" [style.color]="'var(--text-primary)'">{{ m.value }}%</span></div>
            </div>
          }
        </div>
      </div>
    }
    @if (activeTab() === 'Guard Performance') {
      <div class="card p-5"><h3 class="text-sm font-semibold mb-3" [style.color]="'var(--text-primary)'">Guard Performance Index — Current Month</h3>
        <div class="space-y-2">
          @for (g of guardPerformance; track g.name) {
            <div class="card p-3 card-hover"><div class="flex items-center justify-between">
              <div class="flex-1"><div class="flex items-center gap-2 mb-1"><span class="text-sm font-semibold" [style.color]="'var(--text-primary)'">{{ g.name }}</span>
                <span class="text-[10px] font-bold px-1.5 py-0.5 rounded" [ngClass]="g.grade.startsWith('A') ? 'bg-emerald-50 text-emerald-600 dark:bg-emerald-950 dark:text-emerald-400' : g.grade.startsWith('B') ? 'bg-blue-50 text-blue-600' : 'bg-amber-50 text-amber-600'">{{ g.grade }}</span></div>
                <div class="grid grid-cols-4 gap-2">
                  @for (s of [['Punctuality', g.punctuality], ['Tours', g.tours], ['Reports', g.reports], ['Response', g.response]]; track s[0]) {
                    <div><p class="text-[9px]" [style.color]="'var(--text-tertiary)'">{{ s[0] }}</p><p class="text-xs font-mono" [style.color]="'var(--text-primary)'">{{ s[1] }}%</p></div>
                  }
                </div></div>
              <div class="text-right ml-4"><span class="text-2xl font-bold" [style.color]="'var(--text-primary)'">{{ g.overall }}</span><p class="text-[9px]" [style.color]="'var(--text-tertiary)'">Overall</p></div>
            </div></div>
          }
        </div>
      </div>
    }
    @if (activeTab() === 'Site Analytics') {
      <div class="card p-5"><h3 class="text-sm font-semibold mb-3" [style.color]="'var(--text-primary)'">Site Performance</h3>
        <p class="text-xs" [style.color]="'var(--text-tertiary)'">Site-level analytics with incident frequency, tour completion, and coverage metrics.</p></div>
    }
  `,
})
export class AnalyticsComponent implements OnInit {
  private api = inject(ApiService);
  readonly BarChart3Icon = BarChart3; readonly ClockIcon = Clock; readonly ShieldIcon = Shield;
  readonly FileTextIcon = FileText; readonly TrendingUpIcon = TrendingUp; readonly UsersIcon = Users;
  readonly activeTab = signal('Overview');
  readonly kpis = signal({ avg_response_time_min: 8.5, tour_compliance_rate: 92.3, incident_resolution_hours: 4.2, guard_punctuality_rate: 88.7 });
  complianceMetrics = [
    { label: 'Tour checkpoint completion', value: 92 }, { label: 'Report submission rate', value: 96 },
    { label: 'Geofence compliance', value: 89 }, { label: 'Clock-in punctuality', value: 88 },
    { label: 'License validity', value: 100 },
  ];
  guardPerformance = [
    { name: 'Musa Ibrahim', grade: 'A+', overall: 96, punctuality: 98, tours: 96, reports: 100, response: 90 },
    { name: 'Chika Nwosu', grade: 'A', overall: 90, punctuality: 92, tours: 90, reports: 95, response: 82 },
    { name: 'Adebayo O.', grade: 'B+', overall: 82, punctuality: 85, tours: 80, reports: 88, response: 75 },
    { name: 'Emeka J.', grade: 'C', overall: 63, punctuality: 70, tours: 55, reports: 72, response: 58 },
  ];
  ngOnInit(): void { this.api.get<any>('/analytics/kpis').subscribe({ next: r => { if (r.data) this.kpis.set(r.data); } }); }
}
