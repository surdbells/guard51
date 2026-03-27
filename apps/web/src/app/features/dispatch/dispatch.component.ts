import { Component, inject, signal, OnInit, OnDestroy } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { NgClass } from '@angular/common';
import { LucideAngularModule, Phone, Plus, MapPin, Users, Clock, Radio, CheckCircle } from 'lucide-angular';
import { PageHeaderComponent } from '@shared/components/page-header/page-header.component';
import { StatsCardComponent } from '@shared/components/stats-card/stats-card.component';
import { BarChartComponent, BarChartData } from '@shared/components/charts/bar-chart.component';
import { ModalComponent } from '@shared/components/modal/modal.component';
import { EmptyStateComponent } from '@shared/components/empty-state/empty-state.component';
import { ApiService } from '@core/services/api.service';
import { ToastService } from '@core/services/toast.service';

@Component({
  selector: 'g51-dispatch',
  standalone: true,
  imports: [FormsModule, NgClass, LucideAngularModule, PageHeaderComponent, StatsCardComponent, BarChartComponent, ModalComponent, EmptyStateComponent],
  template: `
    <g51-page-header title="Dispatch Console" subtitle="Call logging, guard assignment, and response tracking">
      <button (click)="showNewCall.set(true)" class="btn-primary flex items-center gap-2">
        <lucide-icon [img]="PlusIcon" [size]="16" /> New Call
      </button>
    </g51-page-header>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6 stagger-children">
      <g51-stats-card label="Active Calls" [value]="stats().active" [icon]="PhoneIcon" />
      <g51-stats-card label="Guards Available" [value]="stats().guardsAvailable" [icon]="UsersIcon" />
      <g51-stats-card label="Avg Response (min)" [value]="stats().avgResponseMin" [icon]="ClockIcon" />
      <g51-stats-card label="Resolved (24h)" [value]="stats().resolved24h" [icon]="CheckCircleIcon" />
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
      <!-- Active calls queue -->
      <div class="lg:col-span-2">
        <div class="card p-5">
          <h3 class="text-sm font-semibold mb-3" [style.color]="'var(--text-primary)'">Active Calls</h3>
          @for (call of activeCalls(); track call.id) {
            <div class="py-3 border-b last:border-b-0" [style.borderColor]="'var(--border-default)'">
              <div class="flex items-start justify-between">
                <div>
                  <div class="flex items-center gap-2 mb-1">
                    <span class="text-sm font-semibold" [style.color]="'var(--text-primary)'">{{ call.caller_name }}</span>
                    <span class="badge text-[10px]"
                      [ngClass]="call.priority === 'critical' ? 'bg-red-50 text-red-600 dark:bg-red-950 dark:text-red-400'
                        : call.priority === 'high' ? 'bg-amber-50 text-amber-600' : 'bg-[var(--surface-muted)]'">{{ call.priority_label }}</span>
                    <span class="badge text-[10px] bg-blue-50 text-blue-600">{{ call.call_type_label }}</span>
                  </div>
                  <p class="text-xs" [style.color]="'var(--text-secondary)'">{{ call.description }}</p>
                  @if (call.response_time_minutes !== null) {
                    <p class="text-[10px] mt-1" [style.color]="'var(--text-tertiary)'">Response: {{ call.response_time_minutes }} min</p>
                  }
                </div>
                <div class="flex gap-1.5 shrink-0 ml-3">
                  @if (call.status === 'received') {
                    <button class="btn-primary text-xs py-1 px-2.5">Assign Guard</button>
                  }
                  @if (call.is_active) {
                    <button class="btn-secondary text-xs py-1 px-2.5">Resolve</button>
                  }
                </div>
              </div>
            </div>
          } @empty {
            <p class="text-sm py-6 text-center" [style.color]="'var(--text-tertiary)'">No active calls. All quiet.</p>
          }
        </div>
      </div>

      <!-- Nearest guards / response times -->
      <div class="space-y-4">
        <div class="card p-4">
          <h3 class="text-sm font-semibold mb-3 flex items-center gap-2" [style.color]="'var(--text-primary)'">
            <lucide-icon [img]="MapPinIcon" [size]="14" /> Nearest Guards
          </h3>
          @for (g of nearestGuards; track g.name) {
            <div class="flex items-center justify-between py-2 border-b last:border-b-0" [style.borderColor]="'var(--border-default)'">
              <div>
                <p class="text-xs font-medium" [style.color]="'var(--text-primary)'">{{ g.name }}</p>
                <p class="text-[10px]" [style.color]="'var(--text-tertiary)'">{{ g.site }} • {{ g.distance }}</p>
              </div>
              <button class="text-[10px] font-medium" [style.color]="'var(--color-brand-500)'">Assign</button>
            </div>
          }
        </div>

        <div class="card p-4">
          <h3 class="text-sm font-semibold mb-3" [style.color]="'var(--text-primary)'">Response Times</h3>
          <g51-bar-chart [data]="responseData" [height]="160" />
        </div>
      </div>
    </div>

    <!-- Recent dispatch history -->
    <div class="card p-5">
      <h3 class="text-sm font-semibold mb-3" [style.color]="'var(--text-primary)'">Recent History (24h)</h3>
      <div class="space-y-2">
        @for (call of recentCalls(); track call.id) {
          <div class="flex items-center justify-between py-2 border-b last:border-b-0" [style.borderColor]="'var(--border-default)'">
            <div>
              <p class="text-xs font-medium" [style.color]="'var(--text-primary)'">{{ call.caller_name }} — {{ call.call_type_label }}</p>
              <p class="text-[10px]" [style.color]="'var(--text-tertiary)'">{{ call.received_at }}</p>
            </div>
            <span class="badge text-[10px]"
              [ngClass]="call.status === 'resolved' ? 'bg-emerald-50 text-emerald-600 dark:bg-emerald-950 dark:text-emerald-400' : 'bg-[var(--surface-muted)]'">{{ call.status_label }}</span>
          </div>
        }
      </div>
    </div>

    <!-- New call modal -->
    <g51-modal [open]="showNewCall()" title="Log New Call" maxWidth="520px" (closed)="showNewCall.set(false)">
      <div class="space-y-3">
        <div class="grid grid-cols-2 gap-3">
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Caller Name *</label>
            <input type="text" [(ngModel)]="callForm.caller_name" class="input-base w-full" /></div>
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Phone</label>
            <input type="tel" [(ngModel)]="callForm.caller_phone" class="input-base w-full" /></div>
        </div>
        <div class="grid grid-cols-2 gap-3">
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Type *</label>
            <select [(ngModel)]="callForm.call_type" class="input-base w-full">
              <option value="emergency">Emergency</option><option value="routine">Routine</option>
              <option value="complaint">Complaint</option><option value="information">Information</option></select></div>
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Priority *</label>
            <select [(ngModel)]="callForm.priority" class="input-base w-full">
              <option value="low">Low</option><option value="medium">Medium</option>
              <option value="high">High</option><option value="critical">Critical</option></select></div>
        </div>
        <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Description *</label>
          <textarea [(ngModel)]="callForm.description" rows="3" class="input-base w-full resize-none" placeholder="Call details..."></textarea></div>
      </div>
      <div modal-footer>
        <button (click)="showNewCall.set(false)" class="btn-secondary">Cancel</button>
        <button (click)="onCreateCall()" class="btn-primary">Log Call</button>
      </div>
    </g51-modal>
  `,
})
export class DispatchComponent implements OnInit, OnDestroy {
  private api = inject(ApiService); private toast = inject(ToastService);
  readonly PhoneIcon = Phone; readonly PlusIcon = Plus; readonly MapPinIcon = MapPin;
  readonly UsersIcon = Users; readonly ClockIcon = Clock; readonly RadioIcon = Radio; readonly CheckCircleIcon = CheckCircle;
  readonly showNewCall = signal(false);
  readonly activeCalls = signal<any[]>([]);
  readonly recentCalls = signal<any[]>([]);
  readonly stats = signal({ active: 0, guardsAvailable: 12, avgResponseMin: 8, resolved24h: 0 });
  callForm = { caller_name: '', caller_phone: '', call_type: 'routine', priority: 'medium', description: '' };
  private refreshInterval: any;

