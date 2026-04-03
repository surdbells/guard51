import { Component, inject, signal, OnInit } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { NgClass, DecimalPipe } from '@angular/common';
import { LucideAngularModule, Building2, FileText, Receipt, AlertTriangle, MapPin, Clock, Shield, Users, Calendar, Download, Eye, Send, Plus } from 'lucide-angular';
import { PageHeaderComponent } from '@shared/components/page-header/page-header.component';
import { SearchableSelectComponent, SelectOption } from '@shared/components/searchable-select/searchable-select.component';
import { StatsCardComponent } from '@shared/components/stats-card/stats-card.component';
import { ModalComponent } from '@shared/components/modal/modal.component';
import { EmptyStateComponent } from '@shared/components/empty-state/empty-state.component';
import { LoadingSpinnerComponent } from '@shared/components/loading-spinner/loading-spinner.component';
import { ApiService } from '@core/services/api.service';
import { AuthStore } from '@core/services/auth.store';
import { ToastService } from '@core/services/toast.service';
import { exportToCsv } from '@core/utils/csv-export';

@Component({
  selector: 'g51-client-portal',
  standalone: true,
  imports: [FormsModule, NgClass, DecimalPipe, LucideAngularModule, PageHeaderComponent, StatsCardComponent, ModalComponent, EmptyStateComponent, LoadingSpinnerComponent, SearchableSelectComponent],
  template: `
    <g51-page-header title="Client Portal" [subtitle]="'Welcome, ' + (auth.user()?.first_name || 'Client')" />

    <!-- KPI Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6 stagger-children">
      <g51-stats-card label="Active Guards" [value]="stats().active_guards" [icon]="ShieldIcon" />
      <g51-stats-card label="Sites Covered" [value]="stats().total_sites" [icon]="MapPinIcon" />
      <g51-stats-card label="Incidents (30d)" [value]="stats().incidents_30d" [icon]="AlertTriangleIcon" />
      <g51-stats-card label="Outstanding (₦)" [value]="(stats().outstanding_amount | number:'1.0-0') || '0'" [icon]="ReceiptIcon" />
    </div>

    <!-- Tabs -->
    <div class="flex gap-1 mb-4 overflow-x-auto">
      @for (tab of tabs; track tab) {
        <button (click)="activeTab.set(tab); loadTab()" class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors whitespace-nowrap"
          [ngClass]="activeTab() === tab ? 'bg-[var(--color-brand-500)] text-white' : 'bg-[var(--surface-muted)]'"
          [style.color]="activeTab() !== tab ? 'var(--text-secondary)' : ''">{{ tab }}</button>
      }
    </div>

    @if (tabLoading()) { <g51-loading /> }

    <!-- GUARD ACTIVITY -->
    @if (activeTab() === 'Guard Activity' && !tabLoading()) {
      @if (!guardActivity().length) { <g51-empty-state title="No Activity" message="No guard activity recorded yet." [icon]="ShieldIcon" /> }
      @else {
        <div class="space-y-2">
          @for (g of guardActivity(); track g.id || $index) {
            <div class="card p-4">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-sm font-semibold" [style.color]="'var(--text-primary)'">{{ g.guard_name || 'Guard' }}</p>
                  <p class="text-xs" [style.color]="'var(--text-tertiary)'">{{ g.site_name || '' }} · {{ g.action || g.status || '' }}</p>
                </div>
                <div class="text-right">
                  <span class="badge text-[10px]" [ngClass]="g.status === 'clocked_in' ? 'bg-emerald-50 text-emerald-600' : 'bg-gray-100 text-gray-500'">{{ g.status === 'clocked_in' ? 'On Site' : g.status || '' }}</span>
                  <p class="text-[10px] mt-0.5" [style.color]="'var(--text-tertiary)'">{{ g.timestamp || g.created_at || '' }}</p>
                </div>
              </div>
            </div>
          }
        </div>
      }
    }

    <!-- REPORTS -->
    @if (activeTab() === 'Reports' && !tabLoading()) {
      <div class="flex justify-end mb-3">
        <button (click)="exportReports()" class="btn-secondary text-xs">Export CSV</button>
      </div>
      @if (!reports().length) { <g51-empty-state title="No Reports" message="No reports available yet." [icon]="FileTextIcon" /> }
      @else {
        <div class="space-y-2">
          @for (r of reports(); track r.id) {
            <div class="card p-4 card-hover">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-sm font-semibold" [style.color]="'var(--text-primary)'">{{ r.report_type || 'DAR' }}</p>
                  <p class="text-xs" [style.color]="'var(--text-tertiary)'">{{ r.site_name || '' }} · {{ r.guard_name || '' }} · {{ r.report_date || r.created_at }}</p>
                </div>
                <span class="badge text-[10px]" [ngClass]="r.status === 'approved' ? 'bg-emerald-50 text-emerald-600' : 'bg-blue-50 text-blue-600'">{{ r.status }}</span>
              </div>
            </div>
          }
        </div>
      }
    }

    <!-- INVOICES -->
    @if (activeTab() === 'Invoices' && !tabLoading()) {
      @if (!invoices().length) { <g51-empty-state title="No Invoices" message="No invoices yet." [icon]="ReceiptIcon" /> }
      @else {
        <div class="space-y-2">
          @for (i of invoices(); track i.id) {
            <div class="card p-4 card-hover">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-sm font-semibold" [style.color]="'var(--text-primary)'">{{ i.invoice_number }}</p>
                  <p class="text-xs" [style.color]="'var(--text-tertiary)'">Issued: {{ i.issue_date }} · Due: {{ i.due_date }}</p>
                </div>
                <div class="text-right">
                  <p class="text-sm font-bold" [style.color]="'var(--text-primary)'">₦{{ i.total_amount | number:'1.0-0' }}</p>
                  <span class="badge text-[10px]" [ngClass]="i.status === 'paid' ? 'bg-emerald-50 text-emerald-600' : i.status === 'overdue' ? 'bg-red-50 text-red-600' : 'bg-amber-50 text-amber-600'">{{ i.status }}</span>
                </div>
              </div>
              @if (i.status !== 'paid') {
                <div class="flex gap-2 mt-2">
                  <a [href]="apiUrl + '/invoices/' + i.id + '/pdf'" target="_blank" class="btn-secondary text-[10px] py-1 px-2 flex items-center gap-1"><lucide-icon [img]="DownloadIcon" [size]="10" /> PDF</a>
                </div>
              }
            </div>
          }
        </div>
      }
    }

    <!-- INCIDENTS -->
    @if (activeTab() === 'Incidents' && !tabLoading()) {
      @if (!incidents().length) { <g51-empty-state title="No Incidents" message="No incidents reported at your sites." [icon]="AlertTriangleIcon" /> }
      @else {
        <div class="space-y-2">
          @for (inc of incidents(); track inc.id) {
            <div class="card p-4">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-sm font-semibold" [style.color]="'var(--text-primary)'">{{ inc.title }}</p>
                  <p class="text-xs" [style.color]="'var(--text-tertiary)'">{{ inc.incident_type }} · {{ inc.severity }} · {{ inc.site_name || '' }}</p>
                  <p class="text-xs" [style.color]="'var(--text-tertiary)'">{{ inc.occurred_at || inc.created_at }}</p>
                </div>
                <span class="badge text-[10px]" [ngClass]="inc.status === 'resolved' ? 'bg-emerald-50 text-emerald-600' : inc.status === 'escalated' ? 'bg-red-50 text-red-600' : 'bg-amber-50 text-amber-600'">{{ inc.status }}</span>
              </div>
            </div>
          }
        </div>
      }
    }

    <!-- VISITORS -->
    @if (activeTab() === 'Visitors' && !tabLoading()) {
      <div class="flex justify-end mb-3">
        <button (click)="showVisitorModal.set(true)" class="btn-primary text-xs flex items-center gap-1"><lucide-icon [img]="PlusIcon" [size]="12" /> Schedule Visit</button>
      </div>
      @if (!visitors().length) { <g51-empty-state title="No Visitors" message="Schedule your first visitor appointment." [icon]="CalendarIcon" /> }
      @else {
        <div class="space-y-2">
          @for (v of visitors(); track v.id) {
            <div class="card p-4">
              <div class="flex items-center justify-between">
                <div>
                  <div class="flex items-center gap-2 mb-1">
                    <p class="text-sm font-semibold" [style.color]="'var(--text-primary)'">{{ v.visitor_name }}</p>
                    <span class="badge text-[10px] font-mono bg-[var(--brand-50)]" [style.color]="'var(--brand-700)'">{{ v.access_code }}</span>
                  </div>
                  <p class="text-xs" [style.color]="'var(--text-tertiary)'">{{ v.purpose }} · {{ v.scheduled_date }} {{ v.scheduled_time || '' }}</p>
                </div>
                <span class="badge text-[10px]" [ngClass]="v.status === 'checked_in' ? 'bg-emerald-50 text-emerald-600' : v.status === 'completed' ? 'bg-blue-50 text-blue-600' : 'bg-amber-50 text-amber-600'">{{ v.status }}</span>
              </div>
            </div>
          }
        </div>
      }
      <g51-modal [open]="showVisitorModal()" title="Schedule Visitor" maxWidth="500px" (closed)="showVisitorModal.set(false)">
        <div class="space-y-3">
          <div class="grid grid-cols-2 gap-3">
            <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Visitor Name *</label><input type="text" [(ngModel)]="visitorForm.visitor_name" class="input-base w-full" /></div>
            <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Company</label><input type="text" [(ngModel)]="visitorForm.visitor_company" class="input-base w-full" /></div>
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Phone</label><input type="tel" [(ngModel)]="visitorForm.visitor_phone" class="input-base w-full" /></div>
            <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Email</label><input type="email" [(ngModel)]="visitorForm.visitor_email" class="input-base w-full" /></div>
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Date *</label><input type="date" [(ngModel)]="visitorForm.scheduled_date" class="input-base w-full" /></div>
            <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Purpose *</label>
              <select [(ngModel)]="visitorForm.purpose" class="input-base w-full"><option value="meeting">Meeting</option><option value="delivery">Delivery</option><option value="interview">Interview</option><option value="other">Other</option></select></div>
          </div>
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Site *</label>
            <g51-searchable-select [(ngModel)]="visitorForm.site_id" [options]="siteOptions()" placeholder="Select site" /></div>
          <div class="flex gap-3">
            <label class="flex items-center gap-1 text-xs"><input type="checkbox" [(ngModel)]="visitorForm.notify_email" /> Email</label>
            <label class="flex items-center gap-1 text-xs"><input type="checkbox" [(ngModel)]="visitorForm.notify_sms" /> SMS</label>
          </div>
        </div>
        <div modal-footer><button (click)="showVisitorModal.set(false)" class="btn-secondary">Cancel</button>
          <button (click)="scheduleVisitor()" class="btn-primary"><lucide-icon [img]="SendIcon" [size]="12" /> Send Code</button></div>
      </g51-modal>
    }

    <!-- EMPLOYEES -->
    @if (activeTab() === 'Employees' && !tabLoading()) {
      <div class="flex justify-end mb-3">
        <button (click)="showEmployeeModal.set(true)" class="btn-primary text-xs flex items-center gap-1"><lucide-icon [img]="PlusIcon" [size]="12" /> Add Employee</button>
      </div>
      @if (!employees().length) { <g51-empty-state title="No Employees" message="Add your team members to give them app access." [icon]="UsersIcon" /> }
      @else {
        <div class="card overflow-hidden">
          <table class="w-full text-xs">
            <thead><tr [style.background]="'var(--surface-muted)'">
              <th class="text-left py-2.5 px-4 font-semibold" [style.color]="'var(--text-secondary)'">Name</th>
              <th class="text-left py-2.5 px-4 font-semibold" [style.color]="'var(--text-secondary)'">Email</th>
              <th class="text-left py-2.5 px-4 font-semibold" [style.color]="'var(--text-secondary)'">Permissions</th>
              <th class="text-left py-2.5 px-4 font-semibold" [style.color]="'var(--text-secondary)'">Status</th>
            </tr></thead>
            <tbody>
              @for (emp of employees(); track emp.id) {
                <tr class="border-t" [style.borderColor]="'var(--border-default)'">
                  <td class="py-2.5 px-4 font-medium" [style.color]="'var(--text-primary)'">{{ emp.first_name }} {{ emp.last_name }}</td>
                  <td class="py-2.5 px-4" [style.color]="'var(--text-secondary)'">{{ emp.email }}</td>
                  <td class="py-2.5 px-4">
                    <div class="flex flex-wrap gap-1">
                      @if (emp.can_view_reports) { <span class="badge text-[9px] bg-blue-50 text-blue-600">Reports</span> }
                      @if (emp.can_view_tracking) { <span class="badge text-[9px] bg-emerald-50 text-emerald-600">Tracking</span> }
                      @if (emp.can_view_invoices) { <span class="badge text-[9px] bg-purple-50 text-purple-600">Invoices</span> }
                      @if (emp.can_view_incidents) { <span class="badge text-[9px] bg-amber-50 text-amber-600">Incidents</span> }
                      @if (emp.can_message) { <span class="badge text-[9px] bg-gray-100 text-gray-500">Chat</span> }
                    </div>
                  </td>
                  <td class="py-2.5 px-4"><span class="badge text-[10px]" [ngClass]="emp.is_active ? 'bg-emerald-50 text-emerald-600' : 'bg-gray-100 text-gray-500'">{{ emp.is_active ? 'Active' : 'Inactive' }}</span></td>
                </tr>
              }
            </tbody>
          </table>
        </div>
      }
      <g51-modal [open]="showEmployeeModal()" title="Add Team Member" maxWidth="500px" (closed)="showEmployeeModal.set(false)">
        <div class="space-y-3">
          <div class="grid grid-cols-2 gap-3">
            <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">First Name *</label><input type="text" [(ngModel)]="empForm.first_name" class="input-base w-full" /></div>
            <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Last Name</label><input type="text" [(ngModel)]="empForm.last_name" class="input-base w-full" /></div>
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Email *</label><input type="email" [(ngModel)]="empForm.email" class="input-base w-full" /></div>
            <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Phone</label><input type="tel" [(ngModel)]="empForm.phone" class="input-base w-full" /></div>
          </div>
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Temporary Password</label><input type="text" [(ngModel)]="empForm.password" class="input-base w-full" placeholder="Leave blank to auto-generate" /></div>
          <div>
            <label class="block text-xs font-medium mb-2" [style.color]="'var(--text-secondary)'">App Permissions</label>
            <div class="grid grid-cols-2 gap-1">
              <label class="flex items-center gap-1.5 text-xs"><input type="checkbox" [(ngModel)]="empForm.can_view_reports" class="rounded" /> View Reports</label>
              <label class="flex items-center gap-1.5 text-xs"><input type="checkbox" [(ngModel)]="empForm.can_view_tracking" class="rounded" /> Live Tracking</label>
              <label class="flex items-center gap-1.5 text-xs"><input type="checkbox" [(ngModel)]="empForm.can_view_invoices" class="rounded" /> View Invoices</label>
              <label class="flex items-center gap-1.5 text-xs"><input type="checkbox" [(ngModel)]="empForm.can_view_incidents" class="rounded" /> View Incidents</label>
              <label class="flex items-center gap-1.5 text-xs"><input type="checkbox" [(ngModel)]="empForm.can_message" class="rounded" /> Send Messages</label>
            </div>
          </div>
        </div>
        <div modal-footer><button (click)="showEmployeeModal.set(false)" class="btn-secondary">Cancel</button>
          <button (click)="addEmployee()" class="btn-primary">Add Employee</button></div>
      </g51-modal>
    }

    <!-- ATTENDANCE -->
    @if (activeTab() === 'Attendance' && !tabLoading()) {
      <div class="flex justify-end mb-3">
        <button (click)="exportAttendance()" class="btn-secondary text-xs">Export CSV</button>
      </div>
      @if (!attendance().length) { <g51-empty-state title="No Records" message="No attendance data." [icon]="ClockIcon" /> }
      @else {
        <div class="space-y-2">
          @for (a of attendance(); track a.id || $index) {
            <div class="card p-4">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-sm font-semibold" [style.color]="'var(--text-primary)'">{{ a.guard_name || 'Guard' }}</p>
                  <p class="text-xs" [style.color]="'var(--text-tertiary)'">{{ a.site_name || '' }} · In: {{ a.clock_in_time || '-' }} · Out: {{ a.clock_out_time || '-' }}</p>
                </div>
                <p class="text-sm font-bold" [style.color]="'var(--text-primary)'">{{ a.total_hours || '-' }}h</p>
              </div>
            </div>
          }
        </div>
      }
    }
  `,
})
export class ClientPortalComponent implements OnInit {
  readonly auth = inject(AuthStore);
  private api = inject(ApiService); private toast = inject(ToastService);
  readonly ShieldIcon = Shield; readonly MapPinIcon = MapPin; readonly FileTextIcon = FileText;
  readonly ReceiptIcon = Receipt; readonly AlertTriangleIcon = AlertTriangle; readonly ClockIcon = Clock;
  readonly CalendarIcon = Calendar; readonly DownloadIcon = Download; readonly PlusIcon = Plus; readonly SendIcon = Send;

