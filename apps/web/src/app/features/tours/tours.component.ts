import { Component, inject, signal, OnInit } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { NgClass } from '@angular/common';
import { LucideAngularModule, Route, Plus, QrCode, CheckCircle, Clock, MapPin } from 'lucide-angular';
import { PageHeaderComponent } from '@shared/components/page-header/page-header.component';
import { ModalComponent } from '@shared/components/modal/modal.component';
import { EmptyStateComponent } from '@shared/components/empty-state/empty-state.component';
import { LoadingSpinnerComponent } from '@shared/components/loading-spinner/loading-spinner.component';
import { ApiService } from '@core/services/api.service';
import { ToastService } from '@core/services/toast.service';

@Component({
  selector: 'g51-tours',
  standalone: true,
  imports: [FormsModule, NgClass, LucideAngularModule, PageHeaderComponent, ModalComponent, EmptyStateComponent, LoadingSpinnerComponent],
  template: `
    <g51-page-header title="Site Tours" subtitle="Checkpoints, sessions, and compliance tracking">
      <button (click)="showCreate.set(true)" class="btn-primary flex items-center gap-2"><lucide-icon [img]="PlusIcon" [size]="16" /> Add Checkpoint</button>
    </g51-page-header>

    <div class="flex gap-1 mb-4">
      @for (tab of ['Checkpoints', 'Active Sessions', 'History']; track tab) {
        <button (click)="activeTab.set(tab); loadTab()" class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors"
          [ngClass]="activeTab() === tab ? 'bg-[var(--color-brand-500)] text-white' : 'bg-[var(--surface-muted)]'"
          [style.color]="activeTab() !== tab ? 'var(--text-secondary)' : ''">{{ tab }}</button>
      }
    </div>
    @if (loading()) { <g51-loading /> }
    @if (activeTab() === 'Checkpoints' && !loading()) {
      @if (!checkpoints().length) { <g51-empty-state title="No Checkpoints" message="Add NFC/QR checkpoints to your sites." [icon]="QrCodeIcon" /> }
      @else {
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
          @for (c of checkpoints(); track c.id) {
            <div class="card p-4">
              <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-lg flex items-center justify-center" [style.background]="'var(--brand-50)'" [style.color]="'var(--brand-500)'">
                  <lucide-icon [img]="QrCodeIcon" [size]="18" />
                </div>
                <div>
                  <p class="text-sm font-semibold" [style.color]="'var(--text-primary)'">{{ c.name }}</p>
                  <p class="text-xs" [style.color]="'var(--text-tertiary)'">{{ c.site_name || '' }} · {{ c.checkpoint_type || 'QR' }}</p>
                  <p class="text-[10px] font-mono" [style.color]="'var(--text-tertiary)'">Code: {{ c.code || c.id?.slice(0, 8) }}</p>
                </div>
              </div>
            </div>
          }
        </div>
      }
    }
    @if (activeTab() === 'Active Sessions' && !loading()) {
      @if (!activeSessions().length) { <g51-empty-state title="No Active Tours" message="No guards currently on tour." [icon]="RouteIcon" /> }
      @else {
        <div class="space-y-2">
          @for (s of activeSessions(); track s.id) {
            <div class="card p-4"><div class="flex items-center justify-between">
              <div><p class="text-sm font-semibold" [style.color]="'var(--text-primary)'">{{ s.guard_name || 'Guard' }}</p>
                <p class="text-xs" [style.color]="'var(--text-tertiary)'">{{ s.site_name || '' }} · {{ s.scanned || 0 }}/{{ s.total || 0 }} scanned</p></div>
              <div class="text-right"><span class="badge text-[10px] bg-blue-50 text-blue-600">In Progress</span>
                <p class="text-[10px]" [style.color]="'var(--text-tertiary)'">Started {{ s.started_at }}</p></div>
            </div></div>
          }
        </div>
      }
    }
    @if (activeTab() === 'History' && !loading()) {
      @if (!history().length) { <g51-empty-state title="No History" message="No completed tours." [icon]="ClockIcon" /> }
      @else {
        <div class="space-y-2">
          @for (s of history(); track s.id) {
            <div class="card p-4"><div class="flex items-center justify-between">
              <div><p class="text-sm font-semibold" [style.color]="'var(--text-primary)'">{{ s.guard_name }}</p>
                <p class="text-xs" [style.color]="'var(--text-tertiary)'">{{ s.site_name || '' }} · {{ s.scanned || 0 }}/{{ s.total || 0 }} · {{ s.completed_at || s.ended_at }}</p></div>
              <span class="badge text-[10px]" [ngClass]="s.compliance_pct >= 80 ? 'bg-emerald-50 text-emerald-600' : 'bg-amber-50 text-amber-600'">{{ s.compliance_pct || 0 }}%</span>
            </div></div>
          }
        </div>
      }
    }

    <g51-modal [open]="showCreate()" title="Add Checkpoint" maxWidth="420px" (closed)="showCreate.set(false)">
      <div class="space-y-3">
        <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Name *</label><input type="text" [(ngModel)]="form.name" class="input-base w-full" /></div>
        <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Site *</label>
          <select [(ngModel)]="form.site_id" class="input-base w-full"><option value="">Select</option>
            @for (s of sites(); track s.id) { <option [value]="s.id">{{ s.name }}</option> }
          </select></div>
        <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Type</label>
          <select [(ngModel)]="form.checkpoint_type" class="input-base w-full"><option value="qr">QR Code</option><option value="nfc">NFC Tag</option><option value="virtual">Virtual (GPS)</option></select></div>
      </div>
      <div modal-footer><button (click)="showCreate.set(false)" class="btn-secondary">Cancel</button>
        <button (click)="createCheckpoint()" class="btn-primary">Create</button></div>
    </g51-modal>
  `,
})
export class ToursComponent implements OnInit {
  private api = inject(ApiService); private toast = inject(ToastService);
  readonly RouteIcon = Route; readonly PlusIcon = Plus; readonly QrCodeIcon = QrCode; readonly ClockIcon = Clock;
  readonly activeTab = signal('Checkpoints'); readonly loading = signal(true); readonly showCreate = signal(false);
  readonly checkpoints = signal<any[]>([]); readonly activeSessions = signal<any[]>([]); readonly history = signal<any[]>([]); readonly sites = signal<any[]>([]);
  form: any = { name: '', site_id: '', checkpoint_type: 'qr' };

  ngOnInit(): void { this.loadTab(); this.api.get<any>('/sites').subscribe({ next: r => this.sites.set(r.data?.sites || r.data || []) }); }
  loadTab(): void {
    this.loading.set(true);
    const t = this.activeTab();
    if (t === 'Checkpoints') { this.api.get<any>('/tours/checkpoints').subscribe({ next: r => { this.checkpoints.set(r.data?.checkpoints || r.data || []); this.loading.set(false); }, error: () => this.loading.set(false) }); }
    else if (t === 'Active Sessions') { this.api.get<any>('/tours/sessions?status=in_progress').subscribe({ next: r => { this.activeSessions.set(r.data?.sessions || r.data || []); this.loading.set(false); }, error: () => this.loading.set(false) }); }
    else { this.api.get<any>('/tours/sessions?status=completed').subscribe({ next: r => { this.history.set(r.data?.sessions || r.data || []); this.loading.set(false); }, error: () => this.loading.set(false) }); }
  }
  createCheckpoint(): void { this.api.post('/tours/checkpoints', this.form).subscribe({ next: () => { this.showCreate.set(false); this.toast.success('Checkpoint created'); this.form = { name: '', site_id: '', checkpoint_type: 'qr' }; this.loadTab(); } }); }
}
