import { Component, inject, signal, OnInit } from '@angular/core';
import { RouterLink } from '@angular/router';
import { NgClass, DecimalPipe } from '@angular/common';
import { LucideAngularModule, Building2, Users, CreditCard, AlertTriangle, TrendingUp, Shield, BarChart3 } from 'lucide-angular';
import { PageHeaderComponent } from '@shared/components/page-header/page-header.component';
import { StatsCardComponent } from '@shared/components/stats-card/stats-card.component';
import { LoadingSpinnerComponent } from '@shared/components/loading-spinner/loading-spinner.component';
import { ApiService } from '@core/services/api.service';

@Component({
  selector: 'g51-sa-dashboard',
  standalone: true,
  imports: [RouterLink, NgClass, DecimalPipe, LucideAngularModule, PageHeaderComponent, StatsCardComponent, LoadingSpinnerComponent],
  template: `
    <g51-page-header title="Platform Dashboard" subtitle="Guard51 SaaS Administration" />

    @if (loading()) { <g51-loading /> } @else {
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6 stagger-children">
        <g51-stats-card label="Total Companies" [value]="stats().total_tenants" [icon]="BuildingIcon" />
        <g51-stats-card label="Active Subscriptions" [value]="stats().active_subscriptions" [icon]="CreditCardIcon" />
        <g51-stats-card label="Total Guards" [value]="stats().total_guards" [icon]="ShieldIcon" />
        <g51-stats-card label="MRR (₦)" [value]="(stats().mrr | number:'1.0-0') || '0'" [icon]="TrendingUpIcon" />
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
        <div class="card p-5">
          <div class="flex items-center justify-between mb-3">
            <h3 class="text-sm font-semibold font-heading" [style.color]="'var(--text-primary)'">Recent Companies</h3>
            <a routerLink="../tenants" class="text-xs font-medium" [style.color]="'var(--brand-500)'">View all →</a>
          </div>
          @for (t of recentTenants(); track t.id) {
            <div class="flex items-center justify-between py-2 border-b" [style.borderColor]="'var(--border-default)'">
              <div><p class="text-sm font-medium" [style.color]="'var(--text-primary)'">{{ t.name }}</p>
                <p class="text-xs" [style.color]="'var(--text-tertiary)'">{{ t.email }} · {{ t.tenant_type }}</p></div>
              <span class="badge text-[10px]" [ngClass]="t.status === 'active' ? 'bg-emerald-50 text-emerald-600' : t.status === 'trial' ? 'bg-blue-50 text-blue-600' : 'bg-red-50 text-red-600'">{{ t.status }}</span>
            </div>
          }
          @if (!recentTenants().length) { <p class="text-xs py-4 text-center" [style.color]="'var(--text-tertiary)'">No tenants yet</p> }
        </div>

        <div class="card p-5">
          <div class="flex items-center justify-between mb-3">
            <h3 class="text-sm font-semibold font-heading" [style.color]="'var(--text-primary)'">Platform Metrics</h3>
          </div>
          <div class="space-y-3">
            @for (m of metrics(); track m.label) {
              <div class="flex items-center justify-between">
                <span class="text-xs" [style.color]="'var(--text-secondary)'">{{ m.label }}</span>
                <span class="text-sm font-bold" [style.color]="'var(--text-primary)'">{{ m.value }}</span>
              </div>
            }
          </div>
        </div>
      </div>
    }
  `,
})
export class SaDashboardComponent implements OnInit {
  private api = inject(ApiService);
  readonly BuildingIcon = Building2; readonly CreditCardIcon = CreditCard;
  readonly ShieldIcon = Shield; readonly TrendingUpIcon = TrendingUp;
  readonly loading = signal(true);
  readonly stats = signal<any>({ total_tenants: 0, active_subscriptions: 0, total_guards: 0, mrr: 0 });
  readonly recentTenants = signal<any[]>([]);
  readonly metrics = signal<{ label: string; value: string }[]>([]);

  ngOnInit(): void {
    this.api.get<any>('/admin/stats').subscribe({
      next: res => {
        if (res.data) {
          this.stats.set(res.data);
          this.metrics.set([
            { label: 'Active Companies', value: String(res.data.active || 0) },
            { label: 'Trial Companies', value: String(res.data.trial || 0) },
            { label: 'Suspended', value: String(res.data.suspended || 0) },
            { label: 'Total Users', value: String(res.data.total_users || 0) },
            { label: 'Total Sites', value: String(res.data.total_sites || 0) },
          ]);
        }
        this.loading.set(false);
      },
      error: () => this.loading.set(false),
    });
    this.api.get<any>('/admin/tenants?per_page=5').subscribe({
      next: res => this.recentTenants.set(res.data?.tenants || res.data || []),
    });
  }
}
