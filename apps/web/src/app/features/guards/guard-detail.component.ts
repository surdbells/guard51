import { Component, inject, signal, OnInit } from '@angular/core';
import { ActivatedRoute, RouterLink } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { NgClass } from '@angular/common';
import { LucideAngularModule, ArrowLeft, Edit, Shield, FileText, Award, AlertTriangle, CheckCircle, Upload, X, Clock, BarChart3 } from 'lucide-angular';
import { PageHeaderComponent } from '@shared/components/page-header/page-header.component';
import { ModalComponent } from '@shared/components/modal/modal.component';
import { LoadingSpinnerComponent } from '@shared/components/loading-spinner/loading-spinner.component';
import { ApiService } from '@core/services/api.service';
import { ToastService } from '@core/services/toast.service';

@Component({
  selector: 'g51-guard-detail',
  standalone: true,
  imports: [RouterLink, FormsModule, NgClass, LucideAngularModule, PageHeaderComponent, ModalComponent, LoadingSpinnerComponent],
  template: `
    <g51-page-header [title]="guard()?.first_name + ' ' + guard()?.last_name" subtitle="Guard profile, performance, and documents">
      <button class="btn-secondary flex items-center gap-2" routerLink="/guards"><lucide-icon [img]="ArrowLeftIcon" [size]="14" /> Back</button>
      <button class="btn-primary flex items-center gap-2" [routerLink]="['/guards', guard()?.id, 'edit']"><lucide-icon [img]="EditIcon" [size]="14" /> Edit</button>
    </g51-page-header>

    @if (loading()) { <g51-loading /> } @else if (guard()) {
      <!-- Tabs -->
      <div class="flex gap-1 mb-4">
        @for (tab of ['Profile', 'Performance', 'Documents']; track tab) {
          <button (click)="activeTab.set(tab)" class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors"
            [ngClass]="activeTab() === tab ? 'bg-[var(--color-brand-500)] text-white' : 'bg-[var(--surface-muted)]'"
            [style.color]="activeTab() !== tab ? 'var(--text-secondary)' : ''">{{ tab }}</button>
        }
      </div>

      @if (activeTab() === 'Profile') {
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
          <div class="card p-5 text-center">
            @if (guard()!.photo_url) { <img [src]="guard()!.photo_url" class="h-24 w-24 rounded-full mx-auto mb-3 object-cover" /> }
            @else { <div class="h-24 w-24 rounded-full mx-auto mb-3 flex items-center justify-center text-2xl font-bold text-white" [style.background]="'var(--color-brand-500)'">{{ guard()!.first_name?.charAt(0) }}{{ guard()!.last_name?.charAt(0) }}</div> }
            <h3 class="text-base font-bold" [style.color]="'var(--text-primary)'">{{ guard()!.first_name }} {{ guard()!.last_name }}</h3>
            <p class="text-xs" [style.color]="'var(--text-tertiary)'">{{ guard()!.employee_number }}</p>
            <span class="badge text-[10px] mt-2" [ngClass]="guard()!.status === 'active' ? 'bg-emerald-50 text-emerald-600' : 'bg-red-50 text-red-600'">{{ guard()!.status }}</span>
          </div>
          <div class="lg:col-span-2 card p-5">
            <h3 class="text-sm font-semibold mb-3" [style.color]="'var(--text-primary)'">Details</h3>
            <div class="grid grid-cols-2 gap-y-3 gap-x-6 text-xs">
              @for (field of profileFields; track field.key) {
                <div><span [style.color]="'var(--text-tertiary)'">{{ field.label }}</span>
                  <p class="font-medium" [style.color]="'var(--text-primary)'">{{ guard()![field.key] || '—' }}</p></div>
              }
            </div>
          </div>
        </div>
      }

      @if (activeTab() === 'Performance') {
        <div class="card p-5 mb-4">
          <h3 class="text-sm font-semibold mb-3" [style.color]="'var(--text-primary)'">Guard Performance Index (GPI)</h3>
          @if (performance()) {
            <div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-4">
              <div class="text-center p-3 rounded-lg" [style.background]="'var(--surface-muted)'">
                <p class="text-2xl font-bold" [style.color]="'var(--color-brand-500)'">{{ performance()!.overall_score || 0 }}%</p>
                <p class="text-[10px]" [style.color]="'var(--text-tertiary)'">Overall</p></div>
              <div class="text-center p-3 rounded-lg" [style.background]="'var(--surface-muted)'">
                <p class="text-lg font-bold" [style.color]="'var(--text-primary)'">{{ performance()!.punctuality_score || 0 }}%</p>
                <p class="text-[10px]" [style.color]="'var(--text-tertiary)'">Punctuality</p></div>
              <div class="text-center p-3 rounded-lg" [style.background]="'var(--surface-muted)'">
                <p class="text-lg font-bold" [style.color]="'var(--text-primary)'">{{ performance()!.report_completion_score || 0 }}%</p>
                <p class="text-[10px]" [style.color]="'var(--text-tertiary)'">Reports</p></div>
              <div class="text-center p-3 rounded-lg" [style.background]="'var(--surface-muted)'">
                <p class="text-lg font-bold" [style.color]="'var(--text-primary)'">{{ performance()!.tour_compliance_score || 0 }}%</p>
                <p class="text-[10px]" [style.color]="'var(--text-tertiary)'">Tour Compliance</p></div>
              <div class="text-center p-3 rounded-lg" [style.background]="'var(--surface-muted)'">
                <p class="text-lg font-bold" [style.color]="'var(--text-primary)'">{{ performance()!.incident_response_score || 0 }}%</p>
                <p class="text-[10px]" [style.color]="'var(--text-tertiary)'">Incident Response</p></div>
            </div>
          } @else { <p class="text-xs" [style.color]="'var(--text-tertiary)'">No performance data calculated yet.</p> }
        </div>
      }

      @if (activeTab() === 'Documents') {
        <div class="card p-5">
          <div class="flex items-center justify-between mb-3">
            <h3 class="text-sm font-semibold" [style.color]="'var(--text-primary)'">Guard Documents</h3>
            <button (click)="showUploadDoc.set(true)" class="btn-primary text-xs flex items-center gap-1"><lucide-icon [img]="UploadIcon" [size]="12" /> Upload Document</button>
          </div>
          @if (!documents().length) { <p class="text-xs" [style.color]="'var(--text-tertiary)'">No documents uploaded.</p> }
          @else {
            <div class="space-y-2">
              @for (doc of documents(); track doc.id) {
                <div class="flex items-center justify-between py-2 border-b" [style.borderColor]="'var(--border-default)'">
                  <div><p class="text-sm font-medium" [style.color]="'var(--text-primary)'">{{ doc.document_type || doc.name }}</p>
                    <p class="text-xs" [style.color]="'var(--text-tertiary)'">{{ doc.issue_date || '' }} {{ doc.expiry_date ? '· Expires: ' + doc.expiry_date : '' }}</p></div>
                  <div class="flex items-center gap-2">
                    <span class="badge text-[10px]" [ngClass]="doc.is_verified ? 'bg-emerald-50 text-emerald-600' : 'bg-amber-50 text-amber-600'">{{ doc.is_verified ? 'Verified' : 'Pending' }}</span>
                    @if (doc.file_url) { <a [href]="doc.file_url" target="_blank" class="btn-secondary text-[10px] py-1 px-2">View</a> }
                  </div>
                </div>
              }
            </div>
          }
        </div>
        <g51-modal [open]="showUploadDoc()" title="Upload Document" maxWidth="480px" (closed)="showUploadDoc.set(false)">
          <div class="space-y-3">
            <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Document Type *</label>
              <select [(ngModel)]="docForm.document_type" class="input-base w-full">
                <option value="national_id">National ID</option><option value="drivers_license">Driver's License</option>
                <option value="security_license">Security License</option><option value="first_aid">First Aid Certificate</option>
                <option value="medical">Medical Report</option><option value="passport">Passport</option><option value="other">Other</option>
              </select></div>
            <div class="grid grid-cols-2 gap-3">
              <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Issue Date</label>
                <input type="date" [(ngModel)]="docForm.issue_date" class="input-base w-full" /></div>
              <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Expiry Date</label>
                <input type="date" [(ngModel)]="docForm.expiry_date" class="input-base w-full" /></div>
            </div>
            <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">File *</label>
              <label class="btn-secondary inline-flex items-center gap-2 cursor-pointer text-xs">
                <lucide-icon [img]="UploadIcon" [size]="14" /> {{ docFile ? docFile.name : 'Choose file' }}
                <input type="file" accept="image/*,.pdf" (change)="onDocFileSelect($event)" class="hidden" />
              </label></div>
          </div>
          <div modal-footer><button (click)="showUploadDoc.set(false)" class="btn-secondary">Cancel</button>
            <button (click)="uploadDocument()" class="btn-primary">Upload</button></div>
        </g51-modal>
      }
    }
  `,
})
export class GuardDetailComponent implements OnInit {
  private api = inject(ApiService); private toast = inject(ToastService);
  private route = inject(ActivatedRoute);
  readonly ArrowLeftIcon = ArrowLeft; readonly EditIcon = Edit; readonly UploadIcon = Upload;
  readonly ShieldIcon = Shield; readonly BarChartIcon = BarChart3;

