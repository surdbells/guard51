import { Component, inject, signal, OnInit } from '@angular/core';
import { RouterLink } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { NgClass } from '@angular/common';
import { LucideAngularModule, AlertTriangle, Plus, Search, Upload, X, Eye, FileText } from 'lucide-angular';
import { PageHeaderComponent } from '@shared/components/page-header/page-header.component';
import { StatsCardComponent } from '@shared/components/stats-card/stats-card.component';
import { ModalComponent } from '@shared/components/modal/modal.component';
import { EmptyStateComponent } from '@shared/components/empty-state/empty-state.component';
import { LoadingSpinnerComponent } from '@shared/components/loading-spinner/loading-spinner.component';
import { PieChartComponent, PieChartData } from '@shared/components/charts/pie-chart.component';
import { LineChartComponent, LineChartSeries } from '@shared/components/charts/line-chart.component';
import { ApiService } from '@core/services/api.service';
import { ToastService } from '@core/services/toast.service';

@Component({
  selector: 'g51-incidents',
  standalone: true,
  imports: [RouterLink, FormsModule, NgClass, LucideAngularModule, PageHeaderComponent, StatsCardComponent, ModalComponent, EmptyStateComponent, LoadingSpinnerComponent, PieChartComponent, LineChartComponent],
  template: `
    <g51-page-header title="Incident Reports" subtitle="Report, track, and resolve security incidents">
      <button class="btn-primary flex items-center gap-2" (click)="showCreate.set(true)"><lucide-icon [img]="PlusIcon" [size]="16" /> Report Incident</button>
    </g51-page-header>

    <div class="flex items-center gap-3 mb-4">
      <div class="relative flex-1 max-w-sm">
        <lucide-icon [img]="SearchIcon" [size]="14" class="absolute left-3 top-1/2 -translate-y-1/2" [style.color]="'var(--text-tertiary)'" />
        <input type="text" [(ngModel)]="search" (ngModelChange)="loadIncidents()" placeholder="Search incidents..." class="input-base w-full pl-9" />
      </div>
      <select [(ngModel)]="statusFilter" (ngModelChange)="loadIncidents()" class="input-base text-xs py-2">
        <option value="">All Status</option><option value="reported">Reported</option><option value="investigating">Investigating</option><option value="escalated">Escalated</option><option value="resolved">Resolved</option><option value="closed">Closed</option>
      </select>
      <select [(ngModel)]="severityFilter" (ngModelChange)="loadIncidents()" class="input-base text-xs py-2">
        <option value="">All Severity</option><option value="critical">Critical</option><option value="high">High</option><option value="medium">Medium</option><option value="low">Low</option>
      </select>
    </div>

    @if (loading()) { <g51-loading /> }
    @else if (!incidents().length) { <g51-empty-state title="No Incidents" message="No incidents reported yet." [icon]="AlertTriangleIcon" /> }
    @else {
      <div class="space-y-2">
        @for (i of incidents(); track i.id) {
          <a [routerLink]="[i.id]" class="card p-4 card-hover block">
            <div class="flex items-center justify-between">
              <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 mb-1">
                  <span class="h-2 w-2 rounded-full" [ngClass]="i.severity === 'critical' ? 'bg-red-500' : i.severity === 'high' ? 'bg-orange-500' : i.severity === 'medium' ? 'bg-amber-500' : 'bg-blue-400'"></span>
                  <p class="text-sm font-semibold truncate" [style.color]="'var(--text-primary)'">{{ i.title }}</p>
                </div>
                <p class="text-xs" [style.color]="'var(--text-tertiary)'">{{ i.incident_type }} · {{ i.site_name || 'Unknown site' }} · {{ i.occurred_at || i.reported_at }}</p>
                @if (i.attachments?.length) { <p class="text-[10px] mt-1 text-blue-500">{{ i.attachments.length }} evidence file(s) attached</p> }
              </div>
              <div class="flex items-center gap-2 ml-3">
                <span class="badge text-[10px]" [ngClass]="i.severity === 'critical' ? 'bg-red-50 text-red-600' : i.severity === 'high' ? 'bg-orange-50 text-orange-600' : i.severity === 'medium' ? 'bg-amber-50 text-amber-600' : 'bg-blue-50 text-blue-600'">{{ i.severity }}</span>
                <span class="badge text-[10px]" [ngClass]="i.status === 'resolved' || i.status === 'closed' ? 'bg-emerald-50 text-emerald-600' : i.status === 'escalated' ? 'bg-red-50 text-red-600' : 'bg-gray-100 text-gray-500'">{{ i.status }}</span>
              </div>
            </div>
          </a>
        }
      </div>
    }

    <!-- Create Incident Modal -->
    <g51-modal [open]="showCreate()" title="Report New Incident" maxWidth="640px" (closed)="showCreate.set(false)">
      <div class="space-y-4">
        <div class="grid grid-cols-2 gap-3">
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Incident Type *</label>
            <select [(ngModel)]="form.incident_type" class="input-base w-full" required>
              <option value="">Select type</option><option value="theft">Theft</option><option value="trespass">Trespass</option>
              <option value="vandalism">Vandalism</option><option value="assault">Assault</option><option value="fire">Fire</option>
              <option value="medical">Medical Emergency</option><option value="suspicious_activity">Suspicious Activity</option>
              <option value="equipment_failure">Equipment Failure</option><option value="other">Other</option>
            </select>
            @if (submitted && !form.incident_type) { <p class="text-[10px] text-red-500 mt-0.5">Required</p> }
          </div>
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Severity *</label>
            <select [(ngModel)]="form.severity" class="input-base w-full" required>
              <option value="low">Low</option><option value="medium">Medium</option><option value="high">High</option><option value="critical">Critical</option>
            </select></div>
        </div>

        <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Title *</label>
          <input type="text" [(ngModel)]="form.title" class="input-base w-full" placeholder="Brief description of the incident" required />
          @if (submitted && !form.title) { <p class="text-[10px] text-red-500 mt-0.5">Title is required</p> }
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Site *</label>
            <select [(ngModel)]="form.site_id" class="input-base w-full" required>
              <option value="">Select site</option>
              @for (s of sites(); track s.id) { <option [value]="s.id">{{ s.name }}</option> }
            </select>
            @if (submitted && !form.site_id) { <p class="text-[10px] text-red-500 mt-0.5">Required</p> }
          </div>
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Date & Time of Occurrence *</label>
            <input type="datetime-local" [(ngModel)]="form.occurred_at" class="input-base w-full" required /></div>
        </div>

        <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Location Detail</label>
          <input type="text" [(ngModel)]="form.location_detail" class="input-base w-full" placeholder="e.g. Main entrance, 2nd floor corridor, parking lot B" /></div>

        <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Detailed Description *</label>
          <textarea [(ngModel)]="form.description" rows="4" class="input-base w-full resize-none" placeholder="Describe what happened, who was involved, and any immediate actions taken..." required></textarea>
          @if (submitted && !form.description) { <p class="text-[10px] text-red-500 mt-0.5">Description is required</p> }
        </div>

        <!-- Evidence Upload -->
        <div>
          <label class="block text-xs font-medium mb-2" [style.color]="'var(--text-secondary)'">Evidence Files (Photos, Videos, Documents)</label>
          <div class="flex flex-wrap gap-2 mb-2">
            @for (file of evidenceFiles; track file.name; let i = $index) {
              <div class="relative flex items-center gap-1 px-2 py-1 rounded-lg text-xs" [style.background]="'var(--surface-muted)'" [style.color]="'var(--text-secondary)'">
                <lucide-icon [img]="FileTextIcon" [size]="12" />
                <span class="max-w-32 truncate">{{ file.name }}</span>
                <span class="text-[10px]" [style.color]="'var(--text-tertiary)'">({{ (file.size / 1024).toFixed(0) }}KB)</span>
                <button (click)="removeEvidence(i)" class="ml-1 text-red-400 hover:text-red-600"><lucide-icon [img]="XIcon" [size]="10" /></button>
              </div>
            }
          </div>
          <label class="btn-secondary inline-flex items-center gap-2 cursor-pointer text-xs">
            <lucide-icon [img]="UploadIcon" [size]="14" /> Attach Evidence
            <input type="file" accept="image/*,video/*,.pdf,.doc,.docx" multiple (change)="onEvidenceSelect($event)" class="hidden" />
          </label>
          <p class="text-[10px] mt-1" [style.color]="'var(--text-tertiary)'">Max 5 files. Photos, videos, PDFs accepted (max 10MB each).</p>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">GPS Latitude</label>
            <input type="number" step="0.00000001" [(ngModel)]="form.latitude" class="input-base w-full" placeholder="Auto-detected" /></div>
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">GPS Longitude</label>
            <input type="number" step="0.00000001" [(ngModel)]="form.longitude" class="input-base w-full" placeholder="Auto-detected" /></div>
        </div>
      </div>
      <div modal-footer>
        <button (click)="showCreate.set(false)" class="btn-secondary">Cancel</button>
        <button (click)="onCreate()" class="btn-primary flex items-center gap-2" [disabled]="creating()">
          {{ creating() ? 'Submitting...' : 'Submit Report' }}
        </button>
      </div>
    </g51-modal>
  `,
})
export class IncidentsComponent implements OnInit {
  private api = inject(ApiService); private toast = inject(ToastService);
  readonly AlertTriangleIcon = AlertTriangle; readonly PlusIcon = Plus; readonly SearchIcon = Search;
  readonly UploadIcon = Upload; readonly XIcon = X; readonly EyeIcon = Eye; readonly FileTextIcon = FileText;

