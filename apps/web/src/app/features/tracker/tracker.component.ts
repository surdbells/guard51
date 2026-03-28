import { Component, inject, signal, OnInit, OnDestroy } from '@angular/core';
import { NgClass, DatePipe } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { LucideAngularModule, Navigation, MapPin, Battery, Clock, AlertTriangle, RefreshCw, Search, Filter } from 'lucide-angular';
import { PageHeaderComponent } from '@shared/components/page-header/page-header.component';
import { StatsCardComponent } from '@shared/components/stats-card/stats-card.component';
import { ApiService } from '@core/services/api.service';

@Component({
  selector: 'g51-tracker',
  standalone: true,
  imports: [NgClass, DatePipe, FormsModule, LucideAngularModule, PageHeaderComponent, StatsCardComponent],
  template: `
    <g51-page-header title="Live Tracker" subtitle="Real-time guard locations and geofence monitoring">
      <button (click)="refreshLocations()" class="btn-secondary flex items-center gap-2">
        <lucide-icon [img]="RefreshCwIcon" [size]="16" /> Refresh
      </button>
    </g51-page-header>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6 stagger-children">
      <g51-stats-card label="Guards Online" [value]="stats().online" [icon]="NavigationIcon" />
      <g51-stats-card label="Moving" [value]="stats().moving" [icon]="MapPinIcon" />
      <g51-stats-card label="Geofence Alerts" [value]="stats().geofenceAlerts" [icon]="AlertTriangleIcon" />
      <g51-stats-card label="Idle Alerts" [value]="stats().idleAlerts" [icon]="ClockIcon" />
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
      <!-- Map area -->
      <div class="lg:col-span-2 card overflow-hidden" style="min-height: 500px">
        <div class="h-full flex flex-col items-center justify-center" [style.background]="'var(--surface-muted)'">
          <lucide-icon [img]="MapPinIcon" [size]="48" [style.color]="'var(--text-tertiary)'" />
          <p class="text-sm mt-3 font-medium" [style.color]="'var(--text-secondary)'">Live Map</p>
          <p class="text-xs mt-1 max-w-sm text-center" [style.color]="'var(--text-tertiary)'">
            Google Maps integration renders here. Guard positions update via WebSocket with HTTP polling fallback.
          </p>
          <div class="mt-4 flex flex-wrap gap-2 justify-center">
            @for (guard of guards(); track guard.guard_id) {
              <div class="px-3 py-2 rounded-lg text-xs cursor-pointer transition-colors hover:ring-1 hover:ring-[var(--color-brand-500)]"
                [style.background]="selectedGuardId() === guard.guard_id ? 'var(--color-brand-500)' : 'var(--surface-card)'"
                [style.color]="selectedGuardId() === guard.guard_id ? 'white' : 'var(--text-primary)'"
                (click)="selectGuard(guard.guard_id)">
                <span class="font-medium">{{ guard.first_name }} {{ guard.last_name }}</span>
                <span class="ml-2" [style.color]="selectedGuardId() === guard.guard_id ? 'rgba(255,255,255,.7)' : 'var(--text-tertiary)'">{{ guard.lat?.toFixed(4) }}, {{ guard.lng?.toFixed(4) }}</span>
                @if (guard.battery_level) {
                  <span class="ml-2 flex items-center gap-0.5 inline-flex" [style.color]="guard.battery_level < 20 ? 'var(--color-danger)' : selectedGuardId() === guard.guard_id ? 'rgba(255,255,255,.7)' : 'var(--text-tertiary)'">
                    <lucide-icon [img]="BatteryIcon" [size]="10" /> {{ guard.battery_level }}%
                  </span>
                }
              </div>
            }
          </div>

          <!-- Path replay panel (when guard selected) -->
          @if (selectedGuardId()) {
            <div class="mt-4 p-3 rounded-lg" [style.background]="'var(--surface-card)'" [style.borderLeft]="'3px solid var(--color-brand-500)'">
              <div class="flex items-center justify-between mb-2">
                <h4 class="text-sm font-semibold" [style.color]="'var(--text-primary)'">Path Replay</h4>
                <button (click)="selectedGuardId.set(null)" class="text-xs" [style.color]="'var(--text-tertiary)'">Close</button>
              </div>
              @if (pathPoints().length > 0) {
                <p class="text-xs" [style.color]="'var(--text-secondary)'">{{ pathPoints().length }} location points today</p>
                <div class="mt-2 max-h-[120px] overflow-y-auto space-y-1">
                  @for (point of pathPoints().slice(0, 20); track $index) {
                    <div class="text-[10px] flex justify-between" [style.color]="'var(--text-tertiary)'">
                      <span>{{ point.lat?.toFixed(5) }}, {{ point.lng?.toFixed(5) }}</span>
                      <span>{{ point.recorded_at | date:'shortTime' }}</span>
                    </div>
                  }
                </div>
              } @else {
                <p class="text-xs" [style.color]="'var(--text-tertiary)'">Loading path data...</p>
              }
            </div>
          }
          </div>
        </div>
      </div>

      <!-- Alert sidebar -->
      <div class="space-y-4">
        <!-- Filters -->
        <div class="card p-4">
          <h3 class="text-sm font-semibold mb-3" [style.color]="'var(--text-primary)'">Filters</h3>
          <div class="space-y-2">
            <select class="input-base w-full text-sm" [(ngModel)]="filterSite" (ngModelChange)="refreshLocations()">
              <option value="">All Sites</option>
            </select>
            <select class="input-base w-full text-sm" [(ngModel)]="filterStatus">
              <option value="">All Statuses</option>
              <option value="moving">Moving</option>
              <option value="stationary">Stationary</option>
            </select>
          </div>
        </div>

        <!-- Geofence Alerts -->
        <div class="card p-4">
          <h3 class="text-sm font-semibold mb-3 flex items-center gap-2" [style.color]="'var(--text-primary)'">
            <lucide-icon [img]="AlertTriangleIcon" [size]="14" class="text-amber-500" /> Geofence Alerts
          </h3>
          @for (alert of geofenceAlerts(); track alert.id) {
            <div class="py-2 border-b last:border-b-0" [style.borderColor]="'var(--border-default)'">
              <div class="flex items-center justify-between">
                <span class="text-xs font-medium" [style.color]="'var(--text-primary)'">{{ alert.alert_type_label }}</span>
                <span class="badge text-[9px]" [ngClass]="alert.severity === 'critical' ? 'bg-red-50 text-red-600 dark:bg-red-950 dark:text-red-400' : 'bg-amber-50 text-amber-600'">{{ alert.severity }}</span>
              </div>
              <p class="text-[11px] mt-0.5" [style.color]="'var(--text-tertiary)'">{{ alert.message }}</p>
              @if (!alert.is_acknowledged) {
                <button class="text-[10px] mt-1 font-medium" [style.color]="'var(--color-brand-500)'" (click)="acknowledgeGeofence(alert.id)">Acknowledge</button>
              }
            </div>
          } @empty {
            <p class="text-xs py-2" [style.color]="'var(--text-tertiary)'">No active alerts</p>
          }
        </div>

        <!-- Idle Alerts -->
        <div class="card p-4">
          <h3 class="text-sm font-semibold mb-3 flex items-center gap-2" [style.color]="'var(--text-primary)'">
            <lucide-icon [img]="ClockIcon" [size]="14" class="text-blue-500" /> Idle Alerts
          </h3>
          @for (alert of idleAlerts(); track alert.id) {
            <div class="py-2 border-b last:border-b-0" [style.borderColor]="'var(--border-default)'">
              <p class="text-xs font-medium" [style.color]="'var(--text-primary)'">Idle {{ alert.idle_duration_minutes }} min</p>
              <p class="text-[11px]" [style.color]="'var(--text-tertiary)'">Guard {{ alert.guard_id?.substring(0,8) }}</p>
            </div>
          } @empty {
            <p class="text-xs py-2" [style.color]="'var(--text-tertiary)'">No idle alerts</p>
          }
        </div>

        <!-- Alert History (recent 24h) -->
        <div class="card p-4">
          <h3 class="text-sm font-semibold mb-3" [style.color]="'var(--text-primary)'">Alert History (24h)</h3>
          @for (alert of recentAlerts(); track alert.id) {
            <div class="py-2 border-b last:border-b-0" [style.borderColor]="'var(--border-default)'">
              <div class="flex items-center justify-between">
                <span class="text-xs font-medium" [style.color]="'var(--text-primary)'">{{ alert.alert_type_label }}</span>
                <span class="text-[10px]" [style.color]="'var(--text-tertiary)'">{{ alert.created_at | date:'shortTime' }}</span>
              </div>
              <p class="text-[10px]" [style.color]="alert.is_acknowledged ? 'var(--text-tertiary)' : 'var(--color-warning)'">
                {{ alert.is_acknowledged ? 'Acknowledged' : 'Pending' }}
              </p>
            </div>
          } @empty {
            <p class="text-xs py-2" [style.color]="'var(--text-tertiary)'">No alerts in last 24 hours</p>
          }
        </div>
      </div>
  `,
})
export class TrackerComponent implements OnInit, OnDestroy {
  private api = inject(ApiService);
  readonly NavigationIcon = Navigation; readonly MapPinIcon = MapPin; readonly BatteryIcon = Battery;
  readonly ClockIcon = Clock; readonly AlertTriangleIcon = AlertTriangle; readonly RefreshCwIcon = RefreshCw;
  readonly SearchIcon = Search; readonly FilterIcon = Filter;

