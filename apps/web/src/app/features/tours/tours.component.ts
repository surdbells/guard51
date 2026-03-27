import { Component, inject, signal, OnInit } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { NgClass } from '@angular/common';
import { LucideAngularModule, MapPin, QrCode, Wifi, Plus, CheckCircle, BarChart3, Eye } from 'lucide-angular';
import { PageHeaderComponent } from '@shared/components/page-header/page-header.component';
import { StatsCardComponent } from '@shared/components/stats-card/stats-card.component';
import { BarChartComponent, BarChartData } from '@shared/components/charts/bar-chart.component';
import { ModalComponent } from '@shared/components/modal/modal.component';
import { EmptyStateComponent } from '@shared/components/empty-state/empty-state.component';
import { ApiService } from '@core/services/api.service';
import { ToastService } from '@core/services/toast.service';

@Component({
  selector: 'g51-tours',
  standalone: true,
  imports: [FormsModule, NgClass, LucideAngularModule, PageHeaderComponent, StatsCardComponent, BarChartComponent, ModalComponent, EmptyStateComponent],
  template: `
    <g51-page-header title="Site Tours" subtitle="Manage checkpoints, tour sessions, and compliance">
      <button (click)="showAddCheckpoint.set(true)" class="btn-primary flex items-center gap-2">
        <lucide-icon [img]="PlusIcon" [size]="16" /> Add Checkpoint
      </button>
    </g51-page-header>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6 stagger-children">
      <g51-stats-card label="Total Checkpoints" [value]="stats().checkpoints" [icon]="MapPinIcon" />
      <g51-stats-card label="Tours Today" [value]="stats().toursToday" [icon]="CheckCircleIcon" />
      <g51-stats-card label="Completion Rate" [value]="stats().completionRate + '%'" [icon]="BarChart3Icon" />
      <g51-stats-card label="Missed Tours" [value]="stats().missed" [icon]="EyeIcon" />
    </div>

    <!-- Tabs -->
    <div class="flex gap-1 mb-6">
      @for (tab of ['Checkpoints', 'Sessions', 'Reports']; track tab) {
        <button (click)="activeTab.set(tab)" class="px-4 py-2 rounded-lg text-sm font-medium transition-colors"
          [ngClass]="activeTab() === tab ? 'bg-[var(--color-brand-500)] text-white' : 'bg-[var(--surface-muted)]'"
          [style.color]="activeTab() !== tab ? 'var(--text-secondary)' : ''">{{ tab }}</button>
      }
    </div>

    @if (activeTab() === 'Checkpoints') {
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
        @for (cp of checkpoints(); track cp.id) {
          <div class="card p-4 card-hover">
            <div class="flex items-start justify-between mb-2">
              <div class="flex items-center gap-2">
                <div class="h-8 w-8 rounded-lg flex items-center justify-center" [style.background]="'var(--surface-muted)'">
                  <lucide-icon [img]="cp.checkpoint_type === 'qr' ? QrCodeIcon : cp.checkpoint_type === 'nfc' ? WifiIcon : MapPinIcon" [size]="16" [style.color]="'var(--text-secondary)'" />
                </div>
                <div>
                  <h4 class="text-sm font-semibold" [style.color]="'var(--text-primary)'">{{ cp.name }}</h4>
                  <p class="text-xs" [style.color]="'var(--text-tertiary)'">{{ cp.checkpoint_type_label }}</p>
                </div>
              </div>
              <span class="text-xs font-mono px-1.5 py-0.5 rounded" [style.background]="'var(--surface-muted)'" [style.color]="'var(--text-secondary)'">#{{ cp.sequence_order }}</span>
            </div>
            @if (cp.lat) {
              <p class="text-xs" [style.color]="'var(--text-tertiary)'">{{ cp.lat?.toFixed(5) }}, {{ cp.lng?.toFixed(5) }}</p>
            }
            <div class="flex items-center gap-2 mt-2">
              @if (cp.is_required) { <span class="badge bg-amber-50 text-amber-600 text-[10px]">Required</span> }
              <span class="badge text-[10px]" [class]="cp.is_active ? 'bg-emerald-50 text-emerald-600 dark:bg-emerald-950 dark:text-emerald-400' : 'bg-[var(--surface-muted)]'">{{ cp.is_active ? 'Active' : 'Inactive' }}</span>
            </div>
          </div>
        } @empty {
          <div class="col-span-full">
            <g51-empty-state title="No Checkpoints" message="Add NFC, QR, or virtual checkpoints to start site tours." [icon]="MapPinIcon" />
          </div>
        }
      </div>
    }

    @if (activeTab() === 'Sessions') {
      <div class="space-y-2">
        @for (s of sessions; track s.id) {
          <div class="card p-4 card-hover">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm font-medium" [style.color]="'var(--text-primary)'">{{ s.guardName }} — {{ s.siteName }}</p>
                <p class="text-xs" [style.color]="'var(--text-tertiary)'">{{ s.date }} • {{ s.scanned }}/{{ s.total }} checkpoints</p>
              </div>
              <div class="flex items-center gap-2">
                <div class="w-20 h-2 rounded-full overflow-hidden" [style.background]="'var(--surface-muted)'">
                  <div class="h-full rounded-full transition-all" [style.width.%]="s.rate" [style.background]="s.rate === 100 ? 'var(--color-success)' : 'var(--color-warning)'"></div>
                </div>
                <span class="text-xs font-medium tabular-nums" [style.color]="'var(--text-primary)'">{{ s.rate }}%</span>
              </div>
            </div>
          </div>
        }
      </div>
    }

    @if (activeTab() === 'Reports') {
      <div class="card p-5">
        <h3 class="text-sm font-semibold mb-3" [style.color]="'var(--text-primary)'">Tour Completion by Guard</h3>
        <g51-bar-chart [data]="guardCompletionData" [height]="240" />
      </div>
    }

    <!-- Add Checkpoint Modal -->
    <g51-modal [open]="showAddCheckpoint()" title="Add Checkpoint" (closed)="showAddCheckpoint.set(false)">
      <div class="space-y-3">
        <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Name *</label>
          <input type="text" [(ngModel)]="cpForm.name" class="input-base w-full" placeholder="Main Gate" /></div>
        <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Type *</label>
          <select [(ngModel)]="cpForm.checkpoint_type" class="input-base w-full">
            <option value="qr">QR Code</option><option value="nfc">NFC Tag</option><option value="virtual">Virtual (GPS)</option></select></div>
        <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Sequence Order</label>
          <input type="number" [(ngModel)]="cpForm.sequence_order" class="input-base w-full" /></div>
      </div>
      <div modal-footer>
        <button (click)="showAddCheckpoint.set(false)" class="btn-secondary">Cancel</button>
        <button (click)="onCreateCheckpoint()" class="btn-primary">Create</button>
      </div>
    </g51-modal>
  `,
})
export class ToursComponent implements OnInit {
  private api = inject(ApiService); private toast = inject(ToastService);
  readonly MapPinIcon = MapPin; readonly QrCodeIcon = QrCode; readonly WifiIcon = Wifi;
  readonly PlusIcon = Plus; readonly CheckCircleIcon = CheckCircle; readonly BarChart3Icon = BarChart3; readonly EyeIcon = Eye;

