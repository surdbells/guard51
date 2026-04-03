import { Component, inject, signal, OnInit } from '@angular/core';
import { RouterLink, Router } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { LucideAngularModule, ArrowLeft, Zap, Loader2, Calendar } from 'lucide-angular';
import { PageHeaderComponent } from '@shared/components/page-header/page-header.component';
import { SearchableSelectComponent, SelectOption } from '@shared/components/searchable-select/searchable-select.component';
import { ApiService } from '@core/services/api.service';
import { ToastService } from '@core/services/toast.service';

@Component({
  selector: 'g51-bulk-wizard',
  standalone: true,
  imports: [RouterLink, FormsModule, LucideAngularModule, PageHeaderComponent, SearchableSelectComponent],
  template: `
    <g51-page-header title="Bulk Generate Shifts" subtitle="Create multiple shifts from a template across a date range">
      <a routerLink="/scheduling" class="btn-secondary flex items-center gap-1.5"><lucide-icon [img]="ArrowLeftIcon" [size]="16" /> Back</a>
    </g51-page-header>

    <div class="max-w-2xl space-y-6">
      <!-- Step 1: Template -->
      <div class="card p-5">
        <div class="flex items-center gap-2 mb-4">
          <div class="h-7 w-7 rounded-full flex items-center justify-center text-xs font-bold" style="background: var(--color-brand-500); color: white">1</div>
          <h3 class="text-sm font-semibold" [style.color]="'var(--text-primary)'">Select Template</h3>
        </div>
        <select [(ngModel)]="form.template_id" class="input-base w-full">
          <option value="">Choose a shift template...</option>
          @for (t of templates(); track t.id) {
            <option [value]="t.id">{{ t.name }} ({{ t.start_time }}–{{ t.end_time }}, {{ t.day_labels?.join(', ') }})</option>
          }
        </select>
      </div>

      <!-- Step 2: Date Range -->
      <div class="card p-5">
        <div class="flex items-center gap-2 mb-4">
          <div class="h-7 w-7 rounded-full flex items-center justify-center text-xs font-bold" style="background: var(--color-brand-500); color: white">2</div>
          <h3 class="text-sm font-semibold" [style.color]="'var(--text-primary)'">Date Range</h3>
        </div>
        <div class="grid grid-cols-2 gap-4">
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Start Date</label>
            <input type="date" [(ngModel)]="form.start_date" class="input-base w-full" /></div>
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">End Date</label>
            <input type="date" [(ngModel)]="form.end_date" class="input-base w-full" /></div>
        </div>
      </div>

      <!-- Step 3: Site override -->
      <div class="card p-5">
        <div class="flex items-center gap-2 mb-4">
          <div class="h-7 w-7 rounded-full flex items-center justify-center text-xs font-bold" style="background: var(--color-brand-500); color: white">3</div>
          <h3 class="text-sm font-semibold" [style.color]="'var(--text-primary)'">Site (Optional Override)</h3>
        </div>
        <select [(ngModel)]="form.site_id" class="input-base w-full">
          <option value="">Use template default</option>
          @for (s of sites(); track s.id) { <option [value]="s.id">{{ s.name }}</option> }
        </select>
      </div>

      @if (result()) {
        <div class="card p-5 bg-emerald-50 dark:bg-emerald-950 border-emerald-200 dark:border-emerald-800">
          <p class="text-sm font-semibold text-emerald-700 dark:text-emerald-400">{{ result()!.created }} shifts created as drafts.</p>
          <p class="text-xs mt-1 text-emerald-600 dark:text-emerald-500">Use the scheduling calendar to review and publish them.</p>
        </div>
      }

      <button (click)="onGenerate()" [disabled]="generating() || !form.template_id || !form.start_date || !form.end_date" class="btn-primary flex items-center gap-2">
        @if (generating()) { <lucide-icon [img]="Loader2Icon" [size]="16" class="animate-spin" /> }
        <lucide-icon [img]="ZapIcon" [size]="16" /> Generate Shifts
      </button>
    </div>
  `,
})
export class BulkWizardComponent implements OnInit {
  private api = inject(ApiService); private router = inject(Router); private toast = inject(ToastService);
  readonly ArrowLeftIcon = ArrowLeft; readonly ZapIcon = Zap; readonly Loader2Icon = Loader2; readonly CalendarIcon = Calendar;
  readonly templates = signal<any[]>([]); readonly sites = signal<any[]>([]);
  readonly siteOptions = signal<SelectOption[]>([]);
  readonly generating = signal(false); readonly result = signal<any>(null);

  form = { template_id: '', start_date: '', end_date: '', site_id: '' };

  ngOnInit(): void {
    this.api.get<any>('/shift-templates?active=true').subscribe({ next: res => { if (res.data) this.templates.set(res.data.templates || []); } });
    this.api.get<any>('/sites').subscribe({ next: res => { if (res.data) this.sites.set(res.data.sites || []); } });
  }

  onGenerate(): void {
    this.generating.set(true); this.result.set(null);
    const body: Record<string, any> = { template_id: this.form.template_id, start_date: this.form.start_date, end_date: this.form.end_date };
    if (this.form.site_id) body['site_id'] = this.form.site_id;
    this.api.post<any>('/shifts/bulk-generate', body).subscribe({
      next: res => { this.generating.set(false); if (res.data) { this.result.set(res.data); this.toast.success(`${res.data.created} shifts generated`); } },
      error: () => this.generating.set(false),
    });
  }
}
