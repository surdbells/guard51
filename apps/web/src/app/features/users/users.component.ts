import { Component, inject, signal, OnInit } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { NgClass } from '@angular/common';
import { LucideAngularModule, Users, Plus, Shield, Key, Trash2, Mail, Check, X, UserCheck } from 'lucide-angular';
import { PageHeaderComponent } from '@shared/components/page-header/page-header.component';
import { ModalComponent } from '@shared/components/modal/modal.component';
import { EmptyStateComponent } from '@shared/components/empty-state/empty-state.component';
import { LoadingSpinnerComponent } from '@shared/components/loading-spinner/loading-spinner.component';
import { ApiService } from '@core/services/api.service';
import { ToastService } from '@core/services/toast.service';

@Component({
  selector: 'g51-users',
  standalone: true,
  imports: [FormsModule, NgClass, LucideAngularModule, PageHeaderComponent, ModalComponent, EmptyStateComponent, LoadingSpinnerComponent],
  template: `
    <g51-page-header title="Team Management" subtitle="Manage users, roles, and granular module permissions">
      <button class="btn-primary flex items-center gap-2" (click)="showInvite.set(true)"><lucide-icon [img]="PlusIcon" [size]="16" /> Invite User</button>
    </g51-page-header>

    <div class="flex gap-1 mb-4">
      @for (tab of ['Active Users', 'Roles & Permissions']; track tab) {
        <button (click)="activeTab.set(tab)" class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors"
          [ngClass]="activeTab() === tab ? 'bg-[var(--color-brand-500)] text-white' : 'bg-[var(--surface-muted)]'"
          [style.color]="activeTab() !== tab ? 'var(--text-secondary)' : ''">{{ tab }}</button>
      }
    </div>

    @if (loading()) { <g51-loading /> }

    @if (activeTab() === 'Active Users') {
      @if (!users().length) { <g51-empty-state title="No Team Members" message="Invite your first team member." [icon]="UsersIcon" /> }
      @else {
        <div class="space-y-2">
          @for (user of users(); track user.id) {
            <div class="card p-4 card-hover">
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                  <div class="h-10 w-10 rounded-full flex items-center justify-center text-sm font-bold text-white" [style.background]="roleColor(user.role)">{{ user.first_name?.charAt(0) }}{{ user.last_name?.charAt(0) }}</div>
                  <div>
                    <p class="text-sm font-semibold" [style.color]="'var(--text-primary)'">{{ user.first_name }} {{ user.last_name }}</p>
                    <p class="text-xs" [style.color]="'var(--text-tertiary)'">{{ user.email }} · Joined {{ user.created_at }}</p>
                  </div>
                </div>
                <div class="flex items-center gap-2">
                  <select [(ngModel)]="user.role" (ngModelChange)="changeRole(user.id, $event)" class="input-base text-xs py-1 px-2">
                    <option value="company_admin">Admin</option><option value="supervisor">Supervisor</option>
                    <option value="guard">Guard</option><option value="dispatcher">Dispatcher</option>
                    <option value="client">Client</option>
                  </select>
                  <span class="badge text-[10px]" [ngClass]="user.is_active ? 'bg-emerald-50 text-emerald-600' : 'bg-red-50 text-red-600'">{{ user.is_active ? 'Active' : 'Inactive' }}</span>
                  <button (click)="showPermissions(user)" class="btn-secondary text-xs py-1 px-2 flex items-center gap-1" title="Module Permissions">
                    <lucide-icon [img]="KeyIcon" [size]="12" /> Permissions</button>
                </div>
              </div>
            </div>
          }
        </div>
      }
    }

    @if (activeTab() === 'Roles & Permissions') {
      <div class="card p-6">
        <h3 class="text-sm font-semibold mb-4" [style.color]="'var(--text-primary)'">Role Definitions & Default Permissions</h3>
        <p class="text-xs mb-4" [style.color]="'var(--text-tertiary)'">Each role has default access. Use per-user permissions (above) to override.</p>
        <div class="space-y-3">
          @for (role of roleDefinitions; track role.value) {
            <div class="p-4 rounded-lg" [style.background]="'var(--surface-muted)'">
              <div class="flex items-center gap-3 mb-2">
                <div class="h-8 w-8 rounded-full flex items-center justify-center text-xs font-bold text-white" [style.background]="role.color">{{ role.abbr }}</div>
                <div>
                  <p class="text-sm font-semibold" [style.color]="'var(--text-primary)'">{{ role.label }}</p>
                  <p class="text-xs" [style.color]="'var(--text-tertiary)'">{{ role.description }}</p>
                </div>
              </div>
              <div class="flex flex-wrap gap-1 mt-2">
                @for (perm of role.defaultPermissions; track perm) {
                  <span class="text-[9px] px-1.5 py-0.5 rounded" [style.background]="'var(--surface-card)'" [style.color]="'var(--text-secondary)'">{{ perm }}</span>
                }
              </div>
            </div>
          }
        </div>
      </div>
    }

    <!-- Permissions Modal -->
    <g51-modal [open]="showPermModal()" [title]="'Module Permissions: ' + (selectedUser()?.first_name || '') + ' ' + (selectedUser()?.last_name || '')" maxWidth="720px" (closed)="showPermModal.set(false)">
      <div>
        <p class="text-xs mb-3" [style.color]="'var(--text-secondary)'">Set granular access per module. Check/uncheck permissions as needed.</p>
        <div class="overflow-x-auto">
          <table class="w-full text-xs">
            <thead>
              <tr [style.borderBottom]="'1px solid var(--border-default)'">
                <th class="text-left py-2 px-1 font-semibold" [style.color]="'var(--text-primary)'">Module</th>
                @for (p of permTypes; track p) { <th class="text-center py-2 px-1 font-semibold" [style.color]="'var(--text-primary)'">{{ p }}</th> }
                <th class="text-center py-2 px-1 font-semibold" [style.color]="'var(--text-primary)'">All</th>
              </tr>
            </thead>
            <tbody>
              @for (mod of modules(); track mod) {
                <tr [style.borderBottom]="'1px solid var(--border-default)'">
                  <td class="py-2 px-1 font-medium capitalize" [style.color]="'var(--text-primary)'">{{ mod.replace('_', ' ') }}</td>
                  @for (p of permTypes; track p) {
                    <td class="text-center py-2 px-1">
                      <input type="checkbox" class="rounded h-3.5 w-3.5"
                        [checked]="getPermValue(mod, p)"
                        (change)="togglePerm(mod, p, $event)" />
                    </td>
                  }
                  <td class="text-center py-2 px-1">
                    <button (click)="grantAll(mod)" class="text-[9px] text-blue-500 hover:underline">Grant All</button>
                  </td>
                </tr>
              }
            </tbody>
          </table>
        </div>
      </div>
      <div modal-footer><button (click)="showPermModal.set(false)" class="btn-primary">Done</button></div>
    </g51-modal>

    <!-- Invite Modal -->
    <g51-modal [open]="showInvite()" title="Invite Team Member" maxWidth="480px" (closed)="showInvite.set(false)">
      <div class="space-y-3">
        <div class="grid grid-cols-2 gap-3">
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">First Name</label>
            <input type="text" [(ngModel)]="inviteForm.first_name" class="input-base w-full" /></div>
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Last Name</label>
            <input type="text" [(ngModel)]="inviteForm.last_name" class="input-base w-full" /></div>
        </div>
        <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Email *</label>
          <input type="email" [(ngModel)]="inviteForm.email" class="input-base w-full" required />
          @if (inviteSubmitted && !inviteForm.email) { <p class="text-[10px] text-red-500 mt-0.5">Email is required</p> }
        </div>
        <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Role *</label>
          <select [(ngModel)]="inviteForm.role" class="input-base w-full">
            <option value="guard">Guard</option><option value="supervisor">Supervisor</option>
            <option value="dispatcher">Dispatcher</option><option value="company_admin">Admin</option>
          </select></div>
        <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Personal Message (optional)</label>
          <textarea [(ngModel)]="inviteForm.personal_message" rows="2" class="input-base w-full resize-none" placeholder="Welcome message..."></textarea></div>
      </div>
      <div modal-footer><button (click)="showInvite.set(false)" class="btn-secondary">Cancel</button>
        <button (click)="onInvite()" class="btn-primary flex items-center gap-2"><lucide-icon [img]="MailIcon" [size]="14" /> Send Invite</button></div>
    </g51-modal>
  `,
})
export class UsersComponent implements OnInit {
  private api = inject(ApiService); private toast = inject(ToastService);
  readonly UsersIcon = Users; readonly PlusIcon = Plus; readonly ShieldIcon = Shield;
  readonly KeyIcon = Key; readonly MailIcon = Mail; readonly CheckIcon = Check;

