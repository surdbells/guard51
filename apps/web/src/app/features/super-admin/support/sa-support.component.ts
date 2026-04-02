import { Component, inject, signal, OnInit } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { NgClass } from '@angular/common';
import { LucideAngularModule, LifeBuoy, Search, UserPlus, CheckCircle, XCircle } from 'lucide-angular';
import { PageHeaderComponent } from '@shared/components/page-header/page-header.component';
import { StatsCardComponent } from '@shared/components/stats-card/stats-card.component';
import { EmptyStateComponent } from '@shared/components/empty-state/empty-state.component';
import { LoadingSpinnerComponent } from '@shared/components/loading-spinner/loading-spinner.component';
import { ApiService } from '@core/services/api.service';
import { ToastService } from '@core/services/toast.service';

@Component({
  selector: 'g51-sa-support',
  standalone: true,
  imports: [FormsModule, NgClass, LucideAngularModule, PageHeaderComponent, StatsCardComponent, EmptyStateComponent, LoadingSpinnerComponent],
  template: `
    <g51-page-header title="Support Tickets" subtitle="All support requests across all companies" />

    <div class="grid grid-cols-4 gap-4 mb-4 stagger-children">
      <g51-stats-card label="Open" [value]="openCount()" [icon]="LifeBuoyIcon" />
      <g51-stats-card label="In Progress" [value]="inProgressCount()" [icon]="LifeBuoyIcon" />
      <g51-stats-card label="Resolved" [value]="resolvedCount()" [icon]="CheckIcon" />
      <g51-stats-card label="Total" [value]="tickets().length" [icon]="LifeBuoyIcon" />
    </div>

    <div class="flex items-center gap-3 mb-4">
      <select [(ngModel)]="statusFilter" (ngModelChange)="load()" class="input-base text-xs py-2">
        <option value="">All Status</option><option value="open">Open</option><option value="in_progress">In Progress</option><option value="resolved">Resolved</option><option value="closed">Closed</option>
      </select>
    </div>

    @if (loading()) { <g51-loading /> }
    @else if (!tickets().length) { <g51-empty-state title="No Tickets" message="No support tickets." [icon]="LifeBuoyIcon" /> }
    @else {
      <div class="card overflow-hidden">
        <table class="w-full text-xs">
          <thead><tr [style.background]="'var(--surface-muted)'">
            <th class="text-left py-2.5 px-4 font-semibold" [style.color]="'var(--text-secondary)'">Subject</th>
            <th class="text-left py-2.5 px-4 font-semibold" [style.color]="'var(--text-secondary)'">Company</th>
            <th class="text-left py-2.5 px-4 font-semibold" [style.color]="'var(--text-secondary)'">Priority</th>
            <th class="text-left py-2.5 px-4 font-semibold" [style.color]="'var(--text-secondary)'">Status</th>
            <th class="text-left py-2.5 px-4 font-semibold" [style.color]="'var(--text-secondary)'">Created</th>
            <th class="text-right py-2.5 px-4 font-semibold" [style.color]="'var(--text-secondary)'">Actions</th>
          </tr></thead>
          <tbody>
            @for (t of tickets(); track t.id) {
              <tr class="border-t hover:bg-[var(--surface-hover)]" [style.borderColor]="'var(--border-default)'">
                <td class="py-2.5 px-4"><p class="font-medium" [style.color]="'var(--text-primary)'">{{ t.subject }}</p>
                  <p class="text-[10px]" [style.color]="'var(--text-tertiary)'">{{ t.description?.slice(0, 80) }}</p></td>
                <td class="py-2.5 px-4" [style.color]="'var(--text-secondary)'">{{ t.tenant_name || t.tenant_id?.slice(0, 8) }}</td>
                <td class="py-2.5 px-4"><span class="badge text-[10px]" [ngClass]="t.priority === 'critical' ? 'bg-red-50 text-red-600' : t.priority === 'high' ? 'bg-amber-50 text-amber-600' : 'bg-gray-100 text-gray-500'">{{ t.priority }}</span></td>
                <td class="py-2.5 px-4"><span class="badge text-[10px]" [ngClass]="t.status === 'open' ? 'bg-blue-50 text-blue-600' : t.status === 'resolved' ? 'bg-emerald-50 text-emerald-600' : 'bg-amber-50 text-amber-600'">{{ t.status }}</span></td>
                <td class="py-2.5 px-4" [style.color]="'var(--text-tertiary)'">{{ t.created_at?.slice(0, 10) }}</td>
                <td class="py-2.5 px-4 text-right">
                  @if (t.status === 'open') { <button (click)="resolve(t)" class="btn-secondary text-[10px] py-1 px-2">Resolve</button> }
                  @if (t.status === 'resolved') { <button (click)="close(t)" class="btn-secondary text-[10px] py-1 px-2">Close</button> }
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
  readonly LifeBuoyIcon = LifeBuoy; readonly CheckIcon = CheckCircle;
  readonly loading = signal(true); readonly tickets = signal<any[]>([]);
  readonly openCount = signal(0); readonly inProgressCount = signal(0); readonly resolvedCount = signal(0);
  statusFilter = '';

  ngOnInit(): void { this.load(); }
  load(): void {
    this.loading.set(true);
    const p = this.statusFilter ? `?status=${this.statusFilter}` : '';
    this.api.get<any>(`/admin/support/tickets${p}`).subscribe({
      next: r => {
        const t = r.data?.tickets || r.data || [];
        this.tickets.set(t);
        this.openCount.set(t.filter((x: any) => x.status === 'open').length);
        this.inProgressCount.set(t.filter((x: any) => x.status === 'in_progress').length);
        this.resolvedCount.set(t.filter((x: any) => x.status === 'resolved').length);
        this.loading.set(false);
      },
      error: () => this.loading.set(false),
    });
  }
  resolve(t: any): void { this.api.post(`/support/tickets/${t.id}/resolve`, {}).subscribe({ next: () => { this.toast.success('Resolved'); this.load(); } }); }
  close(t: any): void { this.api.post(`/support/tickets/${t.id}/close`, {}).subscribe({ next: () => { this.toast.success('Closed'); this.load(); } }); }
}
