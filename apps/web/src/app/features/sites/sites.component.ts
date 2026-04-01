import { Component, inject, signal, OnInit } from '@angular/core';
import { RouterLink } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { NgClass } from '@angular/common';
import { LucideAngularModule, MapPin, Plus, Search, Trash2, Eye, Power, PowerOff } from 'lucide-angular';
import { PageHeaderComponent } from '@shared/components/page-header/page-header.component';
import { EmptyStateComponent } from '@shared/components/empty-state/empty-state.component';
import { LoadingSpinnerComponent } from '@shared/components/loading-spinner/loading-spinner.component';
import { ApiService } from '@core/services/api.service';
import { ToastService } from '@core/services/toast.service';

@Component({
  selector: 'g51-sites',
  standalone: true,
  imports: [RouterLink, FormsModule, NgClass, LucideAngularModule, PageHeaderComponent, EmptyStateComponent, LoadingSpinnerComponent],
  template: `
    <g51-page-header title="Post Sites" subtitle="Manage security deployment locations">
      <button class="btn-primary flex items-center gap-2" routerLink="new"><lucide-icon [img]="PlusIcon" [size]="16" /> Add Site</button>
    </g51-page-header>
    <div class="flex items-center gap-3 mb-4">
      <div class="relative flex-1 max-w-sm">
        <lucide-icon [img]="SearchIcon" [size]="14" class="absolute left-3 top-1/2 -translate-y-1/2" [style.color]="'var(--text-tertiary)'" />
        <input type="text" [(ngModel)]="search" (ngModelChange)="onSearch()" placeholder="Search sites..." class="input-base w-full pl-9" />
      </div>
      <select [(ngModel)]="statusFilter" (ngModelChange)="loadSites()" class="input-base text-xs py-2">
        <option value="">All Status</option><option value="active">Active</option><option value="inactive">Inactive</option><option value="suspended">Suspended</option>
      </select>
    </div>
    @if (loading()) { <g51-loading /> }
    @else if (!sites().length) { <g51-empty-state title="No Sites" message="Add your first post site to get started." [icon]="MapPinIcon" /> }
    @else {
      <div class="space-y-2">
        @for (s of sites(); track s.id) {
          <div class="card p-4 card-hover">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-lg flex items-center justify-center" [style.background]="'var(--color-brand-50)'" [style.color]="'var(--color-brand-500)'"><lucide-icon [img]="MapPinIcon" [size]="18" /></div>
                <div>
                  <a [routerLink]="[s.id]" class="text-sm font-semibold hover:underline" [style.color]="'var(--text-primary)'">{{ s.name }}</a>
                  <p class="text-xs" [style.color]="'var(--text-tertiary)'">{{ s.address || 'No address' }} · {{ s.city || '' }}</p>
                </div>
              </div>
              <div class="flex items-center gap-2">
                <span class="badge text-[10px]" [ngClass]="s.status === 'active' ? 'bg-emerald-50 text-emerald-600' : 'bg-gray-100 text-gray-500'">{{ s.status }}</span>
                <a [routerLink]="[s.id]" class="btn-secondary text-xs py-1 px-2"><lucide-icon [img]="EyeIcon" [size]="12" /></a>
                <a [routerLink]="['edit', s.id]" class="btn-secondary text-xs py-1 px-2">Edit</a>
                @if (s.status === 'active') {
                  <button (click)="suspend(s)" class="btn-secondary text-xs py-1 px-2 text-amber-600"><lucide-icon [img]="PowerOffIcon" [size]="12" /></button>
                } @else {
                  <button (click)="activate(s)" class="btn-secondary text-xs py-1 px-2 text-emerald-600"><lucide-icon [img]="PowerIcon" [size]="12" /></button>
                }
                <button (click)="confirmDelete(s)" class="btn-secondary text-xs py-1 px-2 text-red-500"><lucide-icon [img]="TrashIcon" [size]="12" /></button>
              </div>
            </div>
          </div>
        }
      </div>
    }
  `,
})
export class SitesComponent implements OnInit {
  private api = inject(ApiService); private toast = inject(ToastService);
  readonly MapPinIcon = MapPin; readonly PlusIcon = Plus; readonly SearchIcon = Search;
  readonly TrashIcon = Trash2; readonly EyeIcon = Eye; readonly PowerIcon = Power; readonly PowerOffIcon = PowerOff;
  readonly sites = signal<any[]>([]); readonly loading = signal(true);
  search = ''; statusFilter = '';
  ngOnInit(): void { this.loadSites(); }
  onSearch(): void { this.loadSites(); }
  loadSites(): void {
    this.loading.set(true);
    const p = new URLSearchParams();
    if (this.search) p.set('search', this.search);
    if (this.statusFilter) p.set('status', this.statusFilter);
    this.api.get<any>(`/sites?${p}`).subscribe({
      next: res => { this.sites.set(res.data?.sites || res.data?.items || res.data || []); this.loading.set(false); },
      error: () => this.loading.set(false),
    });
  }
  suspend(s: any): void { this.api.post(`/sites/${s.id}/suspend`, {}).subscribe({ next: () => { this.toast.success('Site suspended'); this.loadSites(); } }); }
  activate(s: any): void { this.api.post(`/sites/${s.id}/activate`, {}).subscribe({ next: () => { this.toast.success('Site activated'); this.loadSites(); } }); }
  confirmDelete(s: any): void { if (confirm(`Delete site "${s.name}"?`)) { this.api.delete(`/sites/${s.id}`).subscribe({ next: () => { this.toast.success('Site deleted'); this.loadSites(); } }); } }
}
