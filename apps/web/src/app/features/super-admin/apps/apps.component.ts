import { Component, inject, signal, OnInit } from '@angular/core';
import { NgClass } from '@angular/common';
import { LucideAngularModule, Smartphone, Upload } from 'lucide-angular';
import { PageHeaderComponent } from '@shared/components/page-header/page-header.component';
import { LoadingSpinnerComponent } from '@shared/components/loading-spinner/loading-spinner.component';
import { EmptyStateComponent } from '@shared/components/empty-state/empty-state.component';
import { ApiService } from '@core/services/api.service';

@Component({
  selector: 'g51-sa-apps',
  standalone: true,
  imports: [NgClass, LucideAngularModule, PageHeaderComponent, LoadingSpinnerComponent, EmptyStateComponent],
  template: `
    <g51-page-header title="App Distribution" subtitle="Manage mobile and desktop app releases" />
    @if (loading()) { <g51-loading /> }
    @else if (!releases().length) { <g51-empty-state title="No Releases" message="Upload your first app release." [icon]="SmartphoneIcon" /> }
    @else {
      <div class="space-y-2">
        @for (r of releases(); track r.id) {
          <div class="card p-4">
            <div class="flex items-center justify-between">
              <div><p class="text-sm font-semibold" [style.color]="'var(--text-primary)'">{{ r.app_key }} v{{ r.version }}</p>
                <p class="text-xs" [style.color]="'var(--text-tertiary)'">{{ r.platform }} · {{ r.release_type }} · {{ r.created_at }}</p></div>
              <span class="badge text-[10px]" [ngClass]="r.is_latest ? 'bg-emerald-50 text-emerald-600' : 'bg-gray-100 text-gray-500'">{{ r.is_latest ? 'Latest' : 'Archived' }}</span>
            </div>
          </div>
        }
      </div>
    }
  `,
})
export class AppsComponent implements OnInit {
  private api = inject(ApiService);
  readonly SmartphoneIcon = Smartphone;
  readonly releases = signal<any[]>([]); readonly loading = signal(true);
  ngOnInit(): void { this.api.get<any>('/admin/apps/releases').subscribe({ next: res => { this.releases.set(res.data?.releases || res.data || []); this.loading.set(false); }, error: () => this.loading.set(false) }); }
}
