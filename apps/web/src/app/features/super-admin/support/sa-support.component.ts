import { Component, inject, signal, OnInit } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { NgClass } from '@angular/common';
import { LucideAngularModule, LifeBuoy, UserPlus, CheckCircle, XCircle } from 'lucide-angular';
import { PageHeaderComponent } from '@shared/components/page-header/page-header.component';
import { EmptyStateComponent } from '@shared/components/empty-state/empty-state.component';
import { LoadingSpinnerComponent } from '@shared/components/loading-spinner/loading-spinner.component';
import { ApiService } from '@core/services/api.service';
import { ToastService } from '@core/services/toast.service';

@Component({
  selector: 'g51-sa-support',
  standalone: true,
  imports: [FormsModule, NgClass, LucideAngularModule, PageHeaderComponent, EmptyStateComponent, LoadingSpinnerComponent],
  template: `
    <g51-page-header title="Support Tickets" subtitle="All support requests across companies" />
    <div class="grid grid-cols-4 gap-3 mb-4">
      <div class="card p-3 text-center"><p class="text-xl font-bold" [style.color]="'var(--text-primary)'">{{ openCount() }}</p><p class="text-[10px]" [style.color]="'var(--text-tertiary)'">Open</p></div>
      <div class="card p-3 text-center"><p class="text-xl font-bold" [style.color]="'var(--text-primary)'">{{ inProgressCount() }}</p><p class="text-[10px]" [style.color]="'var(--text-tertiary)'">In Progress</p></div>
      <div class="card p-3 text-center"><p class="text-xl font-bold" [style.color]="'var(--text-primary)'">{{ resolvedCount() }}</p><p class="text-[10px]" [style.color]="'var(--text-tertiary)'">Resolved</p></div>
      <div class="card p-3 text-center"><p class="text-xl font-bold" [style.color]="'var(--text-primary)'">{{ tickets().length }}</p><p class="text-[10px]" [style.color]="'var(--text-tertiary)'">Total</p></div>
    </div>
    <div class="flex gap-1 mb-4">
      @for (tab of ['All', 'Open', 'In Progress', 'Resolved', 'Closed']; track tab) {
        <button (click)="filter.set(tab === 'All' ? '' : tab === 'In Progress' ? 'in_progress' : tab.toLowerCase()); load()"
          class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors"
          [ngClass]="(tab === 'All' && !filter()) || filter() === (tab === 'In Progress' ? 'in_progress' : tab.toLowerCase()) ? 'bg-[var(--color-brand-500)] text-white' : 'bg-[var(--surface-muted)]'"
          [style.color]="!((tab === 'All' && !filter()) || filter() === (tab === 'In Progress' ? 'in_progress' : tab.toLowerCase())) ? 'var(--text-secondary)' : ''">{{ tab }}</button>
      }
    </div>
    @if (loading()) { <g51-loading /> }
    @else if (!filtered().length) { <g51-empty-state title="No Tickets" message="No support tickets in this category." [icon]="LifeBuoyIcon" /> }
    @else {
      <div class="card overflow-hidden">
        <table class="w-full text-xs">
          <thead><tr [style.background]="'var(--surface-muted)'">
            <th class="text-left py-2.5 px-4 font-semibold" [style.color]="'var(--text-secondary)'">Subject</th>
            <th class="text-left py-2.5 px-4 font-semibold" [style.color]="'var(--text-secondary)'">Company</th>
            <th class="text-left py-2.5 px-4 font-semibold" [style.color]="'var(--text-secondary)'">Priority</th>
            <th class="text-left py-2.5 px-4 font-semibold" [style.color]="'var(--text-secondary)'">Status</th>
            <th class="text-left py-2.5 px-4 font-semibold" [style.color]="'var(--text-secondary)'">Created</th>
            <th class="text-center py-2.5 px-4 font-semibold" [style.color]="'var(--text-secondary)'">Actions</th>
          </tr></thead>
          <tbody>
            @for (t of filtered(); track t.id) {
              <tr class="border-t" [style.borderColor]="'var(--border-default)'">
                <td class="py-2.5 px-4 font-medium" [style.color]="'var(--text-primary)'">{{ t.subject }}</td>
                <td class="py-2.5 px-4" [style.color]="'var(--text-secondary)'">{{ t.tenant_name || t.tenant_id?.slice(0,8) }}</td>
                <td class="py-2.5 px-4"><span class="badge text-[10px]" [ngClass]="t.priority === 'critical' ? 'bg-red-50 text-red-600' : t.priority === 'high' ? 'bg-amber-50 text-amber-600' : 'bg-gray-100 text-gray-500'">{{ t.priority }}</span></td>
                <td class="py-2.5 px-4"><span class="badge text-[10px]" [ngClass]="t.status === 'open' ? 'bg-blue-50 text-blue-600' : t.status === 'resolved' ? 'bg-emerald-50 text-emerald-600' : 'bg-gray-100 text-gray-500'">{{ t.status }}</span></td>
                <td class="py-2.5 px-4" [style.color]="'var(--text-tertiary)'">{{ t.created_at }}</td>
                <td class="py-2.5 px-4 text-center">
                  <div class="flex justify-center gap-1">
                    @if (t.status === 'open' || t.status === 'in_progress') {
                      <button (click)="resolve(t)" class="p-1 rounded hover:bg-emerald-50" title="Resolve"><lucide-icon [img]="CheckIcon" [size]="14" class="text-emerald-500" /></button>
                      <button (click)="close(t)" class="p-1 rounded hover:bg-red-50" title="Close"><lucide-icon [img]="XIcon" [size]="14" class="text-red-500" /></button>
                    }
                  </div>
                </td>
              </tr>
            }
          </tbody>
        </table>
      </div>
    }
  `,
})
export class SaSupportComponent implements OnInit {
  private api = inject(ApiService); private toast = inject(ToastService);
  readonly LifeBuoyIcon = LifeBuoy; readonly CheckIcon = CheckCircle; readonly XIcon = XCircle;
  readonly loading = signal(true); readonly filter = signal('');
  readonly tickets = signal<any[]>([]);

  filtered() { const f = this.filter(); return !f ? this.tickets() : this.tickets().filter(t => t.status === f); }
  openCount() { return this.tickets().filter(t => t.status === 'open').length; }
  inProgressCount() { return this.tickets().filter(t => t.status === 'in_progress').length; }
  resolvedCount() { return this.tickets().filter(t => t.status === 'resolved').length; }

  ngOnInit(): void { this.load(); }
  load(): void {
    this.loading.set(true);
    this.api.get<any>('/admin/support/tickets').subscribe({
      next: r => { this.tickets.set(r.data?.tickets || r.data || []); this.loading.set(false); },
      error: () => this.loading.set(false),
    });
  }
  resolve(t: any): void { this.api.post(`/support/tickets/${t.id}/resolve`, {}).subscribe({ next: () => { this.toast.success('Resolved'); this.load(); } }); }
  close(t: any): void { this.api.post(`/support/tickets/${t.id}/close`, {}).subscribe({ next: () => { this.toast.success('Closed'); this.load(); } }); }
}
