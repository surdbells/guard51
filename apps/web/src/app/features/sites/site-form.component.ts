import { Component, inject, signal, OnInit } from '@angular/core';
import { Router, ActivatedRoute } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { LucideAngularModule, Save, ArrowLeft, Upload, X, MapPin } from 'lucide-angular';
import { PageHeaderComponent } from '@shared/components/page-header/page-header.component';
import { ApiService } from '@core/services/api.service';
import { ToastService } from '@core/services/toast.service';

@Component({
  selector: 'g51-site-form',
  standalone: true,
  imports: [FormsModule, LucideAngularModule, PageHeaderComponent],
  template: `
    <g51-page-header [title]="isEdit() ? 'Edit Site' : 'Add New Site'" subtitle="Post site location and configuration">
      <button class="btn-secondary flex items-center gap-2" (click)="goBack()"><lucide-icon [img]="ArrowLeftIcon" [size]="14" /> Back</button>
    </g51-page-header>
    <div class="card p-6 max-w-3xl">
      <h3 class="text-sm font-semibold mb-4" [style.color]="'var(--text-primary)'">Site Details</h3>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
        <div class="sm:col-span-2"><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Site Name *</label>
          <input type="text" [(ngModel)]="form.name" class="input-base w-full" required placeholder="e.g. Lekki Phase 1 Headquarters" /></div>
        <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Assigned Client</label>
          <select [(ngModel)]="form.client_id" class="input-base w-full">
            <option value="">No client assigned</option>
            @for (c of clients(); track c.id) { <option [value]="c.id">{{ c.company_name || c.name }}</option> }
          </select></div>
        <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Timezone</label>
          <select [(ngModel)]="form.timezone" class="input-base w-full">
            <option value="Africa/Lagos">Africa/Lagos (WAT)</option><option value="Africa/Accra">Africa/Accra (GMT)</option>
            <option value="Africa/Johannesburg">Africa/Johannesburg (SAST)</option></select></div>
      </div>
      <div class="sm:col-span-2 mb-4"><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Address</label>
        <textarea [(ngModel)]="form.address" rows="2" class="input-base w-full resize-none" placeholder="Full street address..."></textarea></div>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
        <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">City</label>
          <input type="text" [(ngModel)]="form.city" class="input-base w-full" /></div>
        <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">State</label>
          <select [(ngModel)]="form.state" class="input-base w-full">
            <option value="">Select State</option>
            @for (s of states; track s) { <option [value]="s">{{ s }}</option> }
          </select></div>
      </div>

      <h3 class="text-sm font-semibold mb-4 mt-6" [style.color]="'var(--text-primary)'">Geofence Configuration</h3>
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Latitude *</label>
          <input type="number" step="0.00000001" [(ngModel)]="form.latitude" class="input-base w-full" placeholder="6.4541" /></div>
        <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Longitude *</label>
          <input type="number" step="0.00000001" [(ngModel)]="form.longitude" class="input-base w-full" placeholder="3.3947" /></div>
        <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Geofence Radius (meters)</label>
          <input type="number" [(ngModel)]="form.geofence_radius" class="input-base w-full" min="10" max="5000" /></div>
      </div>

      <h3 class="text-sm font-semibold mb-4 mt-6" [style.color]="'var(--text-primary)'">Site Contact</h3>
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Contact Name</label>
          <input type="text" [(ngModel)]="form.contact_name" class="input-base w-full" /></div>
        <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Contact Phone</label>
          <input type="tel" [(ngModel)]="form.contact_phone" class="input-base w-full" /></div>
        <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Contact Email</label>
          <input type="email" [(ngModel)]="form.contact_email" class="input-base w-full" /></div>
      </div>

      <h3 class="text-sm font-semibold mb-4 mt-6" [style.color]="'var(--text-primary)'">Site Photo</h3>
      <div class="mb-6">
        @if (photoPreview()) {
          <div class="relative inline-block mb-2">
            <img [src]="photoPreview()" class="h-24 w-32 rounded-lg object-cover border" [style.borderColor]="'var(--border-default)'" />
            <button (click)="removePhoto()" class="absolute -top-2 -right-2 h-5 w-5 rounded-full bg-red-500 text-white flex items-center justify-center"><lucide-icon [img]="XIcon" [size]="10" /></button>
          </div>
        }
        <label class="btn-secondary inline-flex items-center gap-2 cursor-pointer text-xs">
          <lucide-icon [img]="UploadIcon" [size]="14" /> Upload Photo
          <input type="file" accept="image/*" (change)="onPhotoSelect($event)" class="hidden" />
        </label>
      </div>

      <div class="mb-6"><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Notes</label>
        <textarea [(ngModel)]="form.notes" rows="3" class="input-base w-full resize-none" placeholder="Special instructions, access codes, etc."></textarea></div>

      <div class="flex justify-end gap-3 pt-4 border-t" [style.borderColor]="'var(--border-default)'">
        <button (click)="goBack()" class="btn-secondary">Cancel</button>
        <button (click)="onSave()" class="btn-primary flex items-center gap-2" [disabled]="saving()">
          <lucide-icon [img]="SaveIcon" [size]="14" /> {{ isEdit() ? 'Update Site' : 'Create Site' }}
        </button>
      </div>
    </div>
  `,
})
export class SiteFormComponent implements OnInit {
  private api = inject(ApiService); private toast = inject(ToastService);
  private router = inject(Router); private route = inject(ActivatedRoute);
  readonly SaveIcon = Save; readonly ArrowLeftIcon = ArrowLeft; readonly UploadIcon = Upload; readonly XIcon = X;
  readonly isEdit = signal(false); readonly saving = signal(false); readonly photoPreview = signal<string | null>(null);
  readonly clients = signal<any[]>([]);
  private photoFile: File | null = null; private siteId: string | null = null;