  readonly loading = signal(true); readonly guard = signal<any>(null);
  readonly performance = signal<any>(null); readonly documents = signal<any[]>([]);
  readonly activeTab = signal('Profile'); readonly showUploadDoc = signal(false);
  docForm = { document_type: 'national_id', issue_date: '', expiry_date: '' };
  docFile: File | null = null;

  profileFields = [
    { key: 'phone', label: 'Phone' }, { key: 'email', label: 'Email' },
    { key: 'gender', label: 'Gender' }, { key: 'date_of_birth', label: 'Date of Birth' },
    { key: 'state', label: 'State' }, { key: 'address', label: 'Address' },
    { key: 'hire_date', label: 'Hire Date' }, { key: 'pay_type', label: 'Pay Type' },
    { key: 'pay_rate', label: 'Pay Rate (₦)' }, { key: 'bank_name', label: 'Bank' },
    { key: 'bank_account_number', label: 'Account #' }, { key: 'bank_account_name', label: 'Account Name' },
    { key: 'emergency_contact_name', label: 'Emergency Contact' }, { key: 'emergency_contact_phone', label: 'Emergency Phone' },
  ];

  ngOnInit(): void {
    const id = this.route.snapshot.paramMap.get('id');
    if (!id) return;
    this.api.get<any>(`/guards/${id}`).subscribe({
      next: res => { this.guard.set(res.data?.guard || res.data); this.loading.set(false); },
      error: () => this.loading.set(false),
    });
    this.api.get<any>(`/analytics/guard/${id}/performance`).subscribe({ next: res => { if (res.data) this.performance.set(res.data); }, error: () => {} });
    this.api.get<any>(`/guards/${id}/documents`).subscribe({ next: res => this.documents.set(res.data?.documents || res.data || []), error: () => {} });
  }

  onDocFileSelect(e: Event): void { this.docFile = (e.target as HTMLInputElement).files?.[0] || null; }

  uploadDocument(): void {
    if (!this.docFile) { this.toast.warning('Please select a file'); return; }
    const id = this.guard()?.id;
    const fd = new FormData();
    fd.append('document_type', this.docForm.document_type);
    if (this.docForm.issue_date) fd.append('issue_date', this.docForm.issue_date);
    if (this.docForm.expiry_date) fd.append('expiry_date', this.docForm.expiry_date);
    fd.append('file', this.docFile);
    this.api.post(`/guards/${id}/documents`, fd).subscribe({
      next: () => { this.showUploadDoc.set(false); this.toast.success('Document uploaded'); this.docFile = null; this.ngOnInit(); },
    });
  }
}
