import { Component, inject, signal, OnInit } from '@angular/core';
import { NgClass } from '@angular/common';
import { LucideAngularModule, Building2, Search, Power, PowerOff, Eye } from 'lucide-angular';
import { FormsModule } from '@angular/forms';
import { PageHeaderComponent } from '@shared/components/page-header/page-header.component';
import { LoadingSpinnerComponent } from '@shared/components/loading-spinner/loading-spinner.component';
import { EmptyStateComponent } from '@shared/components/empty-state/empty-state.component';
import { ApiService } from '@core/services/api.service';
import { ToastService } from '@core/services/toast.service';

@Component({
  selector: 'g51-sa-tenants',
  standalone: true,
  imports: [NgClass, FormsModule, LucideAngularModule, PageHeaderComponent, LoadingSpinnerComponent, EmptyStateComponent],
  template: `
    <g51-page-header title="Tenants" subtitle="Manage security companies on the platform" />
    <div class="flex items-center gap-3 mb-4">
      <div class="relative flex-1 max-w-sm">
        <lucide-icon [img]="SearchIcon" [size]="14" class="absolute left-3 top-1/2 -translate-y-1/2" [style.color]="'var(--text-tertiary)'" />
        <input type="text" [(ngModel)]="search" (ngModelChange)="load()" placeholder="Search tenants..." class="input-base w-full pl-9" />
      </div>
    </div>
    @if (loading()) { <g51-loading /> }
    @else if (!tenants().length) { <g51-empty-state title="No Tenants" message="No security companies registered yet." [icon]="BuildingIcon" /> }
    @else {
      <div class="space-y-2">
        @for (t of tenants(); track t.id) {
          <div class="card p-4 card-hover">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm font-semibold" [style.color]="'var(--text-primary)'">{{ t.name }}</p>
                <p class="text-xs" [style.color]="'var(--text-tertiary)'">{{ t.email }} · {{ t.tenant_type }} · {{ t.guard_count || 0 }} guards</p>
              </div>
              <div class="flex items-center gap-2">
                <span class="badge text-[10px]" [ngClass]="t.status === 'active' || t.status === 'trial' ? 'bg-emerald-50 text-emerald-600' : 'bg-red-50 text-red-600'">{{ t.status }}</span>
                @if (t.status === 'active' || t.status === 'trial') {
                  <button (click)="suspend(t)" class="btn-secondary text-xs py-1 px-2 text-amber-600"><lucide-icon [img]="PowerOffIcon" [size]="12" /> Suspend</button>
                } @else {
                  <button (click)="reactivate(t)" class="btn-secondary text-xs py-1 px-2 text-emerald-600"><lucide-icon [img]="PowerIcon" [size]="12" /> Reactivate</button>
                }
              </div>
            </div>
          </div>
        }
      </div>
    }
  `,
})
export class TenantsComponent implements OnInit {
  private api = inject(ApiService); private toast = inject(ToastService);
  readonly BuildingIcon = Building2; readonly SearchIcon = Search; readonly PowerIcon = Power; readonly PowerOffIcon = PowerOff;
  readonly tenants = signal<any[]>([]); readonly loading = signal(true);
  search = '';
  ngOnInit(): void { this.load(); }
  load(): void {
    this.loading.set(true);
    this.api.get<any>('/admin/tenants').subscribe({
      next: res => { this.tenants.set(res.data?.tenants || res.data || []); this.loading.set(false); },
      error: () => this.loading.set(false),
    });
  }
  suspend(t: any): void { this.api.post(`/admin/tenants/${t.id}/suspend`, {}).subscribe({ next: () => { this.toast.success('Tenant suspended'); this.load(); } }); }
  reactivate(t: any): void { this.api.post(`/admin/tenants/${t.id}/reactivate`, {}).subscribe({ next: () => { this.toast.success('Tenant reactivated'); this.load(); } }); }
}
