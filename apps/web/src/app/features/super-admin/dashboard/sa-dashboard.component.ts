import { Component, inject, signal, OnInit } from '@angular/core';
import { NgClass, DecimalPipe } from '@angular/common';
import { RouterLink } from '@angular/router';
import { LucideAngularModule, Building2, Users, Shield, MapPin, DollarSign, TrendingUp, AlertTriangle, LifeBuoy } from 'lucide-angular';
import { PageHeaderComponent } from '@shared/components/page-header/page-header.component';
import { StatsCardComponent } from '@shared/components/stats-card/stats-card.component';
import { LoadingSpinnerComponent } from '@shared/components/loading-spinner/loading-spinner.component';
import { ApiService } from '@core/services/api.service';

@Component({
  selector: 'g51-sa-dashboard',
  standalone: true,
  imports: [NgClass, DecimalPipe, RouterLink, LucideAngularModule, PageHeaderComponent, StatsCardComponent, LoadingSpinnerComponent],
  template: `
    <g51-page-header title="Platform Dashboard" subtitle="Guard51 SaaS Admin Overview" />

    @if (loading()) { <g51-loading /> }
    @else {
      <!-- Row 1: Key metrics -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6 stagger-children">
        <g51-stats-card label="Total Companies" [value]="stats().total_tenants" [icon]="BuildingIcon" />
        <g51-stats-card label="Active Companies" [value]="stats().active" [icon]="BuildingIcon" />
        <g51-stats-card label="Total Guards" [value]="stats().total_guards" [icon]="ShieldIcon" />
        <g51-stats-card label="MRR (₦)" [value]="(stats().mrr | number:'1.0-0') || '0'" [icon]="DollarIcon" />
      </div>

      <!-- Row 2: Operational metrics -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <g51-stats-card label="Total Users" [value]="stats().total_users" [icon]="UsersIcon" />
        <g51-stats-card label="Sites Managed" [value]="stats().total_sites" [icon]="MapPinIcon" />
        <g51-stats-card label="Signups (30d)" [value]="stats().recent_signups_30d" [icon]="TrendingUpIcon" />
        <g51-stats-card label="Open Tickets" [value]="stats().open_tickets" [icon]="LifeBuoyIcon" />
      </div>

      <!-- Row 3: Status breakdown + Quick actions -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
        <div class="card p-5">
          <h3 class="text-sm font-semibold mb-3 font-heading" [style.color]="'var(--text-primary)'">Company Status Breakdown</h3>
          <div class="space-y-3">
            @for (row of statusRows(); track row.label) {
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                  <span class="h-2.5 w-2.5 rounded-full" [style.background]="row.color"></span>
                  <span class="text-xs" [style.color]="'var(--text-secondary)'">{{ row.label }}</span>
                </div>
                <div class="flex items-center gap-3">
                  <span class="text-sm font-bold" [style.color]="'var(--text-primary)'">{{ row.count }}</span>
                  <div class="w-24 h-2 rounded-full" [style.background]="'var(--surface-muted)'">
                    <div class="h-2 rounded-full transition-all" [style.width.%]="stats().total_tenants ? (row.count / stats().total_tenants * 100) : 0" [style.background]="row.color"></div>
                  </div>
                </div>
              </div>
            }
          </div>
        </div>

        <div class="card p-5">
          <h3 class="text-sm font-semibold mb-3 font-heading" [style.color]="'var(--text-primary)'">Quick Actions</h3>
          <div class="grid grid-cols-2 gap-2">
            <a routerLink="/admin/tenants" class="p-3 rounded-lg text-xs font-medium text-center transition-colors" [style.background]="'var(--surface-muted)'" [style.color]="'var(--text-primary)'">Manage Companies</a>
            <a routerLink="/admin/plans" class="p-3 rounded-lg text-xs font-medium text-center transition-colors" [style.background]="'var(--surface-muted)'" [style.color]="'var(--text-primary)'">Manage Plans</a>
            <a routerLink="/admin/support" class="p-3 rounded-lg text-xs font-medium text-center transition-colors" [style.background]="'var(--surface-muted)'" [style.color]="'var(--text-primary)'">Support Tickets</a>
            <a routerLink="/admin/features" class="p-3 rounded-lg text-xs font-medium text-center transition-colors" [style.background]="'var(--surface-muted)'" [style.color]="'var(--text-primary)'">Feature Flags</a>
          </div>
        </div>
      </div>

      <!-- Recent Tenants -->
      <div class="card p-5">
        <div class="flex justify-between items-center mb-3">
          <h3 class="text-sm font-semibold font-heading" [style.color]="'var(--text-primary)'">Recent Companies</h3>
          <a routerLink="/admin/tenants" class="text-xs font-medium" [style.color]="'var(--brand-500)'">View all →</a>
        </div>
        @for (t of recentTenants(); track t.id) {
          <div class="flex items-center justify-between py-2 border-b last:border-b-0" [style.borderColor]="'var(--border-default)'">
            <div>
              <p class="text-sm font-medium" [style.color]="'var(--text-primary)'">{{ t.company_name || t.name }}</p>
              <p class="text-[10px]" [style.color]="'var(--text-tertiary)'">{{ t.admin_email || t.email || '' }} · {{ t.created_at }}</p>
            </div>
            <span class="badge text-[10px]" [ngClass]="t.status === 'active' ? 'bg-emerald-50 text-emerald-600' : t.status === 'trial' ? 'bg-blue-50 text-blue-600' : 'bg-gray-100 text-gray-500'">{{ t.status }}</span>
          </div>
        }
        @if (!recentTenants().length) {
          <p class="text-xs" [style.color]="'var(--text-tertiary)'">No companies registered yet.</p>
        }
      </div>
    }
  `,
})
export class SaDashboardComponent implements OnInit {
  private api = inject(ApiService);
  readonly BuildingIcon = Building2; readonly UsersIcon = Users; readonly ShieldIcon = Shield;
  readonly MapPinIcon = MapPin; readonly DollarIcon = DollarSign; readonly TrendingUpIcon = TrendingUp;
  readonly AlertTriangleIcon = AlertTriangle; readonly LifeBuoyIcon = LifeBuoy;
  readonly loading = signal(true);
  readonly stats = signal<any>({ total_tenants: 0, active: 0, trial: 0, suspended: 0, cancelled: 0, total_users: 0, total_guards: 0, total_sites: 0, total_clients: 0, mrr: 0, recent_signups_30d: 0, open_tickets: 0 });
  readonly recentTenants = signal<any[]>([]);
  readonly statusRows = signal<{ label: string; count: number; color: string }[]>([]);

  ngOnInit(): void {
    this.api.get<any>('/admin/stats').subscribe({
      next: r => {
        const d = r.data || r;
        this.stats.set(d);
        this.statusRows.set([
          { label: 'Active', count: d.active || 0, color: '#10B981' },
          { label: 'Trial', count: d.trial || 0, color: '#3B82F6' },
          { label: 'Suspended', count: d.suspended || 0, color: '#F59E0B' },
          { label: 'Cancelled', count: d.cancelled || 0, color: '#EF4444' },
        ]);
        this.loading.set(false);
      },
      error: () => this.loading.set(false),
    });
    this.api.get<any>('/admin/tenants?per_page=5').subscribe({
      next: r => this.recentTenants.set(r.data?.items || r.data || []),
    });
  }
}
