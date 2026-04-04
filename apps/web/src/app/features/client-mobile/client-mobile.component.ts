import { Component, inject, signal, OnInit } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { NgClass, DatePipe, DecimalPipe } from '@angular/common';
import { LucideAngularModule, Home, CalendarDays, Truck, Shield, FileText, Bell, User, MessageSquare, Clock, MapPin, Plus, X, Check, Send } from 'lucide-angular';
import { ApiService } from '@core/services/api.service';
import { AuthStore } from '@core/services/auth.store';
import { ToastService } from '@core/services/toast.service';

@Component({
  selector: 'g51-client-mobile',
  standalone: true,
  imports: [FormsModule, NgClass, DatePipe, DecimalPipe, LucideAngularModule],
  template: `
    <!-- Client Mobile App Shell -->
    <div class="min-h-screen" [style.background]="'var(--surface-bg)'">
      <!-- Header -->
      <header class="sticky top-0 z-30 px-4 py-3 border-b" [style.background]="'var(--surface-card)'" [style.borderColor]="'var(--border-default)'">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-base font-bold font-heading" [style.color]="'var(--text-primary)'">{{ greeting() }}</p>
            <p class="text-xs" [style.color]="'var(--text-tertiary)'">{{ auth.user()?.email }}</p>
          </div>
          <div class="flex items-center gap-2">
            <button class="relative p-2 rounded-lg hover:bg-[var(--surface-muted)]">
              <lucide-icon [img]="BellIcon" [size]="18" [style.color]="'var(--text-tertiary)'" />
              @if (unreadCount()) { <span class="absolute top-0.5 right-0.5 h-4 w-4 rounded-full bg-red-500 text-white text-[9px] flex items-center justify-center">{{ unreadCount() }}</span> }
            </button>
            <div class="h-8 w-8 rounded-full flex items-center justify-center text-xs font-bold text-white" [style.background]="'var(--color-brand-500)'">
              {{ auth.user()?.first_name?.charAt(0) }}{{ auth.user()?.last_name?.charAt(0) }}
            </div>
          </div>
        </div>
      </header>

      <!-- Content -->
      <main class="p-4 pb-24">
        <!-- Quick Stats -->
        @if (activeView() === 'home') {
          <div class="grid grid-cols-2 gap-3 mb-4">
            <div class="card p-4 text-center"><p class="text-xl font-bold" [style.color]="'var(--text-primary)'">{{ stats().active_guards }}</p><p class="text-[10px]" [style.color]="'var(--text-tertiary)'">Active Guards</p></div>
            <div class="card p-4 text-center"><p class="text-xl font-bold" [style.color]="'var(--text-primary)'">{{ stats().total_sites }}</p><p class="text-[10px]" [style.color]="'var(--text-tertiary)'">Protected Sites</p></div>
            <div class="card p-4 text-center"><p class="text-xl font-bold" [style.color]="'var(--text-primary)'">{{ stats().incidents_30d }}</p><p class="text-[10px]" [style.color]="'var(--text-tertiary)'">Incidents (30d)</p></div>
            <div class="card p-4 text-center"><p class="text-xl font-bold" [style.color]="'var(--text-primary)'">₦{{ stats().outstanding_amount | number:'1.0-0' }}</p><p class="text-[10px]" [style.color]="'var(--text-tertiary)'">Outstanding</p></div>
          </div>

          <!-- Quick Actions -->
          <h3 class="text-xs font-semibold uppercase tracking-wide mb-2" [style.color]="'var(--text-tertiary)'">Quick Actions</h3>
          <div class="grid grid-cols-4 gap-2 mb-4">
            @for (a of quickActions; track a.label) {
              <button (click)="activeView.set(a.view)" class="flex flex-col items-center gap-1.5 p-3 rounded-xl transition-colors hover:bg-[var(--surface-muted)]">
                <div class="h-10 w-10 rounded-xl flex items-center justify-center" [style.background]="a.bg" [style.color]="a.color">
                  <lucide-icon [img]="a.icon" [size]="18" />
                </div>
                <span class="text-[10px] font-medium" [style.color]="'var(--text-secondary)'">{{ a.label }}</span>
              </button>
            }
          </div>

          <!-- Recent Activity -->
          <h3 class="text-xs font-semibold uppercase tracking-wide mb-2" [style.color]="'var(--text-tertiary)'">Guard Activity</h3>
          @if (!activity().length) { <div class="card p-4 text-center"><p class="text-xs" [style.color]="'var(--text-tertiary)'">No recent activity</p></div> }
          @else {
            <div class="space-y-2">
              @for (a of activity(); track a.id) {
                <div class="card p-3">
                  <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                      <div class="h-8 w-8 rounded-full flex items-center justify-center text-[10px] font-bold text-white" [style.background]="a.status === 'clocked_in' ? '#10B981' : '#6B7280'">{{ a.guard_name?.charAt(0) || '?' }}</div>
                      <div><p class="text-xs font-medium" [style.color]="'var(--text-primary)'">{{ a.guard_name }}</p>
                        <p class="text-[10px]" [style.color]="'var(--text-tertiary)'">{{ a.site_name }}</p></div>
                    </div>
                    <span class="badge text-[9px]" [ngClass]="a.status === 'clocked_in' ? 'bg-emerald-50 text-emerald-600' : 'bg-gray-100 text-gray-500'">{{ a.status === 'clocked_in' ? 'On Duty' : 'Off' }}</span>
                  </div>
                </div>
              }
            </div>
          }
        }

        <!-- APPOINTMENTS VIEW -->
        @if (activeView() === 'appointments') {
          <div class="flex items-center justify-between mb-3">
            <h2 class="text-sm font-semibold" [style.color]="'var(--text-primary)'">My Appointments</h2>
            <button (click)="showSchedule.set(true)" class="btn-primary text-xs flex items-center gap-1"><lucide-icon [img]="PlusIcon" [size]="12" /> Schedule</button>
          </div>
          @if (!appointments().length) { <div class="card p-6 text-center"><p class="text-xs" [style.color]="'var(--text-tertiary)'">No upcoming appointments</p></div> }
          @else {
            <div class="space-y-2">
              @for (apt of appointments(); track apt.id) {
                <div class="card p-3">
                  <p class="text-sm font-medium" [style.color]="'var(--text-primary)'">{{ apt.visitor_name || apt.purpose }}</p>
                  <p class="text-xs" [style.color]="'var(--text-tertiary)'">{{ apt.scheduled_date }} · {{ apt.site_name || '' }}</p>
                  <span class="badge text-[9px] mt-1" [ngClass]="apt.status === 'approved' ? 'bg-emerald-50 text-emerald-600' : 'bg-amber-50 text-amber-600'">{{ apt.status }}</span>
                </div>
              }
            </div>
          }

          <!-- Schedule modal -->
          @if (showSchedule()) {
            <div class="fixed inset-0 z-50 flex items-end sm:items-center justify-center">
              <div class="absolute inset-0 bg-black/40" (click)="showSchedule.set(false)"></div>
              <div class="relative w-full max-w-md rounded-t-2xl sm:rounded-2xl p-5" [style.background]="'var(--surface-card)'">
                <div class="flex items-center justify-between mb-3">
                  <h3 class="text-sm font-semibold" [style.color]="'var(--text-primary)'">Schedule Appointment</h3>
                  <button (click)="showSchedule.set(false)"><lucide-icon [img]="XIcon" [size]="18" [style.color]="'var(--text-tertiary)'" /></button>
                </div>
                <div class="space-y-3">
                  <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Purpose *</label>
                    <select [(ngModel)]="aptForm.purpose" class="input-base w-full"><option value="meeting">Meeting</option><option value="delivery">Delivery</option><option value="interview">Interview</option><option value="maintenance">Maintenance</option><option value="other">Other</option></select></div>
                  <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Visitor Name *</label>
                    <input type="text" [(ngModel)]="aptForm.visitor_name" class="input-base w-full" /></div>
                  <div class="grid grid-cols-2 gap-3">
                    <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Date *</label>
                      <input type="date" [(ngModel)]="aptForm.scheduled_date" class="input-base w-full" /></div>
                    <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Time</label>
                      <input type="time" [(ngModel)]="aptForm.scheduled_time" class="input-base w-full" /></div>
                  </div>
                  <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Notes</label>
                    <textarea [(ngModel)]="aptForm.notes" rows="2" class="input-base w-full resize-none"></textarea></div>
                  <button (click)="scheduleAppointment()" class="btn-primary w-full">Schedule</button>
                </div>
              </div>
            </div>
          }
        }

        <!-- DELIVERIES VIEW -->
        @if (activeView() === 'deliveries') {
          <div class="flex items-center justify-between mb-3">
            <h2 class="text-sm font-semibold" [style.color]="'var(--text-primary)'">Deliveries</h2>
            <button (click)="showDelivery.set(true)" class="btn-primary text-xs flex items-center gap-1"><lucide-icon [img]="PlusIcon" [size]="12" /> Schedule</button>
          </div>
          @if (!deliveries().length) { <div class="card p-6 text-center"><p class="text-xs" [style.color]="'var(--text-tertiary)'">No scheduled deliveries</p></div> }
          @else {
            <div class="space-y-2">
              @for (d of deliveries(); track d.id) {
                <div class="card p-3">
                  <p class="text-sm font-medium" [style.color]="'var(--text-primary)'">{{ d.description || d.carrier_name }}</p>
                  <p class="text-xs" [style.color]="'var(--text-tertiary)'">{{ d.scheduled_date }} · {{ d.site_name || '' }}</p>
                  <span class="badge text-[9px] mt-1" [ngClass]="d.status === 'delivered' ? 'bg-emerald-50 text-emerald-600' : 'bg-blue-50 text-blue-600'">{{ d.status || 'pending' }}</span>
                </div>
              }
            </div>
          }

          @if (showDelivery()) {
            <div class="fixed inset-0 z-50 flex items-end sm:items-center justify-center">
              <div class="absolute inset-0 bg-black/40" (click)="showDelivery.set(false)"></div>
              <div class="relative w-full max-w-md rounded-t-2xl sm:rounded-2xl p-5" [style.background]="'var(--surface-card)'">
                <div class="flex items-center justify-between mb-3">
                  <h3 class="text-sm font-semibold" [style.color]="'var(--text-primary)'">Schedule Delivery</h3>
                  <button (click)="showDelivery.set(false)"><lucide-icon [img]="XIcon" [size]="18" [style.color]="'var(--text-tertiary)'" /></button>
                </div>
                <div class="space-y-3">
                  <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Carrier / Company *</label>
                    <input type="text" [(ngModel)]="delForm.carrier_name" class="input-base w-full" /></div>
                  <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Description</label>
                    <input type="text" [(ngModel)]="delForm.description" class="input-base w-full" placeholder="Package details" /></div>
                  <div class="grid grid-cols-2 gap-3">
                    <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Date *</label>
                      <input type="date" [(ngModel)]="delForm.scheduled_date" class="input-base w-full" /></div>
                    <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Time</label>
                      <input type="time" [(ngModel)]="delForm.scheduled_time" class="input-base w-full" /></div>
                  </div>
                  <button (click)="scheduleDelivery()" class="btn-primary w-full">Schedule</button>
                </div>
              </div>
            </div>
          }
        }

        <!-- GUARD TRACKING VIEW -->
        @if (activeView() === 'tracking') {
          <h2 class="text-sm font-semibold mb-3" [style.color]="'var(--text-primary)'">Guard Tracking</h2>
          @for (g of activity(); track g.id) {
            <div class="card p-3 mb-2">
              <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-full flex items-center justify-center text-xs font-bold text-white" [style.background]="g.status === 'clocked_in' ? '#10B981' : '#6B7280'">{{ g.guard_name?.charAt(0) || '?' }}</div>
                <div class="flex-1"><p class="text-sm font-medium" [style.color]="'var(--text-primary)'">{{ g.guard_name }}</p>
                  <p class="text-xs" [style.color]="'var(--text-tertiary)'">{{ g.site_name }} · {{ g.timestamp?.slice(11,16) || '' }}</p></div>
                <span class="badge text-[9px]" [ngClass]="g.status === 'clocked_in' ? 'bg-emerald-50 text-emerald-600' : 'bg-gray-100 text-gray-500'">{{ g.status === 'clocked_in' ? 'On Duty' : 'Off' }}</span>
              </div>
            </div>
          }
        }

        <!-- REPORTS VIEW -->
        @if (activeView() === 'reports') {
          <h2 class="text-sm font-semibold mb-3" [style.color]="'var(--text-primary)'">Reports & Incidents</h2>
          @for (r of reports(); track r.id) {
            <div class="card p-3 mb-2">
              <p class="text-sm font-medium" [style.color]="'var(--text-primary)'">{{ r.title || r.report_type }}</p>
              <p class="text-xs" [style.color]="'var(--text-tertiary)'">{{ r.site_name || '' }} · {{ r.report_date || r.created_at?.slice(0,10) }}</p>
            </div>
          }
          @if (!reports().length) { <div class="card p-6 text-center"><p class="text-xs" [style.color]="'var(--text-tertiary)'">No reports yet</p></div> }
        }
      </main>

      <!-- Bottom Nav -->
      <nav class="fixed bottom-0 left-0 right-0 z-40 border-t" [style.background]="'var(--surface-card)'" [style.borderColor]="'var(--border-default)'" style="padding-bottom:env(safe-area-inset-bottom,0)">
        <div class="flex items-center justify-around h-14">
          @for (n of navItems; track n.view) {
            <button (click)="activeView.set(n.view); loadView()" class="flex flex-col items-center gap-0.5 flex-1 py-1.5"
              [style.color]="activeView() === n.view ? 'var(--color-brand-500)' : 'var(--text-tertiary)'">
              <lucide-icon [img]="n.icon" [size]="20" />
              <span class="text-[10px] font-medium">{{ n.label }}</span>
            </button>
          }
        </div>
      </nav>
    </div>
  `,
})
export class ClientMobileComponent implements OnInit {
  readonly auth = inject(AuthStore);
  private api = inject(ApiService);
  private toast = inject(ToastService);
  readonly HomeIcon = Home; readonly CalendarIcon = CalendarDays; readonly TruckIcon = Truck;
  readonly ShieldIcon = Shield; readonly FileTextIcon = FileText; readonly BellIcon = Bell;
  readonly UserIcon = User; readonly MsgIcon = MessageSquare; readonly ClockIcon = Clock;
  readonly MapPinIcon = MapPin; readonly PlusIcon = Plus; readonly XIcon = X;

