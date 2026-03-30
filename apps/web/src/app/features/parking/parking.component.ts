import { Component, inject, signal, OnInit } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { NgClass } from '@angular/common';
import { LucideAngularModule, Car, Plus, Search, LogIn, LogOut } from 'lucide-angular';
import { PageHeaderComponent } from '@shared/components/page-header/page-header.component';
import { ModalComponent } from '@shared/components/modal/modal.component';
import { EmptyStateComponent } from '@shared/components/empty-state/empty-state.component';
import { LoadingSpinnerComponent } from '@shared/components/loading-spinner/loading-spinner.component';
import { ApiService } from '@core/services/api.service';
import { ToastService } from '@core/services/toast.service';

@Component({
  selector: 'g51-parking',
  standalone: true,
  imports: [FormsModule, NgClass, LucideAngularModule, PageHeaderComponent, ModalComponent, EmptyStateComponent, LoadingSpinnerComponent],
  template: `
    <g51-page-header title="Parking Management" subtitle="Track vehicles and parking areas">
      <button class="btn-primary flex items-center gap-2" (click)="showCreate.set(true)"><lucide-icon [img]="PlusIcon" [size]="16" /> Log Vehicle</button>
    </g51-page-header>
    <div class="flex items-center gap-3 mb-4">
      <div class="relative flex-1 max-w-sm">
        <lucide-icon [img]="SearchIcon" [size]="14" class="absolute left-3 top-1/2 -translate-y-1/2" [style.color]="'var(--text-tertiary)'" />
        <input type="text" [(ngModel)]="search" (ngModelChange)="loadVehicles()" placeholder="Search by plate or driver..." class="input-base w-full pl-9" />
      </div>
      <select [(ngModel)]="statusFilter" (ngModelChange)="loadVehicles()" class="input-base text-xs py-2">
        <option value="">All</option><option value="parked">Parked</option><option value="exited">Exited</option>
      </select>
    </div>
    @if (loading()) { <g51-loading /> }
    @else if (!vehicles().length) { <g51-empty-state title="No Vehicles" message="Log the first vehicle entry." [icon]="CarIcon" /> }
    @else {
      <div class="space-y-2">
        @for (v of vehicles(); track v.id) {
          <div class="card p-4 card-hover">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm font-semibold" [style.color]="'var(--text-primary)'">{{ v.plate_number }}</p>
                <p class="text-xs" [style.color]="'var(--text-tertiary)'">{{ v.vehicle_type || 'Car' }} · {{ v.driver_name || 'Unknown' }} · {{ v.parking_area_name || '' }}</p>
              </div>
              <div class="flex items-center gap-2">
                <span class="badge text-[10px]" [ngClass]="v.status === 'parked' ? 'bg-blue-50 text-blue-600' : 'bg-gray-100 text-gray-500'">{{ v.status }}</span>
                @if (v.status === 'parked') {
                  <button (click)="exitVehicle(v)" class="btn-secondary text-xs py-1 px-2 flex items-center gap-1"><lucide-icon [img]="LogOutIcon" [size]="12" /> Exit</button>
                }
              </div>
            </div>
          </div>
        }
      </div>
    }
    <g51-modal [open]="showCreate()" title="Log Vehicle Entry" maxWidth="480px" (closed)="showCreate.set(false)">
      <div class="space-y-3">
        <div class="grid grid-cols-2 gap-3">
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Plate Number *</label><input type="text" [(ngModel)]="form.plate_number" class="input-base w-full" /></div>
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Vehicle Type</label>
            <select [(ngModel)]="form.vehicle_type" class="input-base w-full"><option value="car">Car</option><option value="suv">SUV</option><option value="truck">Truck</option><option value="motorcycle">Motorcycle</option><option value="van">Van</option></select></div>
        </div>
        <div class="grid grid-cols-2 gap-3">
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Driver Name</label><input type="text" [(ngModel)]="form.driver_name" class="input-base w-full" /></div>
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Driver Phone</label><input type="tel" [(ngModel)]="form.driver_phone" class="input-base w-full" /></div>
        </div>
        <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Purpose</label><input type="text" [(ngModel)]="form.purpose" class="input-base w-full" placeholder="e.g. Delivery, Visitor, Staff" /></div>
      </div>
      <div modal-footer><button (click)="showCreate.set(false)" class="btn-secondary">Cancel</button><button (click)="onLogEntry()" class="btn-primary">Log Entry</button></div>
    </g51-modal>
  `,
})
export class ParkingComponent implements OnInit {
  private api = inject(ApiService); private toast = inject(ToastService);
  readonly CarIcon = Car; readonly PlusIcon = Plus; readonly SearchIcon = Search; readonly LogOutIcon = LogOut;
  readonly vehicles = signal<any[]>([]); readonly loading = signal(true); readonly showCreate = signal(false);
  search = ''; statusFilter = '';
  form: any = { plate_number: '', vehicle_type: 'car', driver_name: '', driver_phone: '', purpose: '' };
  ngOnInit(): void { this.loadVehicles(); }
  loadVehicles(): void {
    this.loading.set(true);
    const p = new URLSearchParams();
    if (this.search) p.set('search', this.search);
    if (this.statusFilter) p.set('status', this.statusFilter);
    this.api.get<any>(`/parking/vehicles?${p}`).subscribe({
      next: res => { this.vehicles.set(res.data?.vehicles || res.data?.items || res.data || []); this.loading.set(false); },
      error: () => this.loading.set(false),
    });
  }
  onLogEntry(): void { this.api.post('/parking/vehicles', this.form).subscribe({ next: () => { this.showCreate.set(false); this.toast.success('Vehicle logged'); this.loadVehicles(); } }); }
  exitVehicle(v: any): void { this.api.post(`/parking/vehicles/${v.id}/exit`, {}).subscribe({ next: () => { this.toast.success('Vehicle exited'); this.loadVehicles(); } }); }
}
