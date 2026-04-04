import { Component, inject, signal, OnInit } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { NgClass } from '@angular/common';
import { LucideAngularModule, Users, Plus, Shield, Key, Trash2, Mail, Check, X, UserCheck, Edit, Save } from 'lucide-angular';
import { PageHeaderComponent } from '@shared/components/page-header/page-header.component';
import { ModalComponent } from '@shared/components/modal/modal.component';
import { EmptyStateComponent } from '@shared/components/empty-state/empty-state.component';
import { LoadingSpinnerComponent } from '@shared/components/loading-spinner/loading-spinner.component';
import { ApiService } from '@core/services/api.service';
import { ToastService } from '@core/services/toast.service';
import { ConfirmService } from '@core/services/confirm.service';

@Component({
  selector: 'g51-users',
  standalone: true,
  imports: [FormsModule, NgClass, LucideAngularModule, PageHeaderComponent, ModalComponent, EmptyStateComponent, LoadingSpinnerComponent],
  template: `
    <g51-page-header title="Team Management" subtitle="Manage users and role-based access control">
      <button class="btn-primary flex items-center gap-2" (click)="showInvite.set(true)"><lucide-icon [img]="PlusIcon" [size]="16" /> Invite User</button>
    </g51-page-header>

    <div class="tab-pills">
      @for (tab of ['Active Users', 'Roles & Permissions']; track tab) {
        <button (click)="activeTab.set(tab); loadTab()" class="tab-pill" [ngClass]="activeTab() === tab ? 'active' : ''">{{ tab }}</button>
      }
    </div>

    <!-- ACTIVE USERS TAB -->
    @if (activeTab() === 'Active Users') {
      @if (loading()) { <g51-loading /> }
      @else if (!users().length) { <g51-empty-state title="No Users" message="Invite your first team member." [icon]="UsersIcon" /> }
      @else {
        <div class="card overflow-hidden">
          <table class="w-full text-xs">
            <thead><tr [style.background]="'var(--surface-muted)'">
              <th class="text-left py-2.5 px-4 font-semibold" [style.color]="'var(--text-secondary)'">User</th>
              <th class="text-left py-2.5 px-4 font-semibold" [style.color]="'var(--text-secondary)'">Role</th>
              <th class="text-left py-2.5 px-4 font-semibold" [style.color]="'var(--text-secondary)'">Status</th>
              <th class="text-left py-2.5 px-4 font-semibold" [style.color]="'var(--text-secondary)'">Last Login</th>
              <th class="text-center py-2.5 px-4 font-semibold" [style.color]="'var(--text-secondary)'">Actions</th>
            </tr></thead>
            <tbody>
              @for (user of users(); track user.id) {
                <tr class="border-t hover:bg-[var(--surface-hover)]" [style.borderColor]="'var(--border-default)'">
                  <td class="py-2.5 px-4">
                    <div class="flex items-center gap-2">
                      <div class="h-8 w-8 rounded-full flex items-center justify-center text-xs font-bold text-white" [style.background]="roleColor(user.role)">{{ user.first_name?.charAt(0) }}{{ user.last_name?.charAt(0) }}</div>
                      <div><p class="font-medium" [style.color]="'var(--text-primary)'">{{ user.first_name }} {{ user.last_name }}</p>
                        <p class="text-[10px]" [style.color]="'var(--text-tertiary)'">{{ user.email }}</p></div>
                    </div>
                  </td>
                  <td class="py-2.5 px-4">
                    <select [(ngModel)]="user.role" (ngModelChange)="changeRole(user)" class="input-base text-xs py-1 px-2">
                      @for (r of roles(); track r.id || r.value) { <option [value]="r.value || r.name">{{ r.label || r.name }}</option> }
                    </select>
                  </td>
                  <td class="py-2.5 px-4">
                    <span class="badge text-[10px]" [ngClass]="user.is_active ? 'bg-emerald-50 text-emerald-600' : 'bg-gray-100 text-gray-500'">{{ user.is_active ? 'Active' : 'Inactive' }}</span>
                  </td>
                  <td class="py-2.5 px-4" [style.color]="'var(--text-tertiary)'">{{ user.last_login_at || '—' }}</td>
                  <td class="py-2.5 px-4 text-center">
                    <div class="flex justify-center gap-1">
                      <button (click)="resendInvite(user)" class="p-1 rounded hover:bg-[var(--surface-muted)]" title="Resend invite"><lucide-icon [img]="MailIcon" [size]="14" [style.color]="'var(--text-tertiary)'" /></button>
                      <button (click)="removeUser(user)" class="p-1 rounded hover:bg-red-50" title="Remove"><lucide-icon [img]="TrashIcon" [size]="14" class="text-red-400" /></button>
                    </div>
                  </td>
                </tr>
              }
            </tbody>
          </table>
        </div>
      }
    }

    <!-- ROLES & PERMISSIONS TAB -->
    @if (activeTab() === 'Roles & Permissions') {
      <div class="flex justify-end mb-3">
        <button (click)="openRoleCreate()" class="btn-primary text-xs flex items-center gap-1"><lucide-icon [img]="PlusIcon" [size]="12" /> New Custom Role</button>
      </div>

      <div class="space-y-3">
        @for (role of roles(); track role.id || role.value) {
          <div class="card p-4">
            <div class="flex items-center justify-between mb-3">
              <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-full flex items-center justify-center text-xs font-bold text-white" [style.background]="role.color || '#6B7280'">{{ (role.label || role.name)?.slice(0, 2)?.toUpperCase() }}</div>
                <div>
                  <p class="text-sm font-semibold" [style.color]="'var(--text-primary)'">{{ role.label || role.name }}</p>
                  <p class="text-xs" [style.color]="'var(--text-tertiary)'">{{ role.description || (role.is_system ? 'System role' : 'Custom role') }} · {{ role.user_count || 0 }} users</p>
                </div>
              </div>
              <div class="flex items-center gap-2">
                <span class="badge text-[10px]" [ngClass]="role.is_system ? 'bg-blue-50 text-blue-600' : 'bg-purple-50 text-purple-600'">{{ role.is_system ? 'System' : 'Custom' }}</span>
                @if (!role.is_system) {
                  <button (click)="editRole(role)" class="p-1 rounded hover:bg-[var(--surface-muted)]"><lucide-icon [img]="EditIcon" [size]="14" [style.color]="'var(--text-tertiary)'" /></button>
                  <button (click)="deleteRole(role)" class="p-1 rounded hover:bg-red-50"><lucide-icon [img]="TrashIcon" [size]="14" class="text-red-400" /></button>
                }
              </div>
            </div>
            <!-- Permission matrix -->
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-1">
              @for (mod of allModules; track mod) {
                <label class="flex items-center gap-1.5 text-[10px] p-1.5 rounded cursor-pointer hover:bg-[var(--surface-muted)]"
                  [style.color]="(role.permissions || []).includes(mod) ? 'var(--text-primary)' : 'var(--text-tertiary)'"
                  [style.fontWeight]="(role.permissions || []).includes(mod) ? '600' : '400'">
                  <input type="checkbox" [checked]="(role.permissions || []).includes(mod)" (change)="toggleRolePerm(role, mod)" [disabled]="role.is_system && role.value === 'company_admin'" class="rounded" style="width:12px;height:12px" />
                  {{ mod }}
                </label>
              }
            </div>
            @if (!role.is_system) {
              <button (click)="saveRolePerms(role)" class="btn-primary text-[10px] py-1 px-3 mt-2 flex items-center gap-1"><lucide-icon [img]="SaveIcon" [size]="10" /> Save</button>
            }
          </div>
        }
      </div>
    }

    <!-- Invite User Modal -->
    <g51-modal [open]="showInvite()" title="Invite Team Member" maxWidth="450px" (closed)="showInvite.set(false)">
      <div class="space-y-3">
        <div class="grid grid-cols-2 gap-3">
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">First Name *</label><input type="text" [(ngModel)]="inviteForm.first_name" class="input-base w-full" /></div>
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Last Name *</label><input type="text" [(ngModel)]="inviteForm.last_name" class="input-base w-full" /></div>
        </div>
        <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Email *</label><input type="email" [(ngModel)]="inviteForm.email" class="input-base w-full" /></div>
        <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Role *</label>
          <select [(ngModel)]="inviteForm.role" class="input-base w-full">
            @for (r of roles(); track r.id || r.value) { <option [value]="r.value || r.name">{{ r.label || r.name }}</option> }
          </select></div>
      </div>
      <div modal-footer><button (click)="showInvite.set(false)" class="btn-secondary">Cancel</button>
        <button (click)="invite()" class="btn-primary flex items-center gap-1"><lucide-icon [img]="MailIcon" [size]="12" /> Send Invite</button></div>
    </g51-modal>

    <!-- Create/Edit Role Modal -->
    <g51-modal [open]="showRoleModal()" [title]="editingRole() ? 'Edit Role' : 'Create Custom Role'" maxWidth="500px" (closed)="showRoleModal.set(false)">
      <div class="space-y-3">
        <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Role Name *</label><input type="text" [(ngModel)]="roleForm.name" class="input-base w-full" placeholder="e.g. Shift Supervisor" /></div>
        <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Description</label><input type="text" [(ngModel)]="roleForm.description" class="input-base w-full" placeholder="What this role does" /></div>
        <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Color</label>
          <div class="flex gap-2">
            @for (c of roleColors; track c) {
              <button (click)="roleForm.color = c" class="h-7 w-7 rounded-full border-2 transition-all"
                [style.background]="c" [style.borderColor]="roleForm.color === c ? c : 'transparent'"
                [style.transform]="roleForm.color === c ? 'scale(1.2)' : ''"></button>
            }
          </div></div>
        <div>
          <label class="block text-xs font-medium mb-2" [style.color]="'var(--text-secondary)'">Module Permissions</label>
          <div class="grid grid-cols-3 gap-1">
            @for (mod of allModules; track mod) {
              <label class="flex items-center gap-1.5 text-xs p-1.5 rounded cursor-pointer hover:bg-[var(--surface-muted)]">
                <input type="checkbox" [checked]="roleForm.permissions.includes(mod)" (change)="toggleFormPerm(mod)" class="rounded" /> {{ mod }}
              </label>
            }
          </div>
        </div>
      </div>
      <div modal-footer><button (click)="showRoleModal.set(false)" class="btn-secondary">Cancel</button>
        <button (click)="saveRole()" class="btn-primary">{{ editingRole() ? 'Update' : 'Create' }} Role</button></div>
    </g51-modal>
  `,
})
export class UsersComponent implements OnInit {
  private api = inject(ApiService); private toast = inject(ToastService); private confirmSvc = inject(ConfirmService);
  readonly UsersIcon = Users; readonly PlusIcon = Plus; readonly ShieldIcon = Shield; readonly KeyIcon = Key;
  readonly TrashIcon = Trash2; readonly MailIcon = Mail; readonly EditIcon = Edit; readonly SaveIcon = Save;
  readonly activeTab = signal('Active Users'); readonly loading = signal(true);
  readonly showInvite = signal(false); readonly showRoleModal = signal(false); readonly editingRole = signal(false);
  readonly users = signal<any[]>([]); readonly roles = signal<any[]>([]);
  inviteForm: any = { first_name: '', last_name: '', email: '', role: 'guard' };
  roleForm: any = { name: '', description: '', color: '#3B82F6', permissions: [] as string[], id: '' };
  roleColors = ['#3B82F6', '#8B5CF6', '#EC4899', '#10B981', '#F59E0B', '#EF4444', '#6366F1', '#14B8A6'];
  allModules = ['Dashboard', 'Guards', 'Sites', 'Clients', 'Scheduling', 'Attendance', 'Tracking', 'Incidents', 'Dispatch', 'Invoicing', 'Payroll', 'Reports', 'Visitors', 'Tours', 'Passdowns', 'Chat', 'Parking', 'Vehicle Patrol', 'Analytics', 'Licenses'];

