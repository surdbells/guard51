import { Component, inject, signal, OnInit } from '@angular/core';
import { NgClass } from '@angular/common';
import { LucideAngularModule, Shield, Users, FileText, Receipt, MapPin, Clock, AlertTriangle } from 'lucide-angular';
import { PageHeaderComponent } from '@shared/components/page-header/page-header.component';
import { StatsCardComponent } from '@shared/components/stats-card/stats-card.component';
import { EmptyStateComponent } from '@shared/components/empty-state/empty-state.component';
import { ApiService } from '@core/services/api.service';

@Component({
  selector: 'g51-client-portal',
  standalone: true,
  imports: [NgClass, LucideAngularModule, PageHeaderComponent, StatsCardComponent, EmptyStateComponent],
  template: `
    <g51-page-header title="Client Portal" subtitle="Your security service overview" />

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6 stagger-children">
      <g51-stats-card label="Guards On Duty" [value]="stats().guardsOnDuty" [icon]="UsersIcon" />
      <g51-stats-card label="Incidents Today" [value]="stats().incidentsToday" [icon]="AlertTriangleIcon" />
      <g51-stats-card label="Reports (7d)" [value]="stats().reportsWeek" [icon]="FileTextIcon" />
      <g51-stats-card label="Outstanding Invoices" [value]="stats().outstandingInvoices" [icon]="ReceiptIcon" />
    </div>

    <div class="flex gap-1 mb-6">
      @for (tab of ['Dashboard', 'Live Tracking', 'Reports', 'Invoices', 'Attendance', 'Post Orders']; track tab) {
        <button (click)="activeTab.set(tab)" class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors"
          [ngClass]="activeTab() === tab ? 'bg-[var(--color-brand-500)] text-white' : 'bg-[var(--surface-muted)]'"
          [style.color]="activeTab() !== tab ? 'var(--text-secondary)' : ''">{{ tab }}</button>
      }
    </div>

    @if (activeTab() === 'Dashboard') {
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <!-- Guards on duty -->
        <div class="card p-5">
          <h3 class="text-sm font-semibold mb-3 flex items-center gap-2" [style.color]="'var(--text-primary)'">
            <lucide-icon [img]="ShieldIcon" [size]="14" /> Guards Currently On Duty
          </h3>
          @for (g of guardsOnDuty; track g.name) {
            <div class="flex items-center justify-between py-2 border-b last:border-b-0" [style.borderColor]="'var(--border-default)'">
              <div>
                <p class="text-sm font-medium" [style.color]="'var(--text-primary)'">{{ g.name }}</p>
                <p class="text-xs" [style.color]="'var(--text-tertiary)'">{{ g.site }} • Since {{ g.since }}</p>
              </div>
              <span class="badge text-[10px] bg-emerald-50 text-emerald-600 dark:bg-emerald-950 dark:text-emerald-400">On Duty</span>
            </div>
          }
        </div>

        <!-- Recent incidents -->
        <div class="card p-5">
          <h3 class="text-sm font-semibold mb-3 flex items-center gap-2" [style.color]="'var(--text-primary)'">
            <lucide-icon [img]="AlertTriangleIcon" [size]="14" /> Recent Incidents
          </h3>
          @for (inc of recentIncidents; track inc.title) {
            <div class="py-2 border-b last:border-b-0" [style.borderColor]="'var(--border-default)'">
              <div class="flex items-center gap-2 mb-0.5">
                <p class="text-sm font-medium" [style.color]="'var(--text-primary)'">{{ inc.title }}</p>
                <span class="badge text-[9px]"
                  [ngClass]="inc.severity === 'high' ? 'bg-amber-50 text-amber-600' : 'bg-[var(--surface-muted)]'">{{ inc.severity }}</span>
              </div>
              <p class="text-xs" [style.color]="'var(--text-tertiary)'">{{ inc.site }} • {{ inc.time }}</p>
            </div>
          } @empty {
            <p class="text-sm py-4 text-center" [style.color]="'var(--text-tertiary)'">No incidents today</p>
          }
        </div>

        <!-- Recent reports -->
        <div class="card p-5">
          <h3 class="text-sm font-semibold mb-3 flex items-center gap-2" [style.color]="'var(--text-primary)'">
            <lucide-icon [img]="FileTextIcon" [size]="14" /> Recent Reports
          </h3>
          @for (r of recentReports; track r.title) {
            <div class="py-2 border-b last:border-b-0" [style.borderColor]="'var(--border-default)'">
              <p class="text-sm font-medium" [style.color]="'var(--text-primary)'">{{ r.title }}</p>
              <p class="text-xs" [style.color]="'var(--text-tertiary)'">{{ r.guard }} • {{ r.date }}</p>
            </div>
          }
        </div>

        <!-- Attendance summary -->
        <div class="card p-5">
          <h3 class="text-sm font-semibold mb-3 flex items-center gap-2" [style.color]="'var(--text-primary)'">
            <lucide-icon [img]="ClockIcon" [size]="14" /> Today's Check-ins
          </h3>
          @for (a of checkIns; track a.guard) {
            <div class="flex items-center justify-between py-2 border-b last:border-b-0" [style.borderColor]="'var(--border-default)'">
              <div>
                <p class="text-xs font-medium" [style.color]="'var(--text-primary)'">{{ a.guard }}</p>
                <p class="text-[10px]" [style.color]="'var(--text-tertiary)'">{{ a.site }}</p>
              </div>
              <div class="text-right">
                <p class="text-xs tabular-nums" [style.color]="'var(--text-primary)'">In: {{ a.clockIn }}</p>
                @if (a.clockOut) {
                  <p class="text-[10px] tabular-nums" [style.color]="'var(--text-tertiary)'">Out: {{ a.clockOut }}</p>
                }
              </div>
            </div>
          }
        </div>
      </div>
    }

    @if (activeTab() === 'Live Tracking') {
      <div class="card p-5">
        <h3 class="text-sm font-semibold mb-3" [style.color]="'var(--text-primary)'">Guard Locations (Your Sites)</h3>
        <div class="aspect-video bg-[var(--surface-muted)] rounded-lg flex items-center justify-center mb-4">
          <div class="text-center">
            <lucide-icon [img]="MapPinIcon" [size]="32" [style.color]="'var(--text-tertiary)'" />
            <p class="text-sm mt-2" [style.color]="'var(--text-tertiary)'">Live map view — guard positions at your assigned sites</p>
          </div>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
          @for (g of guardsOnDuty; track g.name) {
            <div class="card p-3"><p class="text-xs font-medium" [style.color]="'var(--text-primary)'">{{ g.name }}</p>
              <p class="text-[10px]" [style.color]="'var(--text-tertiary)'">{{ g.site }}</p></div>
          }
        </div>
      </div>
    }

    @if (activeTab() === 'Reports') {
      <div class="card p-5">
        <h3 class="text-sm font-semibold mb-3" [style.color]="'var(--text-primary)'">Approved Reports</h3>
        <div class="space-y-2">
          @for (r of approvedReports(); track r.id) {
            <div class="card p-4 card-hover">
              <p class="text-sm font-medium" [style.color]="'var(--text-primary)'">{{ r.report_date }}</p>
              <p class="text-xs line-clamp-2 mt-1" [style.color]="'var(--text-secondary)'">{{ r.content?.substring(0, 150) }}...</p>
            </div>
          } @empty {
            <g51-empty-state title="No Reports" message="No approved reports available yet." [icon]="FileTextIcon" />
          }
        </div>
      </div>
    }

    @if (activeTab() === 'Invoices') {
      <div class="card p-5">
        <h3 class="text-sm font-semibold mb-3" [style.color]="'var(--text-primary)'">Your Invoices</h3>
        <g51-empty-state title="No Invoices" message="Invoice data will appear here." [icon]="ReceiptIcon" />
      </div>
    }

    @if (activeTab() === 'Attendance') {
      <div class="card p-5">
        <h3 class="text-sm font-semibold mb-3" [style.color]="'var(--text-primary)'">Guard Attendance Summary</h3>
        <g51-empty-state title="Attendance" message="Attendance history for your sites." [icon]="ClockIcon" />
      </div>
    }

    @if (activeTab() === 'Post Orders') {
      <div class="card p-5">
        <h3 class="text-sm font-semibold mb-3" [style.color]="'var(--text-primary)'">Post Orders</h3>
        <g51-empty-state title="Post Orders" message="Post orders for your sites." [icon]="ShieldIcon" />
      </div>
    }
  `,
})
export class ClientPortalComponent implements OnInit {
  private api = inject(ApiService);
  readonly ShieldIcon = Shield; readonly UsersIcon = Users; readonly FileTextIcon = FileText;
  readonly ReceiptIcon = Receipt; readonly MapPinIcon = MapPin; readonly ClockIcon = Clock;
  readonly AlertTriangleIcon = AlertTriangle;
  readonly activeTab = signal('Dashboard');
  readonly stats = signal({ guardsOnDuty: 4, incidentsToday: 1, reportsWeek: 12, outstandingInvoices: 2 });
  readonly approvedReports = signal<any[]>([]);

