import { Component, inject, signal, OnInit } from '@angular/core';
import { NgClass, DecimalPipe } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { LucideAngularModule, CreditCard, ArrowUpCircle, Receipt, CheckCircle, Zap } from 'lucide-angular';
import { PageHeaderComponent } from '@shared/components/page-header/page-header.component';
import { StatsCardComponent } from '@shared/components/stats-card/stats-card.component';
import { ModalComponent } from '@shared/components/modal/modal.component';
import { LoadingSpinnerComponent } from '@shared/components/loading-spinner/loading-spinner.component';
import { ApiService } from '@core/services/api.service';
import { ToastService } from '@core/services/toast.service';

@Component({
  selector: 'g51-billing',
  standalone: true,
  imports: [NgClass, DecimalPipe, FormsModule, LucideAngularModule, PageHeaderComponent, StatsCardComponent, ModalComponent, LoadingSpinnerComponent],
  template: `
    <g51-page-header title="Billing & Subscription" subtitle="Manage your plan, payments, and invoices" />
    @if (loading()) { <g51-loading /> } @else {
      @if (subscription(); as sub) {
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
          <div class="card p-5 lg:col-span-2">
            <div class="flex items-center justify-between mb-4">
              <div><h3 class="text-sm font-semibold" [style.color]="'var(--text-primary)'">Current Plan</h3>
                <p class="text-2xl font-bold mt-1" [style.color]="'var(--color-brand-500)'">{{ sub.plan_name || 'No Plan' }}</p>
                <p class="text-xs" [style.color]="'var(--text-tertiary)'">{{ sub.tier || 'starter' }} tier · {{ sub.status }}</p>
              </div>
              <button (click)="showPlans.set(true)" class="btn-primary flex items-center gap-2"><lucide-icon [img]="ArrowUpCircleIcon" [size]="16" /> Change Plan</button>
            </div>
            <div class="grid grid-cols-3 gap-3">
              <div class="p-3 rounded-lg" [style.background]="'var(--surface-muted)'">
                <p class="text-[10px]" [style.color]="'var(--text-tertiary)'">Guards</p>
                <p class="text-sm font-bold" [style.color]="'var(--text-primary)'">{{ sub.guard_count || 0 }} / {{ sub.max_guards || '∞' }}</p></div>
              <div class="p-3 rounded-lg" [style.background]="'var(--surface-muted)'">
                <p class="text-[10px]" [style.color]="'var(--text-tertiary)'">Sites</p>
                <p class="text-sm font-bold" [style.color]="'var(--text-primary)'">{{ sub.site_count || 0 }} / {{ sub.max_sites || '∞' }}</p></div>
              <div class="p-3 rounded-lg" [style.background]="'var(--surface-muted)'">
                <p class="text-[10px]" [style.color]="'var(--text-tertiary)'">Period</p>
                <p class="text-sm font-bold" [style.color]="'var(--text-primary)'">{{ sub.current_period_end || 'N/A' }}</p></div>
            </div>
          </div>
          <div class="card p-5">
            <h3 class="text-sm font-semibold mb-3" [style.color]="'var(--text-primary)'">Payment Method</h3>
            <p class="text-sm" [style.color]="'var(--text-secondary)'">{{ sub.payment_method || 'Not configured' }}</p>
            <p class="text-xs mt-1" [style.color]="'var(--text-tertiary)'">{{ sub.billing_cycle || 'monthly' }} billing</p>
          </div>
        </div>
      } @else {
        <div class="card p-8 text-center mb-6">
          <lucide-icon [img]="ZapIcon" [size]="32" class="mx-auto mb-3" [style.color]="'var(--color-brand-500)'" />
          <h3 class="text-lg font-bold mb-2" [style.color]="'var(--text-primary)'">Choose a Plan</h3>
          <p class="text-sm mb-4" [style.color]="'var(--text-secondary)'">Select a subscription plan to unlock all features.</p>
          <button (click)="showPlans.set(true)" class="btn-primary">View Plans</button>
        </div>
      }

      <!-- Payment History -->
      <div class="card p-5">
        <h3 class="text-sm font-semibold mb-3" [style.color]="'var(--text-primary)'">Payment History</h3>
        @if (!payments().length) { <p class="text-xs" [style.color]="'var(--text-tertiary)'">No payments recorded yet.</p> }
        @else {
          <div class="space-y-2">
            @for (p of payments(); track p.id) {
              <div class="flex items-center justify-between py-2 border-b" [style.borderColor]="'var(--border-default)'">
                <div><p class="text-sm" [style.color]="'var(--text-primary)'">{{ p.invoice_number || 'Payment' }}</p>
                  <p class="text-xs" [style.color]="'var(--text-tertiary)'">{{ p.period_start }} → {{ p.period_end }}</p></div>
                <div class="text-right">
                  <p class="text-sm font-bold" [style.color]="'var(--text-primary)'">₦{{ p.amount | number:'1.0-0' }}</p>
                  <span class="badge text-[10px]" [ngClass]="p.status === 'paid' ? 'bg-emerald-50 text-emerald-600' : 'bg-amber-50 text-amber-600'">{{ p.status }}</span>
                </div>
              </div>
            }
          </div>
        }
      </div>
    }

    <!-- Plan Selection Modal -->
    <g51-modal [open]="showPlans()" title="Choose a Plan" maxWidth="780px" (closed)="showPlans.set(false)">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @for (plan of plans(); track plan.id) {
          <div class="border rounded-xl p-5 text-center transition-all cursor-pointer hover:shadow-md"
            [ngClass]="selectedPlan() === plan.id ? 'border-2' : ''"
            [style.borderColor]="selectedPlan() === plan.id ? 'var(--color-brand-500)' : 'var(--border-default)'"
            (click)="selectedPlan.set(plan.id)">
            <h4 class="text-sm font-bold mb-1" [style.color]="'var(--text-primary)'">{{ plan.name }}</h4>
            <p class="text-2xl font-bold mb-1" [style.color]="'var(--color-brand-500)'">₦{{ plan.monthly_price | number:'1.0-0' }}<span class="text-xs font-normal" [style.color]="'var(--text-tertiary)'">/mo</span></p>
            <p class="text-[10px] mb-3" [style.color]="'var(--text-tertiary)'">{{ plan.max_guards || '∞' }} guards · {{ plan.max_sites || '∞' }} sites</p>
            @if (selectedPlan() === plan.id) { <lucide-icon [img]="CheckCircleIcon" [size]="20" [style.color]="'var(--color-brand-500)'" /> }
          </div>
        }
      </div>
      <div class="mt-4">
        <label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Payment Method</label>
        <select [(ngModel)]="payMethod" class="input-base w-full">
          <option value="paystack">Paystack (Card)</option><option value="bank_transfer">Bank Transfer</option>
        </select>
      </div>
      <div modal-footer><button (click)="showPlans.set(false)" class="btn-secondary">Cancel</button>
        <button (click)="subscribe()" class="btn-primary" [disabled]="!selectedPlan()">Subscribe</button></div>
    </g51-modal>
  `,
})
export class BillingComponent implements OnInit {
  private api = inject(ApiService); private toast = inject(ToastService);
  readonly CreditCardIcon = CreditCard; readonly ArrowUpCircleIcon = ArrowUpCircle;
  readonly ReceiptIcon = Receipt; readonly CheckCircleIcon = CheckCircle; readonly ZapIcon = Zap;
  readonly loading = signal(true); readonly subscription = signal<any>(null);
  readonly payments = signal<any[]>([]); readonly plans = signal<any[]>([]);
  readonly showPlans = signal(false); readonly selectedPlan = signal('');
  payMethod = 'paystack';

