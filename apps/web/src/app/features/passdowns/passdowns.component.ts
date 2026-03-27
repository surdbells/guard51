import { Component, inject, signal, OnInit } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { NgClass } from '@angular/common';
import { LucideAngularModule, Plus, FileText, CheckCircle, AlertTriangle, Paperclip, Send } from 'lucide-angular';
import { PageHeaderComponent } from '@shared/components/page-header/page-header.component';
import { StatsCardComponent } from '@shared/components/stats-card/stats-card.component';
import { ModalComponent } from '@shared/components/modal/modal.component';
import { EmptyStateComponent } from '@shared/components/empty-state/empty-state.component';
import { ApiService } from '@core/services/api.service';
import { ToastService } from '@core/services/toast.service';

@Component({
  selector: 'g51-passdowns',
  standalone: true,
  imports: [FormsModule, NgClass, LucideAngularModule, PageHeaderComponent, StatsCardComponent, ModalComponent, EmptyStateComponent],
  template: `
    <g51-page-header title="Passdown Logs" subtitle="Shift handover notes between guards">
      <button (click)="showCreate.set(true)" class="btn-primary flex items-center gap-2">
        <lucide-icon [img]="PlusIcon" [size]="16" /> New Passdown
      </button>
    </g51-page-header>

    <div class="grid grid-cols-2 lg:grid-cols-3 gap-4 mb-6 stagger-children">
      <g51-stats-card label="Total Passdowns" [value]="stats().total" [icon]="FileTextIcon" />
      <g51-stats-card label="Unacknowledged" [value]="stats().unacknowledged" [icon]="AlertTriangleIcon" />
      <g51-stats-card label="Acknowledged" [value]="stats().acknowledged" [icon]="CheckCircleIcon" />
    </div>

    <!-- Filter tabs -->
    <div class="flex gap-1 mb-4">
      @for (f of ['All', 'Unacknowledged', 'Urgent']; track f) {
        <button (click)="filter.set(f)" class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors"
          [ngClass]="filter() === f ? 'bg-[var(--color-brand-500)] text-white' : 'bg-[var(--surface-muted)]'"
          [style.color]="filter() !== f ? 'var(--text-secondary)' : ''">{{ f }}</button>
      }
    </div>

    <!-- Passdown feed -->
    <div class="space-y-3">
      @for (p of filteredPassdowns(); track p.id) {
        <div class="card p-4 card-hover">
          <div class="flex items-start gap-3">
            <div class="h-9 w-9 rounded-full flex items-center justify-center text-xs font-semibold shrink-0"
              style="background: var(--color-brand-500); color: var(--text-on-brand)">{{ p.initials }}</div>
            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-2 mb-1">
                <span class="text-sm font-semibold" [style.color]="'var(--text-primary)'">{{ p.guardName }}</span>
                <span class="text-xs" [style.color]="'var(--text-tertiary)'">{{ p.siteName }}</span>
                @if (p.priority !== 'normal') {
                  <span class="badge text-[10px]"
                    [ngClass]="p.priority === 'urgent' ? 'bg-red-50 text-red-600 dark:bg-red-950 dark:text-red-400' : 'bg-amber-50 text-amber-600'">
                    {{ p.priority }}
                  </span>
                }
              </div>
              <p class="text-sm" [style.color]="'var(--text-secondary)'">{{ p.content }}</p>
              @if (p.attachments > 0) {
                <div class="flex items-center gap-1 mt-1.5 text-xs" [style.color]="'var(--text-tertiary)'">
                  <lucide-icon [img]="PaperclipIcon" [size]="12" /> {{ p.attachments }} attachment(s)
                </div>
              }
              <div class="flex items-center justify-between mt-2">
                <span class="text-xs" [style.color]="'var(--text-tertiary)'">{{ p.time }}</span>
                @if (p.acknowledged) {
                  <span class="flex items-center gap-1 text-xs text-emerald-500">
                    <lucide-icon [img]="CheckCircleIcon" [size]="12" /> Acknowledged by {{ p.acknowledgedBy }}
                  </span>
                } @else {
                  <button class="btn-secondary text-xs py-1 px-2.5 flex items-center gap-1">
                    <lucide-icon [img]="CheckCircleIcon" [size]="12" /> Acknowledge
                  </button>
                }
              </div>
            </div>
          </div>
        </div>
      } @empty {
        <g51-empty-state title="No Passdown Logs" message="Create a passdown note when handing over your shift." [icon]="FileTextIcon" />
      }
    </div>

    <!-- Create modal -->
    <g51-modal [open]="showCreate()" title="New Passdown Log" maxWidth="520px" (closed)="showCreate.set(false)">
      <div class="space-y-3">
        <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Content *</label>
          <textarea [(ngModel)]="newPassdown.content" rows="4" class="input-base w-full resize-none" placeholder="Describe what happened during your shift, any issues, and handover notes..."></textarea></div>
        <div class="grid grid-cols-2 gap-3">
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Priority</label>
            <select [(ngModel)]="newPassdown.priority" class="input-base w-full">
              <option value="normal">Normal</option><option value="important">Important</option><option value="urgent">Urgent</option></select></div>
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Incoming Guard</label>
            <input type="text" [(ngModel)]="newPassdown.incoming" class="input-base w-full" placeholder="Guard name" /></div>
        </div>
      </div>
      <div modal-footer>
        <button (click)="showCreate.set(false)" class="btn-secondary">Cancel</button>
        <button (click)="onCreatePassdown()" class="btn-primary flex items-center gap-1.5">
          <lucide-icon [img]="SendIcon" [size]="14" /> Submit
        </button>
      </div>
    </g51-modal>
  `,
})
export class PassdownsComponent implements OnInit {
  private api = inject(ApiService);
  private toast = inject(ToastService);
  readonly PlusIcon = Plus; readonly FileTextIcon = FileText; readonly CheckCircleIcon = CheckCircle;
  readonly AlertTriangleIcon = AlertTriangle; readonly PaperclipIcon = Paperclip; readonly SendIcon = Send;