  readonly tabs = ['Guard Activity', 'Reports', 'Invoices', 'Incidents', 'Visitors', 'Employees', 'Attendance'];
  readonly activeTab = signal('Guard Activity'); readonly tabLoading = signal(true);
  readonly stats = signal<any>({ active_guards: 0, total_sites: 0, incidents_30d: 0, outstanding_amount: 0 });
  readonly guardActivity = signal<any[]>([]); readonly reports = signal<any[]>([]); readonly invoices = signal<any[]>([]);
  readonly incidents = signal<any[]>([]); readonly visitors = signal<any[]>([]); readonly attendance = signal<any[]>([]);
  readonly employees = signal<any[]>([]);
  readonly sites = signal<any[]>([]); readonly siteOptions = signal<any[]>([]); readonly showVisitorModal = signal(false); readonly showEmployeeModal = signal(false);
  visitorForm: any = { visitor_name: '', visitor_company: '', visitor_phone: '', visitor_email: '', scheduled_date: new Date().toISOString().slice(0, 10), purpose: 'meeting', site_id: '', notify_email: true, notify_sms: false };
  empForm: any = { first_name: '', last_name: '', email: '', phone: '', password: '', can_view_reports: true, can_view_tracking: true, can_view_invoices: false, can_view_incidents: true, can_message: true };
  readonly UsersIcon = Users;
  apiUrl = '';

