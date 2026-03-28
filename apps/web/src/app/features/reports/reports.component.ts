import { Component, inject, signal, OnInit } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { NgClass } from '@angular/common';
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
          <div class="card p-4 card-hover">
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
      <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
        @for (w of watchFeed; track w.id) {
          <div class="card overflow-hidden card-hover">
            <div class="aspect-video bg-[var(--surface-muted)] flex items-center justify-center">
              <lucide-icon [img]="CameraIcon" [size]="24" [style.color]="'var(--text-tertiary)'" />
            </div>
            <div class="p-3">
              <p class="text-xs font-medium" [style.color]="'var(--text-primary)'">{{ w.caption }}</p>
              <p class="text-[10px]" [style.color]="'var(--text-tertiary)'">{{ w.guard }} • {{ w.time }}</p>
            </div>
          </div>
        }
      </div>
    }

    <g51-modal [open]="showCreateDAR()" title="New Daily Activity Report" maxWidth="560px" (closed)="showCreateDAR.set(false)">
      <div class="space-y-3">
        <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Report *</label>
          <textarea [(ngModel)]="darForm.content" rows="6" class="input-base w-full resize-none" placeholder="Describe activities, incidents, observations..."></textarea></div>
        <div class="grid grid-cols-2 gap-3">
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Weather</label>
            <input type="text" [(ngModel)]="darForm.weather" class="input-base w-full" placeholder="Clear skies" /></div>
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Date</label>
            <input type="date" [(ngModel)]="darForm.report_date" class="input-base w-full" /></div>
        </div>
      </div>
      <div modal-footer>
        <button (click)="showCreateDAR.set(false)" class="btn-secondary">Cancel</button>
        <button (click)="onCreateDAR()" class="btn-primary">Save as Draft</button>
      </div>
    </g51-modal>
  `,
})
export class ReportsComponent implements OnInit {
  private api = inject(ApiService); private toast = inject(ToastService);
  readonly FileTextIcon = FileText; readonly PlusIcon = Plus; readonly EyeIcon = Eye;
  readonly CheckCircleIcon = CheckCircle; readonly CameraIcon = Camera;
  readonly activeTab = signal('Activity Reports');
  readonly showCreateDAR = signal(false);
  readonly showCreateTemplate = signal(false);
  readonly dars = signal<any[]>([]);
  readonly templates = signal<any[]>([]);
  readonly stats = signal({ darsToday: 0, pendingReview: 0, approved: 0, watchPhotos: 0 });
  darForm = { content: '', weather: '', report_date: new Date().toISOString().substring(0, 10) };
  tplForm: { name: string; description: string; fields: { name: string; type: string; required: boolean }[] } = { name: '', description: '', fields: [] };
  watchFeed = [
    { id: '1', caption: 'Broken fence near parking', guard: 'Musa I.', time: '06:30 AM' },
    { id: '2', caption: 'New visitor log', guard: 'Chika N.', time: '07:15 AM' },
    { id: '3', caption: 'Gate lock replaced', guard: 'Musa I.', time: '09:00 AM' },
  ];
  ngOnInit(): void {
    this.api.get<any>('/reports/dar').subscribe({ next: res => { if (res.data) this.dars.set(res.data.reports || []); } });
    this.api.get<any>('/reports/templates').subscribe({ next: res => { if (res.data) this.templates.set(res.data.templates || []); } });
  }
  onCreateDAR(): void {
    this.api.post('/reports/dar', this.darForm).subscribe({
      next: () => { this.showCreateDAR.set(false); this.toast.success('Report saved'); this.ngOnInit(); },
    });
  }

  addField(): void { this.tplForm.fields.push({ name: '', type: 'text', required: false }); }
  removeField(idx: number): void { this.tplForm.fields.splice(idx, 1); }

  onCreateTemplate(): void {
    this.api.post('/reports/templates', this.tplForm).subscribe({
      next: () => { this.showCreateTemplate.set(false); this.toast.success('Template created'); this.tplForm = { name: '', description: '', fields: [] }; this.ngOnInit(); },
    });
  }
}