  ngOnInit(): void { this.loadTab(); }

  loadTab(): void {
    this.loading.set(true);
    if (this.activeTab() === 'Active Users') {
      this.api.get<any>('/users').subscribe({
        next: r => { this.users.set(r.data?.users || r.data || []); this.loading.set(false); },
        error: () => this.loading.set(false),
      });
    }
    // Always load roles
    this.api.get<any>('/users/roles').subscribe({
      next: r => {
        const custom = r.data?.roles || r.data || [];
        // Merge system roles with custom
        const system = [
          { value: 'company_admin', label: 'Company Admin', description: 'Full access to all modules', color: '#1B3A5C', is_system: true, permissions: [...this.allModules] },
          { value: 'supervisor', label: 'Supervisor', description: 'Manage guards and schedules', color: '#3B82F6', is_system: true, permissions: ['Dashboard', 'Guards', 'Sites', 'Scheduling', 'Attendance', 'Tracking', 'Incidents', 'Reports', 'Passdowns', 'Tours'] },
          { value: 'dispatcher', label: 'Dispatcher', description: 'Handle dispatch and incidents', color: '#8B5CF6', is_system: true, permissions: ['Dashboard', 'Dispatch', 'Incidents', 'Tracking', 'Guards', 'Sites', 'Chat'] },
          { value: 'guard', label: 'Guard', description: 'Field operations only', color: '#10B981', is_system: true, permissions: ['Dashboard', 'Attendance', 'Incidents', 'Passdowns', 'Tours', 'Chat'] },
          { value: 'client', label: 'Client', description: 'View-only client portal', color: '#F59E0B', is_system: true, permissions: ['Dashboard', 'Reports', 'Invoicing', 'Visitors'] },
        ];
        this.roles.set([...system, ...custom.map((r: any) => ({ ...r, is_system: false }))]);
        this.loading.set(false);
      },
      error: () => {
        // Fallback: just system roles
        this.roles.set([
          { value: 'company_admin', label: 'Company Admin', description: 'Full access', color: '#1B3A5C', is_system: true, permissions: [...this.allModules] },
          { value: 'supervisor', label: 'Supervisor', description: 'Manage guards', color: '#3B82F6', is_system: true, permissions: ['Dashboard', 'Guards', 'Sites', 'Scheduling', 'Attendance', 'Tracking', 'Incidents', 'Reports'] },
          { value: 'dispatcher', label: 'Dispatcher', description: 'Dispatch operations', color: '#8B5CF6', is_system: true, permissions: ['Dashboard', 'Dispatch', 'Incidents', 'Tracking', 'Guards', 'Sites'] },
          { value: 'guard', label: 'Guard', description: 'Field operations', color: '#10B981', is_system: true, permissions: ['Dashboard', 'Attendance', 'Incidents', 'Passdowns', 'Tours'] },
          { value: 'client', label: 'Client', description: 'Client portal', color: '#F59E0B', is_system: true, permissions: ['Dashboard', 'Reports', 'Invoicing', 'Visitors'] },
        ]);
        this.loading.set(false);
      },
    });
  }

