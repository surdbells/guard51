import { Component, inject, signal, OnInit } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { NgClass } from '@angular/common';
import { LucideAngularModule, Car, Plus, MapPin, Route, Search, Eye, Play, Square } from 'lucide-angular';
import { PageHeaderComponent } from '@shared/components/page-header/page-header.component';
import { ModalComponent } from '@shared/components/modal/modal.component';
import { EmptyStateComponent } from '@shared/components/empty-state/empty-state.component';
import { LoadingSpinnerComponent } from '@shared/components/loading-spinner/loading-spinner.component';
import { ApiService } from '@core/services/api.service';
import { ToastService } from '@core/services/toast.service';

@Component({
  selector: 'g51-vehicle-patrol',
  standalone: true,
  imports: [FormsModule, NgClass, LucideAngularModule, PageHeaderComponent, ModalComponent, EmptyStateComponent, LoadingSpinnerComponent],
  template: `
    <g51-page-header title="Vehicle Patrol" subtitle="Manage patrol routes, vehicles, and hits">
      <button (click)="showCreate.set(true)" class="btn-primary flex items-center gap-2"><lucide-icon [img]="PlusIcon" [size]="16" /> New Route</button>
    </g51-page-header>

    <div class="flex gap-1 mb-4">
      @for (tab of ['Routes', 'Vehicles', 'Hit Log']; track tab) {
        <button (click)="activeTab.set(tab); loadTab()" class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors"
          [ngClass]="activeTab() === tab ? 'bg-[var(--color-brand-500)] text-white' : 'bg-[var(--surface-muted)]'"
          [style.color]="activeTab() !== tab ? 'var(--text-secondary)' : ''">{{ tab }}</button>
      }
    </div>

    @if (loading()) { <g51-loading /> }

    @if (activeTab() === 'Routes' && !loading()) {
      @if (!routes().length) { <g51-empty-state title="No Routes" message="Create your first patrol route." [icon]="RouteIcon" /> }
      @else {
        <div class="space-y-2">
          @for (r of routes(); track r.id) {
            <div class="card p-4 card-hover">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-sm font-semibold" [style.color]="'var(--text-primary)'">{{ r.name }}</p>
                  <p class="text-xs" [style.color]="'var(--text-tertiary)'">{{ r.waypoints?.length || 0 }} waypoints · {{ r.site_name || '' }} · {{ r.distance_km || '?' }}km</p>
                </div>
                <div class="flex items-center gap-2">
                  <span class="badge text-[10px]" [ngClass]="r.status === 'active' ? 'bg-emerald-50 text-emerald-600' : 'bg-gray-100 text-gray-500'">{{ r.status }}</span>
                  <button (click)="startPatrol(r)" class="btn-primary text-[10px] py-1 px-2 flex items-center gap-1"><lucide-icon [img]="PlayIcon" [size]="10" /> Start</button>
                </div>
              </div>
            </div>
          }
        </div>
      }
    }

    @if (activeTab() === 'Vehicles' && !loading()) {
      @if (!vehicles().length) { <g51-empty-state title="No Vehicles" message="Register patrol vehicles." [icon]="CarIcon" /> }
      @else {
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
          @for (v of vehicles(); track v.id) {
            <div class="card p-4">
              <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-lg flex items-center justify-center" [style.background]="'var(--brand-50)'" [style.color]="'var(--brand-500)'">
                  <lucide-icon [img]="CarIcon" [size]="18" />
                </div>
                <div>
                  <p class="text-sm font-semibold" [style.color]="'var(--text-primary)'">{{ v.plate_number || v.name }}</p>
                  <p class="text-xs" [style.color]="'var(--text-tertiary)'">{{ v.vehicle_type || 'Vehicle' }} · {{ v.assigned_guard || 'Unassigned' }}</p>
                </div>
              </div>
              <span class="badge text-[10px] mt-2" [ngClass]="v.status === 'available' ? 'bg-emerald-50 text-emerald-600' : v.status === 'on_patrol' ? 'bg-blue-50 text-blue-600' : 'bg-gray-100 text-gray-500'">{{ v.status }}</span>
            </div>
          }
        </div>
      }
    }

    @if (activeTab() === 'Hit Log' && !loading()) {
      @if (!hits().length) { <g51-empty-state title="No Hits" message="No plate reads recorded." [icon]="EyeIcon" /> }
      @else {
        <div class="space-y-2">
          @for (h of hits(); track h.id) {
            <div class="card p-3 flex items-center justify-between">
              <div>
                <p class="text-sm font-semibold font-mono" [style.color]="'var(--text-primary)'">{{ h.plate_number }}</p>
                <p class="text-xs" [style.color]="'var(--text-tertiary)'">{{ h.route_name || '' }} · {{ h.guard_name || '' }}</p>
              </div>
              <div class="text-right">
                <span class="badge text-[10px]" [ngClass]="h.hit_type === 'match' ? 'bg-red-50 text-red-600' : 'bg-emerald-50 text-emerald-600'">{{ h.hit_type || 'read' }}</span>
                <p class="text-[10px] mt-0.5" [style.color]="'var(--text-tertiary)'">{{ h.created_at }}</p>
              </div>
            </div>
          }
        </div>
      }
    }

    <g51-modal [open]="showCreate()" title="Create Patrol Route" maxWidth="500px" (closed)="showCreate.set(false)">
      <div class="space-y-3">
        <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Route Name *</label><input type="text" [(ngModel)]="form.name" class="input-base w-full" /></div>
        <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Site</label>
          <select [(ngModel)]="form.site_id" class="input-base w-full"><option value="">Select site</option>
            @for (s of sites(); track s.id) { <option [value]="s.id">{{ s.name }}</option> }
          </select></div>
        <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Distance (km)</label><input type="number" [(ngModel)]="form.distance_km" class="input-base w-full" /></div>
        <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Description</label><textarea [(ngModel)]="form.description" rows="2" class="input-base w-full resize-none"></textarea></div>
      </div>
      <div modal-footer><button (click)="showCreate.set(false)" class="btn-secondary">Cancel</button>
        <button (click)="createRoute()" class="btn-primary">Create Route</button></div>
    </g51-modal>
  `,
})
export class VehiclePatrolComponent implements OnInit {
  private api = inject(ApiService); private toast = inject(ToastService);
  readonly CarIcon = Car; readonly PlusIcon = Plus; readonly RouteIcon = Route; readonly EyeIcon = Eye; readonly PlayIcon = Play;
  readonly activeTab = signal('Routes'); readonly loading = signal(true); readonly showCreate = signal(false);
  readonly routes = signal<any[]>([]); readonly vehicles = signal<any[]>([]); readonly hits = signal<any[]>([]); readonly sites = signal<any[]>([]);
  form: any = { name: '', site_id: '', distance_km: 0, description: '' };

