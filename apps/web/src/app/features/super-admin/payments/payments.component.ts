import { Component, inject, signal, OnInit } from '@angular/core';
import { NgClass } from '@angular/common';
import { LucideAngularModule, CreditCard, CheckCircle } from 'lucide-angular';
import { PageHeaderComponent } from '@shared/components/page-header/page-header.component';
import { LoadingSpinnerComponent } from '@shared/components/loading-spinner/loading-spinner.component';
import { EmptyStateComponent } from '@shared/components/empty-state/empty-state.component';
import { ApiService } from '@core/services/api.service';
import { ToastService } from '@core/services/toast.service';

@Component({
  selector: 'g51-sa-payments',
  standalone: true,
  imports: [NgClass, LucideAngularModule, PageHeaderComponent, LoadingSpinnerComponent, EmptyStateComponent],
  template: `
    <g51-page-header title="Pending Payments" subtitle="Bank transfer confirmations" />
    @if (loading()) { <g51-loading /> }
    @else if (!payments().length) { <g51-empty-state title="No Pending Payments" message="All payments are up to date." [icon]="CreditCardIcon" /> }
    @else {
      <div class="space-y-2">
        @for (p of payments(); track p.id) {
          <div class="card p-4">
            <div class="flex items-center justify-between">
              <div><p class="text-sm font-semibold" [style.color]="'var(--text-primary)'">{{ p.tenant_name || 'Tenant' }}</p>
                <p class="text-xs" [style.color]="'var(--text-tertiary)'">₦{{ p.amount }} · {{ p.payment_method }} · {{ p.created_at }}</p></div>
              <button (click)="confirm(p)" class="btn-primary text-xs py-1 px-3 flex items-center gap-1"><lucide-icon [img]="CheckIcon" [size]="12" /> Confirm</button>
            </div>
          </div>
        }
      </div>
    }
  `,
})
export class PaymentsComponent implements OnInit {
  private api = inject(ApiService); private toast = inject(ToastService);
  readonly CreditCardIcon = CreditCard; readonly CheckIcon = CheckCircle;
  readonly payments = signal<any[]>([]); readonly loading = signal(true);
  ngOnInit(): void { this.api.get<any>('/admin/subscriptions/pending').subscribe({ next: res => { this.payments.set(res.data?.payments || res.data || []); this.loading.set(false); }, error: () => this.loading.set(false) }); }
  confirm(p: any): void { this.api.post(`/admin/subscriptions/${p.id}/confirm-payment`, {}).subscribe({ next: () => { this.toast.success('Payment confirmed'); this.ngOnInit(); } }); }
}