  readonly showCreate = signal(false);
  readonly filter = signal('All');
  readonly stats = signal({ total: 0, unacknowledged: 0, acknowledged: 0 });

  newPassdown = { content: '', priority: 'normal', incoming: '' };

  passdowns = [
    { id: '1', guardName: 'Musa Ibrahim', initials: 'MI', siteName: 'Lekki Phase 1', content: 'All clear. Perimeter checked at 2200hrs. New visitor log placed in guardhouse. Main gate lock replaced — new key with supervisor.', priority: 'normal', attachments: 0, time: '2 hours ago', acknowledged: true, acknowledgedBy: 'Chika N.' },
    { id: '2', guardName: 'Adebayo Okonkwo', initials: 'AO', siteName: 'Victoria Island HQ', content: 'Suspicious vehicle observed at 0130hrs near Block C parking. Incident report filed. CCTV footage saved. Security light at west entrance needs replacement.', priority: 'important', attachments: 2, time: '5 hours ago', acknowledged: false, acknowledgedBy: '' },
    { id: '3', guardName: 'Funmi Adeyemi', initials: 'FA', siteName: 'Ikeja Mall', content: 'Fire alarm triggered at 0345hrs — false alarm from kitchen area. Reset at 0355hrs. Notified building management.', priority: 'urgent', attachments: 1, time: '8 hours ago', acknowledged: true, acknowledgedBy: 'Kelechi E.' },
  ];

  filteredPassdowns = () => {
    const f = this.filter();
    if (f === 'Unacknowledged') return this.passdowns.filter(p => !p.acknowledged);
    if (f === 'Urgent') return this.passdowns.filter(p => p.priority === 'urgent');
    return this.passdowns;
  };

  ngOnInit(): void {
    this.stats.set({
      total: this.passdowns.length,
      unacknowledged: this.passdowns.filter(p => !p.acknowledged).length,
      acknowledged: this.passdowns.filter(p => p.acknowledged).length,
    });
  }

  onCreatePassdown(): void {
    this.showCreate.set(false);
    this.toast.success('Passdown submitted', 'Your handover notes have been recorded.');
    this.newPassdown = { content: '', priority: 'normal', incoming: '' };
  }
}