  readonly guards = signal<any[]>([]);
  readonly geofenceAlerts = signal<any[]>([]);
  readonly idleAlerts = signal<any[]>([]);
  readonly recentAlerts = signal<any[]>([]);
  readonly selectedGuardId = signal<string | null>(null);
  readonly pathPoints = signal<any[]>([]);
  readonly stats = signal({ online: 0, moving: 0, geofenceAlerts: 0, idleAlerts: 0 });
  filterSite = ''; filterStatus = '';
  private refreshInterval: any;

  ngOnInit(): void { this.refreshLocations(); this.refreshInterval = setInterval(() => this.refreshLocations(), 15000); }
  ngOnDestroy(): void { if (this.refreshInterval) clearInterval(this.refreshInterval); }

  refreshLocations(): void {
    this.api.get<any>('/tracking/live').subscribe({
      next: res => {
        if (res.data) {
          const guards = res.data.guards || [];
          this.guards.set(guards);
          this.stats.update(s => ({ ...s, online: guards.length, moving: guards.filter((g: any) => g.is_moving).length }));
        }
      },
    });
    this.api.get<any>('/tracking/geofence-alerts').subscribe({
      next: res => { if (res.data) { this.geofenceAlerts.set(res.data.alerts || []); this.stats.update(s => ({ ...s, geofenceAlerts: (res.data.alerts || []).length })); } },
    });
    this.api.get<any>('/tracking/idle-alerts').subscribe({
      next: res => { if (res.data) { this.idleAlerts.set(res.data.alerts || []); this.stats.update(s => ({ ...s, idleAlerts: (res.data.alerts || []).length })); } },
    });
    this.api.get<any>('/tracking/geofence-alerts/recent').subscribe({
      next: res => { if (res.data) this.recentAlerts.set(res.data.alerts || []); },
    });
  }

  selectGuard(guardId: string): void {
    if (this.selectedGuardId() === guardId) {
      this.selectedGuardId.set(null);
      this.pathPoints.set([]);
      return;
    }
    this.selectedGuardId.set(guardId);
    this.pathPoints.set([]);
    this.api.get<any>(`/tracking/guard/${guardId}/path`).subscribe({
      next: res => { if (res.data) this.pathPoints.set(res.data.path || []); },
    });
  }

  acknowledgeGeofence(id: string): void {
    this.api.post(`/tracking/geofence-alerts/${id}/acknowledge`, {}).subscribe({ next: () => this.refreshLocations() });
  }
}