  roleColor(role: string): string {
    const r = this.roles().find(r => r.value === role || r.name === role);
    return r?.color || '#6B7280';
  }

  changeRole(user: any): void {
    this.api.put(`/users/${user.id}/role`, { role: user.role }).subscribe({
      next: () => this.toast.success('Role updated'),
      error: () => this.toast.error('Failed to update role'),
    });
  }

  async removeUser(user: any): Promise<void> {
    const ok = await this.confirmSvc.delete(`${user.first_name} ${user.last_name}`);
    if (ok) this.api.delete(`/users/${user.id}`).subscribe({ next: () => { this.toast.success('User removed'); this.loadTab(); } });
  }

  resendInvite(user: any): void { this.api.post(`/users/${user.id}/resend-invite`, {}).subscribe({ next: () => this.toast.success('Invite resent'), error: () => this.toast.error('Failed') }); }

  invite(): void {
    if (!this.inviteForm.email || !this.inviteForm.first_name) { this.toast.warning('Name and email required'); return; }
    this.api.post('/invitations', this.inviteForm).subscribe({
      next: () => { this.showInvite.set(false); this.toast.success('Invitation sent'); this.inviteForm = { first_name: '', last_name: '', email: '', role: 'guard' }; this.loadTab(); },
    });
  }

