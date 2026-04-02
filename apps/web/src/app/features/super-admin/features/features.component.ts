import { Component, inject, signal, OnInit } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { NgClass } from '@angular/common';
import { LucideAngularModule, Puzzle, ToggleLeft, ToggleRight, Search } from 'lucide-angular';
import { PageHeaderComponent } from '@shared/components/page-header/page-header.component';
import { LoadingSpinnerComponent } from '@shared/components/loading-spinner/loading-spinner.component';
import { ApiService } from '@core/services/api.service';
import { ToastService } from '@core/services/toast.service';

@Component({
  selector: 'g51-features',
  standalone: true,
  imports: [FormsModule, NgClass, LucideAngularModule, PageHeaderComponent, LoadingSpinnerComponent],
  template: `
    <g51-page-header title="Feature Management" subtitle="Enable or disable platform modules globally and per-tenant" />

    <div class="relative max-w-sm mb-4">
      <lucide-icon [img]="SearchIcon" [size]="14" class="absolute left-3 top-1/2 -translate-y-1/2" [style.color]="'var(--text-tertiary)'" />
      <input type="text" [(ngModel)]="search" placeholder="Search modules..." class="input-base w-full pl-9" />
    </div>

    @if (loading()) { <g51-loading /> }
    @else {
      <div class="card overflow-hidden">
        <table class="w-full text-xs">
          <thead><tr [style.background]="'var(--surface-muted)'">
            <th class="text-left py-2.5 px-4 font-semibold" [style.color]="'var(--text-secondary)'">Module</th>
            <th class="text-left py-2.5 px-4 font-semibold" [style.color]="'var(--text-secondary)'">Key</th>
            <th class="text-left py-2.5 px-4 font-semibold" [style.color]="'var(--text-secondary)'">Category</th>
            <th class="text-left py-2.5 px-4 font-semibold" [style.color]="'var(--text-secondary)'">Description</th>
            <th class="text-center py-2.5 px-4 font-semibold" [style.color]="'var(--text-secondary)'">Platform Active</th>
          </tr></thead>
          <tbody>
            @for (f of filteredFeatures(); track f.id || f.module_key) {
              <tr class="border-t hover:bg-[var(--surface-hover)]" [style.borderColor]="'var(--border-default)'">
                <td class="py-3 px-4 font-medium" [style.color]="'var(--text-primary)'">{{ f.name }}</td>
                <td class="py-3 px-4 font-mono text-[10px]" [style.color]="'var(--text-tertiary)'">{{ f.module_key }}</td>
                <td class="py-3 px-4"><span class="badge text-[10px] bg-gray-100 text-gray-500">{{ f.category || 'core' }}</span></td>
                <td class="py-3 px-4" [style.color]="'var(--text-secondary)'">{{ f.description || '' }}</td>
                <td class="py-3 px-4 text-center">
                  <button (click)="toggleFeature(f)" class="p-1">
                    @if (f.is_active) { <lucide-icon [img]="ToggleOnIcon" [size]="24" [style.color]="'var(--color-success)'" /> }
                    @else { <lucide-icon [img]="ToggleOffIcon" [size]="24" [style.color]="'var(--text-tertiary)'" /> }
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
  readonly PuzzleIcon = Puzzle; readonly ToggleOnIcon = ToggleRight; readonly ToggleOffIcon = ToggleLeft; readonly SearchIcon = Search;
  readonly loading = signal(true);
  readonly features = signal<any[]>([]);
  search = '';

  filteredFeatures() {
    const q = this.search.toLowerCase();
    return !q ? this.features() : this.features().filter(f => (f.name || '').toLowerCase().includes(q) || (f.module_key || '').toLowerCase().includes(q));
  }

  ngOnInit(): void {
    this.api.get<any>('/admin/features').subscribe({
      next: r => { this.features.set(r.data?.features || r.data || []); this.loading.set(false); },
      error: () => this.loading.set(false),
    });
  }

  toggleFeature(f: any): void {
    const newState = !f.is_active;
    this.api.put(`/admin/features/${f.id || f.module_key}`, { is_active: newState }).subscribe({
      next: () => {
        f.is_active = newState;
        this.features.set([...this.features()]);
        this.toast.success(`${f.name} ${newState ? 'enabled' : 'disabled'}`);
      },
    });
  }
}
