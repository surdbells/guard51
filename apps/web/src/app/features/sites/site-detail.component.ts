import { Component, inject, signal, OnInit } from '@angular/core';
import { ActivatedRoute, RouterLink } from '@angular/router';
import { LucideAngularModule, ArrowLeft, MapPin, Edit, FileText, Shield, Trash2 } from 'lucide-angular';
import { PageHeaderComponent } from '@shared/components/page-header/page-header.component';
import { EmptyStateComponent } from '@shared/components/empty-state/empty-state.component';
import { LoadingSpinnerComponent } from '@shared/components/loading-spinner/loading-spinner.component';
import { ApiService } from '@core/services/api.service';

@Component({
  selector: 'g51-site-detail',
  standalone: true,
  imports: [RouterLink, LucideAngularModule, PageHeaderComponent, EmptyStateComponent, LoadingSpinnerComponent],
  template: `
    @if (loading()) {
      <g51-loading [fullPage]="true" />
    } @else if (site()) {
      <g51-page-header [title]="site()!.name" [subtitle]="site()!.address || site()!.city || 'No address'">
        <a routerLink="/sites" class="btn-secondary flex items-center gap-1.5">
          <lucide-icon [img]="ArrowLeftIcon" [size]="16" /> Back
        </a>
        <a [routerLink]="'/sites/edit/' + site()!.id" class="btn-primary flex items-center gap-1.5">
          <lucide-icon [img]="EditIcon" [size]="16" /> Edit
        </a>
      </g51-page-header>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <!-- Info card -->
        <div class="lg:col-span-2 card p-5">
          <h3 class="text-sm font-semibold mb-4" [style.color]="'var(--text-primary)'">Site Information</h3>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
            <div><span class="block text-xs font-medium mb-0.5" [style.color]="'var(--text-tertiary)'">Address</span><span [style.color]="'var(--text-primary)'">{{ site()!.address || '—' }}</span></div>
            <div><span class="block text-xs font-medium mb-0.5" [style.color]="'var(--text-tertiary)'">City / State</span><span [style.color]="'var(--text-primary)'">{{ site()!.city || '—' }}, {{ site()!.state || '—' }}</span></div>
            <div><span class="block text-xs font-medium mb-0.5" [style.color]="'var(--text-tertiary)'">Status</span><span class="badge" [class]="site()!.status === 'active' ? 'bg-emerald-50 text-emerald-600 dark:bg-emerald-950 dark:text-emerald-400' : 'bg-amber-50 text-amber-600'">{{ site()!.status }}</span></div>
            <div><span class="block text-xs font-medium mb-0.5" [style.color]="'var(--text-tertiary)'">Geofence</span><span [style.color]="'var(--text-primary)'">{{ site()!.geofence_type }} ({{ site()!.geofence_radius }}m)</span></div>
            <div><span class="block text-xs font-medium mb-0.5" [style.color]="'var(--text-tertiary)'">Coordinates</span><span [style.color]="'var(--text-primary)'">{{ site()!.latitude || '—' }}, {{ site()!.longitude || '—' }}</span></div>
            <div><span class="block text-xs font-medium mb-0.5" [style.color]="'var(--text-tertiary)'">Contact</span><span [style.color]="'var(--text-primary)'">{{ site()!.contact_name || '—' }} {{ site()!.contact_phone ? '• ' + site()!.contact_phone : '' }}</span></div>
          </div>
          @if (site()!.notes) {
            <div class="mt-4 pt-4 border-t" [style.borderColor]="'var(--border-default)'">
              <span class="block text-xs font-medium mb-1" [style.color]="'var(--text-tertiary)'">Notes</span>
              <p class="text-sm" [style.color]="'var(--text-secondary)'">{{ site()!.notes }}</p>
            </div>
          }
        </div>

        <!-- Map placeholder -->
        <div class="card p-5 flex flex-col items-center justify-center min-h-[200px]">
          <lucide-icon [img]="MapPinIcon" [size]="32" [style.color]="'var(--text-tertiary)'" />
          <p class="text-sm mt-2" [style.color]="'var(--text-tertiary)'">Map view</p>
          @if (site()!.latitude) {
            <p class="text-xs mt-1" [style.color]="'var(--text-tertiary)'">{{ site()!.latitude }}, {{ site()!.longitude }}</p>
          }
        </div>
      </div>

      <!-- Post Orders -->
      <div class="mt-6">
        <div class="flex items-center justify-between mb-3">
          <h3 class="text-base font-semibold" [style.color]="'var(--text-primary)'">Post Orders</h3>
          <button class="btn-primary flex items-center gap-1.5 text-sm py-1.5 px-3">
            <lucide-icon [img]="FileTextIcon" [size]="14" /> Add Order
          </button>
        </div>
        @if (postOrders().length > 0) {
          <div class="space-y-2">
            @for (po of postOrders(); track po.id) {
              <div class="card p-4 card-hover">
                <div class="flex items-start justify-between gap-3">
                  <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 mb-1">
                      <h4 class="text-sm font-semibold" [style.color]="'var(--text-primary)'">{{ po.title }}</h4>
                      <span class="badge" [class]="po.priority === 'critical' ? 'bg-red-50 text-red-600 dark:bg-red-950 dark:text-red-400' : po.priority === 'high' ? 'bg-amber-50 text-amber-600' : 'bg-blue-50 text-blue-600'">{{ po.priority_label }}</span>
                      <span class="badge bg-[var(--surface-muted)]" [style.color]="'var(--text-secondary)'">{{ po.category_label }}</span>
                    </div>
                    <p class="text-sm line-clamp-2" [style.color]="'var(--text-secondary)'">{{ po.instructions }}</p>
                    <p class="text-xs mt-1" [style.color]="'var(--text-tertiary)'">v{{ po.version }} • Effective {{ po.effective_from }}</p>
                  </div>
                </div>
              </div>
            }
          </div>
        } @else {
          <g51-empty-state title="No Post Orders" message="Add standing instructions for guards at this site." [icon]="FileTextIcon" />
        }
      </div>
    }
  `,
})
export class SiteDetailComponent implements OnInit {
  private api = inject(ApiService);
  private route = inject(ActivatedRoute);
  readonly ArrowLeftIcon = ArrowLeft; readonly MapPinIcon = MapPin; readonly EditIcon = Edit;
  readonly FileTextIcon = FileText; readonly ShieldIcon = Shield; readonly Trash2Icon = Trash2;
  readonly loading = signal(true);
  readonly site = signal<any>(null);
  readonly postOrders = signal<any[]>([]);

  ngOnInit(): void {
    const id = this.route.snapshot.params['id'];
    this.api.get<any>(`/sites/${id}`).subscribe({
      next: res => { this.loading.set(false); if (res.data) { this.site.set(res.data.site); this.postOrders.set(res.data.post_orders || []); } },
      error: () => this.loading.set(false),
    });
  }
}
