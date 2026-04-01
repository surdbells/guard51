import { Component, inject, signal, OnInit } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { NgClass, DatePipe, DecimalPipe } from '@angular/common';
import { Router } from '@angular/router';
import { LucideAngularModule, Receipt, Plus, DollarSign, AlertTriangle, CheckCircle, Send, CreditCard } from 'lucide-angular';
import { PageHeaderComponent } from '@shared/components/page-header/page-header.component';
import { StatsCardComponent } from '@shared/components/stats-card/stats-card.component';
import { BarChartComponent, BarChartData } from '@shared/components/charts/bar-chart.component';
import { LineChartComponent, LineChartSeries } from '@shared/components/charts/line-chart.component';
import { ModalComponent } from '@shared/components/modal/modal.component';
import { EmptyStateComponent } from '@shared/components/empty-state/empty-state.component';
import { ApiService } from '@core/services/api.service';
import { ToastService } from '@core/services/toast.service';
import { exportToCsv } from '@core/utils/csv-export';

@Component({
  selector: 'g51-invoices',
  standalone: true,
  imports: [FormsModule, NgClass, DatePipe, DecimalPipe, LucideAngularModule, PageHeaderComponent, StatsCardComponent, BarChartComponent, LineChartComponent, ModalComponent, EmptyStateComponent],
  template: `
    <g51-page-header title="Invoices" subtitle="Client billing, payments, and revenue tracking">
      <button (click)="exportInvoices()" class="btn-secondary flex items-center gap-2">Export CSV</button>
      <button (click)="showGenerate.set(true)" class="btn-secondary flex items-center gap-2">
        <lucide-icon [img]="CreditCardIcon" [size]="16" /> Generate from Timesheet
      </button>
      <button (click)="showCreate.set(true)" class="btn-primary flex items-center gap-2">
        <lucide-icon [img]="PlusIcon" [size]="16" /> New Invoice
      </button>
    </g51-page-header>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6 stagger-children">
      <g51-stats-card label="Total Outstanding" [value]="'₦' + stats().outstanding" [icon]="DollarSignIcon" />
      <g51-stats-card label="Paid (30d)" [value]="'₦' + stats().paidMonth" [icon]="CheckCircleIcon" />
      <g51-stats-card label="Overdue" [value]="stats().overdue" [icon]="AlertTriangleIcon" />
      <g51-stats-card label="Sent This Month" [value]="stats().sentMonth" [icon]="SendIcon" />
    </div>

    <div class="flex gap-1 mb-6">
      @for (tab of ['All', 'Draft', 'Sent', 'Overdue', 'Paid', 'Analytics']; track tab) {
        <button (click)="activeTab.set(tab)" class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors"
          [ngClass]="activeTab() === tab ? 'bg-[var(--color-brand-500)] text-white' : 'bg-[var(--surface-muted)]'"
          [style.color]="activeTab() !== tab ? 'var(--text-secondary)' : ''">{{ tab }}</button>
      }
    </div>

    @if (activeTab() !== 'Analytics') {
      <!-- Filters -->
      <div class="flex flex-wrap gap-3 mb-4">
        <select [(ngModel)]="filterClient" class="input-base text-sm py-1.5 min-w-[160px]">
          <option value="">All Clients</option>
        </select>
      </div>

      <div class="space-y-2">
        @for (inv of filteredInvoices(); track inv.id) {
          <div class="card p-4 card-hover cursor-pointer" (click)="openInvoice(inv.id)">
            <div class="flex items-center justify-between">
              <div class="flex-1">
                <div class="flex items-center gap-2 mb-1">
                  <span class="text-sm font-semibold font-mono" [style.color]="'var(--text-primary)'">{{ inv.invoice_number }}</span>
                  <span class="badge text-[10px]"
                    [ngClass]="inv.status === 'paid' ? 'bg-emerald-50 text-emerald-600 dark:bg-emerald-950 dark:text-emerald-400'
                      : inv.status === 'overdue' ? 'bg-red-50 text-red-600 dark:bg-red-950 dark:text-red-400'
                      : inv.status === 'sent' ? 'bg-blue-50 text-blue-600'
                      : inv.status === 'partially_paid' ? 'bg-amber-50 text-amber-600'
                      : 'bg-[var(--surface-muted)]'">{{ inv.status_label }}</span>
                  @if (inv.type === 'estimate') {
                    <span class="badge text-[10px] bg-purple-50 text-purple-600">Estimate</span>
                  }
                </div>
                <p class="text-xs" [style.color]="'var(--text-tertiary)'">Client: {{ inv.client_id?.substring(0,8) }} • Issued: {{ inv.issue_date }} • Due: {{ inv.due_date }}</p>
              </div>
              <div class="text-right shrink-0 ml-4">
                <p class="text-sm font-semibold tabular-nums" [style.color]="'var(--text-primary)'">{{ inv.currency }} {{ inv.total?.toLocaleString() }}</p>
                @if (inv.balance_due > 0 && inv.balance_due !== inv.total) {
                  <p class="text-xs tabular-nums" [style.color]="'var(--color-danger)'">Due: {{ inv.currency }} {{ inv.balance_due?.toLocaleString() }}</p>
                }
              </div>
            </div>
          </div>
        } @empty {
          <g51-empty-state title="No Invoices" message="Create your first invoice to start billing clients." [icon]="ReceiptIcon" />
        }
      </div>
    }

    @if (activeTab() === 'Analytics') {
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <div class="card p-5">
          <h3 class="text-sm font-semibold mb-3" [style.color]="'var(--text-primary)'">Revenue by Client</h3>
          <g51-bar-chart [data]="revenueByClient" [height]="220" />
        </div>
        <div class="card p-5">
          <h3 class="text-sm font-semibold mb-3" [style.color]="'var(--text-primary)'">Monthly Revenue Trend</h3>
          <g51-line-chart [seriesData]="revenueTrend" [labels]="trendLabels" [height]="220" />
        </div>
      </div>
    }

    <!-- Create Invoice Modal -->
    <g51-modal [open]="showCreate()" title="Create Invoice" maxWidth="600px" (closed)="showCreate.set(false)">
      <div class="space-y-3">
        <div class="grid grid-cols-2 gap-3">
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Type</label>
            <select [(ngModel)]="form.type" class="input-base w-full">
              <option value="invoice">Invoice</option><option value="estimate">Estimate</option></select></div>
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Tax Rate (%)</label>
            <input type="number" [(ngModel)]="form.tax_rate" class="input-base w-full" step="0.5" /></div>
        </div>
        <div class="grid grid-cols-2 gap-3">
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Issue Date</label>
            <input type="date" [(ngModel)]="form.issue_date" class="input-base w-full" /></div>
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Due Date</label>
            <input type="date" [(ngModel)]="form.due_date" class="input-base w-full" /></div>
        </div>
        <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Notes</label>
          <textarea [(ngModel)]="form.notes" rows="2" class="input-base w-full resize-none" placeholder="Payment terms, bank details..."></textarea></div>

        <!-- Line items -->
        <div>
          <div class="flex items-center justify-between mb-2">
            <label class="text-xs font-medium" [style.color]="'var(--text-secondary)'">Line Items</label>
            <button (click)="addItem()" class="text-xs font-medium" [style.color]="'var(--color-brand-500)'">+ Add Item</button>
          </div>
          @for (item of form.items; track $index) {
            <div class="flex items-center gap-2 mb-2">
              <input type="text" [(ngModel)]="item.description" class="input-base flex-1 text-xs" placeholder="Description" />
              <input type="number" [(ngModel)]="item.quantity" class="input-base w-16 text-xs" placeholder="Qty" />
              <input type="number" [(ngModel)]="item.unit_price" class="input-base w-24 text-xs" placeholder="Rate" />
              <span class="text-xs tabular-nums w-20 text-right" [style.color]="'var(--text-secondary)'">₦{{ (item.quantity * item.unit_price) | number:'1.2-2' }}</span>
              <button (click)="removeItem($index)" class="text-xs p-1" [style.color]="'var(--color-danger)'">✕</button>
            </div>
          }
        </div>
      </div>
      <div modal-footer>
        <button (click)="showCreate.set(false)" class="btn-secondary">Cancel</button>
        <button (click)="onCreate()" class="btn-primary">Create Invoice</button>
      </div>
    </g51-modal>

    <!-- Generate from Timesheet Modal -->
    <g51-modal [open]="showGenerate()" title="Generate Invoice from Timesheet" maxWidth="480px" (closed)="showGenerate.set(false)">
      <div class="space-y-3">
        <p class="text-xs" [style.color]="'var(--text-tertiary)'">Auto-create an invoice from guard time clock records. Hours worked × billing rate = line items per site.</p>
        <div class="grid grid-cols-2 gap-3">
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Period Start *</label>
            <input type="date" [(ngModel)]="genForm.start_date" class="input-base w-full" /></div>
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Period End *</label>
            <input type="date" [(ngModel)]="genForm.end_date" class="input-base w-full" /></div>
        </div>
        <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Billing Rate (₦/hr) *</label>
          <input type="number" [(ngModel)]="genForm.billing_rate" class="input-base w-full" step="50" /></div>
      </div>
      <div modal-footer>
        <button (click)="showGenerate.set(false)" class="btn-secondary">Cancel</button>
        <button (click)="onGenerate()" class="btn-primary">Generate Invoice</button>
      </div>
    </g51-modal>
  `,
})
export class InvoicesComponent implements OnInit {
  private api = inject(ApiService); private toast = inject(ToastService); private router = inject(Router);
  readonly ReceiptIcon = Receipt; readonly PlusIcon = Plus; readonly DollarSignIcon = DollarSign;
  readonly AlertTriangleIcon = AlertTriangle; readonly CheckCircleIcon = CheckCircle;
  readonly SendIcon = Send; readonly CreditCardIcon = CreditCard;

