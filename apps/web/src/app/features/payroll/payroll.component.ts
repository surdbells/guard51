import { Component, inject, signal, OnInit } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { NgClass, DecimalPipe } from '@angular/common';
import { LucideAngularModule, Wallet, Plus, Calculator, CheckCircle, Clock, Users, Settings } from 'lucide-angular';
import { PageHeaderComponent } from '@shared/components/page-header/page-header.component';
import { StatsCardComponent } from '@shared/components/stats-card/stats-card.component';
import { BarChartComponent, BarChartData } from '@shared/components/charts/bar-chart.component';
import { ModalComponent } from '@shared/components/modal/modal.component';
import { EmptyStateComponent } from '@shared/components/empty-state/empty-state.component';
import { ApiService } from '@core/services/api.service';
import { ToastService } from '@core/services/toast.service';

@Component({
  selector: 'g51-payroll',
  standalone: true,
  imports: [FormsModule, NgClass, DecimalPipe, LucideAngularModule, PageHeaderComponent, StatsCardComponent, BarChartComponent, ModalComponent, EmptyStateComponent],
  template: `
    <g51-page-header title="Payroll" subtitle="Guard payroll, PAYE tax, and payslip management">
      <button (click)="showCreatePeriod.set(true)" class="btn-primary flex items-center gap-2">
        <lucide-icon [img]="PlusIcon" [size]="16" /> New Period
      </button>
    </g51-page-header>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6 stagger-children">
      <g51-stats-card label="Total Gross (Current)" [value]="'₦' + stats().totalGross" [icon]="WalletIcon" />
      <g51-stats-card label="Total Net" [value]="'₦' + stats().totalNet" [icon]="WalletIcon" />
      <g51-stats-card label="Guards on Payroll" [value]="stats().guardsCount" [icon]="UsersIcon" />
      <g51-stats-card label="Periods (YTD)" [value]="stats().periodsCount" [icon]="ClockIcon" />
    </div>

    <div class="tab-pills">
      @for (tab of ['Payroll Periods', 'Rate Multipliers', 'Analytics']; track tab) {
        <button (click)="activeTab.set(tab)" class="tab-pill" [ngClass]="activeTab() === tab ? 'active' : ''">{{ tab }}</button>
      }
    </div>

    @if (activeTab() === 'Payroll Periods') {
      <div class="space-y-2">
        @for (period of periods(); track period.id) {
          <div class="card p-4 card-hover">
            <div class="flex items-center justify-between">
              <div class="flex-1">
                <div class="flex items-center gap-2 mb-1">
                  <span class="text-sm font-semibold" [style.color]="'var(--text-primary)'">{{ period.period_start }} — {{ period.period_end }}</span>
                  <span class="badge text-[10px]"
                    [ngClass]="period.status === 'paid' ? 'bg-emerald-50 text-emerald-600 dark:bg-emerald-950 dark:text-emerald-400'
                      : period.status === 'approved' ? 'bg-blue-50 text-blue-600'
                      : period.status === 'review' ? 'bg-amber-50 text-amber-600'
                      : period.status === 'calculating' ? 'bg-purple-50 text-purple-600'
                      : 'bg-[var(--surface-muted)]'">{{ period.status_label }}</span>
                </div>
                <div class="flex items-center gap-4 text-xs" [style.color]="'var(--text-tertiary)'">
                  <span>Gross: ₦{{ period.total_gross | number:'1.2-2' }}</span>
                  <span>Deductions: ₦{{ period.total_deductions | number:'1.2-2' }}</span>
                  <span class="font-medium" [style.color]="'var(--text-primary)'">Net: ₦{{ period.total_net | number:'1.2-2' }}</span>
                </div>
              </div>
              <div class="flex gap-1.5 shrink-0 ml-3">
                @if (period.status === 'draft') {
                  <button (click)="calculatePeriod(period.id)" class="btn-secondary text-xs py-1 px-2.5 flex items-center gap-1">
                    <lucide-icon [img]="CalculatorIcon" [size]="12" /> Calculate
                  </button>
                }
                @if (period.status === 'review') {
                  <button (click)="viewDetail(period.id)" class="btn-secondary text-xs py-1 px-2.5">Review</button>
                  <button (click)="approvePeriod(period.id)" class="btn-primary text-xs py-1 px-2.5 flex items-center gap-1">
                    <lucide-icon [img]="CheckCircleIcon" [size]="12" /> Approve
                  </button>
                }
                @if (period.status === 'approved' || period.status === 'paid') {
                  <button (click)="viewDetail(period.id)" class="btn-secondary text-xs py-1 px-2.5">View Payslips</button>
                }
              </div>
            </div>
          </div>
        } @empty {
          <g51-empty-state title="No Payroll Periods" message="Create a payroll period to start processing guard pay." [icon]="WalletIcon" />
        }
      </div>

      <!-- Period detail (guard breakdown) -->
      @if (periodDetail(); as detail) {
        <div class="card p-5 mt-4">
          <div class="flex items-center justify-between mb-3">
            <h3 class="text-sm font-semibold" [style.color]="'var(--text-primary)'">Guard Breakdown — {{ detail.period.period_start }} to {{ detail.period.period_end }}</h3>
            <button (click)="periodDetail.set(null)" class="text-xs" [style.color]="'var(--text-tertiary)'">Close</button>
          </div>
          <table class="w-full text-xs">
            <thead>
              <tr class="border-b" [style.borderColor]="'var(--border-default)'">
                <th class="py-2 text-left" [style.color]="'var(--text-secondary)'">Guard</th>
                <th class="py-2 text-right" [style.color]="'var(--text-secondary)'">Reg Hrs</th>
                <th class="py-2 text-right" [style.color]="'var(--text-secondary)'">OT Hrs</th>
                <th class="py-2 text-right" [style.color]="'var(--text-secondary)'">Gross</th>
                <th class="py-2 text-right" [style.color]="'var(--text-secondary)'">PAYE</th>
                <th class="py-2 text-right" [style.color]="'var(--text-secondary)'">Pension</th>
                <th class="py-2 text-right" [style.color]="'var(--text-secondary)'">NHF</th>
                <th class="py-2 text-right font-semibold" [style.color]="'var(--text-secondary)'">Net Pay</th>
              </tr>
            </thead>
            <tbody>
              @for (item of detail.items; track item.id) {
                <tr class="border-b last:border-b-0" [style.borderColor]="'var(--border-default)'">
                  <td class="py-2" [style.color]="'var(--text-primary)'">{{ item.guard_id?.substring(0,8) }}...</td>
                  <td class="py-2 text-right tabular-nums" [style.color]="'var(--text-secondary)'">{{ item.regular_hours }}</td>
                  <td class="py-2 text-right tabular-nums" [style.color]="item.overtime_hours > 0 ? 'var(--color-warning)' : 'var(--text-tertiary)'">{{ item.overtime_hours }}</td>
                  <td class="py-2 text-right tabular-nums" [style.color]="'var(--text-primary)'">₦{{ item.gross_pay | number:'1.0-0' }}</td>
                  <td class="py-2 text-right tabular-nums" [style.color]="'var(--text-tertiary)'">{{ item.deductions?.paye | number:'1.0-0' }}</td>
                  <td class="py-2 text-right tabular-nums" [style.color]="'var(--text-tertiary)'">{{ item.deductions?.pension | number:'1.0-0' }}</td>
                  <td class="py-2 text-right tabular-nums" [style.color]="'var(--text-tertiary)'">{{ item.deductions?.nhf | number:'1.0-0' }}</td>
                  <td class="py-2 text-right tabular-nums font-semibold" [style.color]="'var(--color-success)'">₦{{ item.net_pay | number:'1.0-0' }}</td>
                </tr>
              }
            </tbody>
          </table>
        </div>
      }
    }

    @if (activeTab() === 'Rate Multipliers') {
      <div class="card p-5">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-sm font-semibold" [style.color]="'var(--text-primary)'">Pay Rate Multipliers</h3>
          <button (click)="showCreateRate.set(true)" class="btn-primary text-xs py-1.5 px-3 flex items-center gap-1">
            <lucide-icon [img]="PlusIcon" [size]="12" /> Add Rate
          </button>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
          @for (rate of rates(); track rate.id) {
            <div class="card p-4">
              <div class="flex items-center justify-between mb-1">
                <h4 class="text-sm font-semibold" [style.color]="'var(--text-primary)'">{{ rate.name }}</h4>
                <span class="badge text-[10px]"
                  [ngClass]="rate.applies_to === 'overtime' ? 'bg-amber-50 text-amber-600'
                    : rate.applies_to === 'holiday' ? 'bg-blue-50 text-blue-600'
                    : rate.applies_to === 'night' ? 'bg-purple-50 text-purple-600'
                    : 'bg-[var(--surface-muted)]'">{{ rate.applies_to_label }}</span>
              </div>
              <p class="text-2xl font-bold tabular-nums" [style.color]="'var(--color-brand-500)'">{{ rate.multiplier }}x</p>
            </div>
          } @empty {
            <div class="col-span-3">
              <g51-empty-state title="No Multipliers" message="Add overtime, holiday, or night shift pay multipliers." [icon]="SettingsIcon" />
            </div>
          }
        </div>
      </div>
    }

    @if (activeTab() === 'Analytics') {
      <div class="card p-5">
        <h3 class="text-sm font-semibold mb-3" [style.color]="'var(--text-primary)'">Payroll by Site</h3>
        <g51-bar-chart [data]="payrollBySite" [height]="220" />
      </div>
    }

    <!-- Create period modal -->
    <g51-modal [open]="showCreatePeriod()" title="New Payroll Period" maxWidth="400px" (closed)="showCreatePeriod.set(false)">
      <div class="space-y-3">
        <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Period Start *</label>
          <input type="date" [(ngModel)]="periodForm.period_start" class="input-base w-full" /></div>
        <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Period End *</label>
          <input type="date" [(ngModel)]="periodForm.period_end" class="input-base w-full" /></div>
      </div>
      <div modal-footer>
        <button (click)="showCreatePeriod.set(false)" class="btn-secondary">Cancel</button>
        <button (click)="onCreatePeriod()" class="btn-primary">Create Period</button>
      </div>
    </g51-modal>

    <!-- Create rate modal -->
    <g51-modal [open]="showCreateRate()" title="Add Pay Rate Multiplier" maxWidth="400px" (closed)="showCreateRate.set(false)">
      <div class="space-y-3">
        <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Name *</label>
          <input type="text" [(ngModel)]="rateForm.name" class="input-base w-full" placeholder="Overtime 1.5x" /></div>
        <div class="grid grid-cols-2 gap-3">
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Multiplier *</label>
            <input type="number" [(ngModel)]="rateForm.multiplier" class="input-base w-full" step="0.25" min="1" /></div>
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Applies To *</label>
            <select [(ngModel)]="rateForm.applies_to" class="input-base w-full">
              <option value="overtime">Overtime</option><option value="holiday">Holiday</option>
              <option value="night">Night Shift</option><option value="weekend">Weekend</option></select></div>
        </div>
      </div>
      <div modal-footer>
        <button (click)="showCreateRate.set(false)" class="btn-secondary">Cancel</button>
        <button (click)="onCreateRate()" class="btn-primary">Add Multiplier</button>
      </div>
    </g51-modal>
  `,
})
export class PayrollComponent implements OnInit {
  private api = inject(ApiService); private toast = inject(ToastService);
  readonly WalletIcon = Wallet; readonly PlusIcon = Plus; readonly CalculatorIcon = Calculator;
  readonly CheckCircleIcon = CheckCircle; readonly ClockIcon = Clock; readonly UsersIcon = Users; readonly SettingsIcon = Settings;

