import { Component, inject, signal, OnInit } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { NgClass } from '@angular/common';
import { LucideAngularModule, BookOpen, Plus, Send, CheckCircle, Clock } from 'lucide-angular';
import { PageHeaderComponent } from '@shared/components/page-header/page-header.component';
import { SearchableSelectComponent, SelectOption } from '@shared/components/searchable-select/searchable-select.component';
import { ModalComponent } from '@shared/components/modal/modal.component';
import { EmptyStateComponent } from '@shared/components/empty-state/empty-state.component';
import { LoadingSpinnerComponent } from '@shared/components/loading-spinner/loading-spinner.component';
import { ApiService } from '@core/services/api.service';
import { AuthStore } from '@core/services/auth.store';
import { ToastService } from '@core/services/toast.service';

@Component({
  selector: 'g51-passdowns',
  standalone: true,
  imports: [FormsModule, NgClass, LucideAngularModule, PageHeaderComponent, ModalComponent, EmptyStateComponent, LoadingSpinnerComponent, SearchableSelectComponent],
  template: `
    <g51-page-header title="Passdown Logs" subtitle="Shift handover notes and acknowledgements">
      <button (click)="showCreate.set(true)" class="btn-primary flex items-center gap-2"><lucide-icon [img]="PlusIcon" [size]="16" /> New Passdown</button>
    </g51-page-header>

    <div class="tab-pills">
      @for (tab of ['Pending', 'Acknowledged', 'All']; track tab) {
        <button (click)="activeTab.set(tab); loadPassdowns()" class="tab-pill" [ngClass]="activeTab() === tab ? 'active' : ''">{{ tab }}</button>
      }
    </div>

    @if (loading()) { <g51-loading /> }
    @else if (!passdowns().length) { <g51-empty-state title="No Passdowns" message="No passdown logs in this category." [icon]="BookOpenIcon" /> }
    @else {
      <div class="space-y-2">
        @for (p of passdowns(); track p.id) {
          <div class="card p-4">
            <div class="flex items-center justify-between mb-1">
              <div class="flex items-center gap-2">
                <span class="h-2 w-2 rounded-full" [ngClass]="p.priority === 'high' ? 'bg-red-500' : p.priority === 'medium' ? 'bg-amber-500' : 'bg-emerald-500'"></span>
                <p class="text-sm font-semibold" [style.color]="'var(--text-primary)'">{{ p.title || 'Passdown' }}</p>
                <span class="badge text-[10px]" [ngClass]="p.priority === 'high' ? 'bg-red-50 text-red-600' : p.priority === 'medium' ? 'bg-amber-50 text-amber-600' : 'bg-emerald-50 text-emerald-600'">{{ p.priority }}</span>
              </div>
              <span class="badge text-[10px]" [ngClass]="p.is_acknowledged || p.acknowledged_at ? 'bg-emerald-50 text-emerald-600' : 'bg-gray-100 text-gray-500'">{{ p.is_acknowledged || p.acknowledged_at ? 'Ack' : 'Pending' }}</span>
            </div>
            <p class="text-xs" [style.color]="'var(--text-secondary)'">{{ p.content }}</p>
            <div class="flex items-center justify-between mt-2">
              <p class="text-[10px]" [style.color]="'var(--text-tertiary)'">{{ p.site_name || '' }} · By {{ p.created_by_name || '' }} · {{ p.created_at }}</p>
              @if (!p.is_acknowledged && !p.acknowledged_at) {
                <button (click)="acknowledge(p)" class="btn-secondary text-[10px] py-1 px-2">Acknowledge</button>
              }
            </div>
          </div>
        }
      </div>
    }

    <g51-modal [open]="showCreate()" title="New Passdown" maxWidth="500px" (closed)="showCreate.set(false)">
      <div class="space-y-3">
        <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Title *</label><input type="text" [(ngModel)]="form.title" class="input-base w-full" /></div>
        <div class="grid grid-cols-2 gap-3">
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Priority</label>
            <select [(ngModel)]="form.priority" class="input-base w-full"><option value="low">Low</option><option value="medium">Medium</option><option value="high">High</option></select></div>
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Site</label>
            <g51-searchable-select [(ngModel)]="form.site_id" [options]="siteOptions()" placeholder="All Sites" [allowEmpty]="true" emptyLabel="All Sites" /></div>
        </div>
        <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Content *</label>
          <textarea [(ngModel)]="form.content" rows="4" class="input-base w-full resize-none" placeholder="Handover notes for the incoming shift..."></textarea></div>
      </div>
      <div modal-footer><button (click)="showCreate.set(false)" class="btn-secondary">Cancel</button>
        <button (click)="create()" class="btn-primary flex items-center gap-1"><lucide-icon [img]="SendIcon" [size]="12" /> Submit</button></div>
    </g51-modal>
  `,
})
export class PassdownsComponent implements OnInit {
  private api = inject(ApiService);
  readonly auth = inject(AuthStore); private toast = inject(ToastService);
  readonly BookOpenIcon = BookOpen; readonly PlusIcon = Plus; readonly SendIcon = Send;
  readonly activeTab = signal('Pending'); readonly loading = signal(true); readonly showCreate = signal(false);
  readonly passdowns = signal<any[]>([]); readonly sites = signal<any[]>([]);
  readonly siteOptions = signal<SelectOption[]>([]);
  form: any = { title: '', priority: 'low', site_id: '', content: '' };

  ngOnInit(): void { this.loadPassdowns(); this.api.get<any>('/sites').subscribe({ next: (r: any) => { const s = r.data?.sites || r.data || []; this.sites.set(s); this.siteOptions.set(s.map((x: any) => ({ value: x.id, label: x.name, sublabel: x.address || '' }))); } }); }
  loadPassdowns(): void {
    this.loading.set(true);
    const status = this.activeTab() === 'Pending' ? '?status=pending' : this.activeTab() === 'Acknowledged' ? '?status=acknowledged' : '';
    this.api.get<any>(`/passdowns${status}`).subscribe({
      next: r => { this.passdowns.set(r.data?.passdowns || r.data || []); this.loading.set(false); },
      error: () => this.loading.set(false),
    });
  }
  acknowledge(p: any): void { this.api.post(`/passdowns/${p.id}/acknowledge`, {}).subscribe({ next: () => { this.toast.success('Acknowledged'); this.loadPassdowns(); } }); }
  create(): void {
    if (!this.form.content) { this.toast.warning('Content required'); return; }
    this.api.post('/passdowns', this.form).subscribe({ next: () => { this.showCreate.set(false); this.toast.success('Passdown created'); this.form = { title: '', priority: 'low', site_id: '', content: '' }; this.loadPassdowns(); } });
  }
}
