import { Component, inject, signal, OnInit } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { NgClass } from '@angular/common';
import { LucideAngularModule, BookOpen, Plus, Check, Search } from 'lucide-angular';
import { PageHeaderComponent } from '@shared/components/page-header/page-header.component';
import { ModalComponent } from '@shared/components/modal/modal.component';
import { EmptyStateComponent } from '@shared/components/empty-state/empty-state.component';
import { LoadingSpinnerComponent } from '@shared/components/loading-spinner/loading-spinner.component';
import { ApiService } from '@core/services/api.service';
import { ToastService } from '@core/services/toast.service';

@Component({
  selector: 'g51-passdowns',
  standalone: true,
  imports: [FormsModule, NgClass, LucideAngularModule, PageHeaderComponent, ModalComponent, EmptyStateComponent, LoadingSpinnerComponent],
  template: `
    <g51-page-header title="Passdown Logs" subtitle="Shift handover notes between guards">
      <button class="btn-primary flex items-center gap-2" (click)="showCreate.set(true)"><lucide-icon [img]="PlusIcon" [size]="16" /> New Passdown</button>
    </g51-page-header>
    <div class="flex gap-1 mb-4">
      @for (tab of ['All', 'Unacknowledged']; track tab) {
        <button (click)="activeTab.set(tab); load()" class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors"
          [ngClass]="activeTab() === tab ? 'bg-[var(--color-brand-500)] text-white' : 'bg-[var(--surface-muted)]'" [style.color]="activeTab() !== tab ? 'var(--text-secondary)' : ''">{{ tab }}</button>
      }
    </div>
    @if (loading()) { <g51-loading /> }
    @else if (!passdowns().length) { <g51-empty-state title="No Passdowns" message="Create the first passdown log." [icon]="BookIcon" /> }
    @else {
      <div class="space-y-2">
        @for (p of passdowns(); track p.id) {
          <div class="card p-4">
            <div class="flex items-center justify-between mb-2">
              <div><p class="text-sm font-semibold" [style.color]="'var(--text-primary)'">{{ p.title || 'Passdown' }}</p>
                <p class="text-xs" [style.color]="'var(--text-tertiary)'">{{ p.from_guard_name || 'Guard' }} → {{ p.to_guard_name || 'Next shift' }} · {{ p.site_name || '' }} · {{ p.created_at }}</p></div>
              <div class="flex items-center gap-2">
                <span class="badge text-[10px]" [ngClass]="p.priority === 'high' || p.priority === 'urgent' ? 'bg-red-50 text-red-600' : p.priority === 'medium' || p.priority === 'important' ? 'bg-amber-50 text-amber-600' : 'bg-gray-100 text-gray-500'">{{ p.priority }}</span>
                @if (!p.is_acknowledged) { <button (click)="acknowledge(p)" class="btn-secondary text-xs py-1 px-2 flex items-center gap-1"><lucide-icon [img]="CheckIcon" [size]="12" /> Ack</button> }
                @else { <span class="text-[10px] text-emerald-500 font-medium">Acknowledged</span> }
              </div>
            </div>
            <p class="text-xs" [style.color]="'var(--text-secondary)'">{{ p.content }}</p>
          </div>
        }
      </div>
    }
    <g51-modal [open]="showCreate()" title="New Passdown" maxWidth="520px" (closed)="showCreate.set(false)">
      <div class="space-y-3">
        <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Site *</label>
          <select [(ngModel)]="form.site_id" class="input-base w-full">
            <option value="">Select site</option>
            @for (s of sites(); track s.id) { <option [value]="s.id">{{ s.name }}</option> }
          </select></div>
        <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Priority</label>
          <select [(ngModel)]="form.priority" class="input-base w-full"><option value="low">Low</option><option value="medium">Medium</option><option value="high">High</option></select></div>
        <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Title</label>
          <input type="text" [(ngModel)]="form.title" class="input-base w-full" placeholder="Brief summary" /></div>
        <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Content *</label>
          <textarea [(ngModel)]="form.content" rows="4" class="input-base w-full resize-none" placeholder="Detailed handover notes..."></textarea></div>
      </div>
      <div modal-footer><button (click)="showCreate.set(false)" class="btn-secondary">Cancel</button><button (click)="onCreate()" class="btn-primary">Submit</button></div>
    </g51-modal>
  `,
})
export class PassdownsComponent implements OnInit {
  private api = inject(ApiService); private toast = inject(ToastService);
  readonly BookIcon = BookOpen; readonly PlusIcon = Plus; readonly CheckIcon = Check; readonly SearchIcon = Search;
  readonly passdowns = signal<any[]>([]); readonly sites = signal<any[]>([]); readonly loading = signal(true);
  readonly showCreate = signal(false); readonly activeTab = signal('All');
  form: any = { site_id: '', priority: 'low', title: '', content: '' };
  ngOnInit(): void { this.load(); this.api.get<any>('/sites').subscribe({ next: res => this.sites.set(res.data?.sites || res.data || []) }); }
  load(): void {
    this.loading.set(true);
    const endpoint = this.activeTab() === 'Unacknowledged' ? '/passdowns/unacknowledged' : '/passdowns/site/all';
    this.api.get<any>(endpoint).subscribe({
      next: res => { this.passdowns.set(res.data?.passdowns || res.data?.items || res.data || []); this.loading.set(false); },
      error: () => { this.passdowns.set([]); this.loading.set(false); },
    });
  }
  onCreate(): void { this.api.post('/passdowns', this.form).subscribe({ next: () => { this.showCreate.set(false); this.toast.success('Passdown created'); this.load(); } }); }
  acknowledge(p: any): void { this.api.post(`/passdowns/${p.id}/acknowledge`, {}).subscribe({ next: () => { this.toast.success('Acknowledged'); this.load(); } }); }
}
