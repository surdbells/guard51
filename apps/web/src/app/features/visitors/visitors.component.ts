import { Component, inject, signal, OnInit } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { NgClass } from '@angular/common';
import { LucideAngularModule, Users, Plus, Search, LogIn, LogOut, Trash2 } from 'lucide-angular';
import { PageHeaderComponent } from '@shared/components/page-header/page-header.component';
import { ModalComponent } from '@shared/components/modal/modal.component';
import { EmptyStateComponent } from '@shared/components/empty-state/empty-state.component';
import { LoadingSpinnerComponent } from '@shared/components/loading-spinner/loading-spinner.component';
import { ApiService } from '@core/services/api.service';
import { ToastService } from '@core/services/toast.service';

@Component({
  selector: 'g51-visitors',
  standalone: true,
  imports: [FormsModule, NgClass, LucideAngularModule, PageHeaderComponent, ModalComponent, EmptyStateComponent, LoadingSpinnerComponent],
  template: `
    <g51-page-header title="Visitor Management" subtitle="Track visitor check-ins and check-outs">
      <button class="btn-primary flex items-center gap-2" (click)="showCreate.set(true)"><lucide-icon [img]="PlusIcon" [size]="16" /> Check In Visitor</button>
    </g51-page-header>
    <div class="flex items-center gap-3 mb-4">
      <div class="relative flex-1 max-w-sm">
        <lucide-icon [img]="SearchIcon" [size]="14" class="absolute left-3 top-1/2 -translate-y-1/2" [style.color]="'var(--text-tertiary)'" />
        <input type="text" [(ngModel)]="search" (ngModelChange)="loadVisitors()" placeholder="Search visitors..." class="input-base w-full pl-9" />
      </div>
      <select [(ngModel)]="statusFilter" (ngModelChange)="loadVisitors()" class="input-base text-xs py-2">
        <option value="">All</option><option value="checked_in">Checked In</option><option value="checked_out">Checked Out</option>
      </select>
    </div>
    @if (loading()) { <g51-loading /> }
    @else if (!visitors().length) { <g51-empty-state title="No Visitors" message="Check in your first visitor." [icon]="UsersIcon" /> }
    @else {
      <div class="space-y-2">
        @for (v of visitors(); track v.id) {
          <div class="card p-4 card-hover">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm font-semibold" [style.color]="'var(--text-primary)'">{{ v.first_name }} {{ v.last_name }}</p>
                <p class="text-xs" [style.color]="'var(--text-tertiary)'">{{ v.company_name || 'Individual' }} · {{ v.purpose || 'Visit' }} · Host: {{ v.host_name || 'N/A' }}</p>
              </div>
              <div class="flex items-center gap-2">
                <span class="badge text-[10px]" [ngClass]="v.status === 'checked_in' ? 'bg-emerald-50 text-emerald-600' : 'bg-gray-100 text-gray-500'">{{ v.status === 'checked_in' ? 'IN' : 'OUT' }}</span>
                @if (v.status === 'checked_in') {
                  <button (click)="checkOut(v)" class="btn-secondary text-xs py-1 px-2 flex items-center gap-1"><lucide-icon [img]="LogOutIcon" [size]="12" /> Check Out</button>
                }
              </div>
            </div>
          </div>
        }
      </div>
    }
    <g51-modal [open]="showCreate()" title="Check In Visitor" maxWidth="520px" (closed)="showCreate.set(false)">
      <div class="space-y-3">
        <div class="grid grid-cols-2 gap-3">
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">First Name *</label><input type="text" [(ngModel)]="form.first_name" class="input-base w-full" /></div>
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Last Name *</label><input type="text" [(ngModel)]="form.last_name" class="input-base w-full" /></div>
        </div>
        <div class="grid grid-cols-2 gap-3">
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Company</label><input type="text" [(ngModel)]="form.company_name" class="input-base w-full" /></div>
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Phone</label><input type="tel" [(ngModel)]="form.phone" class="input-base w-full" /></div>
        </div>
        <div class="grid grid-cols-2 gap-3">
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Purpose *</label>
            <select [(ngModel)]="form.purpose" class="input-base w-full"><option value="meeting">Meeting</option><option value="delivery">Delivery</option><option value="maintenance">Maintenance</option><option value="interview">Interview</option><option value="other">Other</option></select></div>
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Host Name</label><input type="text" [(ngModel)]="form.host_name" class="input-base w-full" /></div>
        </div>
        <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">ID Number</label><input type="text" [(ngModel)]="form.id_number" class="input-base w-full" /></div>
      </div>
      <div modal-footer><button (click)="showCreate.set(false)" class="btn-secondary">Cancel</button><button (click)="onCheckIn()" class="btn-primary">Check In</button></div>
    </g51-modal>
  `,
})
export class VisitorsComponent implements OnInit {
  private api = inject(ApiService); private toast = inject(ToastService);
  readonly UsersIcon = Users; readonly PlusIcon = Plus; readonly SearchIcon = Search;
  readonly LogOutIcon = LogOut; readonly LogInIcon = LogIn; readonly TrashIcon = Trash2;
  readonly visitors = signal<any[]>([]); readonly loading = signal(true); readonly showCreate = signal(false);
  search = ''; statusFilter = '';
  form: any = { first_name: '', last_name: '', company_name: '', phone: '', purpose: 'meeting', host_name: '', id_number: '' };
  ngOnInit(): void { this.loadVisitors(); }
  loadVisitors(): void {
    this.loading.set(true);
    const p = new URLSearchParams();
    if (this.search) p.set('search', this.search);
    if (this.statusFilter) p.set('status', this.statusFilter);
    this.api.get<any>(`/visitors?${p}`).subscribe({
      next: res => { this.visitors.set(res.data?.visitors || res.data?.items || res.data || []); this.loading.set(false); },
      error: () => this.loading.set(false),
    });
  }
  onCheckIn(): void { this.api.post('/visitors/check-in', this.form).subscribe({ next: () => { this.showCreate.set(false); this.toast.success('Visitor checked in'); this.loadVisitors(); this.resetForm(); } }); }
  checkOut(v: any): void { this.api.post(`/visitors/${v.id}/check-out`, {}).subscribe({ next: () => { this.toast.success('Visitor checked out'); this.loadVisitors(); } }); }
  resetForm(): void { this.form = { first_name: '', last_name: '', company_name: '', phone: '', purpose: 'meeting', host_name: '', id_number: '' }; }
}
