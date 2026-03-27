import { Component, inject, signal, OnInit, OnDestroy } from '@angular/core';
import { NgClass } from '@angular/common';
import { LucideAngularModule, AlertTriangle, MapPin, CheckCircle, Phone, Radio, XCircle, Clock, Volume2 } from 'lucide-angular';
import { PageHeaderComponent } from '@shared/components/page-header/page-header.component';
import { EmptyStateComponent } from '@shared/components/empty-state/empty-state.component';
import { ApiService } from '@core/services/api.service';
import { ToastService } from '@core/services/toast.service';

@Component({
  selector: 'g51-panic',
  standalone: true,
  imports: [NgClass, LucideAngularModule, PageHeaderComponent, EmptyStateComponent],
  template: `
    <g51-page-header title="Panic Alerts" subtitle="Emergency response and alert management" />

    @if (activeAlerts().length > 0) {
      <!-- Active alerts banner -->
      <div class="mb-6 rounded-xl p-4 border-2 border-red-300 dark:border-red-700 animate-pulse" style="background: color-mix(in srgb, var(--color-danger) 8%, transparent)">
        <div class="flex items-center gap-3 mb-2">
          <lucide-icon [img]="AlertTriangleIcon" [size]="24" class="text-red-500" />
          <h2 class="text-lg font-bold text-red-600 dark:text-red-400">{{ activeAlerts().length }} ACTIVE PANIC ALERT(S)</h2>
        </div>
        <p class="text-sm text-red-500">Immediate response required. Guards are in distress.</p>
      </div>

      @for (alert of activeAlerts(); track alert.id) {
        <div class="card p-5 mb-4 border-l-4" style="border-left-color: var(--color-danger)">
          <div class="flex flex-col lg:flex-row lg:items-start justify-between gap-4">
            <div class="flex-1">
              <div class="flex items-center gap-3 mb-2">
                <div class="h-10 w-10 rounded-full bg-red-100 dark:bg-red-950 flex items-center justify-center">
                  <lucide-icon [img]="AlertTriangleIcon" [size]="20" class="text-red-500" />
                </div>
                <div>
                  <h3 class="text-base font-semibold" [style.color]="'var(--text-primary)'">Guard: {{ alert.guard_id.substring(0,8) }}...</h3>
                  <span class="badge text-xs"
                    [ngClass]="alert.status === 'triggered' ? 'bg-red-100 text-red-600 dark:bg-red-950 dark:text-red-400 animate-pulse'
                      : alert.status === 'acknowledged' ? 'bg-amber-50 text-amber-600'
                      : 'bg-blue-50 text-blue-600'">
                    {{ alert.status_label }}
                  </span>
                </div>
              </div>

              <div class="grid grid-cols-2 gap-3 text-sm mb-3">
                <div class="flex items-center gap-2" [style.color]="'var(--text-secondary)'">
                  <lucide-icon [img]="MapPinIcon" [size]="14" /> {{ alert.lat.toFixed(5) }}, {{ alert.lng.toFixed(5) }}
                </div>
                <div class="flex items-center gap-2" [style.color]="'var(--text-secondary)'">
                  <lucide-icon [img]="ClockIcon" [size]="14" /> {{ alert.created_at | date:'shortTime' }}
                </div>
              </div>

              @if (alert.message) {
                <p class="text-sm" [style.color]="'var(--text-secondary)'">{{ alert.message }}</p>
              }
              @if (alert.audio_url) {
                <div class="flex items-center gap-2 mt-2 text-sm" [style.color]="'var(--color-brand-500)'">
                  <lucide-icon [img]="Volume2Icon" [size]="14" /> Voice recording attached
                </div>
              }
            </div>

            <!-- Action buttons -->
            <div class="flex flex-col gap-2 shrink-0 min-w-[160px]">
              @if (alert.status === 'triggered') {
                <button (click)="acknowledge(alert.id)" class="btn-primary w-full flex items-center justify-center gap-1.5 py-2">
                  <lucide-icon [img]="CheckCircleIcon" [size]="14" /> Acknowledge
                </button>
              }
              @if (alert.status === 'acknowledged') {
                <button (click)="markResponding(alert.id)" class="w-full py-2 px-3 text-sm font-medium rounded-[var(--radius-button)] text-white flex items-center justify-center gap-1.5 bg-blue-500 hover:bg-blue-600">
                  <lucide-icon [img]="RadioIcon" [size]="14" /> Mark Responding
                </button>
              }
              @if (alert.status !== 'resolved' && alert.status !== 'false_alarm') {
                <button (click)="resolve(alert.id)" class="btn-secondary w-full flex items-center justify-center gap-1.5 py-2">
                  <lucide-icon [img]="CheckCircleIcon" [size]="14" /> Resolve
                </button>
                <button (click)="falseAlarm(alert.id)" class="text-xs text-center py-1" [style.color]="'var(--text-tertiary)'" style="cursor: pointer">
                  Mark as false alarm
                </button>
              }
            </div>
          </div>
        </div>
      }
    }

    <!-- Recent resolved alerts -->
    <h3 class="text-base font-semibold mt-8 mb-3" [style.color]="'var(--text-primary)'">Recent Alerts (24h)</h3>
    @if (recentAlerts().length > 0) {
      <div class="space-y-2">
        @for (alert of recentAlerts(); track alert.id) {
          <div class="card p-4">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-3">
                <div class="h-8 w-8 rounded-full flex items-center justify-center"
                  [ngClass]="alert.status === 'resolved' ? 'bg-emerald-50 dark:bg-emerald-950' : alert.status === 'false_alarm' ? 'bg-gray-100 dark:bg-gray-800' : 'bg-red-50 dark:bg-red-950'">
                  <lucide-icon [img]="alert.status === 'false_alarm' ? XCircleIcon : CheckCircleIcon" [size]="16"
                    [ngClass]="alert.status === 'resolved' ? 'text-emerald-500' : alert.status === 'false_alarm' ? 'text-gray-400' : 'text-red-500'" />
                </div>
                <div>
                  <p class="text-sm font-medium" [style.color]="'var(--text-primary)'">Guard {{ alert.guard_id.substring(0,8) }}...</p>
                  <p class="text-xs" [style.color]="'var(--text-tertiary)'">{{ alert.created_at }}</p>
                </div>
              </div>
              <span class="badge text-[10px]"
                [ngClass]="alert.status === 'resolved' ? 'bg-emerald-50 text-emerald-600 dark:bg-emerald-950 dark:text-emerald-400' : 'bg-[var(--surface-muted)]'">
                {{ alert.status_label }}
              </span>
            </div>
          </div>
        }
      </div>
    } @else {
      <g51-empty-state title="No Recent Alerts" message="No panic alerts in the last 24 hours." [icon]="CheckCircleIcon" />
    }
  `,
})
export class PanicComponent implements OnInit, OnDestroy {
  private api = inject(ApiService); private toast = inject(ToastService);
  readonly AlertTriangleIcon = AlertTriangle; readonly MapPinIcon = MapPin;
  readonly CheckCircleIcon = CheckCircle; readonly PhoneIcon = Phone;
  readonly RadioIcon = Radio; readonly XCircleIcon = XCircle;
  readonly ClockIcon = Clock; readonly Volume2Icon = Volume2;

