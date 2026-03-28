import { Component, inject, signal, OnInit } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { NgClass } from '@angular/common';
import { LucideAngularModule, Car, Plus, Route, MapPin, CheckCircle } from 'lucide-angular';
import { PageHeaderComponent } from '@shared/components/page-header/page-header.component';
import { StatsCardComponent } from '@shared/components/stats-card/stats-card.component';
import { ModalComponent } from '@shared/components/modal/modal.component';
import { EmptyStateComponent } from '@shared/components/empty-state/empty-state.component';
import { ApiService } from '@core/services/api.service';
import { ToastService } from '@core/services/toast.service';

@Component({
  selector: 'g51-vehicle-patrol',
  standalone: true,
  imports: [FormsModule, NgClass, LucideAngularModule, PageHeaderComponent, StatsCardComponent, ModalComponent, EmptyStateComponent],
  template: `
    <g51-page-header title="Vehicle Patrol" subtitle="Fleet management, patrol routes, and hit tracking">
      <button (click)="showCreateVehicle.set(true)" class="btn-primary flex items-center gap-2"><lucide-icon [img]="PlusIcon" [size]="16" /> Add Vehicle</button>
    </g51-page-header>
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6 stagger-children">
      <g51-stats-card label="Active Vehicles" [value]="vehicles().length" [icon]="CarIcon" />
      <g51-stats-card label="Patrol Routes" [value]="routes().length" [icon]="RouteIcon" />
      <g51-stats-card label="Hits Today" [value]="stats().hitsToday" [icon]="CheckCircleIcon" />
      <g51-stats-card label="Coverage" [value]="stats().coverage + '%'" [icon]="MapPinIcon" />
    </div>
    <div class="flex gap-1 mb-6">
      @for (tab of ['Vehicles', 'Routes', 'Activity']; track tab) {
        <button (click)="activeTab.set(tab)" class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors"
          [ngClass]="activeTab() === tab ? 'bg-[var(--color-brand-500)] text-white' : 'bg-[var(--surface-muted)]'"
          [style.color]="activeTab() !== tab ? 'var(--text-secondary)' : ''">{{ tab }}</button>
      }
    </div>
    @if (activeTab() === 'Vehicles') {
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
        @for (v of vehicles(); track v.id) {
          <div class="card p-4">
            <div class="flex items-center justify-between mb-2">
              <h4 class="text-sm font-semibold" [style.color]="'var(--text-primary)'">{{ v.vehicle_name }}</h4>
              <span class="badge text-[10px]" [ngClass]="v.status === 'active' ? 'bg-emerald-50 text-emerald-600 dark:bg-emerald-950 dark:text-emerald-400' : 'bg-[var(--surface-muted)]'">{{ v.status_label }}</span>
            </div>
            <p class="text-xs" [style.color]="'var(--text-tertiary)'">{{ v.plate_number }} • {{ v.vehicle_type_label }}</p>
          </div>
        } @empty { <div class="col-span-3"><g51-empty-state title="No Vehicles" message="Add patrol vehicles to get started." [icon]="CarIcon" /></div> }
      </div>
    }
    @if (activeTab() === 'Routes') {
      <div class="space-y-2">
        @for (r of routes(); track r.id) {
          <div class="card p-4 card-hover">
            <div class="flex items-center justify-between">
              <div><h4 class="text-sm font-semibold" [style.color]="'var(--text-primary)'">{{ r.name }}</h4>
                <p class="text-xs" [style.color]="'var(--text-tertiary)'">{{ r.site_count }} sites • {{ r.expected_hits_per_day }} hits/day • Reset {{ r.reset_time }}</p></div>
              <span class="badge text-[10px]" [ngClass]="r.is_active ? 'bg-emerald-50 text-emerald-600' : 'bg-[var(--surface-muted)]'">{{ r.is_active ? 'Active' : 'Inactive' }}</span>
            </div>
          </div>
        }
      </div>
    }
    @if (activeTab() === 'Activity') {
      <div class="card p-5"><h3 class="text-sm font-semibold mb-3" [style.color]="'var(--text-primary)'">Recent Patrol Hits</h3>
        <p class="text-xs" [style.color]="'var(--text-tertiary)'">Patrol activity appears here when guards record site hits.</p></div>
    }
    <g51-modal [open]="showCreateVehicle()" title="Add Vehicle" maxWidth="420px" (closed)="showCreateVehicle.set(false)">
      <div class="space-y-3">
        <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Name *</label><input type="text" [(ngModel)]="vForm.vehicle_name" class="input-base w-full" /></div>
        <div class="grid grid-cols-2 gap-3">
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Plate *</label><input type="text" [(ngModel)]="vForm.plate_number" class="input-base w-full" /></div>
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Type *</label>
            <select [(ngModel)]="vForm.vehicle_type" class="input-base w-full"><option value="car">Car</option><option value="suv">SUV</option><option value="motorcycle">Motorcycle</option><option value="van">Van</option></select></div>
        </div>
      </div>
      <div modal-footer><button (click)="showCreateVehicle.set(false)" class="btn-secondary">Cancel</button><button (click)="onCreateVehicle()" class="btn-primary">Add</button></div>
    </g51-modal>
  `,
})
export class VehiclePatrolComponent implements OnInit {
  private api = inject(ApiService); private toast = inject(ToastService);
  readonly CarIcon = Car; readonly PlusIcon = Plus; readonly RouteIcon = Route; readonly MapPinIcon = MapPin; readonly CheckCircleIcon = CheckCircle;
  readonly activeTab = signal('Vehicles');
  readonly showCreateVehicle = signal(false);
  readonly vehicles = signal<any[]>([]);
  readonly routes = signal<any[]>([]);
  readonly stats = signal({ hitsToday: 0, coverage: 0 });
  vForm = { vehicle_name: '', plate_number: '', vehicle_type: 'car' };
  ngOnInit(): void {
    this.api.get<any>('/vehicle-patrol/vehicles').subscribe({ next: res => { if (res.data) this.vehicles.set(res.data.vehicles || []); } });
    this.api.get<any>('/vehicle-patrol/routes').subscribe({ next: res => { if (res.data) this.routes.set(res.data.routes || []); } });
  }
  onCreateVehicle(): void { this.api.post('/vehicle-patrol/vehicles', this.vForm).subscribe({ next: () => { this.showCreateVehicle.set(false); this.toast.success('Vehicle added'); this.ngOnInit(); } }); }
}
