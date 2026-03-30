import { Component, inject, signal, OnInit } from '@angular/core';
import { NgClass } from '@angular/common';
import { LucideAngularModule, Puzzle } from 'lucide-angular';
import { PageHeaderComponent } from '@shared/components/page-header/page-header.component';
import { LoadingSpinnerComponent } from '@shared/components/loading-spinner/loading-spinner.component';
import { ApiService } from '@core/services/api.service';

@Component({
  selector: 'g51-sa-features',
  standalone: true,
  imports: [NgClass, LucideAngularModule, PageHeaderComponent, LoadingSpinnerComponent],
  template: `
    <g51-page-header title="Feature Modules" subtitle="Platform features available to tenants" />
    @if (loading()) { <g51-loading /> } @else {
      <div class="space-y-2">
        @for (m of modules(); track m.id || m.key) {
          <div class="card p-4">
            <div class="flex items-center justify-between">
              <div><p class="text-sm font-semibold" [style.color]="'var(--text-primary)'">{{ m.name || m.key }}</p>
                <p class="text-xs" [style.color]="'var(--text-tertiary)'">{{ m.description || m.category }} · Min tier: {{ m.minimum_tier }}</p></div>
              <span class="badge text-[10px]" [ngClass]="m.is_active ? 'bg-emerald-50 text-emerald-600' : 'bg-gray-100 text-gray-500'">{{ m.is_active ? 'Active' : 'Disabled' }}</span>
            </div>
          </div>
        }
      </div>
    }
  `,
})
export class FeaturesComponent implements OnInit {
  private api = inject(ApiService);
  readonly PuzzleIcon = Puzzle;
  readonly modules = signal<any[]>([]); readonly loading = signal(true);
  ngOnInit(): void { this.api.get<any>('/features/modules').subscribe({ next: res => { this.modules.set(res.data?.modules || res.data || []); this.loading.set(false); }, error: () => this.loading.set(false) }); }
}
