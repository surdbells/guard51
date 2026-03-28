import { Component, inject, signal, OnInit } from '@angular/core';
import { NgClass, DatePipe, CurrencyPipe } from '@angular/common';
import { LucideAngularModule, CreditCard, ArrowUpCircle, Receipt, CheckCircle } from 'lucide-angular';
import { PageHeaderComponent } from '@shared/components/page-header/page-header.component';
import { StatsCardComponent } from '@shared/components/stats-card/stats-card.component';
import { ApiService } from '@core/services/api.service';
import { ToastService } from '@core/services/toast.service';

@Component({
  selector: 'g51-billing',
  standalone: true,
  imports: [NgClass, DatePipe, CurrencyPipe, LucideAngularModule, PageHeaderComponent, StatsCardComponent],
  template: `
    <g51-page-header title="Billing & Subscription" subtitle="Manage your plan, payments, and invoices" />
    @if (subscription(); as sub) {
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
        <div class="card p-5 lg:col-span-2">
          <div class="flex items-center justify-between mb-4">
            <div><h3 class="text-sm font-semibold" [style.color]="'var(--text-primary)'">Current Plan</h3>
              <p class="text-2xl font-bold mt-1" [style.color]="'var(--color-brand-500)'">{{ sub.plan_name }}</p>
              <p class="text-xs" [style.color]="'var(--text-tertiary)'">{{ sub.tier_label }} tier • {{ sub.status_label }}</p></div>
            <button class="btn-primary flex items-center gap-2"><lucide-icon [img]="ArrowUpCircleIcon" [size]="16" /> Upgrade Plan</button>
          </div>
          <div class="grid grid-cols-3 gap-3">
            <div class="p-3 rounded-lg" [style.background]="'var(--surface-muted)'">
              <p class="text-[10px]" [style.color]="'var(--text-tertiary)'">Guards</p>
              <p class="text-sm font-bold" [style.color]="'var(--text-primary)'">{{ sub.guard_count || 0 }} / {{ sub.max_guards || 'Unlimited' }}</p></div>
            <div class="p-3 rounded-lg" [style.background]="'var(--surface-muted)'">
              <p class="text-[10px]" [style.color]="'var(--text-tertiary)'">Sites</p>
              <p class="text-sm font-bold" [style.color]="'var(--text-primary)'">{{ sub.site_count || 0 }} / {{ sub.max_sites || 'Unlimited' }}</p></div>
            <div class="p-3 rounded-lg" [style.background]="'var(--surface-muted)'">
              <p class="text-[10px]" [style.color]="'var(--text-tertiary)'">Next Billing</p>
              <p class="text-sm font-bold" [style.color]="'var(--text-primary)'">{{ sub.next_billing_date }}</p></div>
          </div>
        </div>
        <div class="card p-5">
          <h3 class="text-sm font-semibold mb-3" [style.color]="'var(--text-primary)'">Payment Method</h3>
          <div class="p-3 rounded-lg mb-3" [style.background]="'var(--surface-muted)'">
            <lucide-icon [img]="CreditCardIcon" [size]="20" [style.color]="'var(--color-brand-500)'" />
            <p class="text-sm mt-2" [style.color]="'var(--text-primary)'">{{ sub.payment_method || 'Paystack' }}</p>
            <p class="text-[10px]" [style.color]="'var(--text-tertiary)'">Auto-renews monthly</p></div>
          <button class="btn-secondary w-full text-xs">Update Payment</button>
        </div>
      </div>
    }
    <div class="card p-5">
      <h3 class="text-sm font-semibold mb-3" [style.color]="'var(--text-primary)'">Billing History</h3>
      @for (inv of invoices(); track inv.id) {
        <div class="flex items-center justify-between py-2 border-b last:border-b-0" [style.borderColor]="'var(--border-default)'">
          <div><p class="text-sm" [style.color]="'var(--text-primary)'">{{ inv.description }}</p>
            <p class="text-[10px]" [style.color]="'var(--text-tertiary)'">{{ inv.date }}</p></div>
          <div class="flex items-center gap-3">
            <span class="text-sm font-mono" [style.color]="'var(--text-primary)'">{{ inv.amount }}</span>
            <span class="badge text-[10px] bg-emerald-50 text-emerald-600 dark:bg-emerald-950 dark:text-emerald-400">{{ inv.status }}</span>
          </div>
        </div>
      } @empty { <p class="text-xs text-center py-4" [style.color]="'var(--text-tertiary)'">No billing history yet</p> }
    </div>
  `,
})
export class BillingComponent implements OnInit {
  private api = inject(ApiService);
  readonly CreditCardIcon = CreditCard; readonly ArrowUpCircleIcon = ArrowUpCircle;
  readonly subscription = signal<any>(null);
  readonly invoices = signal<any[]>([]);
  ngOnInit(): void {
    this.api.get<any>('/subscriptions/current').subscribe({ next: r => { if (r.data) this.subscription.set(r.data); } });
    this.api.get<any>('/subscriptions/invoices').subscribe({ next: r => { if (r.data) this.invoices.set(r.data.invoices || []); } });
  }
}
