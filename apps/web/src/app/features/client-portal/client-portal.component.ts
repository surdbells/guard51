import { Component, inject, signal, OnInit } from '@angular/core';
import { NgClass } from '@angular/common';
import { LucideAngularModule, Building2, FileText, Receipt, AlertTriangle, MapPin, Clock } from 'lucide-angular';
import { PageHeaderComponent } from '@shared/components/page-header/page-header.component';
import { StatsCardComponent } from '@shared/components/stats-card/stats-card.component';
import { EmptyStateComponent } from '@shared/components/empty-state/empty-state.component';
import { LoadingSpinnerComponent } from '@shared/components/loading-spinner/loading-spinner.component';
import { ApiService } from '@core/services/api.service';
import { AuthStore } from '@core/services/auth.store';

@Component({
  selector: 'g51-client-portal',
  standalone: true,
  imports: [NgClass, LucideAngularModule, PageHeaderComponent, StatsCardComponent, EmptyStateComponent, LoadingSpinnerComponent],
  template: `
    <g51-page-header title="Client Portal" [subtitle]="'Welcome, ' + (auth.user()?.first_name || 'Client')" />
    <div class="flex gap-1 mb-4">
      @for (tab of ['Reports', 'Invoices', 'Incidents', 'Attendance']; track tab) {
        <button (click)="activeTab.set(tab); loadTab()" class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors"
          [ngClass]="activeTab() === tab ? 'bg-[var(--color-brand-500)] text-white' : 'bg-[var(--surface-muted)]'" [style.color]="activeTab() !== tab ? 'var(--text-secondary)' : ''">{{ tab }}</button>
      }
    </div>
    @if (loading()) { <g51-loading /> }
    @else {
      @if (activeTab() === 'Reports') {
        @if (!reports().length) { <g51-empty-state title="No Reports" message="No reports available yet." [icon]="FileTextIcon" /> }
        @else { <div class="space-y-2">@for (r of reports(); track r.id) { <div class="card p-4"><p class="text-sm font-semibold" [style.color]="'var(--text-primary)'">{{ r.title || r.report_type || 'Report' }}</p><p class="text-xs" [style.color]="'var(--text-tertiary)'">{{ r.site_name || '' }} · {{ r.created_at }}</p></div> }</div> }
      }
      @if (activeTab() === 'Invoices') {
        @if (!invoices().length) { <g51-empty-state title="No Invoices" message="No invoices yet." [icon]="ReceiptIcon" /> }
        @else { <div class="space-y-2">@for (i of invoices(); track i.id) { <div class="card p-4"><div class="flex justify-between"><div><p class="text-sm font-semibold" [style.color]="'var(--text-primary)'">{{ i.invoice_number }}</p><p class="text-xs" [style.color]="'var(--text-tertiary)'">{{ i.due_date }}</p></div><div class="text-right"><p class="text-sm font-bold" [style.color]="'var(--text-primary)'">₦{{ i.total_amount }}</p><span class="badge text-[10px]" [ngClass]="i.status === 'paid' ? 'bg-emerald-50 text-emerald-600' : 'bg-amber-50 text-amber-600'">{{ i.status }}</span></div></div></div> }</div> }
      }
      @if (activeTab() === 'Incidents') {
        @if (!incidents().length) { <g51-empty-state title="No Incidents" message="No incidents reported." [icon]="AlertTriangleIcon" /> }
        @else { <div class="space-y-2">@for (i of incidents(); track i.id) { <div class="card p-4"><p class="text-sm font-semibold" [style.color]="'var(--text-primary)'">{{ i.title }}</p><p class="text-xs" [style.color]="'var(--text-tertiary)'">{{ i.severity }} · {{ i.status }} · {{ i.created_at }}</p></div> }</div> }
      }
      @if (activeTab() === 'Attendance') {
        @if (!attendance().length) { <g51-empty-state title="No Records" message="No attendance data available." [icon]="ClockIcon" /> }
        @else { <div class="space-y-2">@for (a of attendance(); track a.id) { <div class="card p-4"><p class="text-sm" [style.color]="'var(--text-primary)'">{{ a.guard_name }} · In: {{ a.clock_in_time }} · Out: {{ a.clock_out_time || '-' }}</p></div> }</div> }
      }
    }
  `,
})
export class ClientPortalComponent implements OnInit {
  readonly auth = inject(AuthStore);
  private api = inject(ApiService);
  readonly FileTextIcon = FileText; readonly ReceiptIcon = Receipt; readonly AlertTriangleIcon = AlertTriangle; readonly ClockIcon = Clock;
  readonly activeTab = signal('Reports'); readonly loading = signal(true);
  readonly reports = signal<any[]>([]); readonly invoices = signal<any[]>([]); readonly incidents = signal<any[]>([]); readonly attendance = signal<any[]>([]);
  ngOnInit(): void { this.loadTab(); }
  loadTab(): void {
    this.loading.set(true);
    const tab = this.activeTab();
    const endpoint = tab === 'Reports' ? '/client-portal/reports' : tab === 'Invoices' ? '/client-portal/invoices' : tab === 'Incidents' ? '/client-portal/incidents' : '/client-portal/attendance';
    this.api.get<any>(endpoint).subscribe({
      next: res => {
        const data = res.data?.items || res.data?.[tab.toLowerCase()] || res.data || [];
        if (tab === 'Reports') this.reports.set(data);
        else if (tab === 'Invoices') this.invoices.set(data);
        else if (tab === 'Incidents') this.incidents.set(data);
        else this.attendance.set(data);
        this.loading.set(false);
      },
      error: () => this.loading.set(false),
    });
  }
}
