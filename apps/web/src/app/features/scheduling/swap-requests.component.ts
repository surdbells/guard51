import { Component, inject, signal, OnInit } from '@angular/core';
import { RouterLink } from '@angular/router';
import { NgClass } from '@angular/common';
import { LucideAngularModule, ArrowLeft, ArrowRightLeft, CheckCircle, XCircle, Clock } from 'lucide-angular';
import { PageHeaderComponent } from '@shared/components/page-header/page-header.component';
import { EmptyStateComponent } from '@shared/components/empty-state/empty-state.component';
import { ApiService } from '@core/services/api.service';
import { ToastService } from '@core/services/toast.service';

@Component({
  selector: 'g51-swap-requests',
  standalone: true,
  imports: [RouterLink, NgClass, LucideAngularModule, PageHeaderComponent, EmptyStateComponent],
  template: `
    <g51-page-header title="Shift Swap Requests" subtitle="Review and approve swap requests from guards">
      <a routerLink="/scheduling" class="btn-secondary flex items-center gap-1.5"><lucide-icon [img]="ArrowLeftIcon" [size]="16" /> Back</a>
    </g51-page-header>

    @if (requests().length > 0) {
      <div class="space-y-3">
        @for (r of requests(); track r.id) {
          <div class="card p-5">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
              <div class="flex-1">
                <div class="flex items-center gap-3 mb-2">
                  <div class="flex items-center gap-2">
                    <div class="h-8 w-8 rounded-full flex items-center justify-center text-xs font-semibold"
                      style="background: var(--color-brand-500); color: var(--text-on-brand)">{{ initials(r.requesting_guard_name) }}</div>
                    <span class="text-sm font-medium" [style.color]="'var(--text-primary)'">{{ r.requesting_guard_name }}</span>
                  </div>
                  <lucide-icon [img]="ArrowRightLeftIcon" [size]="16" [style.color]="'var(--text-tertiary)'" />
                  <div class="flex items-center gap-2">
                    <div class="h-8 w-8 rounded-full flex items-center justify-center text-xs font-semibold"
                      style="background: var(--color-accent-500); color: white">{{ initials(r.target_guard_name) }}</div>
                    <span class="text-sm font-medium" [style.color]="'var(--text-primary)'">{{ r.target_guard_name }}</span>
                  </div>
                </div>
                <p class="text-sm" [style.color]="'var(--text-secondary)'">{{ r.reason }}</p>
                <p class="text-xs mt-1" [style.color]="'var(--text-tertiary)'">Shift: {{ r.shift_date }} • {{ r.shift_time }}</p>
              </div>
              <div class="flex items-center gap-2 shrink-0">
                @if (r.status === 'pending') {
                  <button (click)="approve(r.id)" class="btn-primary text-sm py-1.5 px-3 flex items-center gap-1">
                    <lucide-icon [img]="CheckCircleIcon" [size]="14" /> Approve
                  </button>
                  <button (click)="reject(r.id)" class="btn-secondary text-sm py-1.5 px-3 flex items-center gap-1" style="color: var(--color-danger)">
                    <lucide-icon [img]="XCircleIcon" [size]="14" /> Reject
                  </button>
                } @else {
                  <span class="badge text-xs"
                    [ngClass]="r.status === 'approved' ? 'bg-emerald-50 text-emerald-600 dark:bg-emerald-950 dark:text-emerald-400' : 'bg-red-50 text-red-600 dark:bg-red-950 dark:text-red-400'">
                    {{ r.status }}
                  </span>
                }
              </div>
            </div>
          </div>
        }
      </div>
    } @else {
      <g51-empty-state title="No Swap Requests" message="There are no pending shift swap requests to review." [icon]="ClockIcon" />
    }
  `,
})
export class SwapRequestsComponent implements OnInit {
  private api = inject(ApiService); private toast = inject(ToastService);
  readonly ArrowLeftIcon = ArrowLeft; readonly ArrowRightLeftIcon = ArrowRightLeft;
  readonly CheckCircleIcon = CheckCircle; readonly XCircleIcon = XCircle; readonly ClockIcon = Clock;
  readonly requests = signal<any[]>([]);

  // Demo data (will connect to API)
  ngOnInit(): void {
    this.requests.set([
      { id: '1', requesting_guard_name: 'Musa Ibrahim', target_guard_name: 'Chika Nwosu', reason: 'Family emergency - need Friday off', shift_date: '2026-03-28', shift_time: '06:00–18:00', status: 'pending' },
      { id: '2', requesting_guard_name: 'Adebayo Okonkwo', target_guard_name: 'Funmi Adeyemi', reason: 'Medical appointment', shift_date: '2026-03-29', shift_time: '18:00–06:00', status: 'pending' },
    ]);
    // Also fetch from API
    this.api.get<any>('/swap-requests').subscribe({
      next: res => { if (res.data?.swap_requests?.length) this.requests.set(res.data.swap_requests); },
    });
  }

  approve(id: string): void {
    this.api.post(`/swap-requests/${id}/approve`, {}).subscribe({
      next: () => { this.toast.success('Swap approved'); this.requests.update(r => r.map(x => x.id === id ? { ...x, status: 'approved' } : x)); },
    });
  }

  reject(id: string): void {
    this.api.post(`/swap-requests/${id}/reject`, {}).subscribe({
      next: () => { this.toast.success('Swap rejected'); this.requests.update(r => r.map(x => x.id === id ? { ...x, status: 'rejected' } : x)); },
    });
  }

  initials(name: string): string {
    return name?.split(' ').map(w => w[0]).join('').substring(0, 2).toUpperCase() || '??';
  }
}