  guardsOnDuty = [
    { name: 'Musa Ibrahim', site: 'Lekki Phase 1', since: '06:00 AM' },
    { name: 'Chika Nwosu', site: 'V.I. Office', since: '06:15 AM' },
    { name: 'Adebayo O.', site: 'Lekki Phase 1', since: '18:00 PM' },
    { name: 'Emeka J.', site: 'V.I. Office', since: '18:00 PM' },
  ];
  recentIncidents = [
    { title: 'Suspicious vehicle', severity: 'medium', site: 'Lekki Phase 1', time: '2 hours ago' },
    { title: 'Broken gate lock', severity: 'low', site: 'V.I. Office', time: '5 hours ago' },
  ];
  recentReports = [
    { title: 'Daily Activity Report', guard: 'Musa I.', date: 'Today' },
    { title: 'Night Shift Report', guard: 'Adebayo O.', date: 'Yesterday' },
  ];
  checkIns = [
    { guard: 'Musa Ibrahim', site: 'Lekki Phase 1', clockIn: '06:02 AM', clockOut: null },
    { guard: 'Chika Nwosu', site: 'V.I. Office', clockIn: '06:18 AM', clockOut: null },
  ];

  ngOnInit(): void {
    this.api.get<any>('/client-portal/reports').subscribe({
      next: res => { if (res.data) this.approvedReports.set(res.data.reports || []); },
    });
  }
}