  readonly users = signal<any[]>([]); readonly modules = signal<string[]>([]);
  readonly loading = signal(true); readonly showInvite = signal(false);
  readonly showPermModal = signal(false); readonly selectedUser = signal<any>(null);
  readonly activeTab = signal('Active Users');
  readonly userPerms = signal<Record<string, any>>({});
  inviteForm = { email: '', role: 'guard', first_name: '', last_name: '', personal_message: '' };
  inviteSubmitted = false;
  permTypes = ['View', 'Create', 'Edit', 'Delete', 'Export', 'Approve'];

  roleDefinitions = [
    { value: 'company_admin', label: 'Company Admin', abbr: 'CA', color: '#1B3A5C', description: 'Full access to all company data and settings. Can manage users, billing, and integrations.',
      defaultPermissions: ['All Modules: Full Access', 'User Management', 'Billing', 'Settings'] },
    { value: 'supervisor', label: 'Supervisor', abbr: 'SV', color: '#2563EB', description: 'Manages guards at assigned sites. Can approve reports, manage shifts, and view attendance.',
      defaultPermissions: ['Guards: View/Edit', 'Sites: View', 'Scheduling: Full', 'Reports: View/Approve', 'Attendance: View', 'Tours: View', 'Incidents: Full'] },
    { value: 'guard', label: 'Guard', abbr: 'GD', color: '#10B981', description: 'Field security personnel. Can clock in/out, submit reports, scan tour checkpoints, and use messenger.',
      defaultPermissions: ['Clock In/Out', 'Reports: Create', 'Tours: Scan', 'Incidents: Create', 'Messenger', 'Passdowns: View/Create'] },
    { value: 'dispatcher', label: 'Dispatcher', abbr: 'DS', color: '#F59E0B', description: 'Dispatch console operator. Can manage calls, assign guards, and track responses.',
      defaultPermissions: ['Dispatch: Full', 'Guards: View', 'Sites: View', 'Tracking: View', 'Panic Alerts: View/Respond'] },
    { value: 'client', label: 'Client', abbr: 'CL', color: '#8B5CF6', description: 'Security service client. Can view reports, invoices, attendance, and tracking for their sites.',
      defaultPermissions: ['Client Portal: View Reports', 'Client Portal: View Invoices', 'Client Portal: View Attendance', 'Client Portal: View Tracking'] },
  ];

