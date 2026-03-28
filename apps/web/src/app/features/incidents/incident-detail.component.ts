import { Component, inject, signal, OnInit } from '@angular/core';
import { ActivatedRoute, RouterLink } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { NgClass, DatePipe } from '@angular/common';
import { LucideAngularModule, ArrowLeft, Shield, MapPin, Clock, User, AlertTriangle, CheckCircle, ArrowUpRight } from 'lucide-angular';
import { PageHeaderComponent } from '@shared/components/page-header/page-header.component';
import { ApiService } from '@core/services/api.service';
import { ToastService } from '@core/services/toast.service';

@Component({
  selector: 'g51-incident-detail',
  standalone: true,
  imports: [RouterLink, FormsModule, NgClass, DatePipe, LucideAngularModule, PageHeaderComponent],
  template: `
    <g51-page-header [title]="incident()?.title || 'Incident'" subtitle="Incident detail and response timeline">
      <a routerLink="/incidents" class="btn-secondary flex items-center gap-1.5"><lucide-icon [img]="ArrowLeftIcon" [size]="16" /> Back</a>
    </g51-page-header>

    @if (incident(); as inc) {
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <!-- Main content -->
        <div class="lg:col-span-2 space-y-4">
          <div class="card p-5">
            <div class="flex items-center gap-2 mb-3">
              <span class="badge"
                [ngClass]="inc.severity === 'critical' ? 'bg-red-50 text-red-600 dark:bg-red-950 dark:text-red-400'
                  : inc.severity === 'high' ? 'bg-amber-50 text-amber-600' : 'bg-[var(--surface-muted)]'">{{ inc.severity_label }}</span>
              <span class="badge bg-[var(--surface-muted)]">{{ inc.incident_type_label }}</span>
              <span class="badge"
                [ngClass]="inc.is_active ? 'bg-amber-50 text-amber-600' : 'bg-emerald-50 text-emerald-600 dark:bg-emerald-950 dark:text-emerald-400'">{{ inc.status_label }}</span>
            </div>
            <p class="text-sm" [style.color]="'var(--text-secondary)'">{{ inc.description }}</p>
            <div class="grid grid-cols-2 gap-3 mt-4 text-xs" [style.color]="'var(--text-tertiary)'">
              @if (inc.location_detail) {
                <div class="flex items-center gap-1"><lucide-icon [img]="MapPinIcon" [size]="12" /> {{ inc.location_detail }}</div>
              }
              <div class="flex items-center gap-1"><lucide-icon [img]="ClockIcon" [size]="12" /> Reported {{ inc.reported_at | date:'medium' }}</div>
              @if (inc.occurred_at) {
                <div class="flex items-center gap-1"><lucide-icon [img]="ClockIcon" [size]="12" /> Occurred {{ inc.occurred_at | date:'medium' }}</div>
              }
              <div class="flex items-center gap-1"><lucide-icon [img]="UserIcon" [size]="12" /> Guard: {{ inc.guard_id?.substring(0,8) }}...</div>
            </div>
          </div>

          @if (inc.resolution) {
            <div class="card p-5 bg-emerald-50 dark:bg-emerald-950">
              <h4 class="text-sm font-semibold text-emerald-700 dark:text-emerald-400 mb-1">Resolution</h4>
              <p class="text-sm text-emerald-600 dark:text-emerald-500">{{ inc.resolution }}</p>
            </div>
          }

          <!-- Actions -->
          @if (inc.is_active) {
            <div class="card p-4 flex flex-wrap gap-2">
              @if (inc.status === 'reported') {
                <button (click)="updateStatus('acknowledged')" class="btn-secondary text-sm">Acknowledge</button>
              }
              @if (inc.status === 'acknowledged') {
                <button (click)="updateStatus('investigating')" class="btn-secondary text-sm">Start Investigation</button>
              }
              <button (click)="showResolve.set(true)" class="btn-primary text-sm">Resolve</button>
              <button (click)="showEscalate.set(true)" class="btn-secondary text-sm" style="color: var(--color-danger)">Escalate</button>
            </div>
          }

          <!-- Resolve form -->
          @if (showResolve()) {
            <div class="card p-4">
              <h4 class="text-sm font-semibold mb-2" [style.color]="'var(--text-primary)'">Resolve Incident</h4>
              <textarea [(ngModel)]="resolveNotes" rows="3" class="input-base w-full resize-none mb-2" placeholder="Resolution details..."></textarea>
              <div class="flex gap-2">
                <button (click)="resolve()" class="btn-primary text-sm">Confirm Resolve</button>
                <button (click)="showResolve.set(false)" class="btn-secondary text-sm">Cancel</button>
              </div>
            </div>
          }

          <!-- Escalate form -->
          @if (showEscalate()) {
            <div class="card p-4">
              <h4 class="text-sm font-semibold mb-2" [style.color]="'var(--text-primary)'">Escalate Incident</h4>
              <textarea [(ngModel)]="escalateReason" rows="2" class="input-base w-full resize-none mb-2" placeholder="Reason for escalation..."></textarea>
              <div class="flex gap-2">
                <button (click)="escalate()" class="btn-primary text-sm" style="background: var(--color-danger)">Confirm Escalation</button>
                <button (click)="showEscalate.set(false)" class="btn-secondary text-sm">Cancel</button>
              </div>
            </div>
          }
        </div>

        <!-- Timeline sidebar -->
        <div class="card p-5">
          <h3 class="text-sm font-semibold mb-4" [style.color]="'var(--text-primary)'">Timeline</h3>
          <div class="relative">
            <div class="absolute left-3 top-0 bottom-0 w-px" [style.background]="'var(--border-default)'"></div>
            <div class="space-y-4">
              <!-- Reported -->
              <div class="flex gap-3 relative">
                <div class="h-6 w-6 rounded-full flex items-center justify-center shrink-0 z-10 bg-blue-100 dark:bg-blue-950">
                  <lucide-icon [img]="AlertTriangleIcon" [size]="12" class="text-blue-500" />
                </div>
                <div>
                  <p class="text-xs font-medium" [style.color]="'var(--text-primary)'">Reported</p>
                  <p class="text-[10px]" [style.color]="'var(--text-tertiary)'">{{ inc.reported_at | date:'short' }}</p>
                </div>
              </div>

              @for (esc of escalations(); track esc.id) {
                <div class="flex gap-3 relative">
                  <div class="h-6 w-6 rounded-full flex items-center justify-center shrink-0 z-10 bg-red-100 dark:bg-red-950">
                    <lucide-icon [img]="ArrowUpRightIcon" [size]="12" class="text-red-500" />
                  </div>
                  <div>
                    <p class="text-xs font-medium" [style.color]="'var(--text-primary)'">Escalated</p>
                    <p class="text-[10px]" [style.color]="'var(--text-secondary)'">{{ esc.reason }}</p>
                    <p class="text-[10px]" [style.color]="'var(--text-tertiary)'">{{ esc.escalated_at | date:'short' }}</p>
                  </div>
                </div>
              }

              @if (inc.resolved_at) {
                <div class="flex gap-3 relative">
                  <div class="h-6 w-6 rounded-full flex items-center justify-center shrink-0 z-10 bg-emerald-100 dark:bg-emerald-950">
                    <lucide-icon [img]="CheckCircleIcon" [size]="12" class="text-emerald-500" />
                  </div>
                  <div>
                    <p class="text-xs font-medium" [style.color]="'var(--text-primary)'">Resolved</p>
                    <p class="text-[10px]" [style.color]="'var(--text-tertiary)'">{{ inc.resolved_at | date:'short' }}</p>
                  </div>
                </div>
              }
            </div>
          </div>
        </div>
      </div>
    }
  `,
})
export class IncidentDetailComponent implements OnInit {
  private api = inject(ApiService);
  private route = inject(ActivatedRoute);
  private toast = inject(ToastService);
  readonly ArrowLeftIcon = ArrowLeft; readonly ShieldIcon = Shield; readonly MapPinIcon = MapPin;
  readonly ClockIcon = Clock; readonly UserIcon = User; readonly AlertTriangleIcon = AlertTriangle;
  readonly CheckCircleIcon = CheckCircle; readonly ArrowUpRightIcon = ArrowUpRight;