  form: any = { name: '', client_id: '', address: '', city: '', state: '', latitude: '', longitude: '', geofence_radius: 100, timezone: 'Africa/Lagos', contact_name: '', contact_phone: '', contact_email: '', notes: '' };

  states = ['Abia','Adamawa','Akwa Ibom','Anambra','Bauchi','Bayelsa','Benue','Borno','Cross River','Delta','Ebonyi','Edo','Ekiti','Enugu','FCT','Gombe','Imo','Jigawa','Kaduna','Kano','Katsina','Kebbi','Kogi','Kwara','Lagos','Nasarawa','Niger','Ogun','Ondo','Osun','Oyo','Plateau','Rivers','Sokoto','Taraba','Yobe','Zamfara'];

  ngOnInit(): void {
    this.api.get<any>('/clients').subscribe({ next: res => this.clients.set(res.data?.clients || res.data || []) });
    this.siteId = this.route.snapshot.paramMap.get('id');
    if (this.siteId && this.siteId !== 'new') {
      this.isEdit.set(true);
      this.api.get<any>(`/sites/${this.siteId}`).subscribe({ next: res => { if (res.data) { const s = res.data.site || res.data; Object.keys(this.form).forEach(k => { if (s[k] !== undefined) this.form[k] = s[k] || ''; }); if (s.photo_url) this.photoPreview.set(s.photo_url); } } });
    }
  }
  onPhotoSelect(e: Event): void { const f = (e.target as HTMLInputElement).files?.[0]; if (f) { this.photoFile = f; const r = new FileReader(); r.onload = () => this.photoPreview.set(r.result as string); r.readAsDataURL(f); } }
  removePhoto(): void { this.photoPreview.set(null); this.photoFile = null; }
  onSave(): void { this.saving.set(true); const url = this.isEdit() ? `/sites/${this.siteId}` : '/sites'; const req = this.isEdit() ? this.api.put(url, this.form) : this.api.post(url, this.form); req.subscribe({ next: () => { this.saving.set(false); this.toast.success(this.isEdit() ? 'Site updated' : 'Site created'); this.router.navigate(['/sites']); }, error: () => this.saving.set(false) }); }
  goBack(): void { this.router.navigate(['/sites']); }
}
