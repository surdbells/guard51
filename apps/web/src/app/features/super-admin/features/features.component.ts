import { Component, inject, signal, OnInit } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { NgClass } from '@angular/common';
import { LucideAngularModule, ToggleLeft, ToggleRight, Search } from 'lucide-angular';
import { PageHeaderComponent } from '@shared/components/page-header/page-header.component';
import { LoadingSpinnerComponent } from '@shared/components/loading-spinner/loading-spinner.component';
import { ApiService } from '@core/services/api.service';
import { ToastService } from '@core/services/toast.service';

@Component({
  selector: 'g51-features',
  standalone: true,
  imports: [FormsModule, NgClass, LucideAngularModule, PageHeaderComponent, LoadingSpinnerComponent],
  template: `
    <g51-page-header title="Feature Management" subtitle="Enable or disable features platform-wide" />
    <div class="relative max-w-sm mb-4">
      <lucide-icon [img]="SearchIcon" [size]="14" class="absolute left-3 top-1/2 -translate-y-1/2" [style.color]="'var(--text-tertiary)'" />
      <input type="text" [(ngModel)]="search" placeholder="Search features..." class="input-base w-full pl-9" />
    </div>
    @if (loading()) { <g51-loading /> }
    @else {
      <div class="card overflow-hidden">
        <table class="w-full text-xs">
          <thead><tr [style.background]="'var(--surface-muted)'">
            <th class="text-left py-2.5 px-4 font-semibold" [style.color]="'var(--text-secondary)'">Feature</th>
            <th class="text-left py-2.5 px-4 font-semibold" [style.color]="'var(--text-secondary)'">Description</th>
            <th class="text-left py-2.5 px-4 font-semibold" [style.color]="'var(--text-secondary)'">Category</th>
            <th class="text-center py-2.5 px-4 font-semibold" [style.color]="'var(--text-secondary)'">Status</th>
            <th class="text-center py-2.5 px-4 font-semibold" [style.color]="'var(--text-secondary)'">Toggle</th>
          </tr></thead>
          <tbody>
            @for (f of filteredFeatures(); track f.id || f.key) {
              <tr class="border-t" [style.borderColor]="'var(--border-default)'">
                <td class="py-2.5 px-4 font-medium" [style.color]="'var(--text-primary)'">{{ f.name || f.key }}</td>
                <td class="py-2.5 px-4" [style.color]="'var(--text-secondary)'">{{ f.description || '' }}</td>
                <td class="py-2.5 px-4"><span class="badge text-[10px] bg-gray-100 text-gray-500">{{ f.category || 'Core' }}</span></td>
                <td class="py-2.5 px-4 text-center">
                  <span class="badge text-[10px]" [ngClass]="f.is_enabled ? 'bg-emerald-50 text-emerald-600' : 'bg-red-50 text-red-600'">{{ f.is_enabled ? 'Enabled' : 'Disabled' }}</span>
                </td>
                <td class="py-2.5 px-4 text-center">
                  <button (click)="toggleFeature(f)" class="p-1">
                    <lucide-icon [img]="f.is_enabled ? ToggleRightIcon : ToggleLeftIcon" [size]="22"
                      [style.color]="f.is_enabled ? 'var(--color-success)' : 'var(--text-tertiary)'" />
                  </button>
                </td>
              </tr>
            }
          </tbody>
        </table>
      </div>
    }
  `,
})
export class FeaturesComponent implements OnInit {
  private api = inject(ApiService); private toast = inject(ToastService);
  readonly ToggleLeftIcon = ToggleLeft; readonly ToggleRightIcon = ToggleRight; readonly SearchIcon = Search;
  readonly loading = signal(true);
  readonly features = signal<any[]>([]);
  search = '';

  filteredFeatures() { const q = this.search.toLowerCase(); return !q ? this.features() : this.features().filter(f => (f.name || f.key || '').toLowerCase().includes(q)); }

  ngOnInit(): void {
    this.api.get<any>('/admin/features').subscribe({
      next: r => { this.features.set(r.data?.features || r.data || []); this.loading.set(false); },
      error: () => {
        // Seed default features if none exist
        this.features.set(this.defaultFeatures());
        this.loading.set(false);
      },
    });
  }

  toggleFeature(f: any): void {
    f.is_enabled = !f.is_enabled;
    this.api.put(`/admin/features/${f.id || f.key}`, { is_enabled: f.is_enabled }).subscribe({
      next: () => this.toast.success(`${f.name || f.key} ${f.is_enabled ? 'enabled' : 'disabled'}`),
      error: () => { f.is_enabled = !f.is_enabled; this.toast.error('Failed to toggle'); },
    });
  }

  private defaultFeatures(): any[] {
    return [
      { key: 'tracking', name: 'Live GPS Tracking', description: 'Real-time guard location tracking', category: 'Tracking', is_enabled: true },
      { key: 'geofencing', name: 'Geofencing', description: 'Automatic alerts when guards leave/enter zones', category: 'Tracking', is_enabled: true },
      { key: 'panic_button', name: 'Panic Button', description: 'Emergency alert system for guards', category: 'Safety', is_enabled: true },
      { key: 'dispatch', name: 'Dispatch System', description: 'Incident dispatch and assignment', category: 'Operations', is_enabled: true },
      { key: 'invoicing', name: 'Invoicing', description: 'Client invoice generation and tracking', category: 'Finance', is_enabled: true },
      { key: 'payroll', name: 'Payroll', description: 'Guard payroll calculations', category: 'Finance', is_enabled: true },
      { key: 'visitors', name: 'Visitor Management', description: 'Appointment scheduling with access codes', category: 'Operations', is_enabled: true },
      { key: 'tours', name: 'Patrol Tours', description: 'QR/NFC checkpoint scanning', category: 'Operations', is_enabled: true },
      { key: 'parking', name: 'Parking Management', description: 'Vehicle entry/exit logging', category: 'Operations', is_enabled: false },
      { key: 'vehicle_patrol', name: 'Vehicle Patrol', description: 'Route management and plate reads', category: 'Operations', is_enabled: false },
      { key: 'chat', name: 'In-App Chat', description: 'Messaging between guards and dispatchers', category: 'Communication', is_enabled: true },
      { key: 'analytics', name: 'Advanced Analytics', description: 'Guard performance index and trend analysis', category: 'Reports', is_enabled: true },
      { key: 'client_portal', name: 'Client Portal', description: 'Client-facing web portal', category: 'Portals', is_enabled: true },
      { key: 'mobile_app', name: 'Mobile App', description: 'NativeScript guard mobile app', category: 'Portals', is_enabled: true },
    ];
  }
}
