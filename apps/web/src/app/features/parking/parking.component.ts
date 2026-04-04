import { Component, inject, signal, OnInit } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { NgClass } from '@angular/common';
import { LucideAngularModule, Car, Plus, Search, LogOut, ParkingCircle, AlertTriangle } from 'lucide-angular';
import { PageHeaderComponent } from '@shared/components/page-header/page-header.component';
import { ModalComponent } from '@shared/components/modal/modal.component';
import { EmptyStateComponent } from '@shared/components/empty-state/empty-state.component';
import { LoadingSpinnerComponent } from '@shared/components/loading-spinner/loading-spinner.component';
import { ApiService } from '@core/services/api.service';
import { AuthStore } from '@core/services/auth.store';
import { ToastService } from '@core/services/toast.service';
import { exportToCsv } from '@core/utils/csv-export';

@Component({
  selector: 'g51-parking',
  standalone: true,
  imports: [FormsModule, NgClass, LucideAngularModule, PageHeaderComponent, ModalComponent, EmptyStateComponent, LoadingSpinnerComponent],
  template: `
    <g51-page-header title="Parking Management" subtitle="Vehicle entry/exit, areas, and incidents">
      <button (click)="showEntry.set(true)" class="btn-primary flex items-center gap-2"><lucide-icon [img]="PlusIcon" [size]="16" /> Log Entry</button>
    </g51-page-header>

    <div class="tab-pills">
      @for (tab of ['Parked Vehicles', 'Areas', 'Incidents']; track tab) {
        <button (click)="activeTab.set(tab); loadTab()" class="tab-pill" [ngClass]="activeTab() === tab ? 'active' : ''">{{ tab }}</button>
      }
    </div>

    @if (loading()) { <g51-loading /> }

    @if (activeTab() === 'Parked Vehicles' && !loading()) {
      <div class="flex items-center gap-3 mb-4">
        <div class="relative flex-1 max-w-sm">
          <lucide-icon [img]="SearchIcon" [size]="14" class="absolute left-3 top-1/2 -translate-y-1/2" [style.color]="'var(--text-tertiary)'" />
          <input type="text" [(ngModel)]="search" placeholder="Search plate number..." class="input-base w-full pl-9" />
        </div>
        <button (click)="exportVehicles()" class="btn-secondary text-xs">Export CSV</button>
      </div>
      @if (!vehicles().length) { <g51-empty-state title="No Vehicles" message="No vehicles currently parked." [icon]="CarIcon" /> }
      @else {
        <div class="space-y-2">
          @for (v of filteredVehicles(); track v.id) {
            <div class="card p-4">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-sm font-semibold font-mono" [style.color]="'var(--text-primary)'">{{ v.plate_number }}</p>
                  <p class="text-xs" [style.color]="'var(--text-tertiary)'">{{ v.driver_name || 'Unknown' }} · {{ v.purpose || '' }} · {{ v.area_name || '' }}</p>
                  <p class="text-xs" [style.color]="'var(--text-tertiary)'">In: {{ v.entry_time || v.created_at }}</p>
                </div>
                <button (click)="logExit(v)" class="btn-secondary text-xs py-1 px-2 flex items-center gap-1"><lucide-icon [img]="LogOutIcon" [size]="12" /> Exit</button>
              </div>
            </div>
          }
        </div>
      }
    }

    @if (activeTab() === 'Areas' && !loading()) {
      <div class="flex justify-end mb-3"><button (click)="showArea.set(true)" class="btn-primary text-xs flex items-center gap-1"><lucide-icon [img]="PlusIcon" [size]="12" /> New Area</button></div>
      @if (!areas().length) { <g51-empty-state title="No Areas" message="Create parking areas for your sites." [icon]="ParkingIcon" /> }
      @else {
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
          @for (a of areas(); track a.id) {
            <div class="card p-4">
              <p class="text-sm font-semibold" [style.color]="'var(--text-primary)'">{{ a.name }}</p>
              <p class="text-xs" [style.color]="'var(--text-tertiary)'">{{ a.site_name || '' }} · {{ a.total_lots || 0 }} lots · {{ a.occupied || 0 }} occupied</p>
              <div class="w-full bg-gray-100 rounded-full h-2 mt-2"><div class="h-2 rounded-full" [style.width.%]="a.total_lots ? (a.occupied / a.total_lots * 100) : 0" [style.background]="'var(--color-brand-500)'"></div></div>
            </div>
          }
        </div>
      }
    }

    @if (activeTab() === 'Incidents' && !loading()) {
      <div class="flex justify-end mb-3"><button (click)="showIncident.set(true)" class="btn-primary text-xs flex items-center gap-1"><lucide-icon [img]="PlusIcon" [size]="12" /> Report</button></div>
      @if (!parkingIncidents().length) { <g51-empty-state title="No Incidents" message="No parking incidents reported." [icon]="AlertTriangleIcon" /> }
      @else {
        <div class="space-y-2">
          @for (i of parkingIncidents(); track i.id) {
            <div class="card p-4"><p class="text-sm font-semibold" [style.color]="'var(--text-primary)'">{{ i.incident_type_name || i.description }}</p>
              <p class="text-xs" [style.color]="'var(--text-tertiary)'">{{ i.plate_number || '' }} · {{ i.area_name || '' }} · {{ i.created_at }}</p></div>
          }
        </div>
      }
    }

    <!-- Log Entry Modal -->
    <g51-modal [open]="showEntry()" title="Log Vehicle Entry" maxWidth="480px" (closed)="showEntry.set(false)">
      <div class="space-y-3">
        <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Plate Number *</label><input type="text" [(ngModel)]="entryForm.plate_number" class="input-base w-full uppercase" /></div>
        <div class="grid grid-cols-2 gap-3">
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Driver Name</label><input type="text" [(ngModel)]="entryForm.driver_name" class="input-base w-full" /></div>
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Purpose</label>
            <select [(ngModel)]="entryForm.purpose" class="input-base w-full"><option value="visitor">Visitor</option><option value="delivery">Delivery</option><option value="staff">Staff</option><option value="other">Other</option></select></div>
        </div>
        <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Notes</label><input type="text" [(ngModel)]="entryForm.notes" class="input-base w-full" /></div>
      </div>
      <div modal-footer><button (click)="showEntry.set(false)" class="btn-secondary">Cancel</button>
        <button (click)="onLogEntry()" class="btn-primary">Log Entry</button></div>
    </g51-modal>

    <!-- New Area Modal -->
    <g51-modal [open]="showArea()" title="New Parking Area" maxWidth="400px" (closed)="showArea.set(false)">
      <div class="space-y-3">
        <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Name *</label><input type="text" [(ngModel)]="areaForm.name" class="input-base w-full" /></div>
        <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Total Lots</label><input type="number" [(ngModel)]="areaForm.total_lots" class="input-base w-full" /></div>
      </div>
      <div modal-footer><button (click)="showArea.set(false)" class="btn-secondary">Cancel</button>
        <button (click)="createArea()" class="btn-primary">Create</button></div>
    </g51-modal>

    <!-- Report Incident Modal -->
    <g51-modal [open]="showIncident()" title="Report Parking Incident" maxWidth="400px" (closed)="showIncident.set(false)">
      <div class="space-y-3">
        <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Plate Number</label><input type="text" [(ngModel)]="incidentForm.plate_number" class="input-base w-full uppercase" /></div>
        <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Description *</label><textarea [(ngModel)]="incidentForm.description" rows="3" class="input-base w-full resize-none"></textarea></div>
      </div>
      <div modal-footer><button (click)="showIncident.set(false)" class="btn-secondary">Cancel</button>
        <button (click)="reportIncident()" class="btn-primary">Report</button></div>
    </g51-modal>
  `,
})
export class ParkingComponent implements OnInit {
  private api = inject(ApiService);
  readonly auth = inject(AuthStore); private toast = inject(ToastService);
  readonly CarIcon = Car; readonly PlusIcon = Plus; readonly SearchIcon = Search; readonly LogOutIcon = LogOut;
  readonly ParkingIcon = ParkingCircle; readonly AlertTriangleIcon = AlertTriangle;
  readonly activeTab = signal('Parked Vehicles'); readonly loading = signal(true);
  readonly showEntry = signal(false); readonly showArea = signal(false); readonly showIncident = signal(false);
  readonly vehicles = signal<any[]>([]); readonly areas = signal<any[]>([]); readonly parkingIncidents = signal<any[]>([]);
  search = '';
  entryForm: any = { plate_number: '', driver_name: '', purpose: 'visitor', notes: '' };
  areaForm: any = { name: '', total_lots: 0 };
  incidentForm: any = { plate_number: '', description: '' };

