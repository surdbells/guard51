import { Component, inject, signal, OnInit } from '@angular/core';
import { LucideAngularModule, Plus, Search, CreditCard, TrendingUp, AlertCircle, Users } from 'lucide-angular';
import { PageHeaderComponent } from '@shared/components/page-header/page-header.component';
import { StatsCardComponent } from '@shared/components/stats-card/stats-card.component';
import { DataTableComponent, TableColumn } from '@shared/components/data-table/data-table.component';
import { PieChartComponent, PieChartData } from '@shared/components/charts/pie-chart.component';
import { ApiService } from '@core/services/api.service';

@Component({
  selector: 'g51-sa-subscriptions',
  standalone: true,
  imports: [LucideAngularModule, PageHeaderComponent, StatsCardComponent, DataTableComponent, PieChartComponent],
  template: `
    <g51-page-header title="Subscription Management" subtitle="Manage plans, active subscriptions, and revenue">
      <button class="btn-primary flex items-center gap-2">
        <lucide-icon [img]="PlusIcon" [size]="16" /> Create Plan
      </button>
    </g51-page-header>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6 stagger-children">
      <g51-stats-card label="Active Subscriptions" value="42" [icon]="UsersIcon" [trend]="8" trendLabel="from last month" />
      <g51-stats-card label="Monthly Revenue" value="₦3.75M" [icon]="CreditCardIcon" [trend]="15.2" trendLabel="from last month" />
      <g51-stats-card label="Failed Payments" value="3" [icon]="AlertCircleIcon" [trend]="-40" trendLabel="from last month" />
      <g51-stats-card label="Avg Revenue/Tenant" value="₦89.3K" [icon]="TrendingUpIcon" [trend]="4.5" trendLabel="from last month" />
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
      <div class="lg:col-span-2">
        <div class="card p-5 mb-4">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-base font-semibold" [style.color]="'var(--text-primary)'">Active Subscriptions</h3>
            <div class="relative">
              <lucide-icon [img]="SearchIcon" [size]="15" class="absolute left-3 top-1/2 -translate-y-1/2" [style.color]="'var(--text-tertiary)'" />
              <input type="text" class="input-base pl-9 text-sm" placeholder="Search..." />
            </div>
          </div>
          <g51-data-table [columns]="subColumns" [rows]="subRows" [total]="subRows.length" trackBy="id" />
        </div>
      </div>
      <div class="card p-5">
        <h3 class="text-base font-semibold mb-4" [style.color]="'var(--text-primary)'">Revenue by Plan</h3>
        <g51-pie-chart [data]="planRevenue" [size]="140" />
      </div>
    </div>
  `,
})
export class SubscriptionsComponent {
  readonly PlusIcon = Plus; readonly SearchIcon = Search; readonly CreditCardIcon = CreditCard;
  readonly TrendingUpIcon = TrendingUp; readonly AlertCircleIcon = AlertCircle; readonly UsersIcon = Users;

  subColumns: TableColumn[] = [
    { key: 'tenant', label: 'Organization' },
    { key: 'plan', label: 'Plan' },
    { key: 'amount', label: 'Amount', align: 'right' },
    { key: 'method', label: 'Method' },
    { key: 'status', label: 'Status' },
  ];
  subRows = [
    { id: '1', tenant: 'ShieldForce Security', plan: 'Business', amount: '₦150,000', method: 'Paystack', status: 'Active' },
    { id: '2', tenant: 'Fortress Guard', plan: 'Professional', amount: '₦75,000', method: 'Paystack', status: 'Active' },
    { id: '3', tenant: 'Eagle Eye Protection', plan: 'Starter', amount: '₦25,000', method: 'Bank Transfer', status: 'Active' },
    { id: '4', tenant: 'Sentinel Guards', plan: 'Professional', amount: '₦75,000', method: 'Paystack', status: 'Past Due' },
  ];
  planRevenue: PieChartData[] = [
    { label: 'Business', value: 1800000 }, { label: 'Professional', value: 1200000 },
    { label: 'Starter', value: 500000 }, { label: 'Enterprise', value: 250000 },
  ];
}
