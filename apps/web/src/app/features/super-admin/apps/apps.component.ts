import { Component, inject, signal, OnInit } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { NgClass } from '@angular/common';
import { LucideAngularModule, Smartphone, Plus, Upload, Download, CheckCircle } from 'lucide-angular';
import { PageHeaderComponent } from '@shared/components/page-header/page-header.component';
import { ModalComponent } from '@shared/components/modal/modal.component';
import { EmptyStateComponent } from '@shared/components/empty-state/empty-state.component';
import { LoadingSpinnerComponent } from '@shared/components/loading-spinner/loading-spinner.component';
import { ApiService } from '@core/services/api.service';
import { ToastService } from '@core/services/toast.service';

@Component({
  selector: 'g51-apps',
  standalone: true,
  imports: [FormsModule, NgClass, LucideAngularModule, PageHeaderComponent, ModalComponent, EmptyStateComponent, LoadingSpinnerComponent],
  template: `
    <g51-page-header title="App Distribution" subtitle="Manage mobile app releases and distribution">
      <button (click)="showCreate.set(true)" class="btn-primary flex items-center gap-2"><lucide-icon [img]="PlusIcon" [size]="16" /> New Release</button>
    </g51-page-header>

    @if (loading()) { <g51-loading /> }
    @else if (!releases().length) { <g51-empty-state title="No Releases" message="Publish your first app release." [icon]="SmartphoneIcon" /> }
    @else {
      <div class="space-y-3">
        @for (r of releases(); track r.id) {
          <div class="card p-4">
            <div class="flex items-center justify-between">
              <div>
                <div class="flex items-center gap-2 mb-1">
                  <p class="text-sm font-bold font-mono" [style.color]="'var(--text-primary)'">v{{ r.version }}</p>
                  <span class="badge text-[10px]" [ngClass]="r.platform === 'android' ? 'bg-emerald-50 text-emerald-600' : 'bg-blue-50 text-blue-600'">{{ r.platform }}</span>
                  <span class="badge text-[10px]" [ngClass]="r.is_active ? 'bg-emerald-50 text-emerald-600' : 'bg-gray-100 text-gray-500'">{{ r.is_active ? 'Active' : 'Inactive' }}</span>
                  @if (r.is_mandatory) { <span class="badge text-[10px] bg-red-50 text-red-600">Mandatory</span> }
                </div>
                <p class="text-xs" [style.color]="'var(--text-secondary)'">{{ r.release_notes || 'No release notes' }}</p>
                <p class="text-[10px]" [style.color]="'var(--text-tertiary)'">{{ r.release_type || 'stable' }} · {{ r.downloads || 0 }} downloads · {{ r.created_at?.slice(0, 10) }}</p>
              </div>
              <div class="flex items-center gap-2">
                @if (!r.is_active) { <button (click)="activate(r)" class="btn-primary text-[10px] py-1 px-2">Activate</button> }
                @else { <button (click)="deactivate(r)" class="btn-secondary text-[10px] py-1 px-2">Deactivate</button> }
              </div>
            </div>
          </div>
        }
      </div>
    }

    <g51-modal [open]="showCreate()" title="New App Release" maxWidth="480px" (closed)="showCreate.set(false)">
      <div class="space-y-3">
        <div class="grid grid-cols-2 gap-3">
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Version *</label><input type="text" [(ngModel)]="form.version" class="input-base w-full" placeholder="1.0.0" /></div>
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Platform *</label>
            <select [(ngModel)]="form.platform" class="input-base w-full"><option value="android">Android</option><option value="ios">iOS</option></select></div>
        </div>
        <div class="grid grid-cols-2 gap-3">
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Release Type</label>
            <select [(ngModel)]="form.release_type" class="input-base w-full"><option value="stable">Stable</option><option value="beta">Beta</option></select></div>
          <div class="flex items-end pb-1"><label class="flex items-center gap-2 text-xs cursor-pointer"><input type="checkbox" [(ngModel)]="form.is_mandatory" class="rounded" /> Mandatory Update</label></div>
        </div>
        <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Download URL</label><input type="text" [(ngModel)]="form.download_url" class="input-base w-full" placeholder="https://..." /></div>
        <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Release Notes</label><textarea [(ngModel)]="form.release_notes" rows="3" class="input-base w-full resize-none"></textarea></div>
      </div>
      <div modal-footer><button (click)="showCreate.set(false)" class="btn-secondary">Cancel</button>
        <button (click)="createRelease()" class="btn-primary">Publish Release</button></div>
    </g51-modal>
  `,
})
export class AppsComponent implements OnInit {
  private api = inject(ApiService); private toast = inject(ToastService);
  readonly SmartphoneIcon = Smartphone; readonly PlusIcon = Plus;
  readonly loading = signal(true); readonly showCreate = signal(false);
  readonly releases = signal<any[]>([]);
  form: any = { version: '', platform: 'android', release_type: 'stable', is_mandatory: false, download_url: '', release_notes: '' };

  ngOnInit(): void {
    this.api.get<any>('/admin/apps').subscribe({ next: r => { this.releases.set(r.data?.releases || r.data || []); this.loading.set(false); }, error: () => this.loading.set(false) });
  }
  createRelease(): void { this.api.post('/admin/apps', this.form).subscribe({ next: () => { this.showCreate.set(false); this.toast.success('Release published'); this.ngOnInit(); } }); }
  activate(r: any): void { this.api.post(`/admin/apps/${r.id}/activate`, {}).subscribe({ next: () => { this.toast.success('Activated'); this.ngOnInit(); } }); }
  deactivate(r: any): void { this.api.post(`/admin/apps/${r.id}/deactivate`, {}).subscribe({ next: () => { this.toast.success('Deactivated'); this.ngOnInit(); } }); }
}