  filteredVehicles() { const q = this.search.toLowerCase(); return !q ? this.vehicles() : this.vehicles().filter(v => (v.plate_number || '').toLowerCase().includes(q)); }

  ngOnInit(): void { this.loadTab(); }
  loadTab(): void {
    this.loading.set(true);
    const t = this.activeTab();
    if (t === 'Parked Vehicles') { this.api.get<any>('/parking/vehicles').subscribe({ next: r => { this.vehicles.set(r.data?.vehicles || r.data || []); this.loading.set(false); }, error: () => this.loading.set(false) }); }
    else if (t === 'Areas') { this.api.get<any>('/parking/areas').subscribe({ next: r => { this.areas.set(r.data?.areas || r.data || []); this.loading.set(false); }, error: () => this.loading.set(false) }); }
    else { this.api.get<any>('/parking/incidents').subscribe({ next: r => { this.parkingIncidents.set(r.data?.incidents || r.data || []); this.loading.set(false); }, error: () => this.loading.set(false) }); }
  }
  onLogEntry(): void { this.api.post('/parking/vehicles', this.entryForm).subscribe({ next: () => { this.showEntry.set(false); this.toast.success('Vehicle logged'); this.entryForm = { plate_number: '', driver_name: '', purpose: 'visitor', notes: '' }; this.loadTab(); } }); }
  logExit(v: any): void { this.api.post(`/parking/vehicles/${v.id}/exit`, {}).subscribe({ next: () => { this.toast.success('Exit logged'); this.loadTab(); } }); }
  createArea(): void { this.api.post('/parking/areas', this.areaForm).subscribe({ next: () => { this.showArea.set(false); this.toast.success('Area created'); this.loadTab(); } }); }
  reportIncident(): void { this.api.post('/parking/incidents', this.incidentForm).subscribe({ next: () => { this.showIncident.set(false); this.toast.success('Incident reported'); this.loadTab(); } }); }
  exportVehicles(): void { exportToCsv('parked-vehicles', this.vehicles(), [{ key: 'plate_number', label: 'Plate' }, { key: 'driver_name', label: 'Driver' }, { key: 'purpose', label: 'Purpose' }, { key: 'entry_time', label: 'Entry' }]); }
}
