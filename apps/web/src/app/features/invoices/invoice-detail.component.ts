import { Component, inject, signal, OnInit } from '@angular/core';
import { ActivatedRoute, RouterLink } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { NgClass, DatePipe, DecimalPipe } from '@angular/common';
import { LucideAngularModule, ArrowLeft, Send, Download, CreditCard, CheckCircle, RefreshCw } from 'lucide-angular';
import { PageHeaderComponent } from '@shared/components/page-header/page-header.component';
import { ApiService } from '@core/services/api.service';
import { ToastService } from '@core/services/toast.service';

@Component({
  selector: 'g51-invoice-detail',
  standalone: true,
  imports: [RouterLink, FormsModule, NgClass, DatePipe, DecimalPipe, LucideAngularModule, PageHeaderComponent],
  template: `
    <g51-page-header [title]="'Invoice ' + (detail()?.invoice?.invoice_number || '')" subtitle="Invoice detail, payments, and actions">
      <a routerLink="/invoices" class="btn-secondary flex items-center gap-1.5"><lucide-icon [img]="ArrowLeftIcon" [size]="16" /> Back</a>
      <button (click)="exportPdf()" class="btn-secondary flex items-center gap-1.5"><lucide-icon [img]="DownloadIcon" [size]="16" /> Export PDF</button>
      @if (detail()?.invoice?.status === 'draft') {
        <button (click)="sendInvoice()" class="btn-primary flex items-center gap-1.5"><lucide-icon [img]="SendIcon" [size]="16" /> Send</button>
      }
    </g51-page-header>

    @if (detail(); as d) {
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="lg:col-span-2 space-y-4">
          <!-- Invoice header -->
          <div class="card p-5">
            <div class="flex items-center justify-between mb-4">
              <div>
                <h2 class="text-lg font-bold font-mono" [style.color]="'var(--text-primary)'">{{ d.invoice.invoice_number }}</h2>
                <span class="badge"
                  [ngClass]="d.invoice.status === 'paid' ? 'bg-emerald-50 text-emerald-600 dark:bg-emerald-950 dark:text-emerald-400'
                    : d.invoice.status === 'overdue' ? 'bg-red-50 text-red-600' : 'bg-blue-50 text-blue-600'">{{ d.invoice.status_label }}</span>
              </div>
              <div class="text-right">
                <p class="text-2xl font-bold tabular-nums" [style.color]="'var(--text-primary)'">{{ d.invoice.currency }} {{ d.invoice.total | number:'1.2-2' }}</p>
                @if (d.invoice.balance_due > 0 && d.invoice.balance_due !== d.invoice.total) {
                  <p class="text-sm tabular-nums" [style.color]="'var(--color-danger)'">Balance: {{ d.invoice.currency }} {{ d.invoice.balance_due | number:'1.2-2' }}</p>
                }
              </div>
            </div>
            <div class="grid grid-cols-3 gap-4 text-xs" [style.color]="'var(--text-tertiary)'">
              <div><span class="block">Issue Date</span><span [style.color]="'var(--text-primary)'" class="font-medium">{{ d.invoice.issue_date }}</span></div>
              <div><span class="block">Due Date</span><span [style.color]="'var(--text-primary)'" class="font-medium">{{ d.invoice.due_date }}</span></div>
              <div><span class="block">Client</span><span [style.color]="'var(--text-primary)'" class="font-medium">{{ d.invoice.client_id?.substring(0,8) }}...</span></div>
            </div>
          </div>

          <!-- Line items table -->
          <div class="card p-5">
            <h3 class="text-sm font-semibold mb-3" [style.color]="'var(--text-primary)'">Line Items</h3>
            <table class="w-full text-sm">
              <thead>
                <tr class="border-b" [style.borderColor]="'var(--border-default)'">
                  <th class="py-2 text-left font-medium" [style.color]="'var(--text-secondary)'">Description</th>
                  <th class="py-2 text-right font-medium" [style.color]="'var(--text-secondary)'">Qty</th>
                  <th class="py-2 text-right font-medium" [style.color]="'var(--text-secondary)'">Rate</th>
                  <th class="py-2 text-right font-medium" [style.color]="'var(--text-secondary)'">Amount</th>
                </tr>
              </thead>
              <tbody>
                @for (item of d.items; track item.id) {
                  <tr class="border-b last:border-b-0" [style.borderColor]="'var(--border-default)'">
                    <td class="py-2" [style.color]="'var(--text-primary)'">{{ item.description }}</td>
                    <td class="py-2 text-right tabular-nums" [style.color]="'var(--text-secondary)'">{{ item.quantity }}</td>
                    <td class="py-2 text-right tabular-nums" [style.color]="'var(--text-secondary)'">{{ item.unit_price | number:'1.2-2' }}</td>
                    <td class="py-2 text-right tabular-nums font-medium" [style.color]="'var(--text-primary)'">{{ item.amount | number:'1.2-2' }}</td>
                  </tr>
                }
              </tbody>
              <tfoot>
                <tr class="border-t" [style.borderColor]="'var(--border-default)'">
                  <td colspan="3" class="py-2 text-right text-xs" [style.color]="'var(--text-tertiary)'">Subtotal</td>
                  <td class="py-2 text-right tabular-nums" [style.color]="'var(--text-primary)'">{{ d.invoice.subtotal | number:'1.2-2' }}</td>
                </tr>
                <tr>
                  <td colspan="3" class="py-1 text-right text-xs" [style.color]="'var(--text-tertiary)'">VAT ({{ d.invoice.tax_rate }}%)</td>
                  <td class="py-1 text-right tabular-nums" [style.color]="'var(--text-secondary)'">{{ d.invoice.tax_amount | number:'1.2-2' }}</td>
                </tr>
                <tr class="border-t" [style.borderColor]="'var(--border-default)'">
                  <td colspan="3" class="py-2 text-right font-semibold" [style.color]="'var(--text-primary)'">Total</td>
                  <td class="py-2 text-right tabular-nums font-bold text-base" [style.color]="'var(--text-primary)'">{{ d.invoice.currency }} {{ d.invoice.total | number:'1.2-2' }}</td>
                </tr>
              </tfoot>
            </table>
          </div>

          <!-- Payment history -->
          <div class="card p-5">
            <div class="flex items-center justify-between mb-3">
              <h3 class="text-sm font-semibold" [style.color]="'var(--text-primary)'">Payment History</h3>
              @if (!d.invoice.is_paid) {
                <button (click)="showPayment.set(true)" class="btn-primary text-xs py-1.5 px-3 flex items-center gap-1">
                  <lucide-icon [img]="CreditCardIcon" [size]="12" /> Record Payment
                </button>
              }
            </div>
            @for (p of d.payments; track p.id) {
              <div class="flex items-center justify-between py-2 border-b last:border-b-0" [style.borderColor]="'var(--border-default)'">
                <div>
                  <p class="text-sm font-medium" [style.color]="'var(--text-primary)'">{{ p.payment_method_label }}</p>
                  <p class="text-xs" [style.color]="'var(--text-tertiary)'">{{ p.payment_date | date:'mediumDate' }} {{ p.reference ? '• Ref: ' + p.reference : '' }}</p>
                </div>
                <span class="text-sm font-semibold tabular-nums" [style.color]="'var(--color-success)'">+{{ d.invoice.currency }} {{ p.amount | number:'1.2-2' }}</span>
              </div>
            } @empty {
              <p class="text-xs py-3 text-center" [style.color]="'var(--text-tertiary)'">No payments recorded yet</p>
            }
          </div>

          <!-- Record payment form -->
          @if (showPayment()) {
            <div class="card p-5">
              <h3 class="text-sm font-semibold mb-3" [style.color]="'var(--text-primary)'">Record Payment</h3>
              <div class="grid grid-cols-2 gap-3 mb-3">
                <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Amount *</label>
                  <input type="number" [(ngModel)]="payForm.amount" class="input-base w-full" [placeholder]="d.invoice.balance_due" /></div>
                <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Method *</label>
                  <select [(ngModel)]="payForm.payment_method" class="input-base w-full">
                    <option value="bank_transfer">Bank Transfer</option><option value="cash">Cash</option>
                    <option value="pos_card">POS / Card</option><option value="cheque">Cheque</option></select></div>
              </div>
              <div class="mb-3"><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Reference</label>
                <input type="text" [(ngModel)]="payForm.reference" class="input-base w-full" placeholder="Transaction reference" /></div>
              <div class="flex gap-2">
                <button (click)="recordPayment()" class="btn-primary text-sm">Confirm Payment</button>
                <button (click)="showPayment.set(false)" class="btn-secondary text-sm">Cancel</button>
              </div>
            </div>
          }
        </div>

        <!-- Sidebar -->
        <div class="card p-4">
          <h4 class="text-xs font-medium mb-3" [style.color]="'var(--text-tertiary)'">Summary</h4>
          <div class="space-y-3 text-sm">
            <div class="flex justify-between"><span [style.color]="'var(--text-tertiary)'">Subtotal</span><span class="tabular-nums" [style.color]="'var(--text-primary)'">{{ d.invoice.subtotal | number:'1.2-2' }}</span></div>
            <div class="flex justify-between"><span [style.color]="'var(--text-tertiary)'">VAT</span><span class="tabular-nums" [style.color]="'var(--text-primary)'">{{ d.invoice.tax_amount | number:'1.2-2' }}</span></div>
            <div class="flex justify-between border-t pt-2" [style.borderColor]="'var(--border-default)'"><span class="font-semibold" [style.color]="'var(--text-primary)'">Total</span><span class="font-bold tabular-nums" [style.color]="'var(--text-primary)'">{{ d.invoice.total | number:'1.2-2' }}</span></div>
            <div class="flex justify-between"><span [style.color]="'var(--text-tertiary)'">Paid</span><span class="tabular-nums text-emerald-500">{{ d.invoice.amount_paid | number:'1.2-2' }}</span></div>
            <div class="flex justify-between border-t pt-2" [style.borderColor]="'var(--border-default)'"><span class="font-semibold" [style.color]="'var(--color-danger)'">Balance Due</span><span class="font-bold tabular-nums" [style.color]="'var(--color-danger)'">{{ d.invoice.balance_due | number:'1.2-2' }}</span></div>
          </div>
          @if (d.invoice.notes) {
            <div class="mt-4 pt-3 border-t" [style.borderColor]="'var(--border-default)'">
              <p class="text-xs" [style.color]="'var(--text-tertiary)'">Notes</p>
              <p class="text-xs mt-1" [style.color]="'var(--text-secondary)'">{{ d.invoice.notes }}</p>
            </div>
          }
        </div>
      </div>
    }
  `,
})
export class InvoiceDetailComponent implements OnInit {
  private api = inject(ApiService); private route = inject(ActivatedRoute); private toast = inject(ToastService);
  readonly ArrowLeftIcon = ArrowLeft; readonly SendIcon = Send; readonly DownloadIcon = Download;
  readonly CreditCardIcon = CreditCard; readonly CheckCircleIcon = CheckCircle; readonly RefreshCwIcon = RefreshCw;

  readonly detail = signal<any>(null);
  readonly showPayment = signal(false);
  payForm = { amount: 0, payment_method: 'bank_transfer', reference: '' };

  ngOnInit(): void {
    const id = this.route.snapshot.params['id'];
    if (!id) return;
    this.api.get<any>(`/invoices/${id}`).subscribe({ next: res => { if (res.data) this.detail.set(res.data); } });
  }

  sendInvoice(): void {
    this.api.post(`/invoices/${this.detail()?.invoice?.id}/send`, {}).subscribe({
      next: () => { this.toast.success('Invoice sent'); this.ngOnInit(); },
    });
  }

  recordPayment(): void {
    this.api.post(`/invoices/${this.detail()?.invoice?.id}/payment`, this.payForm).subscribe({
      next: () => { this.showPayment.set(false); this.toast.success('Payment recorded'); this.ngOnInit(); },
    });
  }

  exportPdf(): void {
    this.api.get<any>(`/invoices/${this.detail()?.invoice?.id}/export`).subscribe({
      next: () => this.toast.success('PDF export ready'),
    });
  }
}