  ngOnInit(): void {
    this.api.get<any>('/users').subscribe({
      next: res => { this.users.set(res.data?.users || res.data || []); this.loading.set(false); },
      error: () => this.loading.set(false),
    });
    this.api.get<any>('/users/modules').subscribe({ next: res => this.modules.set(res.data?.modules || []) });
  }

  roleColor(role: string): string {
    return this.roleDefinitions.find(r => r.value === role)?.color || '#94A3B8';
  }

  changeRole(userId: string, role: string): void {
    this.api.put('/users/' + userId + '/role', { role }).subscribe({ next: () => this.toast.success('Role updated') });
  }

  showPermissions(user: any): void {
    this.selectedUser.set(user);
    this.userPerms.set({});
    this.api.get<any>('/users/' + user.id + '/permissions').subscribe({
      next: res => {
        const perms: Record<string, any> = {};
        (res.data?.permissions || []).forEach((p: any) => { perms[p.module_key] = p; });
        this.userPerms.set(perms);
        this.showPermModal.set(true);
      },
    });
  }

  getPermValue(mod: string, perm: string): boolean {
    const p = this.userPerms()[mod];
    if (!p) return false;
    return p['can_' + perm.toLowerCase()] || false;
  }

  togglePerm(mod: string, perm: string, event: any): void {
    const body: any = { module_key: mod, ['can_' + perm.toLowerCase()]: event.target.checked };
    this.api.post('/users/' + this.selectedUser().id + '/permissions', body).subscribe();
    // Update local state
    const perms = { ...this.userPerms() };
    if (!perms[mod]) perms[mod] = { module_key: mod };
    perms[mod]['can_' + perm.toLowerCase()] = event.target.checked;
    this.userPerms.set(perms);
  }

  grantAll(mod: string): void {
    const body: any = { module_key: mod, grant_all: true };
    this.api.post('/users/' + this.selectedUser().id + '/permissions', body).subscribe({ next: () => this.toast.success('All permissions granted for ' + mod) });
    const perms = { ...this.userPerms() };
    perms[mod] = { module_key: mod, can_view: true, can_create: true, can_edit: true, can_delete: true, can_export: true, can_approve: true };
    this.userPerms.set(perms);
  }

  onInvite(): void {
    this.inviteSubmitted = true;
    if (!this.inviteForm.email) { this.toast.warning('Email is required'); return; }
    this.api.post('/onboarding/invitations', this.inviteForm).subscribe({
      next: () => { this.showInvite.set(false); this.toast.success('Invitation sent'); this.inviteSubmitted = false; this.ngOnInit(); },
    });
  }
}
