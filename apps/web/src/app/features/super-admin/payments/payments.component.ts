import { Component, inject, signal, OnInit } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { NgClass, DecimalPipe } from '@angular/common';
import { LucideAngularModule, Receipt, Search, Download } from 'lucide-angular';
import { PageHeaderComponent } from '@shared/components/page-header/page-header.component';
import { EmptyStateComponent } from '@shared/components/empty-state/empty-state.component';
import { LoadingSpinnerComponent } from '@shared/components/loading-spinner/loading-spinner.component';
import { ApiService } from '@core/services/api.service';
import { exportToCsv } from '@core/utils/csv-export';

@Component({
  selector: 'g51-payments',
  standalone: true,
  imports: [FormsModule, NgClass, DecimalPipe, LucideAngularModule, PageHeaderComponent, EmptyStateComponent, LoadingSpinnerComponent],
  template: `
    <g51-page-header title="Payment History" subtitle="All subscription payments across the platform">
      <button (click)="exportPayments()" class="btn-secondary text-xs flex items-center gap-1"><lucide-icon [img]="DownloadIcon" [size]="14" /> Export</button>
    </g51-page-header>

    <div class="relative max-w-sm mb-4">
      <lucide-icon [img]="SearchIcon" [size]="14" class="absolute left-3 top-1/2 -translate-y-1/2" [style.color]="'var(--text-tertiary)'" />
      <input type="text" [(ngModel)]="search" placeholder="Search by company..." class="input-base w-full pl-9" />
    </div>

    @if (loading()) { <g51-loading /> }
    @else if (!payments().length) { <g51-empty-state title="No Payments" message="No payment transactions recorded." [icon]="ReceiptIcon" /> }
    @else {
      <div class="card overflow-hidden">
        <table class="w-full text-xs">
          <thead><tr [style.background]="'var(--surface-muted)'">
            <th class="text-left py-2.5 px-4 font-semibold" [style.color]="'var(--text-secondary)'">Company</th>
            <th class="text-left py-2.5 px-4 font-semibold" [style.color]="'var(--text-secondary)'">Plan</th>
            <th class="text-left py-2.5 px-4 font-semibold" [style.color]="'var(--text-secondary)'">Amount</th>
            <th class="text-left py-2.5 px-4 font-semibold" [style.color]="'var(--text-secondary)'">Method</th>
            <th class="text-left py-2.5 px-4 font-semibold" [style.color]="'var(--text-secondary)'">Status</th>
            <th class="text-left py-2.5 px-4 font-semibold" [style.color]="'var(--text-secondary)'">Date</th>
          </tr></thead>
          <tbody>
            @for (p of filteredPayments(); track p.id) {
              <tr class="border-t hover:bg-[var(--surface-hover)]" [style.borderColor]="'var(--border-default)'">
                <td class="py-2.5 px-4 font-medium" [style.color]="'var(--text-primary)'">{{ p.tenant_name || '—' }}</td>
                <td class="py-2.5 px-4" [style.color]="'var(--text-secondary)'">{{ p.plan_name || '—' }}</td>
                <td class="py-2.5 px-4 font-medium" [style.color]="'var(--text-primary)'">₦{{ p.amount | number:'1.0-0' }}</td>
                <td class="py-2.5 px-4" [style.color]="'var(--text-secondary)'">{{ p.payment_method || 'Paystack' }}</td>
                <td class="py-2.5 px-4"><span class="badge text-[10px]" [ngClass]="p.status === 'paid' ? 'bg-emerald-50 text-emerald-600' : p.status === 'failed' ? 'bg-red-50 text-red-600' : 'bg-amber-50 text-amber-600'">{{ p.status }}</span></td>
                <td class="py-2.5 px-4" [style.color]="'var(--text-tertiary)'">{{ p.created_at?.slice(0, 10) }}</td>
              </tr>
            }
          </tbody>
        </table>
      </div>
    }
  `,
})
export class PaymentsComponent implements OnInit {
  private api = inject(ApiService);
  readonly ReceiptIcon = Receipt; readonly SearchIcon = Search; readonly DownloadIcon = Download;
  readonly loading = signal(true); readonly payments = signal<any[]>([]);
  search = '';

  filteredPayments() { const q = this.search.toLowerCase(); return !q ? this.payments() : this.payments().filter(p => (p.tenant_name || '').toLowerCase().includes(q)); }

  ngOnInit(): void {
    this.api.get<any>('/admin/payments').subscribe({
      next: r => { this.payments.set(r.data?.payments || r.data || []); this.loading.set(false); },
      error: () => this.loading.set(false),
    });
  }
  exportPayments(): void { exportToCsv('platform-payments', this.payments(), [{ key: 'tenant_name', label: 'Company' }, { key: 'plan_name', label: 'Plan' }, { key: 'amount', label: 'Amount' }, { key: 'status', label: 'Status' }, { key: 'created_at', label: 'Date' }]); }
}
