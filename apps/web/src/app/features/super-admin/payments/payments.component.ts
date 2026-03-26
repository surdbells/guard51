import { Component } from '@angular/core';
import { LucideAngularModule, CheckCircle, XCircle, Clock, Banknote } from 'lucide-angular';
import { PageHeaderComponent } from '@shared/components/page-header/page-header.component';
import { StatsCardComponent } from '@shared/components/stats-card/stats-card.component';

@Component({
  selector: 'g51-sa-payments',
  standalone: true,
  imports: [LucideAngularModule, PageHeaderComponent, StatsCardComponent],
  template: `
    <g51-page-header title="Payment Confirmation" subtitle="Review and confirm manual bank transfer payments" />

    <div class="grid grid-cols-2 lg:grid-cols-3 gap-4 mb-6 stagger-children">
      <g51-stats-card label="Pending Confirmations" value="3" [icon]="ClockIcon" />
      <g51-stats-card label="Confirmed This Month" value="12" [icon]="CheckCircleIcon" />
      <g51-stats-card label="Total Confirmed" value="₦1.85M" [icon]="BanknoteIcon" />
    </div>

    <div class="space-y-3">
      @for (item of pending; track item.id) {
        <div class="card p-5 card-hover">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
              <h3 class="text-sm font-semibold" [style.color]="'var(--text-primary)'">{{ item.tenant }}</h3>
              <p class="text-xs mt-0.5" [style.color]="'var(--text-secondary)'">{{ item.plan }} • {{ item.amount }} • Ref: {{ item.reference }}</p>
              <p class="text-xs mt-0.5" [style.color]="'var(--text-tertiary)'">Submitted {{ item.date }}</p>
            </div>
            <div class="flex items-center gap-2 shrink-0">
              <button class="btn-primary flex items-center gap-1.5 text-sm py-1.5 px-3">
                <lucide-icon [img]="CheckCircleIcon" [size]="14" /> Confirm
              </button>
              <button class="btn-secondary flex items-center gap-1.5 text-sm py-1.5 px-3" style="color: var(--color-danger)">
                <lucide-icon [img]="XCircleIcon" [size]="14" /> Reject
              </button>
            </div>
          </div>
        </div>
      }
      @empty {
        <div class="card p-12 text-center">
          <p class="text-sm" [style.color]="'var(--text-tertiary)'">No pending payments to confirm.</p>
        </div>
      }
    </div>
  `,
})
export class PaymentsComponent {
  readonly CheckCircleIcon = CheckCircle; readonly XCircleIcon = XCircle;
  readonly ClockIcon = Clock; readonly BanknoteIcon = Banknote;

  pending = [
    { id: '1', tenant: 'Eagle Eye Protection', plan: 'Starter Monthly', amount: '₦25,000', reference: 'TRF-20250320-001', date: '2 hours ago' },
    { id: '2', tenant: 'Royal Shield Ltd', plan: 'Professional Monthly', amount: '₦75,000', reference: 'TRF-20250319-004', date: '1 day ago' },
    { id: '3', tenant: 'Ikeja Community Watch', plan: 'Starter Monthly', amount: '₦25,000', reference: 'TRF-20250318-002', date: '3 days ago' },
  ];
}
