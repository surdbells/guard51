import { Component, inject, signal, OnInit } from '@angular/core';
import { ActivatedRoute, RouterLink } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { NgClass } from '@angular/common';
import { LucideAngularModule, ArrowLeft, Edit, Shield, FileText, Award, AlertTriangle, CheckCircle, Upload, X, Clock, BarChart3, Phone, Mail, MapPin, Calendar, CreditCard, User, Download, Eye } from 'lucide-angular';
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
      <button class="btn-primary flex items-center gap-2" [routerLink]="['/guards/edit', guard()?.id]"><lucide-icon [img]="EditIcon" [size]="14" /> Edit</button>
    </g51-page-header>

    @if (loading()) { <g51-loading /> } @else if (guard()) {
      <div class="flex gap-1 mb-4">
        @for (tab of ['Profile', 'Performance', 'Documents']; track tab) {
          <button (click)="activeTab.set(tab)" class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors"
            [ngClass]="activeTab() === tab ? 'bg-[var(--color-brand-500)] text-white' : 'bg-[var(--surface-muted)]'"
            [style.color]="activeTab() !== tab ? 'var(--text-secondary)' : ''">{{ tab }}</button>
        }
      </div>

      <!-- PROFILE TAB -->
      @if (activeTab() === 'Profile') {
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
          <!-- Left: Avatar card -->
          <div class="card p-5 text-center">
            @if (guard()!.photo_url) { <img [src]="guard()!.photo_url" class="h-24 w-24 rounded-full mx-auto mb-3 object-cover" /> }
            @else { <div class="h-24 w-24 rounded-full mx-auto mb-3 flex items-center justify-center text-2xl font-bold text-white" [style.background]="'var(--color-brand-500)'">{{ guard()!.first_name?.charAt(0) }}{{ guard()!.last_name?.charAt(0) }}</div> }
            <h3 class="text-base font-bold" [style.color]="'var(--text-primary)'">{{ guard()!.first_name }} {{ guard()!.last_name }}</h3>
            <p class="text-xs" [style.color]="'var(--text-tertiary)'">{{ guard()!.employee_number }}</p>
            <span class="badge text-[10px] mt-2" [ngClass]="guard()!.status === 'active' ? 'bg-emerald-50 text-emerald-600' : 'bg-red-50 text-red-600'">{{ guard()!.status_label || guard()!.status }}</span>
            <div class="mt-4 space-y-2 text-left">
              <div class="flex items-center gap-2 text-xs" [style.color]="'var(--text-secondary)'"><lucide-icon [img]="PhoneIcon" [size]="13" [style.color]="'var(--text-tertiary)'" /> {{ guard()!.phone || '—' }}</div>
              <div class="flex items-center gap-2 text-xs" [style.color]="'var(--text-secondary)'"><lucide-icon [img]="MailIcon" [size]="13" [style.color]="'var(--text-tertiary)'" /> {{ guard()!.email || '—' }}</div>
              <div class="flex items-center gap-2 text-xs" [style.color]="'var(--text-secondary)'"><lucide-icon [img]="MapPinIcon" [size]="13" [style.color]="'var(--text-tertiary)'" /> {{ guard()!.state || '—' }}</div>
              <div class="flex items-center gap-2 text-xs" [style.color]="'var(--text-secondary)'"><lucide-icon [img]="CalendarIcon" [size]="13" [style.color]="'var(--text-tertiary)'" /> Hired: {{ guard()!.hire_date || '—' }}</div>
            </div>
          </div>

          <!-- Right: Details grid -->
          <div class="lg:col-span-2 space-y-4">
            <!-- Personal info card -->
            <div class="card p-5">
              <h3 class="text-sm font-semibold mb-3 font-heading" [style.color]="'var(--text-primary)'">Personal Information</h3>
              <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                @for (field of personalFields; track field.key) {
                  <div>
                    <p class="text-[10px] uppercase tracking-wide mb-0.5" [style.color]="'var(--text-tertiary)'">{{ field.label }}</p>
                    <p class="text-sm font-medium" [style.color]="'var(--text-primary)'">{{ guard()![field.key] || '—' }}</p>
                  </div>
                }
              </div>
            </div>
            <!-- Financial card -->
            <div class="card p-5">
              <h3 class="text-sm font-semibold mb-3 font-heading" [style.color]="'var(--text-primary)'">Financial Details</h3>
              <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                @for (field of financialFields; track field.key) {
                  <div>
                    <p class="text-[10px] uppercase tracking-wide mb-0.5" [style.color]="'var(--text-tertiary)'">{{ field.label }}</p>
                    <p class="text-sm font-medium" [style.color]="'var(--text-primary)'">{{ guard()![field.key] || '—' }}</p>
                  </div>
                }
              </div>
            </div>
            <!-- Emergency contact card -->
            <div class="card p-5">
              <h3 class="text-sm font-semibold mb-3 font-heading" [style.color]="'var(--text-primary)'">Emergency Contact</h3>
              <div class="grid grid-cols-2 gap-4">
                <div><p class="text-[10px] uppercase tracking-wide mb-0.5" [style.color]="'var(--text-tertiary)'">Name</p><p class="text-sm font-medium" [style.color]="'var(--text-primary)'">{{ guard()!.emergency_contact_name || '—' }}</p></div>
                <div><p class="text-[10px] uppercase tracking-wide mb-0.5" [style.color]="'var(--text-tertiary)'">Phone</p><p class="text-sm font-medium" [style.color]="'var(--text-primary)'">{{ guard()!.emergency_contact_phone || '—' }}</p></div>
              </div>
            </div>
          </div>
        </div>
      }

      <!-- PERFORMANCE TAB -->
      @if (activeTab() === 'Performance') {
        @if (performance()) {
          <!-- Overall GPI -->
          <div class="card p-5 mb-4 text-center">
            <p class="text-[10px] uppercase tracking-wide mb-1" [style.color]="'var(--text-tertiary)'">Overall Guard Performance Index</p>
            <p class="text-4xl font-bold font-heading" [style.color]="performance()!.overall_score >= 80 ? 'var(--color-success)' : performance()!.overall_score >= 50 ? 'var(--color-warning)' : 'var(--color-danger)'">{{ performance()!.overall_score || 0 }}%</p>
          </div>
          <!-- Metric bars -->
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
          <!-- Stats summary -->
          <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
            <div class="card p-3 text-center"><p class="text-lg font-bold" [style.color]="'var(--text-primary)'">{{ performance()!.total_shifts || 0 }}</p><p class="text-[10px]" [style.color]="'var(--text-tertiary)'">Total Shifts</p></div>
            <div class="card p-3 text-center"><p class="text-lg font-bold" [style.color]="'var(--text-primary)'">{{ performance()!.total_reports || 0 }}</p><p class="text-[10px]" [style.color]="'var(--text-tertiary)'">Reports Filed</p></div>
            <div class="card p-3 text-center"><p class="text-lg font-bold" [style.color]="'var(--text-primary)'">{{ performance()!.total_incidents || 0 }}</p><p class="text-[10px]" [style.color]="'var(--text-tertiary)'">Incidents</p></div>
            <div class="card p-3 text-center"><p class="text-lg font-bold" [style.color]="'var(--text-primary)'">{{ performance()!.total_hours || 0 }}h</p><p class="text-[10px]" [style.color]="'var(--text-tertiary)'">Hours Worked</p></div>
          </div>
        } @else { <div class="card p-8 text-center"><p class="text-sm" [style.color]="'var(--text-tertiary)'">Performance data is calculated from attendance, reports, tours, and incidents. Data will appear after the guard completes shifts.</p></div> }
      }

      <!-- DOCUMENTS TAB -->
      @if (activeTab() === 'Documents') {
        <div class="flex justify-end mb-3">
          <button (click)="showUploadDoc.set(true)" class="btn-primary text-xs flex items-center gap-1"><lucide-icon [img]="UploadIcon" [size]="12" /> Upload Document</button>
        </div>
        @if (!documents().length) { <div class="card p-8 text-center"><p class="text-sm" [style.color]="'var(--text-tertiary)'">No documents uploaded yet. Upload IDs, licenses, and certificates.</p></div> }
        @else {
          <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            @for (doc of documents(); track doc.id) {
              <div class="card p-4">
                <div class="flex items-start gap-3">
                  <div class="h-10 w-10 rounded-lg flex items-center justify-center shrink-0" [style.background]="'var(--brand-50)'" [style.color]="'var(--brand-500)'">
                    <lucide-icon [img]="FileTextIcon" [size]="18" />
                  </div>
                  <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold" [style.color]="'var(--text-primary)'">{{ doc.title || doc.document_type }}</p>
                    <p class="text-xs" [style.color]="'var(--text-tertiary)'">
                      {{ doc.document_type }} {{ doc.issue_date ? '· Issued: ' + doc.issue_date : '' }}
                      {{ doc.expiry_date ? '· Expires: ' + doc.expiry_date : '' }}
                    </p>
                    <div class="flex items-center gap-2 mt-2">
                      <span class="badge text-[10px]" [ngClass]="doc.is_verified ? 'bg-emerald-50 text-emerald-600' : 'bg-amber-50 text-amber-600'">{{ doc.is_verified ? 'Verified' : 'Pending Review' }}</span>
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
              <input type="text" [(ngModel)]="docForm.title" class="input-base w-full" placeholder="Optional — defaults to document type" /></div>
            <div class="grid grid-cols-2 gap-3">
              <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Issue Date</label>
                <input type="date" [(ngModel)]="docForm.issue_date" class="input-base w-full" /></div>
              <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Expiry Date</label>
                <input type="date" [(ngModel)]="docForm.expiry_date" class="input-base w-full" /></div>
            </div>
            <div>
              <label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">File *</label>
              <div class="border-2 border-dashed rounded-lg p-4 text-center cursor-pointer transition-colors"
                [style.borderColor]="docFile ? 'var(--brand-500)' : 'var(--border-default)'"
                (click)="docInput.click()">
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
    }
  `,
})
export class GuardDetailComponent implements OnInit {
  private api = inject(ApiService); private toast = inject(ToastService);
  private route = inject(ActivatedRoute);
  readonly ArrowLeftIcon = ArrowLeft; readonly EditIcon = Edit; readonly UploadIcon = Upload;
  readonly FileTextIcon = FileText; readonly CheckCircleIcon = CheckCircle;
  readonly PhoneIcon = Phone; readonly MailIcon = Mail; readonly MapPinIcon = MapPin;
  readonly CalendarIcon = Calendar; readonly EyeIcon = Eye; readonly DownloadIcon = Download;

  readonly loading = signal(true); readonly guard = signal<any>(null);
  readonly performance = signal<any>(null); readonly documents = signal<any[]>([]);
  readonly activeTab = signal('Profile'); readonly showUploadDoc = signal(false); readonly uploading = signal(false);
  docForm: any = { document_type: 'national_id', title: '', issue_date: '', expiry_date: '' };
  docFile: File | null = null;

  personalFields = [
    { key: 'gender', label: 'Gender' }, { key: 'date_of_birth', label: 'Date of Birth' },
    { key: 'address', label: 'Address' }, { key: 'city', label: 'City' },
    { key: 'state', label: 'State' }, { key: 'hire_date', label: 'Hire Date' },
  ];
  financialFields = [
    { key: 'pay_type', label: 'Pay Type' }, { key: 'pay_rate', label: 'Pay Rate (₦)' },
    { key: 'bank_name', label: 'Bank' }, { key: 'bank_account_number', label: 'Account Number' },
    { key: 'bank_account_name', label: 'Account Name' },
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
