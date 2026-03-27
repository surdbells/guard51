import { Component, inject, signal, OnInit } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { NgClass } from '@angular/common';
import { LucideAngularModule, AlertTriangle, Plus, Shield, TrendingUp, CheckCircle } from 'lucide-angular';
import { PageHeaderComponent } from '@shared/components/page-header/page-header.component';
import { StatsCardComponent } from '@shared/components/stats-card/stats-card.component';
import { PieChartComponent, PieChartData } from '@shared/components/charts/pie-chart.component';
import { LineChartComponent, LineChartSeries } from '@shared/components/charts/line-chart.component';
import { ModalComponent } from '@shared/components/modal/modal.component';
import { EmptyStateComponent } from '@shared/components/empty-state/empty-state.component';
import { ApiService } from '@core/services/api.service';
import { ToastService } from '@core/services/toast.service';

@Component({
  selector: 'g51-incidents',
  standalone: true,
  imports: [FormsModule, NgClass, LucideAngularModule, PageHeaderComponent, StatsCardComponent, PieChartComponent, LineChartComponent, ModalComponent, EmptyStateComponent],
  template: `
    <g51-page-header title="Incidents" subtitle="Incident reporting, investigation, and escalation">
      <button (click)="showCreate.set(true)" class="btn-primary flex items-center gap-2">
        <lucide-icon [img]="PlusIcon" [size]="16" /> Report Incident
      </button>
    </g51-page-header>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6 stagger-children">
      <g51-stats-card label="Active Incidents" [value]="stats().active" [icon]="AlertTriangleIcon" />
      <g51-stats-card label="Critical" [value]="stats().critical" [icon]="ShieldIcon" />
      <g51-stats-card label="Resolved (30d)" [value]="stats().resolved" [icon]="CheckCircleIcon" />
      <g51-stats-card label="Avg Resolution (hrs)" [value]="stats().avgResolutionHrs" [icon]="TrendingUpIcon" />
    </div>

    <div class="flex gap-1 mb-6">
      @for (tab of ['Active', 'All', 'Analytics']; track tab) {
        <button (click)="activeTab.set(tab)" class="px-4 py-2 rounded-lg text-sm font-medium transition-colors"
          [ngClass]="activeTab() === tab ? 'bg-[var(--color-brand-500)] text-white' : 'bg-[var(--surface-muted)]'"
          [style.color]="activeTab() !== tab ? 'var(--text-secondary)' : ''">{{ tab }}</button>
      }
    </div>

    @if (activeTab() !== 'Analytics') {
      <div class="space-y-2">
        @for (inc of incidents(); track inc.id) {
          <div class="card p-4 card-hover border-l-4"
            [style.borderLeftColor]="inc.severity === 'critical' ? 'var(--color-danger)' : inc.severity === 'high' ? 'var(--color-warning)' : inc.severity === 'medium' ? 'var(--color-brand-500)' : 'var(--text-tertiary)'">
            <div class="flex items-start justify-between">
              <div class="flex-1">
                <div class="flex items-center gap-2 mb-1">
                  <h4 class="text-sm font-semibold" [style.color]="'var(--text-primary)'">{{ inc.title }}</h4>
                  <span class="badge text-[10px]"
                    [ngClass]="inc.severity === 'critical' ? 'bg-red-50 text-red-600 dark:bg-red-950 dark:text-red-400'
                      : inc.severity === 'high' ? 'bg-amber-50 text-amber-600' : 'bg-[var(--surface-muted)]'">{{ inc.severity_label }}</span>
                </div>
                <p class="text-xs" [style.color]="'var(--text-tertiary)'">{{ inc.incident_type_label }} • {{ inc.reported_at }}</p>
                <p class="text-xs mt-1 line-clamp-1" [style.color]="'var(--text-secondary)'">{{ inc.description }}</p>
              </div>
              <span class="badge text-[10px] shrink-0 ml-3"
                [ngClass]="inc.is_active ? 'bg-amber-50 text-amber-600' : 'bg-emerald-50 text-emerald-600 dark:bg-emerald-950 dark:text-emerald-400'">{{ inc.status_label }}</span>
            </div>
          </div>
        } @empty {
          <g51-empty-state title="No Incidents" message="No incident reports found." [icon]="ShieldIcon" />
        }
      </div>
    }

    @if (activeTab() === 'Analytics') {
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <div class="card p-5">
          <h3 class="text-sm font-semibold mb-3" [style.color]="'var(--text-primary)'">Incidents by Type</h3>
          <g51-pie-chart [data]="pieData" [size]="220" />
        </div>
        <div class="card p-5">
          <h3 class="text-sm font-semibold mb-3" [style.color]="'var(--text-primary)'">Incident Trend (30 days)</h3>
          <g51-line-chart [series]="trendSeries" [labels]="trendLabels" [height]="220" />
        </div>
      </div>
    }

    <g51-modal [open]="showCreate()" title="Report Incident" maxWidth="560px" (closed)="showCreate.set(false)">
      <div class="space-y-3">
        <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Title *</label>
          <input type="text" [(ngModel)]="form.title" class="input-base w-full" placeholder="Brief incident title" /></div>
        <div class="grid grid-cols-2 gap-3">
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Type *</label>
            <select [(ngModel)]="form.incident_type" class="input-base w-full">
              <option value="theft">Theft</option><option value="trespass">Trespass</option><option value="vandalism">Vandalism</option>
              <option value="fire">Fire</option><option value="medical">Medical</option><option value="suspicious_activity">Suspicious Activity</option>
              <option value="other">Other</option></select></div>
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Severity *</label>
            <select [(ngModel)]="form.severity" class="input-base w-full">
              <option value="low">Low</option><option value="medium">Medium</option><option value="high">High</option><option value="critical">Critical</option></select></div>
        </div>
        <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Description *</label>
          <textarea [(ngModel)]="form.description" rows="4" class="input-base w-full resize-none" placeholder="Detailed description..."></textarea></div>
      </div>
      <div modal-footer>
        <button (click)="showCreate.set(false)" class="btn-secondary">Cancel</button>
        <button (click)="onCreate()" class="btn-primary">Submit Report</button>
      </div>
    </g51-modal>
  `,
})
export class IncidentsComponent implements OnInit {
  private api = inject(ApiService); private toast = inject(ToastService);
  readonly AlertTriangleIcon = AlertTriangle; readonly PlusIcon = Plus;
  readonly ShieldIcon = Shield; readonly TrendingUpIcon = TrendingUp; readonly CheckCircleIcon = CheckCircle;
  readonly activeTab = signal('Active');
  readonly showCreate = signal(false);
  readonly incidents = signal<any[]>([]);
  readonly stats = signal({ active: 0, critical: 0, resolved: 0, avgResolutionHrs: 0 });
  form = { title: '', incident_type: 'theft', severity: 'medium', description: '' };

  pieData: PieChartData[] = [
    { label: 'Theft', value: 8 }, { label: 'Trespass', value: 12 }, { label: 'Vandalism', value: 4 },
    { label: 'Suspicious', value: 15 }, { label: 'Other', value: 3 },
  ];
  trendLabels = ['W1', 'W2', 'W3', 'W4'];
  trendSeries: LineChartSeries[] = [{ name: 'Incidents', data: [8, 12, 6, 10], color: 'var(--color-danger)' }];

  ngOnInit(): void {
    this.api.get<any>('/incidents/active').subscribe({
      next: res => { if (res.data) { this.incidents.set(res.data.incidents || []); this.stats.update(s => ({ ...s, active: (res.data.incidents || []).length })); } },
    });
  }
  onCreate(): void {
    this.showCreate.set(false);
    this.toast.success('Incident reported');
  }
}
