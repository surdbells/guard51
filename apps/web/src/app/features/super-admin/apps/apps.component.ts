import { Component, inject, signal, OnInit } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { NgClass, TitleCasePipe } from '@angular/common';
import { LucideAngularModule, Smartphone, Plus, Upload, Download, CheckCircle, Trash2, ExternalLink, Apple, Globe } from 'lucide-angular';
import { PageHeaderComponent } from '@shared/components/page-header/page-header.component';
import { ModalComponent } from '@shared/components/modal/modal.component';
import { EmptyStateComponent } from '@shared/components/empty-state/empty-state.component';
import { LoadingSpinnerComponent } from '@shared/components/loading-spinner/loading-spinner.component';
import { ApiService } from '@core/services/api.service';
import { ToastService } from '@core/services/toast.service';
import { ConfirmService } from '@core/services/confirm.service';

@Component({
  selector: 'g51-apps',
  standalone: true,
  imports: [FormsModule, NgClass, TitleCasePipe, LucideAngularModule, PageHeaderComponent, ModalComponent, EmptyStateComponent, LoadingSpinnerComponent],
  template: `
    <g51-page-header title="App Distribution" subtitle="Manage mobile app releases and distribution">
      <button (click)="openCreate()" class="btn-primary flex items-center gap-2"><lucide-icon [img]="PlusIcon" [size]="16" /> New Release</button>
    </g51-page-header>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
      <div class="card p-4 text-center"><p class="text-2xl font-bold" [style.color]="'var(--text-primary)'">{{ stats().total_releases }}</p><p class="text-[10px]" [style.color]="'var(--text-tertiary)'">Total Releases</p></div>
      <div class="card p-4 text-center"><p class="text-2xl font-bold" [style.color]="'var(--text-primary)'">{{ stats().android_latest || '—' }}</p><p class="text-[10px]" [style.color]="'var(--text-tertiary)'">Android Latest</p></div>
      <div class="card p-4 text-center"><p class="text-2xl font-bold" [style.color]="'var(--text-primary)'">{{ stats().ios_latest || '—' }}</p><p class="text-[10px]" [style.color]="'var(--text-tertiary)'">iOS Latest</p></div>
      <div class="card p-4 text-center"><p class="text-2xl font-bold" [style.color]="'var(--text-primary)'">{{ stats().total_downloads }}</p><p class="text-[10px]" [style.color]="'var(--text-tertiary)'">Total Downloads</p></div>
    </div>

    <div class="tab-pills">
      @for (tab of ['All', 'Android', 'iOS']; track tab) {
        <button (click)="platformFilter.set(tab === 'All' ? '' : tab.toLowerCase()); load()"
          class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors"
          [ngClass]="(tab === 'All' && !platformFilter()) || platformFilter() === tab.toLowerCase() ? 'bg-[var(--color-brand-500)] text-white' : 'bg-[var(--surface-muted)]'"
          [style.color]="!((tab === 'All' && !platformFilter()) || platformFilter() === tab.toLowerCase()) ? 'var(--text-secondary)' : ''">{{ tab }}</button>
      }
    </div>

    @if (loading()) { <g51-loading /> }
    @else if (!releases().length) { <g51-empty-state title="No Releases" message="Upload your first app release." [icon]="SmartphoneIcon" /> }
    @else {
      <div class="space-y-3">
        @for (r of filteredReleases(); track r.id) {
          <div class="card p-4">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-3">
                <div class="h-11 w-11 rounded-xl flex items-center justify-center" [style.background]="r.platform === 'android' ? '#E8F5E9' : '#E3F2FD'" [style.color]="r.platform === 'android' ? '#2E7D32' : '#1565C0'">
                  <lucide-icon [img]="r.platform === 'ios' ? AppleIcon : SmartphoneIcon" [size]="20" />
                </div>
                <div>
                  <div class="flex items-center gap-2">
                    <p class="text-sm font-semibold" [style.color]="'var(--text-primary)'">v{{ r.version_name || r.version }}</p>
                    <span class="text-[10px] font-mono px-1.5 py-0.5 rounded" [style.background]="'var(--surface-muted)'" [style.color]="'var(--text-tertiary)'">Build {{ r.version_code || r.build_number || '—' }}</span>
                    @if (r.is_latest) { <span class="badge text-[10px] bg-emerald-50 text-emerald-600">Latest</span> }
                  </div>
                  <p class="text-xs" [style.color]="'var(--text-tertiary)'">{{ r.platform | titlecase }} · {{ r.release_type || 'stable' }} · {{ r.created_at?.slice(0, 10) }}</p>
                  @if (r.release_notes) { <p class="text-xs mt-1" [style.color]="'var(--text-secondary)'">{{ r.release_notes?.slice(0, 100) }}{{ r.release_notes?.length > 100 ? '...' : '' }}</p> }
                </div>
              </div>
              <div class="flex items-center gap-2">
                <span class="text-xs tabular-nums" [style.color]="'var(--text-tertiary)'">{{ r.download_count || 0 }} downloads</span>
                @if (r.download_url) {
                  <a [href]="r.download_url" target="_blank" class="btn-secondary text-xs py-1 px-2 flex items-center gap-1"><lucide-icon [img]="DownloadIcon" [size]="12" /> Download</a>
                }
                <button (click)="deleteRelease(r)" class="p-1 rounded hover:bg-red-50"><lucide-icon [img]="TrashIcon" [size]="14" class="text-red-400" /></button>
              </div>
            </div>
          </div>
        }
      </div>
    }

    <!-- Create Release Modal -->
    <g51-modal [open]="showCreate()" title="Upload New Release" maxWidth="520px" (closed)="showCreate.set(false)">
      <div class="space-y-3">
        <div class="grid grid-cols-2 gap-3">
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Platform *</label>
            <select [(ngModel)]="form.platform" class="input-base w-full"><option value="android">Android</option><option value="ios">iOS</option></select></div>
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Release Type</label>
            <select [(ngModel)]="form.release_type" class="input-base w-full"><option value="stable">Stable</option><option value="beta">Beta</option><option value="alpha">Alpha</option></select></div>
        </div>
        <div class="grid grid-cols-2 gap-3">
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Version Name *</label><input type="text" [(ngModel)]="form.version_name" class="input-base w-full" placeholder="e.g. 1.2.0" /></div>
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Build Number *</label><input type="number" [(ngModel)]="form.version_code" class="input-base w-full" placeholder="e.g. 12" /></div>
        </div>
        <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Min Supported Version</label><input type="text" [(ngModel)]="form.min_version" class="input-base w-full" placeholder="e.g. 1.0.0" /></div>
        <div>
          <label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">App File (.apk / .ipa) *</label>
          <div class="border-2 border-dashed rounded-lg p-4 text-center cursor-pointer hover:border-[var(--brand-500)] transition-colors"
            [style.borderColor]="selectedFile ? 'var(--brand-500)' : 'var(--border-default)'"
            (click)="fileInput.click()">
            <input #fileInput type="file" [accept]="form.platform === 'ios' ? '.ipa' : '.apk'" (change)="onFileSelect($event)" class="hidden" />
            @if (selectedFile) {
              <lucide-icon [img]="CheckCircleIcon" [size]="24" class="mx-auto mb-1" [style.color]="'var(--brand-500)'" />
              <p class="text-xs font-medium" [style.color]="'var(--text-primary)'">{{ selectedFile.name }}</p>
              <p class="text-[10px]" [style.color]="'var(--text-tertiary)'">{{ (selectedFile.size / 1024 / 1024).toFixed(1) }} MB</p>
            } @else {
              <lucide-icon [img]="UploadIcon" [size]="24" class="mx-auto mb-1" [style.color]="'var(--text-tertiary)'" />
              <p class="text-xs" [style.color]="'var(--text-tertiary)'">Click to select file or drag & drop</p>
            }
          </div>
        </div>
        <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Release Notes</label>
          <textarea [(ngModel)]="form.release_notes" rows="3" class="input-base w-full resize-none" placeholder="What's new in this release..."></textarea></div>
        <label class="flex items-center gap-2 text-xs"><input type="checkbox" [(ngModel)]="form.force_update" class="rounded" /> Force update (require all users to update)</label>
      </div>
      <div modal-footer><button (click)="showCreate.set(false)" class="btn-secondary">Cancel</button>
        <button (click)="uploadRelease()" class="btn-primary flex items-center gap-1" [disabled]="uploading()">
          @if (uploading()) { <span class="animate-spin">⏳</span> Uploading... } @else { <lucide-icon [img]="UploadIcon" [size]="12" /> Upload Release }
        </button></div>
    </g51-modal>
  `,
})
export class AppsComponent implements OnInit {
  private api = inject(ApiService); private toast = inject(ToastService); private confirmSvc = inject(ConfirmService);
  readonly SmartphoneIcon = Smartphone; readonly PlusIcon = Plus; readonly UploadIcon = Upload;
  readonly DownloadIcon = Download; readonly CheckCircleIcon = CheckCircle; readonly TrashIcon = Trash2;
  readonly AppleIcon = Apple; readonly ExternalLinkIcon = ExternalLink;
  readonly loading = signal(true); readonly showCreate = signal(false); readonly uploading = signal(false);
  readonly platformFilter = signal('');
  readonly releases = signal<any[]>([]);
  readonly stats = signal<any>({ total_releases: 0, android_latest: '', ios_latest: '', total_downloads: 0 });
  selectedFile: File | null = null;
  form: any = { platform: 'android', release_type: 'stable', version_name: '', version_code: 1, min_version: '', release_notes: '', force_update: false };