  readonly activeTab = signal('Payroll Periods');
  readonly showCreatePeriod = signal(false);
  readonly showCreateRate = signal(false);
  readonly periods = signal<any[]>([]);
  readonly rates = signal<any[]>([]);
  readonly periodDetail = signal<any>(null);
  readonly stats = signal({ totalGross: '0', totalNet: '0', guardsCount: 0, periodsCount: 0 });
  periodForm = { period_start: '', period_end: '' };
  rateForm = { name: '', multiplier: 1.5, applies_to: 'overtime' };

  payrollBySite: BarChartData[] = [
    { label: 'Lekki Phase 1', value: 3200000 }, { label: 'V.I. HQ', value: 2100000 },
    { label: 'Ikeja Mall', value: 1800000 }, { label: 'Abuja Office', value: 1400000 },
  ];

  ngOnInit(): void {
    this.api.get<any>('/payroll/periods').subscribe({ next: res => {
      if (res.data) { this.periods.set(res.data.periods || []); this.stats.update(s => ({ ...s, periodsCount: (res.data.periods || []).length })); }
    }});
    this.api.get<any>('/payroll/rates').subscribe({ next: res => { if (res.data) this.rates.set(res.data.rates || []); } });
  }

  onCreatePeriod(): void {
    this.api.post('/payroll/periods', this.periodForm).subscribe({
      next: () => { this.showCreatePeriod.set(false); this.toast.success('Payroll period created'); this.ngOnInit(); },
    });
  }

  calculatePeriod(id: string): void {
    this.api.post(`/payroll/periods/${id}/calculate`, { default_rate: 500 }).subscribe({
      next: (res: any) => { this.toast.success(`Calculated payroll for ${res.data?.calculated || 0} guards`); this.ngOnInit(); },
    });
  }

  approvePeriod(id: string): void {
    this.api.post(`/payroll/periods/${id}/approve`, {}).subscribe({
      next: () => { this.toast.success('Payroll approved — payslips generated'); this.ngOnInit(); },
    });
  }

  viewDetail(id: string): void {
    this.api.get<any>(`/payroll/periods/${id}`).subscribe({
      next: res => { if (res.data) this.periodDetail.set(res.data); },
    });
  }

  onCreateRate(): void {
    this.api.post('/payroll/rates', this.rateForm).subscribe({
      next: () => { this.showCreateRate.set(false); this.toast.success('Rate multiplier added'); this.ngOnInit(); },
    });
  }
}
