import { Component, inject, signal, OnInit } from '@angular/core';
import { RouterLink } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { NgClass } from '@angular/common';
import { LucideAngularModule, Clock, MapPin, FileText, AlertTriangle, LogIn, LogOut, Calendar, Route, BookOpen, Plus, Send } from 'lucide-angular';
import { PageHeaderComponent } from '@shared/components/page-header/page-header.component';
import { ModalComponent } from '@shared/components/modal/modal.component';
import { EmptyStateComponent } from '@shared/components/empty-state/empty-state.component';
import { LoadingSpinnerComponent } from '@shared/components/loading-spinner/loading-spinner.component';
import { ApiService } from '@core/services/api.service';
import { ToastService } from '@core/services/toast.service';
import { AuthStore } from '@core/services/auth.store';

@Component({
  selector: 'g51-guard-portal',
  standalone: true,
  imports: [RouterLink, FormsModule, NgClass, LucideAngularModule, PageHeaderComponent, ModalComponent, EmptyStateComponent, LoadingSpinnerComponent],
  template: `
    <g51-page-header title="My Dashboard" [subtitle]="'Welcome, ' + (auth.user()?.first_name || 'Guard')" />

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
      <div class="card p-4 text-center">
        <lucide-icon [img]="ClockIcon" [size]="20" class="mx-auto mb-2" [style.color]="'var(--color-brand-500)'" />
        @if (clockedIn()) {
          <p class="text-xs font-semibold text-emerald-600 mb-1">Clocked In</p>
          <p class="text-[10px]" [style.color]="'var(--text-tertiary)'">Since {{ clockInTime() }}</p>
          <button (click)="clockOut()" class="btn-danger mt-2 w-full text-xs py-1.5 flex items-center justify-center gap-1"><lucide-icon [img]="LogOutIcon" [size]="12" /> Clock Out</button>
        } @else {
          <p class="text-xs mb-1" [style.color]="'var(--text-secondary)'">Not Clocked In</p>
          <button (click)="clockIn()" class="btn-primary mt-2 w-full text-xs py-1.5 flex items-center justify-center gap-1"><lucide-icon [img]="LogInIcon" [size]="12" /> Clock In</button>
        }
      </div>
      <div class="card p-4 text-center">
        <lucide-icon [img]="MapPinIcon" [size]="20" class="mx-auto mb-2" [style.color]="'var(--color-brand-500)'" />
        <p class="text-xs font-semibold" [style.color]="'var(--text-primary)'">{{ assignedSite() || 'No post' }}</p>
        <p class="text-[10px]" [style.color]="'var(--text-tertiary)'">Current assignment</p>
      </div>
      <div class="card p-4 text-center">
        <p class="text-xl font-bold" [style.color]="'var(--text-primary)'">{{ todayReports() }}</p>
        <p class="text-[10px]" [style.color]="'var(--text-tertiary)'">Reports Today</p>
      </div>
      <div class="card p-4 text-center">
        <p class="text-xl font-bold" [style.color]="'var(--color-danger)'">{{ todayIncidents() }}</p>
        <p class="text-[10px]" [style.color]="'var(--text-tertiary)'">Incidents Today</p>
      </div>
    </div>

    <div class="flex gap-1 mb-4 overflow-x-auto">
      @for (tab of ['My Shifts', 'My Reports', 'My Incidents', 'Passdowns', 'Tours']; track tab) {
        <button (click)="activeTab.set(tab); loadTab()" class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors whitespace-nowrap"
          [ngClass]="activeTab() === tab ? 'bg-[var(--color-brand-500)] text-white' : 'bg-[var(--surface-muted)]'"
          [style.color]="activeTab() !== tab ? 'var(--text-secondary)' : ''">{{ tab }}</button>
      }
    </div>

    @if (tabLoading()) { <g51-loading /> }

    @if (activeTab() === 'My Shifts' && !tabLoading()) {
      @if (!shifts().length) { <g51-empty-state title="No Shifts" message="You have no assigned shifts." [icon]="CalendarIcon" /> }
      @else {
        <div class="space-y-2">
          @for (s of shifts(); track s.id) {
            <div class="card p-4"><div class="flex items-center justify-between">
              <div><p class="text-sm font-semibold" [style.color]="'var(--text-primary)'">{{ s.site_name || 'Site' }}</p>
                <p class="text-xs" [style.color]="'var(--text-tertiary)'">{{ s.shift_date }} · {{ s.start_time }} — {{ s.end_time }}</p></div>
              <span class="badge text-[10px]" [ngClass]="s.status === 'confirmed' ? 'bg-emerald-50 text-emerald-600' : s.status === 'in_progress' ? 'bg-blue-50 text-blue-600' : 'bg-gray-100 text-gray-500'">{{ s.status }}</span>
            </div></div>
          }
        </div>
      }
    }

    @if (activeTab() === 'My Reports' && !tabLoading()) {
      <div class="flex justify-end mb-3"><button class="btn-primary text-xs flex items-center gap-1" routerLink="/reports"><lucide-icon [img]="PlusIcon" [size]="12" /> New Report</button></div>
      @if (!reports().length) { <g51-empty-state title="No Reports" message="Submit your first daily activity report." [icon]="FileTextIcon" /> }
      @else {
        <div class="space-y-2">
          @for (r of reports(); track r.id) {
            <div class="card p-4"><div class="flex items-center justify-between">
              <div><p class="text-sm font-semibold" [style.color]="'var(--text-primary)'">{{ r.report_type || 'DAR' }} — {{ r.site_name || '' }}</p>
                <p class="text-xs" [style.color]="'var(--text-tertiary)'">{{ r.report_date || r.created_at }}</p></div>
              <span class="badge text-[10px]" [ngClass]="r.status === 'approved' ? 'bg-emerald-50 text-emerald-600' : r.status === 'submitted' ? 'bg-blue-50 text-blue-600' : 'bg-gray-100 text-gray-500'">{{ r.status }}</span>
            </div></div>
          }
        </div>
      }
    }

    @if (activeTab() === 'My Incidents' && !tabLoading()) {
      <div class="flex justify-end mb-3"><button class="btn-primary text-xs flex items-center gap-1" routerLink="/incidents"><lucide-icon [img]="PlusIcon" [size]="12" /> Report Incident</button></div>
      @if (!incidents().length) { <g51-empty-state title="No Incidents" message="No incidents reported by you." [icon]="AlertTriangleIcon" /> }
      @else {
        <div class="space-y-2">
          @for (i of incidents(); track i.id) {
            <div class="card p-4"><div class="flex items-center justify-between">
              <div><p class="text-sm font-semibold" [style.color]="'var(--text-primary)'">{{ i.title }}</p>
                <p class="text-xs" [style.color]="'var(--text-tertiary)'">{{ i.incident_type }} · {{ i.severity }} · {{ i.occurred_at || i.created_at }}</p></div>
              <span class="badge text-[10px]" [ngClass]="i.status === 'resolved' ? 'bg-emerald-50 text-emerald-600' : i.status === 'escalated' ? 'bg-red-50 text-red-600' : 'bg-amber-50 text-amber-600'">{{ i.status }}</span>
            </div></div>
          }
        </div>
      }
    }

    @if (activeTab() === 'Passdowns' && !tabLoading()) {
      <div class="flex justify-end mb-3"><button class="btn-primary text-xs flex items-center gap-1" (click)="showPassdown.set(true)"><lucide-icon [img]="PlusIcon" [size]="12" /> New Passdown</button></div>
      @if (!passdowns().length) { <g51-empty-state title="No Passdowns" message="No passdown notes." [icon]="BookOpenIcon" /> }
      @else {
        <div class="space-y-2">
          @for (p of passdowns(); track p.id) {
            <div class="card p-4">
              <div class="flex items-center justify-between mb-1">
                <p class="text-sm font-semibold" [style.color]="'var(--text-primary)'">{{ p.title || 'Passdown' }}</p>
                <span class="badge text-[10px]" [ngClass]="p.is_acknowledged ? 'bg-emerald-50 text-emerald-600' : 'bg-amber-50 text-amber-600'">{{ p.is_acknowledged ? 'Ack' : 'Pending' }}</span>
              </div>
              <p class="text-xs" [style.color]="'var(--text-secondary)'">{{ p.content }}</p>
              @if (!p.is_acknowledged) { <button (click)="ackPassdown(p)" class="btn-secondary text-[10px] mt-2 py-1 px-2">Acknowledge</button> }
            </div>
          }
        </div>
      }
      <g51-modal [open]="showPassdown()" title="New Passdown" maxWidth="480px" (closed)="showPassdown.set(false)">
        <div class="space-y-3">
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Title</label><input type="text" [(ngModel)]="pdForm.title" class="input-base w-full" /></div>
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Priority</label>
            <select [(ngModel)]="pdForm.priority" class="input-base w-full"><option value="low">Low</option><option value="medium">Medium</option><option value="high">High</option></select></div>
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Content *</label>
            <textarea [(ngModel)]="pdForm.content" rows="4" class="input-base w-full resize-none" placeholder="Handover notes..."></textarea></div>
        </div>
        <div modal-footer><button (click)="showPassdown.set(false)" class="btn-secondary">Cancel</button>
          <button (click)="submitPassdown()" class="btn-primary"><lucide-icon [img]="SendIcon" [size]="12" /> Submit</button></div>
      </g51-modal>
    }

    @if (activeTab() === 'Tours' && !tabLoading()) {
      @if (!tours().length) { <g51-empty-state title="No Tours" message="No tour sessions recorded." [icon]="RouteIcon" /> }
      @else {
        <div class="space-y-2">
          @for (t of tours(); track t.id) {
            <div class="card p-4"><div class="flex items-center justify-between">
              <div><p class="text-sm font-semibold" [style.color]="'var(--text-primary)'">{{ t.site_name || 'Site' }}</p>
                <p class="text-xs" [style.color]="'var(--text-tertiary)'">{{ t.scanned_checkpoints || 0 }}/{{ t.total_checkpoints || 0 }} scanned · {{ t.started_at }}</p></div>
              <span class="badge text-[10px]" [ngClass]="t.status === 'completed' ? 'bg-emerald-50 text-emerald-600' : 'bg-blue-50 text-blue-600'">{{ t.status }}</span>
            </div></div>
          }
        </div>
      }
    }
  `,
})
export class GuardPortalComponent implements OnInit {
  readonly auth = inject(AuthStore);
  private api = inject(ApiService); private toast = inject(ToastService);
  readonly ClockIcon = Clock; readonly MapPinIcon = MapPin; readonly FileTextIcon = FileText;
  readonly AlertTriangleIcon = AlertTriangle; readonly LogInIcon = LogIn; readonly LogOutIcon = LogOut;
  readonly CalendarIcon = Calendar; readonly RouteIcon = Route; readonly BookOpenIcon = BookOpen;
  readonly PlusIcon = Plus; readonly SendIcon = Send;

