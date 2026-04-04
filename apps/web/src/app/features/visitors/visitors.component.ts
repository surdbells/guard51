import { Component, inject, signal, OnInit } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { NgClass } from '@angular/common';
import { LucideAngularModule, Users, Plus, Search, LogOut, QrCode, Send, Check, X, Calendar, Mail, Phone, MessageSquare } from 'lucide-angular';
import { PageHeaderComponent } from '@shared/components/page-header/page-header.component';
import { SearchableSelectComponent, SelectOption } from '@shared/components/searchable-select/searchable-select.component';
import { ModalComponent } from '@shared/components/modal/modal.component';
import { EmptyStateComponent } from '@shared/components/empty-state/empty-state.component';
import { LoadingSpinnerComponent } from '@shared/components/loading-spinner/loading-spinner.component';
import { ApiService } from '@core/services/api.service';
import { ToastService } from '@core/services/toast.service';

@Component({
  selector: 'g51-visitors',
  standalone: true,
  imports: [FormsModule, NgClass, LucideAngularModule, PageHeaderComponent, ModalComponent, EmptyStateComponent, LoadingSpinnerComponent, SearchableSelectComponent],
  template: `
    <g51-page-header title="Visitor Management" subtitle="Appointments, access codes, and visitor check-in/out">
      <button class="btn-primary flex items-center gap-2" (click)="showCreate.set(true)"><lucide-icon [img]="PlusIcon" [size]="16" /> Schedule Visit</button>
    </g51-page-header>

    <div class="tab-pills overflow-x-auto">
      @for (tab of ['Appointments', 'Walk-ins', 'Verify Code']; track tab) {
        <button (click)="activeTab.set(tab); loadTab()" class="tab-pill" [ngClass]="activeTab() === tab ? 'active' : ''">{{ tab }}</button>
      }
    </div>

    @if (loading()) { <g51-loading /> }

    <!-- APPOINTMENTS TAB -->
    @if (activeTab() === 'Appointments' && !loading()) {
      <div class="flex items-center gap-3 mb-4">
        <div class="relative flex-1 max-w-sm">
          <lucide-icon [img]="SearchIcon" [size]="14" class="absolute left-3 top-1/2 -translate-y-1/2" [style.color]="'var(--text-tertiary)'" />
          <input type="text" [(ngModel)]="search" (ngModelChange)="loadTab()" placeholder="Search appointments..." class="input-base w-full pl-9" />
        </div>
        <select [(ngModel)]="statusFilter" (ngModelChange)="loadTab()" class="input-base text-xs py-2">
          <option value="">All Status</option><option value="pending">Pending</option><option value="checked_in">Checked In</option><option value="completed">Completed</option><option value="cancelled">Cancelled</option>
        </select>
      </div>
      @if (!appointments().length) { <g51-empty-state title="No Appointments" message="Schedule the first visitor appointment." [icon]="CalendarIcon" /> }
      @else {
        <div class="space-y-2">
          @for (a of appointments(); track a.id) {
            <div class="card p-4 card-hover">
              <div class="flex items-center justify-between">
                <div class="flex-1 min-w-0">
                  <div class="flex items-center gap-2 mb-1">
                    <p class="text-sm font-semibold" [style.color]="'var(--text-primary)'">{{ a.visitor_name }}</p>
                    <span class="badge text-[10px] font-mono" [style.background]="'var(--brand-50)'" [style.color]="'var(--brand-700)'">{{ a.access_code }}</span>
                  </div>
                  <p class="text-xs" [style.color]="'var(--text-tertiary)'">{{ a.visitor_company || '' }} · Host: {{ a.host_name }} · {{ a.purpose }}</p>
                  <p class="text-xs" [style.color]="'var(--text-tertiary)'">{{ a.scheduled_date }} {{ a.scheduled_time || '' }} · {{ a.notify_email ? 'Email' : '' }} {{ a.notify_sms ? 'SMS' : '' }} {{ a.notify_whatsapp ? 'WhatsApp' : '' }}</p>
                </div>
                <div class="flex items-center gap-2 ml-3">
                  <span class="badge text-[10px]" [ngClass]="a.status === 'checked_in' ? 'bg-emerald-50 text-emerald-600' : a.status === 'completed' ? 'bg-blue-50 text-blue-600' : a.status === 'cancelled' ? 'bg-red-50 text-red-600' : 'bg-amber-50 text-amber-600'">{{ a.status }}</span>
                  @if (a.status === 'pending') {
                    <button (click)="checkInAppointment(a)" class="btn-primary text-[10px] py-1 px-2">Check In</button>
                    <button (click)="cancelAppointment(a)" class="btn-secondary text-[10px] py-1 px-2 text-red-500">Cancel</button>
                  }
                  @if (a.status === 'checked_in') {
                    <button (click)="checkOutAppointment(a)" class="btn-secondary text-[10px] py-1 px-2">Check Out</button>
                  }
                </div>
              </div>
            </div>
          }
        </div>
      }
    }

    <!-- WALK-INS TAB -->
    @if (activeTab() === 'Walk-ins' && !loading()) {
      <div class="flex justify-end mb-3">
        <button class="btn-primary text-xs flex items-center gap-1" (click)="showWalkin.set(true)"><lucide-icon [img]="PlusIcon" [size]="12" /> Walk-in Check In</button>
      </div>
      @if (!visitors().length) { <g51-empty-state title="No Walk-ins" message="No walk-in visitors today." [icon]="UsersIcon" /> }
      @else {
        <div class="space-y-2">
          @for (v of visitors(); track v.id) {
            <div class="card p-4">
              <div class="flex items-center justify-between">
                <div><p class="text-sm font-semibold" [style.color]="'var(--text-primary)'">{{ v.first_name }} {{ v.last_name }}</p>
                  <p class="text-xs" [style.color]="'var(--text-tertiary)'">{{ v.company_name || 'Individual' }} · {{ v.purpose }} · Host: {{ v.host_name || 'N/A' }}</p></div>
                <div class="flex items-center gap-2">
                  <span class="badge text-[10px]" [ngClass]="v.status === 'checked_in' ? 'bg-emerald-50 text-emerald-600' : 'bg-gray-100 text-gray-500'">{{ v.status === 'checked_in' ? 'IN' : 'OUT' }}</span>
                  @if (v.status === 'checked_in') { <button (click)="checkOutWalkin(v)" class="btn-secondary text-xs py-1 px-2">Check Out</button> }
                </div>
              </div>
            </div>
          }
        </div>
      }
    }

    <!-- VERIFY CODE TAB -->
    @if (activeTab() === 'Verify Code' && !loading()) {
      <div class="max-w-md mx-auto">
        <div class="card p-6 text-center">
          <lucide-icon [img]="QrCodeIcon" [size]="48" class="mx-auto mb-4" [style.color]="'var(--color-brand-500)'" />
          <h3 class="text-base font-bold mb-2" [style.color]="'var(--text-primary)'">Enter Visitor Access Code</h3>
          <p class="text-xs mb-4" [style.color]="'var(--text-tertiary)'">Ask the visitor for their 6-character access code</p>
          <input type="text" [(ngModel)]="verifyCode" class="input-base w-full text-center text-2xl font-mono tracking-[0.3em] uppercase mb-4" maxlength="6" placeholder="A B C D E F" />
          <button (click)="onVerifyCode()" class="btn-primary w-full" [disabled]="verifyCode.length < 6">Verify Code</button>
        </div>
        @if (verifiedAppointment()) {
          <div class="card p-5 mt-4">
            <h3 class="text-sm font-semibold mb-3" [style.color]="'var(--color-brand-500)'">✓ Appointment Found</h3>
            <div class="grid grid-cols-2 gap-y-2 text-xs">
              <div><span [style.color]="'var(--text-tertiary)'">Visitor</span><p class="font-medium" [style.color]="'var(--text-primary)'">{{ verifiedAppointment()!.visitor_name }}</p></div>
              <div><span [style.color]="'var(--text-tertiary)'">Company</span><p class="font-medium" [style.color]="'var(--text-primary)'">{{ verifiedAppointment()!.visitor_company || '—' }}</p></div>
              <div><span [style.color]="'var(--text-tertiary)'">Host</span><p class="font-medium" [style.color]="'var(--text-primary)'">{{ verifiedAppointment()!.host_name }}</p></div>
              <div><span [style.color]="'var(--text-tertiary)'">Purpose</span><p class="font-medium" [style.color]="'var(--text-primary)'">{{ verifiedAppointment()!.purpose }}</p></div>
              <div><span [style.color]="'var(--text-tertiary)'">Date</span><p class="font-medium" [style.color]="'var(--text-primary)'">{{ verifiedAppointment()!.scheduled_date }}</p></div>
              <div><span [style.color]="'var(--text-tertiary)'">Status</span>
                <span class="badge text-[10px]" [ngClass]="verifiedAppointment()!.status === 'pending' ? 'bg-amber-50 text-amber-600' : 'bg-emerald-50 text-emerald-600'">{{ verifiedAppointment()!.status }}</span></div>
            </div>
            @if (verifiedAppointment()!.status === 'pending') {
              <button (click)="checkInVerified()" class="btn-primary w-full mt-4">Check In Visitor</button>
            }
          </div>
        }
      </div>
    }

    <!-- SCHEDULE VISIT MODAL -->
    <g51-modal [open]="showCreate()" title="Schedule Visitor Appointment" maxWidth="640px" (closed)="showCreate.set(false)">
      <div class="space-y-4">
        <h4 class="text-xs font-semibold uppercase tracking-wide" [style.color]="'var(--text-tertiary)'">Visitor Details</h4>
        <div class="grid grid-cols-2 gap-3">
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Visitor Name *</label>
            <input type="text" [(ngModel)]="form.visitor_name" class="input-base w-full" /></div>
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Company</label>
            <input type="text" [(ngModel)]="form.visitor_company" class="input-base w-full" /></div>
        </div>
        <div class="grid grid-cols-2 gap-3">
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Visitor Phone</label>
            <input type="tel" [(ngModel)]="form.visitor_phone" class="input-base w-full" placeholder="+234..." /></div>
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Visitor Email</label>
            <input type="email" [(ngModel)]="form.visitor_email" class="input-base w-full" /></div>
        </div>

        <h4 class="text-xs font-semibold uppercase tracking-wide pt-2" [style.color]="'var(--text-tertiary)'">Host & Schedule</h4>
        <div class="grid grid-cols-2 gap-3">
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Host Name *</label>
            <input type="text" [(ngModel)]="form.host_name" class="input-base w-full" placeholder="Employee to visit" /></div>
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Host Email</label>
            <input type="email" [(ngModel)]="form.host_email" class="input-base w-full" /></div>
        </div>
        <div class="grid grid-cols-2 gap-3">
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Host Phone</label>
            <input type="tel" [(ngModel)]="form.host_phone" class="input-base w-full" /></div>
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Site *</label>
            <g51-searchable-select [(ngModel)]="form.site_id" [options]="siteOptions()" placeholder="Select site" /></div>
        </div>
        <div class="grid grid-cols-3 gap-3">
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Date *</label>
            <input type="date" [(ngModel)]="form.scheduled_date" class="input-base w-full" /></div>
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Time</label>
            <input type="time" [(ngModel)]="form.scheduled_time" class="input-base w-full" /></div>
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Purpose *</label>
            <select [(ngModel)]="form.purpose" class="input-base w-full">
              <option value="meeting">Meeting</option><option value="delivery">Delivery</option><option value="interview">Interview</option>
              <option value="maintenance">Maintenance</option><option value="inspection">Inspection</option><option value="other">Other</option>
            </select></div>
        </div>

        <h4 class="text-xs font-semibold uppercase tracking-wide pt-2" [style.color]="'var(--text-tertiary)'">Notification Channels</h4>
        <p class="text-[10px]" [style.color]="'var(--text-tertiary)'">Select how the access code is sent to the visitor and how the host is notified on arrival</p>
        <div class="flex gap-4">
          <label class="flex items-center gap-2 cursor-pointer">
            <input type="checkbox" [(ngModel)]="form.notify_email" class="rounded" />
            <lucide-icon [img]="MailIcon" [size]="14" [style.color]="'var(--text-secondary)'" />
            <span class="text-xs" [style.color]="'var(--text-secondary)'">Email</span>
          </label>
          <label class="flex items-center gap-2 cursor-pointer">
            <input type="checkbox" [(ngModel)]="form.notify_sms" class="rounded" />
            <lucide-icon [img]="PhoneIcon" [size]="14" [style.color]="'var(--text-secondary)'" />
            <span class="text-xs" [style.color]="'var(--text-secondary)'">SMS</span>
          </label>
          <label class="flex items-center gap-2 cursor-pointer">
            <input type="checkbox" [(ngModel)]="form.notify_whatsapp" class="rounded" />
            <lucide-icon [img]="MessageSquareIcon" [size]="14" [style.color]="'var(--text-secondary)'" />
            <span class="text-xs" [style.color]="'var(--text-secondary)'">WhatsApp</span>
          </label>
        </div>

        <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Notes</label>
          <textarea [(ngModel)]="form.notes" rows="2" class="input-base w-full resize-none" placeholder="Special instructions..."></textarea></div>
      </div>
      <div modal-footer><button (click)="showCreate.set(false)" class="btn-secondary">Cancel</button>
        <button (click)="onCreate()" class="btn-primary flex items-center gap-1"><lucide-icon [img]="SendIcon" [size]="12" /> Schedule & Send Code</button></div>
    </g51-modal>

    <!-- WALK-IN MODAL -->
    <g51-modal [open]="showWalkin()" title="Walk-in Check In" maxWidth="480px" (closed)="showWalkin.set(false)">
      <div class="space-y-3">
        <div class="grid grid-cols-2 gap-3">
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">First Name *</label><input type="text" [(ngModel)]="walkinForm.first_name" class="input-base w-full" /></div>
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Last Name *</label><input type="text" [(ngModel)]="walkinForm.last_name" class="input-base w-full" /></div>
        </div>
        <div class="grid grid-cols-2 gap-3">
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Phone</label><input type="tel" [(ngModel)]="walkinForm.phone" class="input-base w-full" /></div>
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Company</label><input type="text" [(ngModel)]="walkinForm.company_name" class="input-base w-full" /></div>
        </div>
        <div class="grid grid-cols-2 gap-3">
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Purpose</label>
            <select [(ngModel)]="walkinForm.purpose" class="input-base w-full"><option value="meeting">Meeting</option><option value="delivery">Delivery</option><option value="other">Other</option></select></div>
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Host Name</label><input type="text" [(ngModel)]="walkinForm.host_name" class="input-base w-full" /></div>
        </div>
        <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">ID Number</label><input type="text" [(ngModel)]="walkinForm.id_number" class="input-base w-full" /></div>
      </div>
      <div modal-footer><button (click)="showWalkin.set(false)" class="btn-secondary">Cancel</button><button (click)="onWalkinCheckIn()" class="btn-primary">Check In</button></div>
    </g51-modal>
  `,
})
export class VisitorsComponent implements OnInit {
  private api = inject(ApiService); private toast = inject(ToastService);
  readonly UsersIcon = Users; readonly PlusIcon = Plus; readonly SearchIcon = Search;
  readonly LogOutIcon = LogOut; readonly QrCodeIcon = QrCode; readonly SendIcon = Send;
  readonly CalendarIcon = Calendar; readonly MailIcon = Mail; readonly PhoneIcon = Phone; readonly MessageSquareIcon = MessageSquare;

