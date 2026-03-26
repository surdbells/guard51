import { Component, inject, signal, OnInit } from '@angular/core';
import { LucideAngularModule, Clock, FileText, MapPin, AlertTriangle, CheckCircle, RefreshCw } from 'lucide-angular';
import { AuthStore } from '@core/services/auth.store';
import { ApiService } from '@core/services/api.service';
import { ToastService } from '@core/services/toast.service';

@Component({
  selector: 'g51-guard-portal',
  standalone: true,
  imports: [LucideAngularModule],
  template: `
    <div class="max-w-lg mx-auto py-6 px-4">
      <!-- Header -->
      <div class="text-center mb-6">
        <div class="h-16 w-16 rounded-full mx-auto flex items-center justify-center text-xl font-bold mb-3"
          style="background: var(--color-brand-500); color: var(--text-on-brand)">
          {{ auth.userInitials() }}
        </div>
        <h1 class="text-xl font-semibold" [style.color]="'var(--text-primary)'">{{ auth.user()?.full_name }}</h1>
        <p class="text-sm" [style.color]="'var(--text-secondary)'">Guard Portal</p>
      </div>

      <!-- Clock In/Out -->
      <div class="card p-5 mb-4 text-center">
        <h2 class="text-sm font-medium mb-3" [style.color]="'var(--text-secondary)'">Attendance</h2>
        @if (clockedIn()) {
          <p class="text-xs mb-2" [style.color]="'var(--text-tertiary)'">Clocked in at {{ clockInTime() }}</p>
          <button (click)="clockOut()" class="w-full py-3 rounded-xl text-base font-semibold text-white flex items-center justify-center gap-2" style="background: var(--color-danger)">
            <lucide-icon [img]="ClockIcon" [size]="20" /> Clock Out
          </button>
        } @else {
          <button (click)="clockIn()" class="btn-primary w-full py-3 text-base font-semibold flex items-center justify-center gap-2">
            <lucide-icon [img]="ClockIcon" [size]="20" /> Clock In
          </button>
        }
      </div>

      <!-- Quick Actions Grid -->
      <div class="grid grid-cols-2 gap-3 mb-4">
        <button class="card p-4 card-hover flex flex-col items-center gap-2 text-center">
          <div class="h-10 w-10 rounded-lg flex items-center justify-center" style="background: var(--surface-muted)">
            <lucide-icon [img]="FileTextIcon" [size]="20" [style.color]="'var(--text-secondary)'" />
          </div>
          <span class="text-sm font-medium" [style.color]="'var(--text-primary)'">Submit Report</span>
        </button>

        <button class="card p-4 card-hover flex flex-col items-center gap-2 text-center">
          <div class="h-10 w-10 rounded-lg flex items-center justify-center" style="background: var(--surface-muted)">
            <lucide-icon [img]="MapPinIcon" [size]="20" [style.color]="'var(--text-secondary)'" />
          </div>
          <span class="text-sm font-medium" [style.color]="'var(--text-primary)'">Post Orders</span>
        </button>

        <button class="card p-4 card-hover flex flex-col items-center gap-2 text-center">
          <div class="h-10 w-10 rounded-lg flex items-center justify-center" style="background: var(--surface-muted)">
            <lucide-icon [img]="RefreshCwIcon" [size]="20" [style.color]="'var(--text-secondary)'" />
          </div>
          <span class="text-sm font-medium" [style.color]="'var(--text-primary)'">Shift Swap</span>
        </button>

        <button (click)="triggerPanic()" class="card p-4 flex flex-col items-center gap-2 text-center border-red-200 dark:border-red-800 hover:bg-red-50 dark:hover:bg-red-950 transition-colors">
          <div class="h-10 w-10 rounded-lg flex items-center justify-center bg-red-50 dark:bg-red-950">
            <lucide-icon [img]="AlertTriangleIcon" [size]="20" class="text-red-500" />
          </div>
          <span class="text-sm font-medium text-red-600 dark:text-red-400">Panic Button</span>
        </button>
      </div>

      <!-- My Schedule -->
      <div class="card p-5">
        <h2 class="text-sm font-semibold mb-3" [style.color]="'var(--text-primary)'">Upcoming Shifts</h2>
        <div class="space-y-2">
          @for (shift of upcomingShifts; track shift.id) {
            <div class="flex items-center justify-between py-2 border-b last:border-b-0" [style.borderColor]="'var(--border-default)'">
              <div>
                <p class="text-sm font-medium" [style.color]="'var(--text-primary)'">{{ shift.site }}</p>
                <p class="text-xs" [style.color]="'var(--text-tertiary)'">{{ shift.date }} • {{ shift.time }}</p>
              </div>
              <lucide-icon [img]="CheckCircleIcon" [size]="16" class="text-emerald-500" />
            </div>
          }
        </div>
      </div>
    </div>
  `,
})
export class GuardPortalComponent implements OnInit {
  readonly auth = inject(AuthStore);
  private api = inject(ApiService);
  private toast = inject(ToastService);

  readonly ClockIcon = Clock; readonly FileTextIcon = FileText; readonly MapPinIcon = MapPin;
  readonly AlertTriangleIcon = AlertTriangle; readonly CheckCircleIcon = CheckCircle; readonly RefreshCwIcon = RefreshCw;

  readonly clockedIn = signal(false);
  readonly clockInTime = signal('');

  upcomingShifts = [
    { id: '1', site: 'Lekki Phase 1 Estate', date: 'Today', time: '6:00 AM - 6:00 PM' },
    { id: '2', site: 'Lekki Phase 1 Estate', date: 'Tomorrow', time: '6:00 AM - 6:00 PM' },
    { id: '3', site: 'Victoria Island HQ', date: 'Wed, 28 Mar', time: '6:00 PM - 6:00 AM' },
  ];

  ngOnInit(): void {}

  clockIn(): void {
    this.clockedIn.set(true);
    this.clockInTime.set(new Date().toLocaleTimeString('en-NG', { hour: '2-digit', minute: '2-digit' }));
    this.toast.success('Clocked In', 'Your attendance has been recorded.');
  }

  clockOut(): void {
    this.clockedIn.set(false);
    this.toast.success('Clocked Out', 'Your shift has ended.');
  }

  triggerPanic(): void {
    this.toast.error('Panic Alert Sent', 'Dispatch has been notified of your emergency.');
  }
}
