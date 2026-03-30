import { Component, inject, signal, OnInit } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { NgClass } from '@angular/common';
import { Router } from '@angular/router';
import { LucideAngularModule, FileText, Plus, Eye, CheckCircle, Camera } from 'lucide-angular';
import { PageHeaderComponent } from '@shared/components/page-header/page-header.component';
import { StatsCardComponent } from '@shared/components/stats-card/stats-card.component';
import { EmptyStateComponent } from '@shared/components/empty-state/empty-state.component';
import { ModalComponent } from '@shared/components/modal/modal.component';
import { ApiService } from '@core/services/api.service';
import { ToastService } from '@core/services/toast.service';

@Component({
  selector: 'g51-reports',
  standalone: true,
  imports: [FormsModule, NgClass, LucideAngularModule, PageHeaderComponent, StatsCardComponent, EmptyStateComponent, ModalComponent],
  template: `
    <g51-page-header title="Reports" subtitle="Daily activity reports, custom reports, and watch mode feed">
      <button (click)="showCreateDAR.set(true)" class="btn-primary flex items-center gap-2">
        <lucide-icon [img]="PlusIcon" [size]="16" /> New DAR
      </button>
    </g51-page-header>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6 stagger-children">
      <g51-stats-card label="DARs Today" [value]="stats().darsToday" [icon]="FileTextIcon" />
      <g51-stats-card label="Pending Review" [value]="stats().pendingReview" [icon]="EyeIcon" />
      <g51-stats-card label="Approved" [value]="stats().approved" [icon]="CheckCircleIcon" />
      <g51-stats-card label="Watch Photos" [value]="stats().watchPhotos" [icon]="CameraIcon" />
    </div>

    <div class="flex gap-1 mb-6">
      @for (tab of ['Activity Reports', 'Custom Reports', 'Watch Feed']; track tab) {
        <button (click)="activeTab.set(tab)" class="px-4 py-2 rounded-lg text-sm font-medium transition-colors"
          [ngClass]="activeTab() === tab ? 'bg-[var(--color-brand-500)] text-white' : 'bg-[var(--surface-muted)]'"
          [style.color]="activeTab() !== tab ? 'var(--text-secondary)' : ''">{{ tab }}</button>
      }
    </div>

    @if (activeTab() === 'Activity Reports') {
      <div class="flex flex-wrap gap-3 mb-4">
        <select class="input-base text-sm py-1.5 min-w-[150px]"><option value="">All Sites</option></select>
        <select class="input-base text-sm py-1.5 min-w-[120px]"><option value="">All Status</option>
          <option value="draft">Draft</option><option value="submitted">Submitted</option><option value="approved">Approved</option></select>
      </div>
      <div class="space-y-2">
        @for (dar of dars(); track dar.id) {
          <div class="card p-4 card-hover cursor-pointer" (click)="openDAR(dar.id)">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm font-medium" [style.color]="'var(--text-primary)'">{{ dar.report_date }}</p>
                <p class="text-xs mt-1 line-clamp-2" [style.color]="'var(--text-secondary)'">{{ dar.content?.substring(0, 120) }}...</p>
              </div>
              <span class="badge text-[10px]"
                [ngClass]="dar.status === 'approved' ? 'bg-emerald-50 text-emerald-600 dark:bg-emerald-950 dark:text-emerald-400'
                  : dar.status === 'submitted' ? 'bg-blue-50 text-blue-600' : 'bg-[var(--surface-muted)]'">{{ dar.status_label }}</span>
            </div>
          </div>
        } @empty {
          <g51-empty-state title="No Reports" message="No daily activity reports found." [icon]="FileTextIcon" />
        }
      </div>
    }

    @if (activeTab() === 'Custom Reports') {
      <div class="card p-5">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-sm font-semibold" [style.color]="'var(--text-primary)'">Report Templates</h3>
          <button (click)="showCreateTemplate.set(true)" class="btn-primary text-xs py-1.5 px-3 flex items-center gap-1">
            <lucide-icon [img]="PlusIcon" [size]="12" /> New Template
          </button>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
          @for (t of templates(); track t.id) {
            <div class="card p-4 card-hover">
              <h4 class="text-sm font-semibold" [style.color]="'var(--text-primary)'">{{ t.name }}</h4>
              <p class="text-xs mt-1" [style.color]="'var(--text-tertiary)'">{{ t.field_count }} fields</p>
              @if (t.fields?.length) {
                <div class="mt-2 space-y-1">
                  @for (f of t.fields.slice(0, 3); track f.name) {
                    <div class="text-[10px] flex items-center gap-1.5" [style.color]="'var(--text-secondary)'">
                      <span class="badge text-[8px] bg-[var(--surface-muted)]">{{ f.type }}</span>
                      <span>{{ f.name }}</span>
                      @if (f.required) { <span class="text-red-400">*</span> }
                    </div>
                  }
                  @if (t.fields.length > 3) {
                    <p class="text-[10px]" [style.color]="'var(--text-tertiary)'">+{{ t.fields.length - 3 }} more fields</p>
                  }
                </div>
              }
            </div>
          }
        </div>
      </div>
    }

    <!-- Template Builder Modal -->
    <g51-modal [open]="showCreateTemplate()" title="Create Report Template" maxWidth="600px" (closed)="showCreateTemplate.set(false)">
      <div class="space-y-4">
        <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Template Name *</label>
          <input type="text" [(ngModel)]="tplForm.name" class="input-base w-full" placeholder="Vehicle Inspection" /></div>
        <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Description</label>
          <input type="text" [(ngModel)]="tplForm.description" class="input-base w-full" placeholder="Optional description" /></div>

        <div>
          <div class="flex items-center justify-between mb-2">
            <label class="text-xs font-medium" [style.color]="'var(--text-secondary)'">Fields</label>
            <button (click)="addField()" class="text-xs font-medium" [style.color]="'var(--color-brand-500)'">+ Add Field</button>
          </div>
          @for (field of tplForm.fields; track $index) {
            <div class="flex items-center gap-2 mb-2">
              <input type="text" [(ngModel)]="field.name" class="input-base flex-1 text-xs" placeholder="Field name" />
              <select [(ngModel)]="field.type" class="input-base w-24 text-xs">
                <option value="text">Text</option><option value="number">Number</option>
                <option value="select">Select</option><option value="checkbox">Checkbox</option>
                <option value="date">Date</option><option value="textarea">Textarea</option></select>
              <label class="flex items-center gap-1 text-[10px] whitespace-nowrap" [style.color]="'var(--text-tertiary)'">
                <input type="checkbox" [(ngModel)]="field.required" /> Req</label>
              <button (click)="removeField($index)" class="text-xs p-1" [style.color]="'var(--color-danger)'">✕</button>
            </div>
          }
          @if (tplForm.fields.length === 0) {
            <p class="text-xs py-3 text-center" [style.color]="'var(--text-tertiary)'">No fields added yet</p>
          }
        </div>
      </div>
      <div modal-footer>
        <button (click)="showCreateTemplate.set(false)" class="btn-secondary">Cancel</button>
        <button (click)="onCreateTemplate()" class="btn-primary">Create Template</button>
      </div>
    </g51-modal>

    @if (activeTab() === 'Watch Feed') {
      <div class="max-w-2xl">
        @if (!watchFeed().length) {
          <g51-empty-state title="No Watch Feed" message="Guard watch mode entries will appear here as a live timeline." [icon]="CameraIcon" />
        } @else {
          <div class="relative pl-8 space-y-0">
            <!-- Timeline line -->
            <div class="absolute left-3 top-2 bottom-2 w-px" [style.background]="'var(--border-default)'"></div>
            @for (w of watchFeed(); track w.id || $index) {
              <div class="relative pb-6">
                <!-- Timeline dot -->
                <div class="absolute left-[-21px] top-1 h-3 w-3 rounded-full border-2" [style.borderColor]="'var(--color-brand-500)'" [style.background]="'var(--surface-card)'"></div>
                <div class="card p-4 card-hover ml-2">
                  <div class="flex items-start gap-3">
                    @if (w.media_url || w.photo_url) {
                      <img [src]="w.media_url || w.photo_url" class="h-16 w-16 rounded-lg object-cover shrink-0" />
                    } @else {
                      <div class="h-16 w-16 rounded-lg flex items-center justify-center shrink-0" [style.background]="'var(--surface-muted)'">
                        <lucide-icon [img]="CameraIcon" [size]="20" [style.color]="'var(--text-tertiary)'" />
                      </div>
                    }
                    <div class="flex-1 min-w-0">
                      <p class="text-sm font-medium" [style.color]="'var(--text-primary)'">{{ w.caption || w.description || w.content || 'Watch entry' }}</p>
                      <p class="text-xs mt-1" [style.color]="'var(--text-tertiary)'">
                        {{ w.guard_name || w.guard || 'Guard' }} · {{ w.site_name || '' }}
                      </p>
                      <div class="flex items-center gap-3 mt-2">
                        <span class="text-[10px] px-1.5 py-0.5 rounded" [style.background]="'var(--surface-muted)'" [style.color]="'var(--text-tertiary)'">
                          {{ w.media_type || 'photo' }}
                        </span>
                        <span class="text-[10px]" [style.color]="'var(--text-tertiary)'">{{ w.recorded_at || w.time || w.created_at }}</span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            }
          </div>
        }
      </div>
    }

    <g51-modal [open]="showCreateDAR()" title="New Daily Activity Report" maxWidth="680px" (closed)="showCreateDAR.set(false)">
      <div class="space-y-4">
        <div class="grid grid-cols-2 gap-3">
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Report Date *</label>
            <input type="date" [(ngModel)]="darForm.report_date" class="input-base w-full" /></div>
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Site *</label>
            <select [(ngModel)]="darForm.site_id" class="input-base w-full">
              <option value="">Select site</option>
              @for (s of sites(); track s.id) { <option [value]="s.id">{{ s.name }}</option> }
            </select></div>
        </div>
        <div class="grid grid-cols-3 gap-3">
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Shift Start</label>
            <input type="time" [(ngModel)]="darForm.shift_start" class="input-base w-full" /></div>
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Shift End</label>
            <input type="time" [(ngModel)]="darForm.shift_end" class="input-base w-full" /></div>
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Weather</label>
            <select [(ngModel)]="darForm.weather" class="input-base w-full">
              <option value="">Select</option><option value="clear">Clear</option><option value="cloudy">Cloudy</option>
              <option value="rain">Rain</option><option value="storm">Storm</option><option value="harmattan">Harmattan</option>
            </select></div>
        </div>

        <div><label class="block text-xs font-semibold mb-2" [style.color]="'var(--text-primary)'">Activities Performed</label>
          <div class="space-y-2">
            @for (act of darForm.activities; track $index; let i = $index) {
              <div class="flex items-center gap-2">
                <input type="time" [(ngModel)]="act.time" class="input-base w-24 text-xs" />
                <input type="text" [(ngModel)]="act.description" class="input-base flex-1 text-xs" placeholder="Activity description..." />
                <button (click)="darForm.activities.splice(i, 1)" class="text-red-400 text-xs">✕</button>
              </div>
            }
            <button (click)="darForm.activities.push({time: '', description: ''})" class="text-xs text-blue-500 hover:underline">+ Add Activity</button>
          </div>
        </div>

        <div><label class="block text-xs font-semibold mb-2" [style.color]="'var(--text-primary)'">Incidents / Observations</label>
          <textarea [(ngModel)]="darForm.incidents_summary" rows="3" class="input-base w-full resize-none" placeholder="Any incidents, suspicious activity, or notable observations..."></textarea></div>

        <div><label class="block text-xs font-semibold mb-2" [style.color]="'var(--text-primary)'">Patrol Summary</label>
          <div class="grid grid-cols-3 gap-3">
            <div><label class="block text-[10px] mb-0.5" [style.color]="'var(--text-tertiary)'">Patrols Completed</label>
              <input type="number" [(ngModel)]="darForm.patrols_completed" class="input-base w-full" min="0" /></div>
            <div><label class="block text-[10px] mb-0.5" [style.color]="'var(--text-tertiary)'">Checkpoints Scanned</label>
              <input type="number" [(ngModel)]="darForm.checkpoints_scanned" class="input-base w-full" min="0" /></div>
            <div><label class="block text-[10px] mb-0.5" [style.color]="'var(--text-tertiary)'">Visitors Processed</label>
              <input type="number" [(ngModel)]="darForm.visitors_processed" class="input-base w-full" min="0" /></div>
          </div>
        </div>

        <div><label class="block text-xs font-semibold mb-2" [style.color]="'var(--text-primary)'">Equipment Status</label>
          <textarea [(ngModel)]="darForm.equipment_status" rows="2" class="input-base w-full resize-none" placeholder="Radio, flashlight, keys, uniform condition..."></textarea></div>

        <div><label class="block text-xs font-semibold mb-2" [style.color]="'var(--text-primary)'">Evidence / Photos</label>
          <div class="flex flex-wrap gap-2 mb-2">
            @for (f of darEvidence; track f.name; let i = $index) {
              <span class="px-2 py-1 rounded text-[10px] flex items-center gap-1" [style.background]="'var(--surface-muted)'" [style.color]="'var(--text-secondary)'">{{ f.name }} <button (click)="darEvidence.splice(i, 1)" class="text-red-400">✕</button></span>
            }
          </div>
          <label class="btn-secondary inline-flex items-center gap-2 cursor-pointer text-xs">
            Attach Files <input type="file" accept="image/*,.pdf" multiple (change)="onDarEvidence($event)" class="hidden" />
          </label>
        </div>

        <div><label class="block text-xs font-semibold mb-2" [style.color]="'var(--text-primary)'">Handover Notes</label>
          <textarea [(ngModel)]="darForm.handover_notes" rows="2" class="input-base w-full resize-none" placeholder="Notes for the incoming shift..."></textarea></div>
      </div>
      <div modal-footer>
        <button (click)="showCreateDAR.set(false)" class="btn-secondary">Cancel</button>
        <button (click)="onCreateDAR()" class="btn-primary">Submit Report</button>
      </div>
    </g51-modal>
  `,
})
export class ReportsComponent implements OnInit {
  private api = inject(ApiService); private toast = inject(ToastService);
  private router = inject(Router);
  readonly FileTextIcon = FileText; readonly PlusIcon = Plus; readonly EyeIcon = Eye;
  readonly CheckCircleIcon = CheckCircle; readonly CameraIcon = Camera;
  readonly activeTab = signal('Activity Reports');
  readonly showCreateDAR = signal(false);
  readonly showCreateTemplate = signal(false);
  readonly dars = signal<any[]>([]);
  readonly templates = signal<any[]>([]);
  readonly stats = signal({ darsToday: 0, pendingReview: 0, approved: 0, watchPhotos: 0 });
  darForm: any = {
    report_date: new Date().toISOString().substring(0, 10), site_id: '',
    shift_start: '', shift_end: '', weather: '',
    activities: [{ time: '', description: '' }],
    incidents_summary: '', patrols_completed: 0, checkpoints_scanned: 0,
    visitors_processed: 0, equipment_status: '', handover_notes: '',
  };
  darEvidence: File[] = [];
  readonly sites = signal<any[]>([]);
  tplForm: { name: string; description: string; fields: { name: string; type: string; required: boolean }[] } = { name: '', description: '', fields: [] };
  readonly watchFeed = signal<any[]>([]);
  ngOnInit(): void {
    this.api.get<any>('/reports/dar').subscribe({ next: res => { if (res.data) this.dars.set(res.data.reports || []); } });
    this.api.get<any>('/reports/templates').subscribe({ next: res => { if (res.data) this.templates.set(res.data.templates || []); } });
    this.api.get<any>('/sites').subscribe({ next: res => this.sites.set(res.data?.sites || res.data || []) });
    this.api.get<any>('/reports/watch-feed').subscribe({ next: res => { if (res.data) this.watchFeed.set(res.data.feed || res.data || []); }, error: () => {} });
  }
  onDarEvidence(e: Event): void {
    const files = (e.target as HTMLInputElement).files;
    if (files) for (let i = 0; i < files.length && this.darEvidence.length < 5; i++) this.darEvidence.push(files[i]);
    (e.target as HTMLInputElement).value = '';
  }
  onCreateDAR(): void {
    const fd = new FormData();
    // Flatten the form — serialize activities as JSON
    fd.append('report_date', this.darForm.report_date);
    fd.append('site_id', this.darForm.site_id);
    fd.append('shift_start', this.darForm.shift_start);
    fd.append('shift_end', this.darForm.shift_end);
    fd.append('weather', this.darForm.weather);
    fd.append('activities', JSON.stringify(this.darForm.activities.filter((a: any) => a.description)));
    fd.append('incidents_summary', this.darForm.incidents_summary);
    fd.append('patrols_completed', String(this.darForm.patrols_completed));
    fd.append('checkpoints_scanned', String(this.darForm.checkpoints_scanned));
    fd.append('visitors_processed', String(this.darForm.visitors_processed));
    fd.append('equipment_status', this.darForm.equipment_status);
    fd.append('handover_notes', this.darForm.handover_notes);
    this.darEvidence.forEach((f, i) => fd.append(`evidence_${i}`, f));
    this.api.post('/reports/dar', fd).subscribe({
      next: () => { this.showCreateDAR.set(false); this.toast.success('Report submitted'); this.darEvidence = []; this.ngOnInit(); },
    });
  }

  addField(): void { this.tplForm.fields.push({ name: '', type: 'text', required: false }); }
  removeField(idx: number): void { this.tplForm.fields.splice(idx, 1); }

  onCreateTemplate(): void {
    this.api.post('/reports/templates', this.tplForm).subscribe({
      next: () => { this.showCreateTemplate.set(false); this.toast.success('Template created'); this.tplForm = { name: '', description: '', fields: [] }; this.ngOnInit(); },
    });
  }

  openDAR(id: string): void {
    this.router.navigate(['/reports/dar', id]);
  }
}