  ngOnInit(): void { this.loadTab(); this.api.get<any>('/sites').subscribe({ next: r => this.sites.set(r.data?.sites || r.data || []) }); }
  loadTab(): void {
    this.loading.set(true);
    const t = this.activeTab();
    if (t === 'Routes') { this.api.get<any>('/vehicle-patrol/routes').subscribe({ next: r => { this.routes.set(r.data?.routes || r.data || []); this.loading.set(false); }, error: () => this.loading.set(false) }); }
    else if (t === 'Vehicles') { this.api.get<any>('/vehicle-patrol/vehicles').subscribe({ next: r => { this.vehicles.set(r.data?.vehicles || r.data || []); this.loading.set(false); }, error: () => this.loading.set(false) }); }
    else { this.api.get<any>('/vehicle-patrol/hits').subscribe({ next: r => { this.hits.set(r.data?.hits || r.data || []); this.loading.set(false); }, error: () => this.loading.set(false) }); }
  }
  createRoute(): void { this.api.post('/vehicle-patrol/routes', this.form).subscribe({ next: () => { this.showCreate.set(false); this.toast.success('Route created'); this.loadTab(); } }); }
  startPatrol(r: any): void { this.api.post(`/vehicle-patrol/routes/${r.id}/start`, {}).subscribe({ next: () => this.toast.success('Patrol started'), error: () => this.toast.error('Failed to start') }); }
}
