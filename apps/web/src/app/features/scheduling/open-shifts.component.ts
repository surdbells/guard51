import { Component, inject, signal, OnInit } from '@angular/core';
import { RouterLink } from '@angular/router';
import { LucideAngularModule, ArrowLeft, MapPin, Clock, Hand, CheckCircle } from 'lucide-angular';
import { PageHeaderComponent } from '@shared/components/page-header/page-header.component';
import { EmptyStateComponent } from '@shared/components/empty-state/empty-state.component';
import { ApiService } from '@core/services/api.service';
import { ToastService } from '@core/services/toast.service';

@Component({
  selector: 'g51-open-shifts',
  standalone: true,
  imports: [RouterLink, LucideAngularModule, PageHeaderComponent, EmptyStateComponent],
  template: `
    <g51-page-header title="Open Shifts" subtitle="Available shifts that guards can claim">
      <a routerLink="/scheduling" class="btn-secondary flex items-center gap-1.5"><lucide-icon [img]="ArrowLeftIcon" [size]="16" /> Back</a>
    </g51-page-header>

    @if (shifts().length > 0) {
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
        @for (s of shifts(); track s.id) {
          <div class="card p-4 card-hover border-l-4" style="border-left-color: var(--color-warning)">
            <div class="flex items-start justify-between mb-2">
              <div>
                <h3 class="text-sm font-semibold" [style.color]="'var(--text-primary)'">{{ s.shift_date }}</h3>
                <div class="flex items-center gap-2 mt-1 text-xs" [style.color]="'var(--text-secondary)'">
                  <span class="flex items-center gap-1"><lucide-icon [img]="ClockIcon" [size]="12" /> {{ formatTime(s.start_time) }} — {{ formatTime(s.end_time) }}</span>
                  <span>{{ s.duration_hours }}h</span>
                </div>
              </div>
              <span class="badge bg-amber-50 text-amber-600 dark:bg-amber-950 dark:text-amber-400 text-[10px]">Open</span>
            </div>
            <div class="flex items-center gap-1 text-xs mb-3" [style.color]="'var(--text-tertiary)'">
              <lucide-icon [img]="MapPinIcon" [size]="12" /> {{ s.site_name || s.site_id }}
            </div>
            @if (s.notes) {
              <p class="text-xs mb-3" [style.color]="'var(--text-secondary)'">{{ s.notes }}</p>
            }
            <button (click)="claimShift(s.id)" class="btn-primary w-full text-sm py-2 flex items-center justify-center gap-1.5">
              <lucide-icon [img]="HandIcon" [size]="14" /> Claim This Shift
            </button>
          </div>
        }
      </div>
    } @else {
      <g51-empty-state title="No Open Shifts" message="All shifts are currently assigned. Check back later." [icon]="CheckCircleIcon" />
    }
  `,
})
export class OpenShiftsComponent implements OnInit {
  private api = inject(ApiService); private toast = inject(ToastService);
  readonly ArrowLeftIcon = ArrowLeft; readonly MapPinIcon = MapPin; readonly ClockIcon = Clock;
  readonly HandIcon = Hand; readonly CheckCircleIcon = CheckCircle;
  readonly shifts = signal<any[]>([]);

  ngOnInit(): void {
    this.api.get<any>('/shifts/open').subscribe({
      next: res => { if (res.data) this.shifts.set(res.data.shifts || []); },
    });
  }

  claimShift(shiftId: string): void {
    this.api.post(`/shifts/${shiftId}/claim`, {}).subscribe({
      next: () => {
        this.toast.success('Shift claimed!', 'You have been assigned to this shift.');
        this.shifts.update(s => s.filter(x => x.id !== shiftId));
      },
      error: (err) => this.toast.error('Cannot claim', err.error?.message || 'Failed to claim shift.'),
    });
  }

  formatTime(iso: string): string {
    try { return new Date(iso).toLocaleTimeString('en-NG', { hour: '2-digit', minute: '2-digit' }); }
    catch { return iso; }
  }
}
