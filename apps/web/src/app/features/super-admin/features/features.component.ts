import { Component, signal } from '@angular/core';
import { NgClass } from '@angular/common';
import { LucideAngularModule, Boxes, Search, Check, Lock } from 'lucide-angular';
import { PageHeaderComponent } from '@shared/components/page-header/page-header.component';

interface FeatureModule {
  key: string; name: string; category: string; tier: string; isCore: boolean; dependencies: string[];
}

@Component({
  selector: 'g51-sa-features',
  standalone: true,
  imports: [NgClass, LucideAngularModule, PageHeaderComponent],
  template: `
    <g51-page-header title="Feature Module Management" subtitle="Manage the 52 feature modules, tier assignments, and dependencies" />

    <div class="mb-4 flex flex-col sm:flex-row gap-3">
      <div class="relative flex-1 max-w-sm">
        <lucide-icon [img]="SearchIcon" [size]="16" class="absolute left-3 top-1/2 -translate-y-1/2" [style.color]="'var(--text-tertiary)'" />
        <input type="text" class="input-base w-full pl-9" placeholder="Search modules..." (input)="onSearch($event)" />
      </div>
      <div class="flex gap-1.5 flex-wrap">
        @for (cat of categories; track cat) {
          <button (click)="activeCategory.set(cat)" class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors"
            [ngClass]="activeCategory() === cat
              ? 'bg-[var(--color-brand-500)] text-white'
              : 'bg-[var(--surface-muted)] text-[var(--text-secondary)] hover:bg-[var(--surface-hover)]'"
          >{{ cat }}</button>
        }
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
      @for (mod of filteredModules(); track mod.key) {
        <div class="card p-4 card-hover">
          <div class="flex items-start justify-between gap-2">
            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-2">
                <h3 class="text-sm font-semibold truncate" [style.color]="'var(--text-primary)'">{{ mod.name }}</h3>
                @if (mod.isCore) {
                  <span class="badge bg-emerald-50 text-emerald-600 dark:bg-emerald-950 dark:text-emerald-400">Core</span>
                }
              </div>
              <p class="text-xs mt-1" [style.color]="'var(--text-tertiary)'">{{ mod.category }} • {{ mod.tier }}</p>
              @if (mod.dependencies.length > 0) {
                <p class="text-xs mt-1" [style.color]="'var(--text-tertiary)'">Deps: {{ mod.dependencies.join(', ') }}</p>
              }
            </div>
            <div class="shrink-0">
              @if (mod.isCore) {
                <div class="h-7 w-7 rounded-full bg-emerald-50 dark:bg-emerald-950 flex items-center justify-center">
                  <lucide-icon [img]="CheckIcon" [size]="14" class="text-emerald-500" />
                </div>
              } @else {
                <div class="h-7 w-7 rounded-full flex items-center justify-center" [style.background]="'var(--surface-muted)'">
                  <lucide-icon [img]="LockIcon" [size]="14" [style.color]="'var(--text-tertiary)'" />
                </div>
              }
            </div>
          </div>
        </div>
      }
    </div>
  `,
})
export class FeaturesComponent {
  readonly SearchIcon = Search; readonly BoxesIcon = Boxes; readonly CheckIcon = Check; readonly LockIcon = Lock;

  readonly activeCategory = signal('All');
  readonly searchTerm = signal('');
  categories = ['All', 'core', 'tracking', 'scheduling', 'attendance', 'operations', 'reporting', 'emergency', 'vehicle', 'finance', 'communication', 'analytics', 'security', 'customization', 'platform'];

  modules: FeatureModule[] = [
    { key: 'auth', name: 'Authentication', category: 'core', tier: 'all', isCore: true, dependencies: [] },
    { key: 'guard_management', name: 'Guard Management', category: 'core', tier: 'all', isCore: true, dependencies: [] },
    { key: 'client_management', name: 'Client Management', category: 'core', tier: 'all', isCore: true, dependencies: [] },
    { key: 'site_management', name: 'Site/Post Management', category: 'core', tier: 'all', isCore: true, dependencies: [] },
    { key: 'live_tracker', name: 'Live GPS Tracker', category: 'tracking', tier: 'all', isCore: true, dependencies: ['guard_management'] },
    { key: 'geofencing', name: 'Geo-Fencing & Alerts', category: 'tracking', tier: 'starter', isCore: false, dependencies: ['live_tracker'] },
    { key: 'scheduling', name: 'Shift Scheduling', category: 'scheduling', tier: 'all', isCore: true, dependencies: ['guard_management'] },
    { key: 'time_clock', name: 'Time Clock', category: 'attendance', tier: 'all', isCore: true, dependencies: ['guard_management'] },
    { key: 'payroll', name: 'Payroll Generation', category: 'finance', tier: 'professional', isCore: false, dependencies: ['time_clock'] },
    { key: 'invoicing', name: 'Invoice Management', category: 'finance', tier: 'starter', isCore: false, dependencies: ['client_management'] },
    { key: 'messenger', name: 'Messenger / Chat', category: 'communication', tier: 'starter', isCore: false, dependencies: ['guard_management'] },
    { key: 'vehicle_patrol', name: 'Vehicle Patrol', category: 'vehicle', tier: 'professional', isCore: false, dependencies: ['guard_management'] },
    { key: 'incident_reporting', name: 'Incident Reporting', category: 'reporting', tier: 'all', isCore: true, dependencies: [] },
    { key: 'panic_button', name: 'Panic Button', category: 'emergency', tier: 'all', isCore: true, dependencies: ['guard_management'] },
    { key: 'white_label', name: 'White-Label Branding', category: 'customization', tier: 'enterprise', isCore: false, dependencies: [] },
    { key: 'advanced_analytics', name: 'Advanced Analytics', category: 'analytics', tier: 'business', isCore: false, dependencies: ['basic_analytics'] },
    { key: 'audit_logging', name: 'Audit Logging', category: 'security', tier: 'business', isCore: false, dependencies: [] },
    { key: 'app_distribution', name: 'App Distribution', category: 'platform', tier: 'all', isCore: true, dependencies: [] },
  ];

  filteredModules = () => {
    let result = this.modules;
    const cat = this.activeCategory();
    if (cat !== 'All') result = result.filter(m => m.category === cat);
    const search = this.searchTerm().toLowerCase();
    if (search) result = result.filter(m => m.name.toLowerCase().includes(search) || m.key.includes(search));
    return result;
  };

  onSearch(e: Event): void { this.searchTerm.set((e.target as HTMLInputElement).value); }
}