  readonly activeView = signal('home');
  readonly stats = signal<any>({ active_guards: 0, total_sites: 0, incidents_30d: 0, outstanding_amount: 0 });
  readonly activity = signal<any[]>([]); readonly appointments = signal<any[]>([]);
  readonly deliveries = signal<any[]>([]); readonly reports = signal<any[]>([]);
  readonly unreadCount = signal(0);
  readonly showSchedule = signal(false); readonly showDelivery = signal(false);

  aptForm: any = { purpose: 'meeting', visitor_name: '', scheduled_date: new Date().toISOString().slice(0, 10), scheduled_time: '09:00', notes: '' };
  delForm: any = { carrier_name: '', description: '', scheduled_date: new Date().toISOString().slice(0, 10), scheduled_time: '' };

  navItems = [
    { label: 'Home', icon: Home, view: 'home' },
    { label: 'Appointments', icon: CalendarDays, view: 'appointments' },
    { label: 'Deliveries', icon: Truck, view: 'deliveries' },
    { label: 'Tracking', icon: Shield, view: 'tracking' },
    { label: 'Reports', icon: FileText, view: 'reports' },
  ];

  quickActions = [
    { label: 'Schedule', icon: CalendarDays, view: 'appointments', bg: '#EFF6FF', color: '#3B82F6' },
    { label: 'Delivery', icon: Truck, view: 'deliveries', bg: '#ECFDF5', color: '#10B981' },
    { label: 'Tracking', icon: Shield, view: 'tracking', bg: '#FEF3C7', color: '#F59E0B' },
    { label: 'Reports', icon: FileText, view: 'reports', bg: '#F3E8FF', color: '#8B5CF6' },
  ];