  readonly incident = signal<any>(null);
  readonly escalations = signal<any[]>([]);
  readonly showResolve = signal(false);
  readonly showEscalate = signal(false);
  resolveNotes = '';
  escalateReason = '';

  ngOnInit(): void {
    const id = this.route.snapshot.params['id'];
    if (!id) return;
    // Load from list data or fetch. For now, use the incidents API
    this.api.get<any>(`/incidents`).subscribe({
      next: res => {
        const all = res.data?.incidents || [];
        const found = all.find((i: any) => i.id === id);
        if (found) this.incident.set(found);
      },
    });
    this.api.get<any>(`/incidents/${id}/escalations`).subscribe({
      next: res => { if (res.data) this.escalations.set(res.data.escalations || []); },
    });
  }

  updateStatus(status: string): void {
    const id = this.incident()?.id;
    if (!id) return;
    this.api.post(`/incidents/${id}/status`, { status }).subscribe({
      next: () => { this.toast.success(`Status updated to ${status}`); this.ngOnInit(); },
    });
  }

  resolve(): void {
    const id = this.incident()?.id;
    if (!id) return;
    this.api.post(`/incidents/${id}/resolve`, { resolution: this.resolveNotes }).subscribe({
      next: () => { this.showResolve.set(false); this.toast.success('Incident resolved'); this.ngOnInit(); },
    });
  }

  escalate(): void {
    const id = this.incident()?.id;
    if (!id) return;
    this.api.post(`/incidents/${id}/escalate`, { reason: this.escalateReason, escalated_to: '' }).subscribe({
      next: () => { this.showEscalate.set(false); this.toast.success('Incident escalated'); this.ngOnInit(); },
    });
  }
}
