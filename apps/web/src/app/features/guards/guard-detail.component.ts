import { Component, inject, signal, OnInit } from '@angular/core';
import { ActivatedRoute, RouterLink } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { NgClass } from '@angular/common';
import { LucideAngularModule, ArrowLeft, Edit, Shield, FileText, Upload, CheckCircle, Phone, Mail, MapPin, Calendar, Eye, Download, Pencil } from 'lucide-angular';
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
    @if (loading()) { <g51-loading /> } @else if (guard()) {

    <!-- Breadcrumb -->
    <div class="flex items-center gap-1 text-xs mb-3" [style.color]="'var(--text-tertiary)'">
      <a routerLink="/guards" class="hover:underline cursor-pointer">Guards</a> /
      <span [style.color]="'var(--color-brand-500)'" class="font-medium">Guard Detail</span>
    </div>

    <!-- Tabs -->
    <div class="tab-pills">
      @for (tab of tabs; track tab) {
        <button (click)="activeTab.set(tab)" class="px-4 py-2.5 text-xs font-medium transition-colors border-b-2 -mb-px"
          [style.color]="activeTab() === tab ? 'var(--color-brand-500)' : 'var(--text-tertiary)'"
          [style.borderColor]="activeTab() === tab ? 'var(--color-brand-500)' : 'transparent'">{{ tab }}</button>
      }
    </div>

    <!-- PROFILE TAB -->
    @if (activeTab() === 'Guard Profile') {
      <!-- Header card — avatar + quick info -->
      <div class="card p-5 mb-4">
        <div class="flex flex-col sm:flex-row items-start gap-5">
          <div class="flex items-center gap-4 flex-1">
            @if (guard()!.photo_url) { <img [src]="guard()!.photo_url" class="h-16 w-16 rounded-full object-cover" /> }
            @else { <div class="h-16 w-16 rounded-full flex items-center justify-center text-xl font-bold text-white shrink-0" [style.background]="'var(--color-brand-500)'">{{ guard()!.first_name?.charAt(0) }}{{ guard()!.last_name?.charAt(0) }}</div> }
            <div>
              <h2 class="text-lg font-bold font-heading" [style.color]="'var(--text-primary)'">{{ guard()!.first_name }} {{ guard()!.last_name }}</h2>
              <p class="text-xs"><span [style.color]="'var(--color-brand-500)'" class="font-medium">{{ guard()!.rank || 'Security Guard' }}</span>
                <span [style.color]="'var(--text-tertiary)'"> | {{ guard()!.site_name || 'Unassigned' }}</span></p>
            </div>
          </div>
          <div class="grid grid-cols-2 gap-x-8 gap-y-2 text-xs">
            <div><span [style.color]="'var(--text-tertiary)'">Employee ID:</span> <span class="font-medium ml-2" [style.color]="'var(--text-primary)'">{{ guard()!.employee_number || '—' }}</span></div>
            <div><span [style.color]="'var(--text-tertiary)'">Phone:</span> <span class="font-medium ml-2" [style.color]="'var(--text-primary)'">{{ guard()!.phone || '—' }}</span></div>
            <div><span [style.color]="'var(--text-tertiary)'">Status:</span> <span class="font-medium ml-2" [ngClass]="guard()!.status === 'active' ? 'text-emerald-600' : 'text-red-500'">{{ guard()!.status }}</span></div>
            <div><span [style.color]="'var(--text-tertiary)'">Email:</span> <span class="font-medium ml-2" [style.color]="'var(--text-primary)'">{{ guard()!.email || '—' }}</span></div>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <!-- Personal Information -->
        <div class="card p-5">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-semibold" [style.color]="'var(--text-primary)'">Personal Information</h3>
            <button [routerLink]="['/guards/edit', guard()!.id]" class="p-1.5 rounded-lg hover:bg-[var(--surface-muted)]"><lucide-icon [img]="PencilIcon" [size]="14" [style.color]="'var(--text-tertiary)'" /></button>
          </div>
          <div class="grid grid-cols-2 gap-y-4 gap-x-6 text-xs">
            @for (f of personalFields; track f.key) {
              <div>
                <p [style.color]="'var(--text-tertiary)'" class="mb-0.5">{{ f.label }}</p>
                <p class="font-medium" [style.color]="'var(--text-primary)'">{{ guard()![f.key] || '—' }}</p>
              </div>
            }
          </div>
        </div>

        <!-- Account Information -->
        <div class="space-y-4">
          <div class="card p-5">
            <div class="flex items-center justify-between mb-4">
              <h3 class="text-sm font-semibold" [style.color]="'var(--text-primary)'">Account Information</h3>
              <button [routerLink]="['/guards/edit', guard()!.id]" class="p-1.5 rounded-lg hover:bg-[var(--surface-muted)]"><lucide-icon [img]="PencilIcon" [size]="14" [style.color]="'var(--text-tertiary)'" /></button>
            </div>
            <div class="grid grid-cols-3 gap-4 text-xs">
              <div><p [style.color]="'var(--text-tertiary)'" class="mb-0.5">Bank</p><p class="font-medium" [style.color]="'var(--text-primary)'">{{ guard()!.bank_name || '—' }}</p></div>
              <div><p [style.color]="'var(--text-tertiary)'" class="mb-0.5">Account Name</p><p class="font-medium" [style.color]="'var(--text-primary)'">{{ guard()!.bank_account_name || '—' }}</p></div>
              <div><p [style.color]="'var(--text-tertiary)'" class="mb-0.5">Account Number</p><p class="font-medium" [style.color]="'var(--text-primary)'">{{ guard()!.bank_account_number || '—' }}</p></div>
            </div>
            <div class="grid grid-cols-2 gap-4 text-xs mt-4">
              <div><p [style.color]="'var(--text-tertiary)'" class="mb-0.5">Pay Type</p><p class="font-medium" [style.color]="'var(--text-primary)'">{{ guard()!.pay_type || '—' }}</p></div>
              <div><p [style.color]="'var(--text-tertiary)'" class="mb-0.5">Pay Rate</p><p class="font-medium" [style.color]="'var(--text-primary)'">{{ guard()!.pay_rate ? '₦' + guard()!.pay_rate : '—' }}</p></div>
            </div>
          </div>
          <!-- Emergency Contact -->
          <div class="card p-5">
            <div class="flex items-center justify-between mb-4">
              <h3 class="text-sm font-semibold" [style.color]="'var(--text-primary)'">Emergency Contact</h3>
              <button [routerLink]="['/guards/edit', guard()!.id]" class="p-1.5 rounded-lg hover:bg-[var(--surface-muted)]"><lucide-icon [img]="PencilIcon" [size]="14" [style.color]="'var(--text-tertiary)'" /></button>
            </div>
            <div class="grid grid-cols-2 gap-4 text-xs">
              <div><p [style.color]="'var(--text-tertiary)'" class="mb-0.5">Name</p><p class="font-medium" [style.color]="'var(--text-primary)'">{{ guard()!.emergency_contact_name || '—' }}</p></div>
              <div><p [style.color]="'var(--text-tertiary)'" class="mb-0.5">Phone</p><p class="font-medium" [style.color]="'var(--text-primary)'">{{ guard()!.emergency_contact_phone || '—' }}</p></div>
            </div>
          </div>
        </div>
      </div>
    }

    <!-- PERFORMANCE TAB -->
    @if (activeTab() === 'Performance') {
      @if (performance()) {
        <div class="card p-5 mb-4 text-center">
          <p class="text-[10px] uppercase tracking-wide mb-1" [style.color]="'var(--text-tertiary)'">Overall Guard Performance Index</p>
          <p class="text-4xl font-bold font-heading" [style.color]="(performance()!.overall_score || 0) >= 80 ? '#10B981' : (performance()!.overall_score || 0) >= 50 ? '#F59E0B' : '#EF4444'">{{ performance()!.overall_score || 0 }}%</p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
          @for (m of perfMetrics; track m.key) {
            <div class="card p-4">
              <div class="flex items-center justify-between mb-2">
                <p class="text-xs font-medium" [style.color]="'var(--text-primary)'">{{ m.label }}</p>
                <p class="text-sm font-bold tabular-nums" [style.color]="'var(--text-primary)'">{{ performance()![m.key] || 0 }}%</p>
              </div>
              <div class="w-full h-2.5 rounded-full" [style.background]="'var(--surface-muted)'">
                <div class="h-2.5 rounded-full transition-all" [style.width.%]="performance()![m.key] || 0"
                  [style.background]="(performance()![m.key] || 0) >= 80 ? '#10B981' : (performance()![m.key] || 0) >= 50 ? '#F59E0B' : '#EF4444'"></div>
              </div>
              <p class="text-[10px] mt-1" [style.color]="'var(--text-tertiary)'">{{ m.desc }}</p>
            </div>
          }
        </div>
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
          <div class="card p-3 text-center"><p class="text-lg font-bold" [style.color]="'var(--text-primary)'">{{ performance()!.total_shifts || 0 }}</p><p class="text-[10px]" [style.color]="'var(--text-tertiary)'">Total Shifts</p></div>
          <div class="card p-3 text-center"><p class="text-lg font-bold" [style.color]="'var(--text-primary)'">{{ performance()!.total_reports || 0 }}</p><p class="text-[10px]" [style.color]="'var(--text-tertiary)'">Reports Filed</p></div>
          <div class="card p-3 text-center"><p class="text-lg font-bold" [style.color]="'var(--text-primary)'">{{ performance()!.total_incidents || 0 }}</p><p class="text-[10px]" [style.color]="'var(--text-tertiary)'">Incidents</p></div>
          <div class="card p-3 text-center"><p class="text-lg font-bold" [style.color]="'var(--text-primary)'">{{ performance()!.total_hours || 0 }}h</p><p class="text-[10px]" [style.color]="'var(--text-tertiary)'">Hours Worked</p></div>
        </div>
      } @else { <div class="card p-8 text-center"><p class="text-sm" [style.color]="'var(--text-tertiary)'">Performance data will appear after the guard completes shifts.</p></div> }
    }

    <!-- DOCUMENTS TAB -->
    @if (activeTab() === 'Contracts & Documents') {
      <div class="flex justify-end mb-3">
        <button (click)="showUploadDoc.set(true)" class="btn-primary text-xs flex items-center gap-1"><lucide-icon [img]="UploadIcon" [size]="12" /> Upload Document</button>
      </div>
      @if (!documents().length) { <div class="card p-8 text-center"><p class="text-sm" [style.color]="'var(--text-tertiary)'">No documents uploaded yet.</p></div> }
      @else {
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          @for (doc of documents(); track doc.id) {
            <div class="card p-4">
              <div class="flex items-start gap-3">
                <div class="h-10 w-10 rounded-lg flex items-center justify-center shrink-0" style="background:#EFF6FF;color:#3B82F6">
                  <lucide-icon [img]="FileTextIcon" [size]="18" />
                </div>
                <div class="flex-1 min-w-0">
                  <p class="text-sm font-semibold" [style.color]="'var(--text-primary)'">{{ doc.title || doc.document_type }}</p>
                  <p class="text-xs" [style.color]="'var(--text-tertiary)'">{{ doc.document_type }} {{ doc.expiry_date ? '· Expires: ' + doc.expiry_date : '' }}</p>
                  <div class="flex items-center gap-2 mt-2">
                    <span class="badge text-[10px]" [ngClass]="doc.is_verified ? 'bg-emerald-50 text-emerald-600' : 'bg-amber-50 text-amber-600'">{{ doc.is_verified ? 'Verified' : 'Pending' }}</span>
                    @if (doc.file_url) {
                      <a [href]="getDocUrl(doc.file_url)" target="_blank" class="btn-secondary text-[10px] py-1 px-2 flex items-center gap-1"><lucide-icon [img]="EyeIcon" [size]="10" /> Preview</a>
                      <a [href]="getDocUrl(doc.file_url)" download class="btn-secondary text-[10px] py-1 px-2 flex items-center gap-1"><lucide-icon [img]="DownloadIcon" [size]="10" /> Download</a>
                    }
                  </div>
                </div>
              </div>
            </div>
          }
        </div>
      }
      <g51-modal [open]="showUploadDoc()" title="Upload Document" maxWidth="480px" (closed)="showUploadDoc.set(false)">
        <div class="space-y-3">
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Document Type *</label>
            <select [(ngModel)]="docForm.document_type" class="input-base w-full">
              <option value="national_id">National ID (NIN)</option><option value="drivers_license">Driver's License</option>
              <option value="security_license">Security License</option><option value="first_aid">First Aid Certificate</option>
              <option value="medical">Medical Report</option><option value="passport">International Passport</option><option value="other">Other</option>
            </select></div>
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Title</label>
            <input type="text" [(ngModel)]="docForm.title" class="input-base w-full" placeholder="Optional" /></div>
          <div class="grid grid-cols-2 gap-3">
            <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Issue Date</label><input type="date" [(ngModel)]="docForm.issue_date" class="input-base w-full" /></div>
            <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Expiry Date</label><input type="date" [(ngModel)]="docForm.expiry_date" class="input-base w-full" /></div>
          </div>
          <div>
            <label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">File *</label>
            <div class="border-2 border-dashed rounded-lg p-4 text-center cursor-pointer transition-colors"
              [style.borderColor]="docFile ? 'var(--brand-500)' : 'var(--border-default)'" (click)="docInput.click()">
              <input #docInput type="file" accept="image/*,.pdf,.doc,.docx" (change)="onDocFileSelect($event)" class="hidden" />
              @if (docFile) {
                <lucide-icon [img]="CheckCircleIcon" [size]="20" class="mx-auto mb-1" [style.color]="'var(--brand-500)'" />
                <p class="text-xs font-medium" [style.color]="'var(--text-primary)'">{{ docFile.name }}</p>
                <p class="text-[10px]" [style.color]="'var(--text-tertiary)'">{{ (docFile.size / 1024).toFixed(0) }} KB</p>
              } @else {
                <lucide-icon [img]="UploadIcon" [size]="20" class="mx-auto mb-1" [style.color]="'var(--text-tertiary)'" />
                <p class="text-xs" [style.color]="'var(--text-tertiary)'">Click to select PDF, image, or document</p>
              }
            </div>
          </div>
        </div>
        <div modal-footer><button (click)="showUploadDoc.set(false)" class="btn-secondary">Cancel</button>
          <button (click)="uploadDocument()" class="btn-primary" [disabled]="uploading()">{{ uploading() ? 'Uploading...' : 'Upload' }}</button></div>
      </g51-modal>
    }

    <!-- PAYROLL TAB -->
    @if (activeTab() === 'Payroll & Benefits') {
      <div class="card p-5">
        <h3 class="text-sm font-semibold mb-3" [style.color]="'var(--text-primary)'">Compensation</h3>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-xs">
          <div><p [style.color]="'var(--text-tertiary)'" class="mb-0.5">Pay Type</p><p class="font-medium" [style.color]="'var(--text-primary)'">{{ guard()!.pay_type || '—' }}</p></div>
          <div><p [style.color]="'var(--text-tertiary)'" class="mb-0.5">Base Rate</p><p class="font-medium" [style.color]="'var(--text-primary)'">₦{{ guard()!.pay_rate || 0 }}</p></div>
          <div><p [style.color]="'var(--text-tertiary)'" class="mb-0.5">Bank</p><p class="font-medium" [style.color]="'var(--text-primary)'">{{ guard()!.bank_name || '—' }}</p></div>
          <div><p [style.color]="'var(--text-tertiary)'" class="mb-0.5">Hire Date</p><p class="font-medium" [style.color]="'var(--text-primary)'">{{ guard()!.hire_date || '—' }}</p></div>
        </div>
      </div>
    }

    }
  `,
})
export class GuardDetailComponent implements OnInit {
  private api = inject(ApiService); private toast = inject(ToastService); private route = inject(ActivatedRoute);
  readonly ArrowLeftIcon = ArrowLeft; readonly EditIcon = Edit; readonly UploadIcon = Upload;
  readonly FileTextIcon = FileText; readonly CheckCircleIcon = CheckCircle;
  readonly PhoneIcon = Phone; readonly MailIcon = Mail; readonly MapPinIcon = MapPin;
  readonly CalendarIcon = Calendar; readonly EyeIcon = Eye; readonly DownloadIcon = Download; readonly PencilIcon = Pencil;

  readonly loading = signal(true); readonly guard = signal<any>(null);
  readonly performance = signal<any>(null); readonly documents = signal<any[]>([]);
  readonly activeTab = signal('Guard Profile'); readonly showUploadDoc = signal(false); readonly uploading = signal(false);
  docForm: any = { document_type: 'national_id', title: '', issue_date: '', expiry_date: '' };
  docFile: File | null = null;

  tabs = ['Guard Profile', 'Performance', 'Contracts & Documents', 'Payroll & Benefits'];

  personalFields = [
    { key: 'gender', label: 'Gender' }, { key: 'date_of_birth', label: 'Date of Birth' },
    { key: 'state', label: 'State' }, { key: 'address', label: 'Address' },
    { key: 'hire_date', label: 'Hire Date' }, { key: 'notes', label: 'Notes' },
  ];
  perfMetrics = [
    { key: 'punctuality_score', label: 'Punctuality', desc: 'Based on clock-in times vs scheduled shift start' },
    { key: 'report_completion_score', label: 'Report Completion', desc: 'Daily activity reports submitted on time' },
    { key: 'tour_compliance_score', label: 'Tour Compliance', desc: 'Checkpoint scans completed vs required' },
    { key: 'incident_response_score', label: 'Incident Response', desc: 'Response time and reporting quality' },
  ];

  ngOnInit(): void {
    const id = this.route.snapshot.paramMap.get('id');
    if (!id) return;
    this.api.get<any>(`/guards/${id}`).subscribe({ next: res => { this.guard.set(res.data?.guard || res.data); this.loading.set(false); }, error: () => this.loading.set(false) });
    this.api.get<any>(`/analytics/guard/${id}/performance`).subscribe({ next: res => { if (res.data) this.performance.set(res.data); }, error: () => {} });
    this.api.get<any>(`/guards/${id}/documents`).subscribe({ next: res => this.documents.set(res.data?.documents || res.data || []), error: () => {} });
  }

  onDocFileSelect(e: Event): void { this.docFile = (e.target as HTMLInputElement).files?.[0] || null; }
  getDocUrl(url: string): string { return url.startsWith('http') ? url : 'https://api.guard51.com' + url; }

  uploadDocument(): void {
    if (!this.docFile) { this.toast.warning('Please select a file'); return; }
    this.uploading.set(true);
    const id = this.guard()?.id;
    const fd = new FormData();
    fd.append('document_type', this.docForm.document_type);
    fd.append('title', this.docForm.title || this.docForm.document_type);
    if (this.docForm.issue_date) fd.append('issue_date', this.docForm.issue_date);
    if (this.docForm.expiry_date) fd.append('expiry_date', this.docForm.expiry_date);
    fd.append('file', this.docFile);
    const token = localStorage.getItem('g51_access_token') || '';
    fetch(`https://api.guard51.com/api/v1/guards/${id}/documents`, {
      method: 'POST', headers: { 'Authorization': `Bearer ${token}` }, body: fd,
    }).then(r => r.json()).then(data => {
      this.uploading.set(false);
      if (data.success !== false) { this.showUploadDoc.set(false); this.toast.success('Document uploaded'); this.docFile = null; this.ngOnInit(); }
      else this.toast.error(data.message || 'Upload failed');
    }).catch(() => { this.uploading.set(false); this.toast.error('Upload failed'); });
  }
}