  readonly incidents = signal<any[]>([]); readonly sites = signal<any[]>([]);
  readonly loading = signal(true); readonly showCreate = signal(false); readonly creating = signal(false);
  search = ''; statusFilter = ''; severityFilter = '';
  submitted = false;
  evidenceFiles: File[] = [];

  form: any = {
    incident_type: '', severity: 'medium', title: '', description: '',
    site_id: '', occurred_at: '', location_detail: '', latitude: '', longitude: '',
  };

  pieData: PieChartData[] = [];
  trendSeries: LineChartSeries[] = [];
  trendLabels: string[] = [];

  ngOnInit(): void {
    this.loadIncidents();
    this.api.get<any>('/sites').subscribe({ next: res => this.sites.set(res.data?.sites || res.data || []) });
    // Auto-detect GPS
    navigator.geolocation?.getCurrentPosition(pos => {
      this.form.latitude = pos.coords.latitude;
      this.form.longitude = pos.coords.longitude;
    }, () => {});
  }

  loadIncidents(): void {
    this.loading.set(true);
    const p = new URLSearchParams();
    if (this.search) p.set('search', this.search);
    if (this.statusFilter) p.set('status', this.statusFilter);
    if (this.severityFilter) p.set('severity', this.severityFilter);
    this.api.get<any>(`/incidents?${p}`).subscribe({
      next: res => { this.incidents.set(res.data?.incidents || res.data?.items || res.data || []); this.loading.set(false); },
      error: () => this.loading.set(false),
    });
  }

