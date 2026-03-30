import { Component, inject, signal, OnInit } from '@angular/core';
import { LucideAngularModule, Building2, Users, CreditCard, Activity } from 'lucide-angular';
import { PageHeaderComponent } from '@shared/components/page-header/page-header.component';
import { StatsCardComponent } from '@shared/components/stats-card/stats-card.component';
import { LoadingSpinnerComponent } from '@shared/components/loading-spinner/loading-spinner.component';
import { ApiService } from '@core/services/api.service';

@Component({
  selector: 'g51-sa-dashboard',
  standalone: true,
  imports: [LucideAngularModule, PageHeaderComponent, StatsCardComponent, LoadingSpinnerComponent],
  template: `
    <g51-page-header title="Platform Dashboard" subtitle="Guard51 SaaS Administration" />
    @if (loading()) { <g51-loading /> } @else {
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6 stagger-children">
        <g51-stats-card label="Total Tenants" [value]="stats().total_tenants" [icon]="BuildingIcon" />
        <g51-stats-card label="Active Tenants" [value]="stats().active_tenants" [icon]="ActivityIcon" />
        <g51-stats-card label="Total Users" [value]="stats().total_users" [icon]="UsersIcon" />
        <g51-stats-card label="Total Guards" [value]="stats().total_guards" [icon]="UsersIcon" />
      </div>
    }
  `,
})
export class SaDashboardComponent implements OnInit {
  private api = inject(ApiService);
  readonly BuildingIcon = Building2; readonly UsersIcon = Users; readonly CreditCardIcon = CreditCard; readonly ActivityIcon = Activity;
  readonly loading = signal(true);
  readonly stats = signal<any>({ total_tenants: 0, active_tenants: 0, total_users: 0, total_guards: 0 });
  ngOnInit(): void {
    this.api.get<any>('/admin/tenants/stats').subscribe({
      next: res => { if (res.data) this.stats.set(res.data); this.loading.set(false); },
      error: () => this.loading.set(false),
    });
  }
}