  readonly activeTab = signal('All');
  readonly showCreate = signal(false);
  readonly showGenerate = signal(false);
  readonly invoices = signal<any[]>([]);
  readonly stats = signal({ outstanding: '0', paidMonth: '0', overdue: 0, sentMonth: 0 });
  filterClient = '';

  form: any = { type: 'invoice', tax_rate: 7.5, issue_date: new Date().toISOString().substring(0, 10), due_date: '', notes: '', items: [{ description: '', quantity: 1, unit_price: 0 }] };
  genForm = { start_date: '', end_date: '', billing_rate: 500 };

  revenueByClient: BarChartData[] = [
    { label: 'Lekki Estate', value: 2500000 }, { label: 'V.I. Corp', value: 1800000 },
    { label: 'Ikeja Mall', value: 1200000 }, { label: 'Abuja Office', value: 950000 },
  ];
  trendLabels = ['Oct', 'Nov', 'Dec', 'Jan', 'Feb', 'Mar'];
  revenueTrend: LineChartSeries[] = [{ name: 'Revenue', data: [4200000, 4500000, 4800000, 5100000, 4900000, 5300000], color: 'var(--color-success)' }];

  filteredInvoices = () => {
    const tab = this.activeTab();
    const all = this.invoices();
    if (tab === 'All') return all;
    return all.filter(i => i.status === tab.toLowerCase().replace(' ', '_'));
  };

