import { Component, inject, signal, OnInit } from '@angular/core';
import { LucideAngularModule, BarChart3 } from 'lucide-angular';
import { PageHeaderComponent } from '@shared/components/page-header/page-header.component';
import { StatsCardComponent } from '@shared/components/stats-card/stats-card.component';
import { LoadingSpinnerComponent } from '@shared/components/loading-spinner/loading-spinner.component';
import { ApiService } from '@core/services/api.service';

@Component({
  selector: 'g51-sa-analytics',
  standalone: true,
  imports: [LucideAngularModule, PageHeaderComponent, StatsCardComponent, LoadingSpinnerComponent],
  template: `
    <g51-page-header title="Platform Analytics" subtitle="Usage metrics across all tenants" />
    @if (loading()) { <g51-loading /> } @else {
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 stagger-children">
        <g51-stats-card label="Total Tenants" [value]="stats().total_tenants" [icon]="ChartIcon" />
        <g51-stats-card label="Total Users" [value]="stats().total_users" [icon]="ChartIcon" />
        <g51-stats-card label="Total Guards" [value]="stats().total_guards" [icon]="ChartIcon" />
        <g51-stats-card label="Active Subscriptions" [value]="stats().active_subscriptions" [icon]="ChartIcon" />
      </div>
    }
  `,
})
export class AnalyticsComponent implements OnInit {
  private api = inject(ApiService);
  readonly ChartIcon = BarChart3;
  readonly stats = signal<any>({}); readonly loading = signal(true);
  ngOnInit(): void { this.api.get<any>('/admin/tenants/stats').subscribe({ next: res => { if (res.data) this.stats.set(res.data); this.loading.set(false); }, error: () => this.loading.set(false) }); }
}