  readonly activeAlerts = signal<any[]>([]);
  readonly recentAlerts = signal<any[]>([]);
  private refreshInterval: any;

  ngOnInit(): void { this.loadAlerts(); this.refreshInterval = setInterval(() => this.loadAlerts(), 10000); }
  ngOnDestroy(): void { if (this.refreshInterval) clearInterval(this.refreshInterval); }

  loadAlerts(): void {
    this.api.get<any>('/panic/active').subscribe({ next: res => { if (res.data) this.activeAlerts.set(res.data.alerts || []); } });
    this.api.get<any>('/panic/recent').subscribe({ next: res => { if (res.data) this.recentAlerts.set(res.data.alerts || []); } });
  }

  acknowledge(id: string): void {
    this.api.post(`/panic/${id}/acknowledge`, {}).subscribe({ next: () => { this.toast.success('Alert acknowledged'); this.loadAlerts(); } });
  }
  markResponding(id: string): void {
    this.api.post(`/panic/${id}/responding`, {}).subscribe({ next: () => { this.toast.success('Marked as responding'); this.loadAlerts(); } });
  }
  resolve(id: string): void {
    this.api.post(`/panic/${id}/resolve`, { notes: 'Resolved via admin console' }).subscribe({ next: () => { this.toast.success('Alert resolved'); this.loadAlerts(); } });
  }
  falseAlarm(id: string): void {
    this.api.post(`/panic/${id}/false-alarm`, { notes: 'False alarm confirmed' }).subscribe({ next: () => { this.toast.info('Marked as false alarm'); this.loadAlerts(); } });
  }
}