  ngOnInit(): void {
    this.api.get<any>('/subscriptions/current').subscribe({
      next: res => { if (res.data) this.subscription.set(res.data); this.loading.set(false); },
      error: () => this.loading.set(false),
    });
    this.api.get<any>('/subscriptions/invoices').subscribe({
      next: res => this.payments.set(res.data?.invoices || res.data || []),
      error: () => {},
    });
    this.api.get<any>('/subscriptions/plans').subscribe({
      next: res => this.plans.set(res.data?.plans || res.data || []),
      error: () => {},
    });
  }

  subscribe(): void {
    if (!this.selectedPlan()) return;
    if (this.payMethod === 'paystack') {
      this.api.post('/subscriptions/initialize', { plan_id: this.selectedPlan() }).subscribe({
        next: (res: any) => {
          if (res.data?.authorization_url) {
            window.location.href = res.data.authorization_url;
          } else { this.toast.success('Subscription initiated'); this.showPlans.set(false); this.ngOnInit(); }
        },
      });
    } else {
      this.api.post('/subscriptions/bank-transfer', { plan_id: this.selectedPlan(), billing_cycle: 'monthly' }).subscribe({
        next: () => { this.toast.success('Bank transfer initiated. Upload proof of payment.'); this.showPlans.set(false); this.ngOnInit(); },
      });
    }
  }
}
