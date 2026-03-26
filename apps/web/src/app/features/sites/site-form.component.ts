import { Component, inject, signal, OnInit } from '@angular/core';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { LucideAngularModule, ArrowLeft, Save, Loader2 } from 'lucide-angular';
import { PageHeaderComponent } from '@shared/components/page-header/page-header.component';
import { ApiService } from '@core/services/api.service';
import { ToastService } from '@core/services/toast.service';

@Component({
  selector: 'g51-site-form',
  standalone: true,
  imports: [RouterLink, FormsModule, LucideAngularModule, PageHeaderComponent],
  template: `
    <g51-page-header [title]="isEdit() ? 'Edit Site' : 'Add New Site'" subtitle="Enter site details and configure geofence">
      <a routerLink="/sites" class="btn-secondary flex items-center gap-1.5">
        <lucide-icon [img]="ArrowLeftIcon" [size]="16" /> Back
      </a>
    </g51-page-header>

    <form (ngSubmit)="onSubmit()" class="max-w-3xl space-y-6">
      <!-- Basic Info -->
      <div class="card p-5">
        <h3 class="text-sm font-semibold mb-4" [style.color]="'var(--text-primary)'">Basic Information</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div class="sm:col-span-2"><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Site Name *</label>
            <input type="text" [(ngModel)]="form.name" name="name" class="input-base w-full" required /></div>
          <div class="sm:col-span-2"><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Address</label>
            <input type="text" [(ngModel)]="form.address" name="address" class="input-base w-full" /></div>
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">City</label>
            <input type="text" [(ngModel)]="form.city" name="city" class="input-base w-full" /></div>
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">State</label>
            <input type="text" [(ngModel)]="form.state" name="state" class="input-base w-full" /></div>
        </div>
      </div>

      <!-- Geofence -->
      <div class="card p-5">
        <h3 class="text-sm font-semibold mb-4" [style.color]="'var(--text-primary)'">Geofence Configuration</h3>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Latitude</label>
            <input type="number" step="0.00000001" [(ngModel)]="form.latitude" name="latitude" class="input-base w-full" /></div>
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Longitude</label>
            <input type="number" step="0.00000001" [(ngModel)]="form.longitude" name="longitude" class="input-base w-full" /></div>
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Radius (meters)</label>
            <input type="number" [(ngModel)]="form.geofence_radius" name="geofence_radius" class="input-base w-full" /></div>
        </div>
        <div class="mt-3 p-8 rounded-lg border-2 border-dashed text-center" [style.borderColor]="'var(--border-default)'" [style.color]="'var(--text-tertiary)'">
          <p class="text-sm">Map with geofence drawing will render here</p>
          <p class="text-xs mt-1">Drag to set center, resize to adjust radius</p>
        </div>
      </div>

      <!-- Contact -->
      <div class="card p-5">
        <h3 class="text-sm font-semibold mb-4" [style.color]="'var(--text-primary)'">Site Contact</h3>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Contact Name</label>
            <input type="text" [(ngModel)]="form.contact_name" name="contact_name" class="input-base w-full" /></div>
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Phone</label>
            <input type="tel" [(ngModel)]="form.contact_phone" name="contact_phone" class="input-base w-full" /></div>
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Email</label>
            <input type="email" [(ngModel)]="form.contact_email" name="contact_email" class="input-base w-full" /></div>
        </div>
      </div>

      <!-- Notes -->
      <div class="card p-5">
        <label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Notes</label>
        <textarea [(ngModel)]="form.notes" name="notes" rows="3" class="input-base w-full resize-none"></textarea>
      </div>

      <button type="submit" [disabled]="saving()" class="btn-primary flex items-center gap-2">
        @if (saving()) { <lucide-icon [img]="Loader2Icon" [size]="16" class="animate-spin" /> }
        <lucide-icon [img]="SaveIcon" [size]="16" /> {{ isEdit() ? 'Update Site' : 'Create Site' }}
      </button>
    </form>
  `,
})
export class SiteFormComponent implements OnInit {
  private api = inject(ApiService);
  private router = inject(Router);
  private route = inject(ActivatedRoute);
  private toast = inject(ToastService);
  readonly ArrowLeftIcon = ArrowLeft; readonly SaveIcon = Save; readonly Loader2Icon = Loader2;
  readonly isEdit = signal(false);
  readonly saving = signal(false);
  form: Record<string, any> = { name: '', address: '', city: '', state: '', latitude: null, longitude: null, geofence_radius: 100, contact_name: '', contact_phone: '', contact_email: '', notes: '' };

  ngOnInit(): void {
    const id = this.route.snapshot.params['id'];
    if (id) {
      this.isEdit.set(true);
      this.api.get<any>(`/sites/${id}`).subscribe({ next: res => { if (res.data?.site) Object.assign(this.form, res.data.site); } });
    }
  }

  onSubmit(): void {
    this.saving.set(true);
    const req = this.isEdit() ? this.api.put(`/sites/${this.route.snapshot.params['id']}`, this.form) : this.api.post('/sites', this.form);
    req.subscribe({
      next: () => { this.saving.set(false); this.toast.success(this.isEdit() ? 'Site updated' : 'Site created'); this.router.navigate(['/sites']); },
      error: () => this.saving.set(false),
    });
  }
}