  readonly clockedIn = signal(false); readonly clockInTime = signal('');
  readonly assignedSite = signal(''); readonly todayReports = signal(0); readonly todayIncidents = signal(0);
  readonly tabLoading = signal(false); readonly activeTab = signal('My Shifts');
  readonly shifts = signal<any[]>([]); readonly reports = signal<any[]>([]);
  readonly incidents = signal<any[]>([]); readonly passdowns = signal<any[]>([]); readonly tours = signal<any[]>([]);
  readonly showPassdown = signal(false);
  pdForm = { title: '', priority: 'low', content: '' };

  ngOnInit(): void {
    this.api.get<any>('/time-clock/status').subscribe({ next: res => { if (res.data?.clocked_in) { this.clockedIn.set(true); this.clockInTime.set(res.data.clock_in_time || ''); } if (res.data?.site_name) this.assignedSite.set(res.data.site_name); }, error: () => {} });
    this.api.get<any>('/dashboard/today').subscribe({ next: res => { if (res.data?.snapshot) { this.todayReports.set(res.data.snapshot.total_reports || 0); this.todayIncidents.set(res.data.snapshot.total_incidents || 0); } }, error: () => {} });
    this.loadTab();
  }
  loadTab(): void {
    this.tabLoading.set(true);
    const t = this.activeTab();
    if (t === 'My Shifts') { this.api.get<any>('/shifts?mine=true').subscribe({ next: r => { this.shifts.set(r.data?.shifts || r.data || []); this.tabLoading.set(false); }, error: () => this.tabLoading.set(false) }); }
    else if (t === 'My Reports') { this.api.get<any>('/reports/dar?mine=true').subscribe({ next: r => { this.reports.set(r.data?.reports || r.data || []); this.tabLoading.set(false); }, error: () => this.tabLoading.set(false) }); }
    else if (t === 'My Incidents') { this.api.get<any>('/incidents?mine=true').subscribe({ next: r => { this.incidents.set(r.data?.incidents || r.data || []); this.tabLoading.set(false); }, error: () => this.tabLoading.set(false) }); }
    else if (t === 'Passdowns') { this.api.get<any>('/passdowns/unacknowledged').subscribe({ next: r => { this.passdowns.set(r.data?.passdowns || r.data || []); this.tabLoading.set(false); }, error: () => this.tabLoading.set(false) }); }
    else if (t === 'Tours') { this.api.get<any>('/tours/guard/' + this.auth.user()?.id + '/sessions').subscribe({ next: r => { this.tours.set(r.data?.sessions || r.data || []); this.tabLoading.set(false); }, error: () => this.tabLoading.set(false) }); }
  }
  ackPassdown(p: any): void { this.api.post('/passdowns/' + p.id + '/acknowledge', {}).subscribe({ next: () => { this.toast.success('Acknowledged'); this.loadTab(); } }); }
  submitPassdown(): void { if (!this.pdForm.content) { this.toast.warning('Content required'); return; } this.api.post('/passdowns', this.pdForm).subscribe({ next: () => { this.showPassdown.set(false); this.toast.success('Submitted'); this.pdForm = { title: '', priority: 'low', content: '' }; this.loadTab(); } }); }
  clockIn(): void { navigator.geolocation?.getCurrentPosition(p => { this.api.post('/time-clock/clock-in', { latitude: p.coords.latitude, longitude: p.coords.longitude }).subscribe({ next: () => { this.clockedIn.set(true); this.clockInTime.set(new Date().toLocaleTimeString()); this.toast.success('Clocked in'); } }); }, () => { this.api.post('/time-clock/clock-in', {}).subscribe({ next: () => { this.clockedIn.set(true); this.clockInTime.set(new Date().toLocaleTimeString()); this.toast.success('Clocked in'); } }); }); }
  clockOut(): void { navigator.geolocation?.getCurrentPosition(p => { this.api.post('/time-clock/clock-out', { latitude: p.coords.latitude, longitude: p.coords.longitude }).subscribe({ next: () => { this.clockedIn.set(false); this.toast.success('Clocked out'); } }); }, () => { this.api.post('/time-clock/clock-out', {}).subscribe({ next: () => { this.clockedIn.set(false); this.toast.success('Clocked out'); } }); }); }
}