  ngOnInit(): void {
    this.api.get<any>('/invoices').subscribe({
      next: res => { if (res.data) this.invoices.set(res.data.invoices || []); },
    });
  }

  addItem(): void { this.form.items.push({ description: '', quantity: 1, unit_price: 0 }); }
  removeItem(idx: number): void { this.form.items.splice(idx, 1); }

  openInvoice(id: string): void { this.router.navigate(['/invoices', id]); }

  onCreate(): void {
    this.api.post('/invoices', this.form).subscribe({
      next: () => { this.showCreate.set(false); this.toast.success('Invoice created'); this.ngOnInit(); },
    });
  }

  onGenerate(): void {
    this.api.post('/invoices/generate', this.genForm).subscribe({
      next: () => { this.showGenerate.set(false); this.toast.success('Invoice generated from timesheet'); this.ngOnInit(); },
    });
  }

  exportInvoices(): void {
    exportToCsv('invoices', this.invoices(), [
      { key: 'invoice_number', label: 'Invoice #' }, { key: 'client_name', label: 'Client' },
      { key: 'issue_date', label: 'Issue Date' }, { key: 'due_date', label: 'Due Date' },
      { key: 'total_amount', label: 'Total (₦)' }, { key: 'amount_paid', label: 'Paid (₦)' },
      { key: 'balance_due', label: 'Balance (₦)' }, { key: 'status', label: 'Status' },
    ]);
  }
}