  // Role CRUD
  openRoleCreate(): void { this.roleForm = { name: '', description: '', color: '#3B82F6', permissions: [], id: '' }; this.editingRole.set(false); this.showRoleModal.set(true); }
  editRole(role: any): void { this.roleForm = { ...role, permissions: [...(role.permissions || [])] }; this.editingRole.set(true); this.showRoleModal.set(true); }
  toggleFormPerm(mod: string): void { const i = this.roleForm.permissions.indexOf(mod); i >= 0 ? this.roleForm.permissions.splice(i, 1) : this.roleForm.permissions.push(mod); }
  toggleRolePerm(role: any, mod: string): void {
    if (!role.permissions) role.permissions = [];
    const i = role.permissions.indexOf(mod);
    i >= 0 ? role.permissions.splice(i, 1) : role.permissions.push(mod);
  }

  saveRole(): void {
    if (!this.roleForm.name) { this.toast.warning('Role name required'); return; }
    const obs = this.editingRole()
      ? this.api.put(`/users/roles/${this.roleForm.id}`, this.roleForm)
      : this.api.post('/users/roles', this.roleForm);
    obs.subscribe({ next: () => { this.showRoleModal.set(false); this.toast.success(this.editingRole() ? 'Role updated' : 'Role created'); this.loadTab(); } });
  }

  saveRolePerms(role: any): void {
    this.api.put(`/users/roles/${role.id}/permissions`, { permissions: role.permissions }).subscribe({
      next: () => this.toast.success('Permissions saved'),
      error: () => this.toast.error('Failed to save'),
    });
  }

  async deleteRole(role: any): Promise<void> {
    const ok = await this.confirmSvc.delete(role.label || role.name);
    if (ok) this.api.delete(`/users/roles/${role.id}`).subscribe({ next: () => { this.toast.success('Role deleted'); this.loadTab(); } });
  }
}
