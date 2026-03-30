import { Component, inject, signal, OnInit } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { NgClass } from '@angular/common';
import { LucideAngularModule, Route, Plus, MapPin, QrCode, Search } from 'lucide-angular';
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
    <g51-page-header title="Site Tours" subtitle="Checkpoints, sessions, and patrol routes">
      <button class="btn-primary flex items-center gap-2" (click)="showAddCheckpoint.set(true)"><lucide-icon [img]="PlusIcon" [size]="16" /> Add Checkpoint</button>
    </g51-page-header>
    <div class="flex gap-1 mb-4">
      @for (tab of ['Checkpoints', 'Sessions']; track tab) {
        <button (click)="activeTab.set(tab)" class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors"
          [ngClass]="activeTab() === tab ? 'bg-[var(--color-brand-500)] text-white' : 'bg-[var(--surface-muted)]'" [style.color]="activeTab() !== tab ? 'var(--text-secondary)' : ''">{{ tab }}</button>
      }
      <select [(ngModel)]="selectedSite" (ngModelChange)="loadData()" class="input-base text-xs py-1 ml-auto">
        <option value="">All Sites</option>
        @for (s of sites(); track s.id) { <option [value]="s.id">{{ s.name }}</option> }
      </select>
    </div>
    @if (loading()) { <g51-loading /> }
    @else if (activeTab() === 'Checkpoints') {
      @if (!checkpoints().length) { <g51-empty-state title="No Checkpoints" message="Add checkpoints to enable tour patrol." [icon]="MapPinIcon" /> }
      @else {
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
          @for (cp of checkpoints(); track cp.id) {
            <div class="card p-4">
              <div class="flex items-center gap-2 mb-2">
                <lucide-icon [img]="QrCodeIcon" [size]="16" [style.color]="'var(--color-brand-500)'" />
                <p class="text-sm font-semibold" [style.color]="'var(--text-primary)'">{{ cp.name }}</p>
              </div>
              <p class="text-xs" [style.color]="'var(--text-tertiary)'">{{ cp.checkpoint_type || 'nfc' }} · Order: {{ cp.sort_order || 0 }} · {{ cp.site_name || '' }}</p>
            </div>
          }
        </div>
      }
    }
    @else if (activeTab() === 'Sessions') {
      @if (!sessions().length) { <g51-empty-state title="No Sessions" message="No tour sessions recorded yet." [icon]="RouteIcon" /> }
      @else {
        <div class="space-y-2">
          @for (s of sessions(); track s.id) {
            <div class="card p-4">
              <div class="flex items-center justify-between">
                <div><p class="text-sm font-semibold" [style.color]="'var(--text-primary)'">{{ s.guard_name || 'Guard' }}</p>
                  <p class="text-xs" [style.color]="'var(--text-tertiary)'">{{ s.site_name || '' }} · {{ s.scans_count || 0 }}/{{ s.total_checkpoints || 0 }} scanned · {{ s.started_at }}</p></div>
                <span class="badge text-[10px]" [ngClass]="s.status === 'completed' ? 'bg-emerald-50 text-emerald-600' : s.status === 'in_progress' ? 'bg-blue-50 text-blue-600' : 'bg-gray-100 text-gray-500'">{{ s.status }}</span>
              </div>
            </div>
          }
        </div>
      }
    }
    <g51-modal [open]="showAddCheckpoint()" title="Add Checkpoint" maxWidth="480px" (closed)="showAddCheckpoint.set(false)">
      <div class="space-y-3">
        <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Site *</label>
          <select [(ngModel)]="cpForm.site_id" class="input-base w-full">
            @for (s of sites(); track s.id) { <option [value]="s.id">{{ s.name }}</option> }
          </select></div>
        <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Checkpoint Name *</label>
          <input type="text" [(ngModel)]="cpForm.name" class="input-base w-full" placeholder="e.g. Main Gate, Server Room" /></div>
        <div class="grid grid-cols-2 gap-3">
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Type</label>
            <select [(ngModel)]="cpForm.checkpoint_type" class="input-base w-full"><option value="nfc">NFC Tag</option><option value="qr">QR Code</option><option value="gps">GPS</option></select></div>
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Sort Order</label>
            <input type="number" [(ngModel)]="cpForm.sort_order" class="input-base w-full" min="0" /></div>
        </div>
        <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Description</label>
          <textarea [(ngModel)]="cpForm.description" rows="2" class="input-base w-full resize-none" placeholder="What to check at this point..."></textarea></div>
      </div>
      <div modal-footer><button (click)="showAddCheckpoint.set(false)" class="btn-secondary">Cancel</button><button (click)="onCreateCheckpoint()" class="btn-primary">Add</button></div>
    </g51-modal>
  `,
})
export class ToursComponent implements OnInit {
  private api = inject(ApiService); private toast = inject(ToastService);
  readonly RouteIcon = Route; readonly PlusIcon = Plus; readonly MapPinIcon = MapPin;
  readonly QrCodeIcon = QrCode; readonly SearchIcon = Search;
  readonly checkpoints = signal<any[]>([]); readonly sessions = signal<any[]>([]); readonly sites = signal<any[]>([]);
  readonly loading = signal(true); readonly showAddCheckpoint = signal(false); readonly activeTab = signal('Checkpoints');
  selectedSite = '';
  cpForm: any = { site_id: '', name: '', checkpoint_type: 'nfc', sort_order: 0, description: '' };
  ngOnInit(): void { this.api.get<any>('/sites').subscribe({ next: res => this.sites.set(res.data?.sites || res.data || []) }); this.loadData(); }
  loadData(): void {
    this.loading.set(true);
    if (this.activeTab() === 'Checkpoints' && this.selectedSite) {
      this.api.get<any>(`/tours/site/${this.selectedSite}/checkpoints`).subscribe({ next: res => { this.checkpoints.set(res.data?.checkpoints || res.data || []); this.loading.set(false); }, error: () => this.loading.set(false) });
    } else if (this.activeTab() === 'Sessions' && this.selectedSite) {
      this.api.get<any>(`/tours/site/${this.selectedSite}/sessions`).subscribe({ next: res => { this.sessions.set(res.data?.sessions || res.data || []); this.loading.set(false); }, error: () => this.loading.set(false) });
    } else { this.loading.set(false); }
  }
  onCreateCheckpoint(): void {
    if (!this.cpForm.site_id) { this.toast.warning('Select a site'); return; }
    this.api.post(`/tours/site/${this.cpForm.site_id}/checkpoints`, this.cpForm).subscribe({ next: () => { this.showAddCheckpoint.set(false); this.toast.success('Checkpoint added'); this.selectedSite = this.cpForm.site_id; this.loadData(); } });
  }
}
