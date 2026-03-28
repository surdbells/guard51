import { Component, inject, signal, OnInit } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { NgClass } from '@angular/common';
import { LucideAngularModule, ParkingCircle, Plus, Car, AlertTriangle } from 'lucide-angular';
import { PageHeaderComponent } from '@shared/components/page-header/page-header.component';
import { StatsCardComponent } from '@shared/components/stats-card/stats-card.component';
import { ModalComponent } from '@shared/components/modal/modal.component';
import { EmptyStateComponent } from '@shared/components/empty-state/empty-state.component';
import { ApiService } from '@core/services/api.service';
import { ToastService } from '@core/services/toast.service';

@Component({
  selector: 'g51-parking',
  standalone: true,
  imports: [FormsModule, NgClass, LucideAngularModule, PageHeaderComponent, StatsCardComponent, ModalComponent, EmptyStateComponent],
  template: `
    <g51-page-header title="Parking Manager" subtitle="Vehicle logging, occupancy, and incidents">
      <button (click)="showLogEntry.set(true)" class="btn-primary flex items-center gap-2"><lucide-icon [img]="PlusIcon" [size]="16" /> Log Vehicle</button>
    </g51-page-header>
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6 stagger-children">
      <g51-stats-card label="Currently Parked" [value]="stats().parked" [icon]="CarIcon" />
      <g51-stats-card label="Total Spaces" [value]="stats().totalSpaces" [icon]="ParkingCircleIcon" />
      <g51-stats-card label="Violations" [value]="stats().violations" [icon]="AlertTriangleIcon" />
      <g51-stats-card label="Occupancy" [value]="stats().occupancy + '%'" [icon]="ParkingCircleIcon" />
    </div>
    <div class="flex gap-1 mb-6">
      @for (tab of ['Parked Vehicles', 'Areas & Lots', 'Incidents']; track tab) {
        <button (click)="activeTab.set(tab)" class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors"
          [ngClass]="activeTab() === tab ? 'bg-[var(--color-brand-500)] text-white' : 'bg-[var(--surface-muted)]'"
          [style.color]="activeTab() !== tab ? 'var(--text-secondary)' : ''">{{ tab }}</button>
      }
    </div>
    @if (activeTab() === 'Parked Vehicles') {
      <div class="space-y-2">
        @for (v of parked(); track v.id) {
          <div class="card p-4 card-hover">
            <div class="flex items-center justify-between">
              <div>
                <div class="flex items-center gap-2 mb-0.5"><span class="text-sm font-semibold font-mono" [style.color]="'var(--text-primary)'">{{ v.plate_number }}</span>
                  <span class="badge text-[10px]" [ngClass]="v.status === 'violation' ? 'bg-red-50 text-red-600' : 'bg-emerald-50 text-emerald-600 dark:bg-emerald-950 dark:text-emerald-400'">{{ v.status_label }}</span>
                  <span class="badge text-[10px] bg-[var(--surface-muted)]">{{ v.owner_type_label }}</span></div>
                <p class="text-xs" [style.color]="'var(--text-tertiary)'">{{ v.make }} {{ v.model }} {{ v.color ? '• ' + v.color : '' }} {{ v.owner_name ? '• ' + v.owner_name : '' }}</p>
              </div>
              <button (click)="logExit(v.id)" class="btn-secondary text-xs py-1 px-2.5">Exit</button>
            </div>
          </div>
        } @empty { <g51-empty-state title="No Vehicles" message="No vehicles currently parked." [icon]="CarIcon" /> }
      </div>
    }
    @if (activeTab() === 'Areas & Lots') {
      <div class="card p-5"><h3 class="text-sm font-semibold mb-3" [style.color]="'var(--text-primary)'">Parking Areas</h3>
        @for (a of areas(); track a.id) {
          <div class="py-2 border-b last:border-b-0" [style.borderColor]="'var(--border-default)'">
            <p class="text-sm font-medium" [style.color]="'var(--text-primary)'">{{ a.name }}</p>
            <p class="text-xs" [style.color]="'var(--text-tertiary)'">{{ a.total_spaces }} spaces • {{ a.status_label }}</p>
          </div>
        } @empty { <p class="text-xs py-3 text-center" [style.color]="'var(--text-tertiary)'">No parking areas configured</p> }
      </div>
    }
    @if (activeTab() === 'Incidents') {
      <div class="card p-5"><h3 class="text-sm font-semibold mb-3" [style.color]="'var(--text-primary)'">Parking Incidents</h3>
        <g51-empty-state title="No Incidents" message="Parking incidents will appear here." [icon]="AlertTriangleIcon" /></div>
    }
    <g51-modal [open]="showLogEntry()" title="Log Vehicle Entry" maxWidth="480px" (closed)="showLogEntry.set(false)">
      <div class="space-y-3">
        <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Plate Number *</label><input type="text" [(ngModel)]="entryForm.plate_number" class="input-base w-full" placeholder="LG-234-KJA" /></div>
        <div class="grid grid-cols-3 gap-3">
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Make</label><input type="text" [(ngModel)]="entryForm.make" class="input-base w-full" /></div>
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Model</label><input type="text" [(ngModel)]="entryForm.model" class="input-base w-full" /></div>
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Color</label><input type="text" [(ngModel)]="entryForm.color" class="input-base w-full" /></div>
        </div>
        <div class="grid grid-cols-2 gap-3">
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Owner Name</label><input type="text" [(ngModel)]="entryForm.owner_name" class="input-base w-full" /></div>
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Owner Type</label>
            <select [(ngModel)]="entryForm.owner_type" class="input-base w-full"><option value="visitor">Visitor</option><option value="resident">Resident</option><option value="staff">Staff</option><option value="unknown">Unknown</option></select></div>
        </div>
      </div>
      <div modal-footer><button (click)="showLogEntry.set(false)" class="btn-secondary">Cancel</button><button (click)="onLogEntry()" class="btn-primary">Log Entry</button></div>
    </g51-modal>
  `,
})
export class ParkingComponent implements OnInit {
  private api = inject(ApiService); private toast = inject(ToastService);
  readonly ParkingCircleIcon = ParkingCircle; readonly PlusIcon = Plus; readonly CarIcon = Car; readonly AlertTriangleIcon = AlertTriangle;
  readonly activeTab = signal('Parked Vehicles');
  readonly showLogEntry = signal(false);
  readonly parked = signal<any[]>([]);
  readonly areas = signal<any[]>([]);
  readonly stats = signal({ parked: 0, totalSpaces: 0, violations: 0, occupancy: 0 });
  entryForm: any = { plate_number: '', make: '', model: '', color: '', owner_name: '', owner_type: 'visitor', site_id: '' };
  ngOnInit(): void {
    this.api.get<any>('/parking/areas').subscribe({ next: res => { if (res.data) this.areas.set(res.data.areas || []); } });
  }
  onLogEntry(): void { this.api.post('/parking/vehicles', this.entryForm).subscribe({ next: () => { this.showLogEntry.set(false); this.toast.success('Vehicle logged'); } }); }
  logExit(id: string): void { this.api.post('/parking/vehicles/' + id + '/exit', {}).subscribe({ next: () => { this.toast.success('Vehicle exit logged'); this.ngOnInit(); } }); }
}
