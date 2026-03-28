import { Component, inject, signal, OnInit } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { NgClass } from '@angular/common';
import { LucideAngularModule, UserCheck, Plus, Search, LogOut } from 'lucide-angular';
import { PageHeaderComponent } from '@shared/components/page-header/page-header.component';
import { StatsCardComponent } from '@shared/components/stats-card/stats-card.component';
import { ModalComponent } from '@shared/components/modal/modal.component';
import { EmptyStateComponent } from '@shared/components/empty-state/empty-state.component';
import { ApiService } from '@core/services/api.service';
import { ToastService } from '@core/services/toast.service';

@Component({
  selector: 'g51-visitors',
  standalone: true,
  imports: [FormsModule, NgClass, LucideAngularModule, PageHeaderComponent, StatsCardComponent, ModalComponent, EmptyStateComponent],
  template: `
    <g51-page-header title="Visitor Management" subtitle="Check-in, check-out, and visitor log">
      <button (click)="showCheckIn.set(true)" class="btn-primary flex items-center gap-2"><lucide-icon [img]="PlusIcon" [size]="16" /> Check In Visitor</button>
    </g51-page-header>
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6 stagger-children">
      <g51-stats-card label="Currently On-Site" [value]="checkedIn().length" [icon]="UserCheckIcon" />
      <g51-stats-card label="Today Total" [value]="stats().todayTotal" [icon]="UserCheckIcon" />
      <g51-stats-card label="This Week" [value]="stats().weekTotal" [icon]="UserCheckIcon" />
      <g51-stats-card label="Returning" [value]="stats().returning" [icon]="SearchIcon" />
    </div>
    <div class="space-y-2">
      @for (v of checkedIn(); track v.id) {
        <div class="card p-4 card-hover">
          <div class="flex items-center justify-between">
            <div>
              <div class="flex items-center gap-2 mb-0.5">
                <span class="text-sm font-semibold" [style.color]="'var(--text-primary)'">{{ v.full_name }}</span>
                <span class="badge text-[10px] bg-emerald-50 text-emerald-600 dark:bg-emerald-950 dark:text-emerald-400">On-Site</span>
              </div>
              <p class="text-xs" [style.color]="'var(--text-tertiary)'">{{ v.purpose }} • Host: {{ v.host_name || 'N/A' }} • {{ v.company || '' }}</p>
              <p class="text-[10px]" [style.color]="'var(--text-tertiary)'">ID: {{ v.id_type || 'None' }} {{ v.id_number || '' }} {{ v.vehicle_plate ? '• Vehicle: ' + v.vehicle_plate : '' }}</p>
            </div>
            <button (click)="checkOut(v.id)" class="btn-secondary text-xs py-1 px-2.5 flex items-center gap-1"><lucide-icon [img]="LogOutIcon" [size]="12" /> Check Out</button>
          </div>
        </div>
      } @empty { <g51-empty-state title="No Visitors" message="No visitors currently on-site." [icon]="UserCheckIcon" /> }
    </div>
    <g51-modal [open]="showCheckIn()" title="Check In Visitor" maxWidth="560px" (closed)="showCheckIn.set(false)">
      <div class="space-y-3">
        <div class="grid grid-cols-2 gap-3">
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">First Name *</label><input type="text" [(ngModel)]="form.first_name" class="input-base w-full" /></div>
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Last Name *</label><input type="text" [(ngModel)]="form.last_name" class="input-base w-full" /></div>
        </div>
        <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Purpose *</label><input type="text" [(ngModel)]="form.purpose" class="input-base w-full" placeholder="Meeting, Delivery, Interview..." /></div>
        <div class="grid grid-cols-2 gap-3">
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Phone</label><input type="tel" [(ngModel)]="form.phone" class="input-base w-full" /></div>
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Host Name</label><input type="text" [(ngModel)]="form.host_name" class="input-base w-full" /></div>
        </div>
        <div class="grid grid-cols-2 gap-3">
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">ID Type</label>
            <select [(ngModel)]="form.id_type" class="input-base w-full"><option value="">None</option><option value="national_id">National ID</option><option value="drivers_license">Drivers License</option><option value="passport">Passport</option></select></div>
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">ID Number</label><input type="text" [(ngModel)]="form.id_number" class="input-base w-full" /></div>
        </div>
        <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Vehicle Plate</label><input type="text" [(ngModel)]="form.vehicle_plate" class="input-base w-full" placeholder="AB-123-CD" /></div>
      </div>
      <div modal-footer><button (click)="showCheckIn.set(false)" class="btn-secondary">Cancel</button><button (click)="onCheckIn()" class="btn-primary">Check In</button></div>
    </g51-modal>
  `,
})
export class VisitorsComponent implements OnInit {
  private api = inject(ApiService); private toast = inject(ToastService);
  readonly UserCheckIcon = UserCheck; readonly PlusIcon = Plus; readonly SearchIcon = Search; readonly LogOutIcon = LogOut;
  readonly showCheckIn = signal(false);
  readonly checkedIn = signal<any[]>([]);
  readonly stats = signal({ todayTotal: 0, weekTotal: 0, returning: 0 });
  form: any = { first_name: '', last_name: '', purpose: '', phone: '', host_name: '', id_type: '', id_number: '', vehicle_plate: '', site_id: '' };
  ngOnInit(): void { /* load checked-in visitors for default site */ }
  onCheckIn(): void { this.api.post('/visitors/check-in', this.form).subscribe({ next: () => { this.showCheckIn.set(false); this.toast.success('Visitor checked in'); } }); }
  checkOut(id: string): void { this.api.post('/visitors/' + id + '/check-out', {}).subscribe({ next: () => { this.toast.success('Visitor checked out'); this.ngOnInit(); } }); }
}