  ngOnInit(): void {
    this.apiUrl = 'https://api.guard51.com/api/v1';
    this.api.get<any>('/client-portal/stats').subscribe({ next: r => { if (r.data) this.stats.set(r.data); }, error: () => {} });
    this.api.get<any>('/client-portal/sites').subscribe({ next: (r: any) => { const ss = r.data?.sites || r.data || []; this.sites.set(ss); this.siteOptions.set(ss.map((x: any) => ({ value: x.id, label: x.name }))); }, error: () => {} });
    this.loadTab();
  }

  loadTab(): void {
    this.tabLoading.set(true);
    const t = this.activeTab();
    const map: Record<string, string> = { 'Guard Activity': '/client-portal/guard-activity', 'Reports': '/client-portal/reports', 'Invoices': '/client-portal/invoices', 'Incidents': '/client-portal/incidents', 'Visitors': '/visitors/appointments', 'Employees': '/client-portal/employees', 'Attendance': '/client-portal/attendance' };
    this.api.get<any>(map[t] || map['Guard Activity']).subscribe({
      next: r => {
        const d = r.data?.items || r.data?.employees || r.data?.[t.toLowerCase().replace(/ /g, '_')] || r.data?.appointments || r.data || [];
        if (t === 'Guard Activity') this.guardActivity.set(d);
        else if (t === 'Reports') this.reports.set(d);
        else if (t === 'Invoices') this.invoices.set(d);
        else if (t === 'Incidents') this.incidents.set(d);
        else if (t === 'Visitors') this.visitors.set(d);
        else if (t === 'Employees') this.employees.set(d);
        else this.attendance.set(d);
        this.tabLoading.set(false);
      },
      error: () => this.tabLoading.set(false),
    });
  }

