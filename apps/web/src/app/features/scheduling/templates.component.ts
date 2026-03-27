import { Component, inject, signal, OnInit } from '@angular/core';
import { RouterLink } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { LucideAngularModule, Plus, Clock, Edit, ArrowLeft } from 'lucide-angular';
import { PageHeaderComponent } from '@shared/components/page-header/page-header.component';
import { ModalComponent } from '@shared/components/modal/modal.component';
import { ApiService } from '@core/services/api.service';
import { ToastService } from '@core/services/toast.service';

@Component({
  selector: 'g51-shift-templates',
  standalone: true,
  imports: [RouterLink, FormsModule, LucideAngularModule, PageHeaderComponent, ModalComponent],
  template: `
    <g51-page-header title="Shift Templates" subtitle="Define reusable shift patterns">
      <a routerLink="/scheduling" class="btn-secondary flex items-center gap-1.5"><lucide-icon [img]="ArrowLeftIcon" [size]="16" /> Back</a>
      <button (click)="showModal.set(true)" class="btn-primary flex items-center gap-2"><lucide-icon [img]="PlusIcon" [size]="16" /> New Template</button>
    </g51-page-header>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
      @for (t of templates(); track t.id) {
        <div class="card p-4 card-hover">
          <div class="flex items-center justify-between mb-2">
            <div class="flex items-center gap-2">
              <div class="h-3 w-3 rounded-full" [style.background]="t.color || 'var(--color-brand-500)'"></div>
              <h3 class="text-sm font-semibold" [style.color]="'var(--text-primary)'">{{ t.name }}</h3>
            </div>
            <span class="badge" [class]="t.is_active ? 'bg-emerald-50 text-emerald-600 dark:bg-emerald-950 dark:text-emerald-400' : 'bg-[var(--surface-muted)]'">{{ t.is_active ? 'Active' : 'Inactive' }}</span>
          </div>
          <div class="flex items-center gap-4 text-xs" [style.color]="'var(--text-secondary)'">
            <span class="flex items-center gap-1"><lucide-icon [img]="ClockIcon" [size]="12" /> {{ t.start_time }} — {{ t.end_time }}</span>
            <span>{{ t.duration_hours }}h</span>
          </div>
          <p class="text-xs mt-1.5" [style.color]="'var(--text-tertiary)'">{{ t.day_labels?.join(', ') }}</p>
          @if (t.is_overnight) {
            <span class="badge mt-1.5 bg-purple-50 text-purple-600 dark:bg-purple-950 dark:text-purple-400 text-[10px]">Overnight</span>
          }
        </div>
      } @empty {
        <div class="col-span-full card p-12 text-center" [style.color]="'var(--text-tertiary)'">
          <p class="text-sm">No templates yet. Create one to start generating shifts.</p>
        </div>
      }
    </div>

    <g51-modal [open]="showModal()" title="New Shift Template" (closed)="showModal.set(false)">
      <div class="space-y-3">
        <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Name *</label>
          <input type="text" [(ngModel)]="form.name" class="input-base w-full" placeholder="Day Shift" /></div>
        <div class="grid grid-cols-2 gap-3">
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Start Time</label>
            <input type="time" [(ngModel)]="form.start_time" class="input-base w-full" /></div>
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">End Time</label>
            <input type="time" [(ngModel)]="form.end_time" class="input-base w-full" /></div>
        </div>
      </div>
      <div modal-footer>
        <button (click)="showModal.set(false)" class="btn-secondary">Cancel</button>
        <button (click)="onCreate()" class="btn-primary">Create</button>
      </div>
    </g51-modal>
  `,
})
export class TemplatesComponent implements OnInit {
  private api = inject(ApiService);
  private toast = inject(ToastService);
  readonly PlusIcon = Plus; readonly ClockIcon = Clock; readonly EditIcon = Edit; readonly ArrowLeftIcon = ArrowLeft;
  readonly templates = signal<any[]>([]);
  readonly showModal = signal(false);
  form = { name: '', start_time: '06:00', end_time: '18:00' };

  ngOnInit(): void {
    this.api.get<any>('/shift-templates').subscribe({ next: res => { if (res.data) this.templates.set(res.data.templates || []); } });
  }
  onCreate(): void {
    this.api.post('/shift-templates', this.form).subscribe({
      next: () => { this.showModal.set(false); this.toast.success('Template created'); this.ngOnInit(); this.form = { name: '', start_time: '06:00', end_time: '18:00' }; }
    });
  }
}