  onEvidenceSelect(event: Event): void {
    const files = (event.target as HTMLInputElement).files;
    if (!files) return;
    for (let i = 0; i < files.length && this.evidenceFiles.length < 5; i++) {
      const f = files[i];
      if (f.size > 10 * 1024 * 1024) { this.toast.warning(`${f.name} exceeds 10MB limit`); continue; }
      this.evidenceFiles.push(f);
    }
    (event.target as HTMLInputElement).value = '';
  }

  removeEvidence(index: number): void { this.evidenceFiles.splice(index, 1); }

  onCreate(): void {
    this.submitted = true;
    if (!this.form.incident_type || !this.form.title || !this.form.description || !this.form.site_id) {
      this.toast.warning('Please fill in all required fields');
      return;
    }
    this.creating.set(true);

    const fd = new FormData();
    Object.entries(this.form).forEach(([k, v]) => { if (v) fd.append(k, String(v)); });
    this.evidenceFiles.forEach((f, i) => fd.append(`evidence_${i}`, f));
    fd.append('evidence_count', String(this.evidenceFiles.length));

    this.api.post('/incidents', fd).subscribe({
      next: () => {
        this.showCreate.set(false); this.creating.set(false);
        this.toast.success('Incident reported'); this.submitted = false;
        this.evidenceFiles = [];
        this.form = { incident_type: '', severity: 'medium', title: '', description: '', site_id: '', occurred_at: '', location_detail: '', latitude: '', longitude: '' };
        this.loadIncidents();
      },
      error: () => this.creating.set(false),
    });
  }
}