  readonly activeTab = signal('Checkpoints');
  readonly showAddCheckpoint = signal(false);
  readonly checkpoints = signal<any[]>([]);
  readonly stats = signal({ checkpoints: 0, toursToday: 3, completionRate: 87, missed: 1 });
  cpForm = { name: '', checkpoint_type: 'qr', sequence_order: 0 };

  sessions = [
    { id: '1', guardName: 'Musa Ibrahim', siteName: 'Lekki Phase 1', date: 'Today 06:30', scanned: 5, total: 5, rate: 100 },
    { id: '2', guardName: 'Chika Nwosu', siteName: 'V.I. HQ', date: 'Today 07:15', scanned: 3, total: 4, rate: 75 },
    { id: '3', guardName: 'Adebayo Okonkwo', siteName: 'Ikeja Mall', date: 'Yesterday 18:45', scanned: 6, total: 6, rate: 100 },
  ];

  guardCompletionData: BarChartData[] = [
    { label: 'Musa I.', value: 98 }, { label: 'Chika N.', value: 85 },
    { label: 'Adebayo O.', value: 92 }, { label: 'Funmi A.', value: 100 },
    { label: 'Kelechi E.', value: 78 },
  ];

  ngOnInit(): void {}

  onCreateCheckpoint(): void {
    this.showAddCheckpoint.set(false);
    this.toast.success('Checkpoint created');
    this.cpForm = { name: '', checkpoint_type: 'qr', sequence_order: 0 };
  }
}