  readonly appointments = signal<any[]>([]); readonly visitors = signal<any[]>([]); readonly sites = signal<any[]>([]);
  readonly siteOptions = signal<SelectOption[]>([]);
  readonly loading = signal(true); readonly showCreate = signal(false); readonly showWalkin = signal(false);
  readonly activeTab = signal('Appointments'); readonly verifiedAppointment = signal<any>(null);
  search = ''; statusFilter = ''; verifyCode = '';

  form: any = { visitor_name: '', visitor_email: '', visitor_phone: '', visitor_company: '', host_name: '', host_email: '', host_phone: '', site_id: '', scheduled_date: new Date().toISOString().slice(0, 10), scheduled_time: '', purpose: 'meeting', notify_email: true, notify_sms: false, notify_whatsapp: false, notes: '' };
  walkinForm: any = { first_name: '', last_name: '', phone: '', company_name: '', purpose: 'meeting', host_name: '', id_number: '' };

  ngOnInit(): void { this.loadTab(); this.api.get<any>('/sites').subscribe({ next: (res: any) => { const s = res.data?.sites || res.data || []; this.sites.set(s); this.siteOptions.set(s.map((x: any) => ({ value: x.id, label: x.name, sublabel: x.address || '' }))); } }); }

  loadTab(): void {
    this.loading.set(true);
    if (this.activeTab() === 'Appointments') {
      const p = new URLSearchParams();
      if (this.statusFilter) p.set('status', this.statusFilter);
      this.api.get<any>(`/visitors/appointments?${p}`).subscribe({ next: res => { this.appointments.set(res.data?.appointments || res.data || []); this.loading.set(false); }, error: () => this.loading.set(false) });
    } else if (this.activeTab() === 'Walk-ins') {
      this.api.get<any>('/visitors/search?q=').subscribe({ next: res => { this.visitors.set(res.data?.visitors || res.data || []); this.loading.set(false); }, error: () => this.loading.set(false) });
    } else { this.loading.set(false); }
  }

