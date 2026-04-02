import { Component, inject, signal, OnInit } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { NgClass } from '@angular/common';
import { LucideAngularModule, LifeBuoy, Plus, Send, CheckCircle, Clock, AlertTriangle } from 'lucide-angular';
import { PageHeaderComponent } from '@shared/components/page-header/page-header.component';
import { ModalComponent } from '@shared/components/modal/modal.component';
import { EmptyStateComponent } from '@shared/components/empty-state/empty-state.component';
import { LoadingSpinnerComponent } from '@shared/components/loading-spinner/loading-spinner.component';
import { ApiService } from '@core/services/api.service';
import { ToastService } from '@core/services/toast.service';

@Component({
  selector: 'g51-support',
  standalone: true,
  imports: [FormsModule, NgClass, LucideAngularModule, PageHeaderComponent, ModalComponent, EmptyStateComponent, LoadingSpinnerComponent],
  template: `
    <g51-page-header title="Support" subtitle="Submit and track support requests">
      <button (click)="showCreate.set(true)" class="btn-primary flex items-center gap-2"><lucide-icon [img]="PlusIcon" [size]="16" /> New Ticket</button>
    </g51-page-header>

    <div class="flex gap-1 mb-4">
      @for (tab of ['All', 'Open', 'In Progress', 'Resolved']; track tab) {
        <button (click)="statusFilter.set(tab === 'All' ? '' : tab === 'In Progress' ? 'in_progress' : tab.toLowerCase()); load()"
          class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors"
          [ngClass]="(tab === 'All' && !statusFilter()) || statusFilter() === (tab === 'In Progress' ? 'in_progress' : tab.toLowerCase()) ? 'bg-[var(--color-brand-500)] text-white' : 'bg-[var(--surface-muted)]'"
          [style.color]="!((tab === 'All' && !statusFilter()) || statusFilter() === (tab === 'In Progress' ? 'in_progress' : tab.toLowerCase())) ? 'var(--text-secondary)' : ''">{{ tab }}</button>
      }
    </div>

    @if (loading()) { <g51-loading /> }
    @else if (!tickets().length) { <g51-empty-state title="No Tickets" message="You haven't submitted any support requests." [icon]="LifeBuoyIcon" /> }
    @else {
      <div class="space-y-2">
        @for (t of tickets(); track t.id) {
          <div class="card p-4">
            <div class="flex items-center justify-between mb-1">
              <p class="text-sm font-semibold" [style.color]="'var(--text-primary)'">{{ t.subject }}</p>
              <div class="flex items-center gap-2">
                <span class="badge text-[10px]" [ngClass]="t.priority === 'critical' ? 'bg-red-50 text-red-600' : t.priority === 'high' ? 'bg-amber-50 text-amber-600' : 'bg-gray-100 text-gray-500'">{{ t.priority }}</span>
                <span class="badge text-[10px]" [ngClass]="t.status === 'open' ? 'bg-blue-50 text-blue-600' : t.status === 'resolved' ? 'bg-emerald-50 text-emerald-600' : t.status === 'in_progress' ? 'bg-amber-50 text-amber-600' : 'bg-gray-100 text-gray-500'">{{ t.status }}</span>
              </div>
            </div>
            <p class="text-xs" [style.color]="'var(--text-secondary)'">{{ t.description?.slice(0, 150) }}{{ t.description?.length > 150 ? '...' : '' }}</p>
            <p class="text-[10px] mt-1" [style.color]="'var(--text-tertiary)'">{{ t.category || 'General' }} · {{ t.created_at }}</p>
          </div>
        }
      </div>
    }

    <g51-modal [open]="showCreate()" title="Submit Support Ticket" maxWidth="520px" (closed)="showCreate.set(false)">
      <div class="space-y-3">
        <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Subject *</label><input type="text" [(ngModel)]="form.subject" class="input-base w-full" placeholder="Brief summary of your issue" /></div>
        <div class="grid grid-cols-2 gap-3">
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Category</label>
            <select [(ngModel)]="form.category" class="input-base w-full"><option value="general">General</option><option value="billing">Billing</option><option value="technical">Technical</option><option value="feature_request">Feature Request</option><option value="bug">Bug Report</option></select></div>
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Priority</label>
            <select [(ngModel)]="form.priority" class="input-base w-full"><option value="low">Low</option><option value="medium">Medium</option><option value="high">High</option><option value="critical">Critical</option></select></div>
        </div>
        <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Description *</label>
          <textarea [(ngModel)]="form.description" rows="5" class="input-base w-full resize-none" placeholder="Describe the issue in detail..."></textarea></div>
      </div>
      <div modal-footer><button (click)="showCreate.set(false)" class="btn-secondary">Cancel</button>
        <button (click)="submit()" class="btn-primary flex items-center gap-1"><lucide-icon [img]="SendIcon" [size]="12" /> Submit</button></div>
    </g51-modal>
  `,
})
export class SupportComponent implements OnInit {
  private api = inject(ApiService); private toast = inject(ToastService);
  readonly LifeBuoyIcon = LifeBuoy; readonly PlusIcon = Plus; readonly SendIcon = Send;
  readonly loading = signal(true); readonly showCreate = signal(false); readonly statusFilter = signal('');
  readonly tickets = signal<any[]>([]);
  form: any = { subject: '', category: 'general', priority: 'medium', description: '' };

  ngOnInit(): void { this.load(); }
  load(): void {
    this.loading.set(true);
    const s = this.statusFilter();
    this.api.get<any>(`/support/tickets${s ? '?status=' + s : ''}`).subscribe({
      next: r => { this.tickets.set(r.data?.tickets || r.data || []); this.loading.set(false); },
      error: () => this.loading.set(false),
    });
  }
  submit(): void {
    if (!this.form.subject || !this.form.description) { this.toast.warning('Subject and description required'); return; }
    this.api.post('/support/tickets', this.form).subscribe({ next: () => { this.showCreate.set(false); this.toast.success('Ticket submitted'); this.form = { subject: '', category: 'general', priority: 'medium', description: '' }; this.load(); } });
  }
}
