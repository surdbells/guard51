import { Component, inject, signal, OnInit } from '@angular/core';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { LucideAngularModule, ArrowLeft, Save, Loader2 } from 'lucide-angular';
import { PageHeaderComponent } from '@shared/components/page-header/page-header.component';
import { SearchableSelectComponent, SelectOption } from '@shared/components/searchable-select/searchable-select.component';
import { ApiService } from '@core/services/api.service';
import { ToastService } from '@core/services/toast.service';

@Component({
  selector: 'g51-shift-form',
  standalone: true,
  imports: [RouterLink, FormsModule, LucideAngularModule, PageHeaderComponent, SearchableSelectComponent],
  template: `
    <g51-page-header [title]="isEdit() ? 'Edit Shift' : 'Create Shift'" subtitle="Assign a guard to a site for a specific date and time">
      <a routerLink="/scheduling" class="btn-secondary flex items-center gap-1.5"><lucide-icon [img]="ArrowLeftIcon" [size]="16" /> Back</a>
    </g51-page-header>

    <form (ngSubmit)="onSubmit()" class="max-w-3xl space-y-6">
      <div class="card p-5">
        <h3 class="text-sm font-semibold mb-4" [style.color]="'var(--text-primary)'">Shift Details</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Site *</label>
            <g51-searchable-select [(ngModel)]="form.site_id" [options]="siteOptions()" placeholder="Select site..." /></div>
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Date *</label>
            <input type="date" [(ngModel)]="form.shift_date" name="shift_date" class="input-base w-full" required /></div>
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Start Time *</label>
            <input type="datetime-local" [(ngModel)]="form.start_time" name="start_time" class="input-base w-full" required /></div>
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">End Time *</label>
            <input type="datetime-local" [(ngModel)]="form.end_time" name="end_time" class="input-base w-full" required /></div>
        </div>
      </div>

      <div class="card p-5">
        <h3 class="text-sm font-semibold mb-4" [style.color]="'var(--text-primary)'">Assignment</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Guard (optional)</label>
            <g51-searchable-select [(ngModel)]="form.guard_id" [options]="guardOptions()" placeholder="Unassigned / Open shift" [allowEmpty]="true" emptyLabel="Unassigned" /></div>
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Template (optional)</label>
            <select [(ngModel)]="form.template_id" name="template_id" class="input-base w-full">
              <option value="">None</option>
              @for (t of templates(); track t.id) { <option [value]="t.id">{{ t.name }}</option> }
            </select></div>
        </div>
        <div class="mt-4 flex items-center gap-3">
          <label class="flex items-center gap-2 text-sm cursor-pointer">
            <input type="checkbox" [(ngModel)]="form.is_open" name="is_open" class="rounded" />
            <span [style.color]="'var(--text-secondary)'">Open shift (guards can claim)</span>
          </label>
        </div>
      </div>

      <div class="card p-5">
        <label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Notes</label>
        <textarea [(ngModel)]="form.notes" name="notes" rows="3" class="input-base w-full resize-none" placeholder="Special instructions for this shift..."></textarea>
      </div>

      <button type="submit" [disabled]="saving()" class="btn-primary flex items-center gap-2">
        @if (saving()) { <lucide-icon [img]="Loader2Icon" [size]="16" class="animate-spin" /> }
        <lucide-icon [img]="SaveIcon" [size]="16" /> {{ isEdit() ? 'Update Shift' : 'Create Shift' }}
      </button>
    </form>
  `,
})
export class ShiftFormComponent implements OnInit {
  private api = inject(ApiService); private router = inject(Router);
  private route = inject(ActivatedRoute); private toast = inject(ToastService);
  readonly ArrowLeftIcon = ArrowLeft; readonly SaveIcon = Save; readonly Loader2Icon = Loader2;
  readonly isEdit = signal(false); readonly saving = signal(false);
  readonly sites = signal<any[]>([]); readonly guards = signal<any[]>([]); readonly templates = signal<any[]>([]);
  readonly siteOptions = signal<SelectOption[]>([]); readonly guardOptions = signal<SelectOption[]>([]);

  form: Record<string, any> = { site_id: '', shift_date: '', start_time: '', end_time: '', guard_id: '', template_id: '', is_open: false, notes: '' };

  ngOnInit(): void {
    this.api.get<any>('/sites').subscribe({ next: (res: any) => { const s = res.data?.sites || []; this.sites.set(s); this.siteOptions.set(s.map((x: any) => ({ value: x.id, label: x.name, sublabel: x.address || '' }))); } });
    this.api.get<any>('/guards').subscribe({ next: (res: any) => { const g = res.data?.guards || []; this.guards.set(g); this.guardOptions.set(g.map((x: any) => ({ value: x.id, label: (x.first_name || '') + ' ' + (x.last_name || ''), sublabel: x.employee_number || '' }))); } });
    this.api.get<any>('/shift-templates?active=true').subscribe({ next: res => { if (res.data) this.templates.set(res.data.templates || []); } });

    const id = this.route.snapshot.params['id'];
    if (id) { this.isEdit.set(true); }
  }

  onSubmit(): void {
    this.saving.set(true);
    this.api.post('/shifts', this.form).subscribe({
      next: () => { this.saving.set(false); this.toast.success('Shift created'); this.router.navigate(['/scheduling']); },
      error: () => this.saving.set(false),
    });
  }
}