  nearestGuards = [
    { name: 'Musa Ibrahim', site: 'Lekki Phase 1', distance: '0.3 km' },
    { name: 'Chika Nwosu', site: 'V.I. HQ', distance: '1.2 km' },
    { name: 'Adebayo O.', site: 'Ikeja Mall', distance: '4.8 km' },
  ];
  responseData: BarChartData[] = [
    { label: 'Mon', value: 6 }, { label: 'Tue', value: 8 }, { label: 'Wed', value: 5 },
    { label: 'Thu', value: 12 }, { label: 'Fri', value: 7 }, { label: 'Sat', value: 4 }, { label: 'Sun', value: 3 },
  ];

  ngOnInit(): void { this.loadData(); this.refreshInterval = setInterval(() => this.loadData(), 15000); }
  ngOnDestroy(): void { if (this.refreshInterval) clearInterval(this.refreshInterval); }

  loadData(): void {
    this.api.get<any>('/dispatch/active').subscribe({
      next: res => { if (res.data) { this.activeCalls.set(res.data.calls || []); this.stats.update(s => ({ ...s, active: (res.data.calls || []).length })); } },
    });
    this.api.get<any>('/dispatch/recent').subscribe({ next: res => { if (res.data) this.recentCalls.set(res.data.calls || []); } });
  }

  onCreateCall(): void {
    this.api.post('/dispatch', this.callForm).subscribe({
      next: () => { this.showNewCall.set(false); this.toast.success('Call logged'); this.loadData(); },
    });
  }
}