  onCreate(): void {
    if (!this.form.visitor_name || !this.form.host_name || !this.form.site_id) { this.toast.warning('Visitor name, host, and site are required'); return; }
    this.api.post('/visitors/appointments', this.form).subscribe({ next: (res: any) => {
      this.showCreate.set(false); this.toast.success('Appointment created! Access code: ' + (res.data?.access_code || '')); this.loadTab();
    }});
  }

  onVerifyCode(): void {
    this.api.post('/visitors/appointments/verify', { code: this.verifyCode }).subscribe({
      next: (res: any) => { this.verifiedAppointment.set(res.data); this.toast.success('Appointment found!'); },
      error: () => this.verifiedAppointment.set(null),
    });
  }

  checkInVerified(): void {
    const a = this.verifiedAppointment();
    if (!a) return;
    this.api.post(`/visitors/appointments/${a.id}/check-in`, {}).subscribe({ next: (res: any) => { this.verifiedAppointment.set(res.data); this.toast.success('Visitor checked in! Host notified.'); } });
  }

  checkInAppointment(a: any): void { this.api.post(`/visitors/appointments/${a.id}/check-in`, {}).subscribe({ next: () => { this.toast.success('Checked in'); this.loadTab(); } }); }
  checkOutAppointment(a: any): void { this.api.post(`/visitors/appointments/${a.id}/check-out`, {}).subscribe({ next: () => { this.toast.success('Checked out'); this.loadTab(); } }); }
  cancelAppointment(a: any): void { this.api.post(`/visitors/appointments/${a.id}/cancel`, {}).subscribe({ next: () => { this.toast.success('Cancelled'); this.loadTab(); } }); }
  onWalkinCheckIn(): void { this.api.post('/visitors/check-in', this.walkinForm).subscribe({ next: () => { this.showWalkin.set(false); this.toast.success('Walk-in checked in'); this.loadTab(); } }); }
  checkOutWalkin(v: any): void { this.api.post(`/visitors/${v.id}/check-out`, {}).subscribe({ next: () => { this.toast.success('Checked out'); this.loadTab(); } }); }
}