  addEmployee(): void {
    if (!this.empForm.email || !this.empForm.first_name) { this.toast.warning('Name and email required'); return; }
    this.api.post('/client-portal/employees', this.empForm).subscribe({
      next: () => {
        this.showEmployeeModal.set(false);
        this.toast.success('Employee added');
        this.empForm = { first_name: '', last_name: '', email: '', phone: '', password: '', can_view_reports: true, can_view_tracking: true, can_view_invoices: false, can_view_incidents: true, can_message: true };
        this.loadTab();
      },
    });
  }
  scheduleVisitor(): void {
    const u = this.auth.user();
    const body = { ...this.visitorForm, host_name: `${u?.first_name || ''} ${u?.last_name || ''}`.trim(), host_email: u?.email || '', host_phone: u?.phone || '' };
    this.api.post('/visitors/appointments', body).subscribe({
      next: (r: any) => { this.showVisitorModal.set(false); this.toast.success('Visit scheduled. Code: ' + (r.data?.access_code || '')); this.loadTab(); },
    });
  }

  exportReports(): void { exportToCsv('client-reports', this.reports(), [{ key: 'report_type', label: 'Type' }, { key: 'site_name', label: 'Site' }, { key: 'guard_name', label: 'Guard' }, { key: 'report_date', label: 'Date' }, { key: 'status', label: 'Status' }]); }
  exportAttendance(): void { exportToCsv('client-attendance', this.attendance(), [{ key: 'guard_name', label: 'Guard' }, { key: 'site_name', label: 'Site' }, { key: 'clock_in_time', label: 'In' }, { key: 'clock_out_time', label: 'Out' }, { key: 'total_hours', label: 'Hours' }]); }
}