  greeting() {
    const h = new Date().getHours();
    const name = this.auth.user()?.first_name || '';
    return h < 12 ? `Good morning, ${name}` : h < 17 ? `Good afternoon, ${name}` : `Good evening, ${name}`;
  }

  ngOnInit(): void { this.loadView(); }

  loadView(): void {
    const v = this.activeView();
    if (v === 'home') {
      this.api.get<any>('/client-portal/stats').subscribe({ next: r => { if (r.data) this.stats.set(r.data); } });
      this.api.get<any>('/client-portal/guard-activity').subscribe({ next: r => this.activity.set(r.data?.items || []) });
    } else if (v === 'appointments') {
      this.api.get<any>('/visitors/appointments').subscribe({ next: r => this.appointments.set(r.data?.appointments || r.data || []) });
    } else if (v === 'deliveries') {
      this.api.get<any>('/client-portal/deliveries').subscribe({ next: r => this.deliveries.set(r.data?.deliveries || []), error: () => {} });
    } else if (v === 'tracking') {
      this.api.get<any>('/client-portal/guard-activity').subscribe({ next: r => this.activity.set(r.data?.items || []) });
    } else if (v === 'reports') {
      this.api.get<any>('/client-portal/reports').subscribe({ next: r => this.reports.set(r.data?.reports || r.data || []) });
    }
  }

  scheduleAppointment(): void {
    if (!this.aptForm.visitor_name) { this.toast.warning('Visitor name required'); return; }
    this.api.post('/visitors/appointments', this.aptForm).subscribe({
      next: () => { this.showSchedule.set(false); this.toast.success('Appointment scheduled'); this.loadView(); },
    });
  }

  scheduleDelivery(): void {
    if (!this.delForm.carrier_name) { this.toast.warning('Carrier name required'); return; }
    this.api.post('/client-portal/deliveries', this.delForm).subscribe({
      next: () => { this.showDelivery.set(false); this.toast.success('Delivery scheduled'); this.loadView(); },
      error: () => this.toast.error('Failed to schedule'),
    });
  }
}