  filteredReleases() { const p = this.platformFilter(); return !p ? this.releases() : this.releases().filter(r => r.platform === p); }

  ngOnInit(): void { this.load(); }

  load(): void {
    this.loading.set(true);
    this.api.get<any>('/admin/apps/releases').subscribe({
      next: r => {
        const data = r.data?.releases || r.data || [];
        this.releases.set(data);
        const android = data.filter((x: any) => x.platform === 'android');
        const ios = data.filter((x: any) => x.platform === 'ios');
        this.stats.set({
          total_releases: data.length,
          android_latest: android[0]?.version_name || '—',
          ios_latest: ios[0]?.version_name || '—',
          total_downloads: data.reduce((sum: number, x: any) => sum + (x.download_count || 0), 0),
        });
        this.loading.set(false);
      },
      error: () => this.loading.set(false),
    });
  }

  openCreate(): void { this.form = { platform: 'android', release_type: 'stable', version_name: '', version_code: 1, min_version: '', release_notes: '', force_update: false }; this.selectedFile = null; this.showCreate.set(true); }

  onFileSelect(event: Event): void {
    const input = event.target as HTMLInputElement;
    if (input.files?.length) this.selectedFile = input.files[0];
  }

  uploadRelease(): void {
    if (!this.form.version_name) { this.toast.warning('Version name required'); return; }
    this.uploading.set(true);

    const formData = new FormData();
    Object.entries(this.form).forEach(([k, v]) => formData.append(k, String(v)));
    if (this.selectedFile) formData.append('file', this.selectedFile);

    // Use raw fetch for multipart upload
    const token = localStorage.getItem('g51_access_token') || '';
    fetch('https://api.guard51.com/api/v1/admin/apps/releases', {
      method: 'POST', headers: { 'Authorization': `Bearer ${token}` }, body: formData,
    }).then(res => res.json()).then(data => {
      this.uploading.set(false);
      if (data.success !== false) { this.showCreate.set(false); this.toast.success('Release uploaded'); this.load(); }
      else { this.toast.error(data.message || 'Upload failed'); }
    }).catch(() => { this.uploading.set(false); this.toast.error('Upload failed'); });
  }

  async deleteRelease(r: any): Promise<void> {
    if (await this.confirmSvc.delete(`v${r.version_name || r.version}`)) {
      this.api.delete(`/admin/apps/releases/${r.id}`).subscribe({ next: () => { this.toast.success('Deleted'); this.load(); } });
    }
  }
}
