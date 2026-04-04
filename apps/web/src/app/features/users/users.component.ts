import { Component, inject, signal, computed, OnInit } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { NgClass } from '@angular/common';
import { LucideAngularModule, Users, Plus, Shield, Trash2, Mail, Edit, Save, ChevronDown, ChevronUp, Check, X, ToggleLeft, ToggleRight } from 'lucide-angular';
import { PageHeaderComponent } from '@shared/components/page-header/page-header.component';
import { ModalComponent } from '@shared/components/modal/modal.component';
import { EmptyStateComponent } from '@shared/components/empty-state/empty-state.component';
import { LoadingSpinnerComponent } from '@shared/components/loading-spinner/loading-spinner.component';
import { ApiService } from '@core/services/api.service';
import { AuthStore } from '@core/services/auth.store';
import { ToastService } from '@core/services/toast.service';
import { ConfirmService } from '@core/services/confirm.service';

// Permission structure — module → individual permissions
interface PermissionDef { key: string; label: string; description: string; }
interface ModuleDef { key: string; label: string; icon: string; permissions: PermissionDef[]; }

const PERMISSION_MODULES: ModuleDef[] = [
  { key: 'dashboard', label: 'Dashboard', icon: '📊', permissions: [
    { key: 'dashboard.view', label: 'View Dashboard', description: 'Access dashboard overview and KPIs' },
    { key: 'dashboard.export', label: 'Export Dashboard Data', description: 'Download dashboard reports' },
  ]},
  { key: 'guards', label: 'Guards', icon: '🛡️', permissions: [
    { key: 'guards.view', label: 'View Guards', description: 'See guard list and profiles' },
    { key: 'guards.create', label: 'Create Guards', description: 'Add new guard personnel' },
    { key: 'guards.edit', label: 'Edit Guards', description: 'Modify guard details and assignments' },
    { key: 'guards.delete', label: 'Delete Guards', description: 'Remove guards from the system' },
    { key: 'guards.suspend', label: 'Suspend/Activate Guards', description: 'Change guard active status' },
    { key: 'guards.documents', label: 'Manage Documents', description: 'Upload and verify guard documents' },
  ]},
  { key: 'sites', label: 'Sites', icon: '📍', permissions: [
    { key: 'sites.view', label: 'View Sites', description: 'See site list and details' },
    { key: 'sites.create', label: 'Create Sites', description: 'Add new guard sites' },
    { key: 'sites.edit', label: 'Edit Sites', description: 'Modify site configuration' },
    { key: 'sites.delete', label: 'Delete Sites', description: 'Remove sites from the system' },
  ]},
  { key: 'clients', label: 'Clients', icon: '🏢', permissions: [
    { key: 'clients.view', label: 'View Clients', description: 'See client list and contracts' },
    { key: 'clients.create', label: 'Create Clients', description: 'Add new clients' },
    { key: 'clients.edit', label: 'Edit Clients', description: 'Modify client details and billing' },
    { key: 'clients.delete', label: 'Delete Clients', description: 'Remove clients' },
  ]},
  { key: 'scheduling', label: 'Scheduling', icon: '📅', permissions: [
    { key: 'scheduling.view', label: 'View Schedules', description: 'See shift calendar and assignments' },
    { key: 'scheduling.create', label: 'Create Shifts', description: 'Schedule new shifts' },
    { key: 'scheduling.edit', label: 'Edit Shifts', description: 'Modify existing shifts' },
    { key: 'scheduling.approve_swaps', label: 'Approve Swap Requests', description: 'Approve or reject shift swap requests' },
    { key: 'scheduling.templates', label: 'Manage Templates', description: 'Create and edit shift templates' },
  ]},
  { key: 'attendance', label: 'Attendance', icon: '⏰', permissions: [
    { key: 'attendance.view', label: 'View Attendance', description: 'See clock-in/out records' },
    { key: 'attendance.manual_entry', label: 'Manual Entry', description: 'Manually add attendance records' },
    { key: 'attendance.export', label: 'Export Attendance', description: 'Download attendance reports' },
  ]},
  { key: 'incidents', label: 'Incidents', icon: '⚠️', permissions: [
    { key: 'incidents.view', label: 'View Incidents', description: 'See incident reports' },
    { key: 'incidents.create', label: 'Report Incidents', description: 'File new incident reports' },
    { key: 'incidents.edit', label: 'Edit Incidents', description: 'Modify incident details and status' },
    { key: 'incidents.resolve', label: 'Resolve Incidents', description: 'Close and resolve incidents' },
    { key: 'incidents.export', label: 'Export Incidents', description: 'Download incident reports' },
  ]},
  { key: 'tracking', label: 'Live Tracking', icon: '📡', permissions: [
    { key: 'tracking.view', label: 'View Live Tracking', description: 'See real-time guard locations' },
    { key: 'tracking.geofence', label: 'Manage Geofences', description: 'Configure geofence alerts' },
  ]},
  { key: 'reports', label: 'Reports & DARs', icon: '📋', permissions: [
    { key: 'reports.view', label: 'View Reports', description: 'Access daily activity reports' },
    { key: 'reports.create', label: 'Create Reports', description: 'Submit new reports' },
    { key: 'reports.export', label: 'Export Reports', description: 'Download reports as CSV/PDF' },
  ]},
  { key: 'invoicing', label: 'Invoicing', icon: '💰', permissions: [
    { key: 'invoicing.view', label: 'View Invoices', description: 'See invoice list and details' },
    { key: 'invoicing.create', label: 'Create Invoices', description: 'Generate new invoices' },
    { key: 'invoicing.edit', label: 'Edit Invoices', description: 'Modify invoice details' },
    { key: 'invoicing.record_payment', label: 'Record Payments', description: 'Log invoice payments' },
  ]},
  { key: 'payroll', label: 'Payroll', icon: '💳', permissions: [
    { key: 'payroll.view', label: 'View Payroll', description: 'Access payroll periods and payslips' },
    { key: 'payroll.process', label: 'Process Payroll', description: 'Run payroll calculations' },
    { key: 'payroll.approve', label: 'Approve Payroll', description: 'Approve payroll for payment' },
  ]},
  { key: 'visitors', label: 'Visitors', icon: '🚶', permissions: [
    { key: 'visitors.view', label: 'View Visitors', description: 'See visitor log and appointments' },
    { key: 'visitors.create', label: 'Schedule Visits', description: 'Create visitor appointments' },
    { key: 'visitors.checkin', label: 'Check In/Out', description: 'Process visitor arrivals and departures' },
  ]},
  { key: 'tours', label: 'Tours', icon: '🗺️', permissions: [
    { key: 'tours.view', label: 'View Tours', description: 'See tour routes and scan history' },
    { key: 'tours.create', label: 'Create Tours', description: 'Set up tour routes and checkpoints' },
    { key: 'tours.edit', label: 'Edit Tours', description: 'Modify tour configuration' },
  ]},
  { key: 'passdowns', label: 'Passdowns', icon: '📝', permissions: [
    { key: 'passdowns.view', label: 'View Passdowns', description: 'See passdown logs' },
    { key: 'passdowns.create', label: 'Create Passdowns', description: 'Write new passdown entries' },
    { key: 'passdowns.acknowledge', label: 'Acknowledge', description: 'Acknowledge passdown entries' },
  ]},
  { key: 'chat', label: 'Chat', icon: '💬', permissions: [
    { key: 'chat.view', label: 'View Messages', description: 'Access chat conversations' },
    { key: 'chat.send', label: 'Send Messages', description: 'Send chat messages' },
  ]},
  { key: 'users', label: 'User Management', icon: '👥', permissions: [
    { key: 'users.view', label: 'View Users', description: 'See user list' },
    { key: 'users.invite', label: 'Invite Users', description: 'Send user invitations' },
    { key: 'users.edit_roles', label: 'Edit Roles', description: 'Change user role assignments' },
    { key: 'users.manage_permissions', label: 'Manage Permissions', description: 'Configure role permissions' },
    { key: 'users.delete', label: 'Delete Users', description: 'Remove users from the system' },
  ]},
  { key: 'settings', label: 'Settings', icon: '⚙️', permissions: [
    { key: 'settings.view', label: 'View Settings', description: 'Access company settings' },
    { key: 'settings.edit', label: 'Edit Settings', description: 'Modify company configuration' },
    { key: 'settings.branding', label: 'Manage Branding', description: 'Update logo, colors, and white-labeling' },
  ]},
  { key: 'analytics', label: 'Analytics', icon: '📈', permissions: [
    { key: 'analytics.view', label: 'View Analytics', description: 'Access performance dashboards' },
    { key: 'analytics.export', label: 'Export Analytics', description: 'Download analytics reports' },
  ]},
];

@Component({
  selector: 'g51-users',
  standalone: true,
  imports: [FormsModule, NgClass, LucideAngularModule, PageHeaderComponent, ModalComponent, EmptyStateComponent, LoadingSpinnerComponent],
  template: `
    <g51-page-header title="Team Management" subtitle="Manage users and role-based access control">
      @if (auth.isAdmin()) { <button class="btn-primary flex items-center gap-2 text-xs" (click)="showInvite.set(true)"><lucide-icon [img]="PlusIcon" [size]="14" /> Invite User</button> }
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
              <th class="text-left py-2.5 px-4 font-semibold hidden sm:table-cell" [style.color]="'var(--text-secondary)'">Status</th>
              <th class="text-center py-2.5 px-4 font-semibold" [style.color]="'var(--text-secondary)'">Actions</th>
            </tr></thead>
            <tbody>
              @for (user of users(); track user.id) {
                <tr class="border-t hover:bg-[var(--surface-hover)]" [style.borderColor]="'var(--border-default)'">
                  <td class="py-2.5 px-4"><div class="flex items-center gap-2">
                    <div class="h-8 w-8 rounded-full flex items-center justify-center text-xs font-bold text-white" [style.background]="'var(--color-brand-500)'">{{ user.first_name?.charAt(0) }}{{ user.last_name?.charAt(0) }}</div>
                    <div><p class="font-medium" [style.color]="'var(--text-primary)'">{{ user.first_name }} {{ user.last_name }}</p><p class="text-[10px]" [style.color]="'var(--text-tertiary)'">{{ user.email }}</p></div>
                  </div></td>
                  <td class="py-2.5 px-4">
                    <select [(ngModel)]="user.role" (ngModelChange)="changeRole(user)" class="input-base text-xs py-1 px-2">
                      @for (r of allRoles(); track r.value) { <option [value]="r.value">{{ r.label }}</option> }
                    </select></td>
                  <td class="py-2.5 px-4 hidden sm:table-cell"><span class="badge text-[10px]" [ngClass]="user.is_active ? 'bg-emerald-50 text-emerald-600' : 'bg-gray-100 text-gray-500'">{{ user.is_active ? 'Active' : 'Inactive' }}</span></td>
                  <td class="py-2.5 px-4 text-center">
                    <div class="flex justify-center gap-1">
                      <button (click)="resendInvite(user)" class="p-1 rounded hover:bg-[var(--surface-muted)]" title="Resend invite"><lucide-icon [img]="MailIcon" [size]="14" [style.color]="'var(--text-tertiary)'" /></button>
                      <button (click)="removeUser(user)" class="p-1 rounded hover:bg-red-50" title="Remove"><lucide-icon [img]="TrashIcon" [size]="14" class="text-red-400" /></button>
                    </div></td>
                </tr>
              }
            </tbody>
          </table>
        </div>
      }
    }

    <!-- ROLES & PERMISSIONS TAB (Lodgik-style) -->
    @if (activeTab() === 'Roles & Permissions') {
      <div class="flex gap-4" style="min-height:60vh">
        <!-- Left sidebar: role list -->
        <div class="w-48 shrink-0 hidden md:block">
          <div class="flex items-center justify-between mb-2">
            <h4 class="text-[10px] font-bold uppercase tracking-wider" [style.color]="'var(--text-tertiary)'">Roles</h4>
            <button (click)="openRoleCreate()" class="p-1 rounded hover:bg-[var(--surface-muted)]"><lucide-icon [img]="PlusIcon" [size]="14" [style.color]="'var(--text-tertiary)'" /></button>
          </div>
          <div class="space-y-0.5">
            @for (role of allRoles(); track role.value) {
              <button (click)="selectRole(role)" class="w-full text-left px-3 py-2.5 rounded-lg transition-all text-xs"
                [ngClass]="selectedRole()?.value === role.value ? 'font-semibold' : ''"
                [style.background]="selectedRole()?.value === role.value ? 'var(--surface-active)' : 'transparent'"
                [style.color]="selectedRole()?.value === role.value ? 'var(--color-brand-500)' : 'var(--text-secondary)'">
                <div class="flex items-center gap-2">
                  <span>{{ role.icon || '👤' }}</span>
                  <div>
                    <p>{{ role.label }}</p>
                    <p class="text-[10px] font-normal" [style.color]="'var(--text-tertiary)'">{{ grantedCount(role) }}/{{ totalPermissions }} granted</p>
                  </div>
                </div>
              </button>
            }
          </div>
        </div>

        <!-- Mobile role selector -->
        <select [(ngModel)]="mobileRoleValue" (ngModelChange)="onMobileRoleChange($event)" class="input-base w-full md:hidden mb-3 text-sm">
          @for (role of allRoles(); track role.value) { <option [value]="role.value">{{ role.label }} ({{ grantedCount(role) }}/{{ totalPermissions }})</option> }
        </select>

        <!-- Right: permission editor -->
        @if (selectedRole()) {
          <div class="flex-1 min-w-0">
            <!-- Role header -->
            <div class="card p-4 mb-4">
              <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                  <div class="h-10 w-10 rounded-xl flex items-center justify-center text-lg" [style.background]="(selectedRole()!.color || '#3B82F6') + '15'" [style.color]="selectedRole()!.color || '#3B82F6'">{{ selectedRole()!.icon || '👤' }}</div>
                  <div>
                    <h3 class="text-sm font-bold" [style.color]="'var(--text-primary)'">{{ selectedRole()!.label }}</h3>
                    <p class="text-xs" [style.color]="'var(--text-tertiary)'">{{ selectedRole()!.description || (selectedRole()!.is_system ? 'System role' : 'Custom role') }}</p>
                  </div>
                </div>
                <div class="flex items-center gap-2">
                  <!-- Progress -->
                  <div class="flex items-center gap-2">
                    <span class="text-sm font-bold" [style.color]="'var(--text-primary)'">{{ grantedCount(selectedRole()!) }}</span>
                    <div class="w-20 h-2 rounded-full" [style.background]="'var(--surface-muted)'">
                      <div class="h-2 rounded-full transition-all" [style.width.%]="(grantedCount(selectedRole()!) / totalPermissions * 100)" [style.background]="'var(--color-brand-500)'"></div>
                    </div>
                    <span class="text-xs" [style.color]="'var(--text-tertiary)'">/{{ totalPermissions }} granted</span>
                  </div>
                  <button (click)="grantAll()" class="btn-secondary text-[10px] py-1 px-2">Grant All</button>
                  <button (click)="revokeAll()" class="text-[10px] py-1 px-2 rounded border border-red-200 text-red-500 hover:bg-red-50">Revoke All</button>
                  <button (click)="savePermissions()" class="btn-primary text-[10px] py-1 px-3 flex items-center gap-1"><lucide-icon [img]="SaveIcon" [size]="10" /> Save Changes</button>
                </div>
              </div>
            </div>

            <!-- Permission modules (accordion) -->
            <div class="space-y-2">
              @for (mod of permissionModules; track mod.key) {
                <div class="card overflow-hidden">
                  <!-- Module header -->
                  <button (click)="toggleModule(mod.key)" class="w-full flex items-center justify-between px-4 py-3 hover:bg-[var(--surface-hover)] transition-colors">
                    <div class="flex items-center gap-2">
                      <span class="text-base">{{ mod.icon }}</span>
                      <span class="text-sm font-semibold" [style.color]="'var(--text-primary)'">{{ mod.label }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                      <span class="text-xs font-medium px-2 py-0.5 rounded-full" [style.background]="moduleGrantedCount(mod) === mod.permissions.length ? '#ECFDF5' : '#FEF3C7'" [style.color]="moduleGrantedCount(mod) === mod.permissions.length ? '#059669' : '#D97706'">{{ moduleGrantedCount(mod) }}/{{ mod.permissions.length }}</span>
                      <button (click)="$event.stopPropagation(); grantModule(mod)" class="text-[10px] px-1.5 py-0.5 rounded border hover:bg-emerald-50" [style.borderColor]="'var(--border-default)'" [style.color]="'var(--text-tertiary)'">All</button>
                      <button (click)="$event.stopPropagation(); revokeModule(mod)" class="text-[10px] px-1.5 py-0.5 rounded border text-red-400 hover:bg-red-50" [style.borderColor]="'var(--border-default)'">None</button>
                      <lucide-icon [img]="expandedModules().includes(mod.key) ? ChevronUpIcon : ChevronDownIcon" [size]="14" [style.color]="'var(--text-tertiary)'" />
                    </div>
                  </button>

                  <!-- Expanded permissions -->
                  @if (expandedModules().includes(mod.key)) {
                    <div class="border-t" [style.borderColor]="'var(--border-default)'">
                      @for (perm of mod.permissions; track perm.key) {
                        <div class="flex items-center justify-between px-5 py-2.5 hover:bg-[var(--surface-hover)] transition-colors border-b" [style.borderColor]="'var(--border-default)'">
                          <div class="flex items-center gap-3">
                            <!-- Toggle switch -->
                            <button (click)="togglePermission(perm.key)" class="relative w-10 h-5 rounded-full transition-colors"
                              [style.background]="isGranted(perm.key) ? 'var(--color-brand-500)' : 'var(--border-strong)'">
                              <span class="absolute top-0.5 h-4 w-4 rounded-full bg-white shadow transition-transform"
                                [style.left]="isGranted(perm.key) ? '22px' : '2px'"></span>
                            </button>
                            <div>
                              <p class="text-xs font-medium" [style.color]="'var(--text-primary)'">{{ perm.label }}</p>
                              <p class="text-[10px]" [style.color]="'var(--text-tertiary)'">{{ perm.description }}</p>
                            </div>
                          </div>
                          <span class="text-[10px] font-mono hidden lg:block" [style.color]="'var(--text-tertiary)'">{{ perm.key }}</span>
                        </div>
                      }
                    </div>
                  }
                </div>
              }
            </div>
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
            @for (r of allRoles(); track r.value) { <option [value]="r.value">{{ r.label }}</option> }
          </select></div>
      </div>
      <div modal-footer><button (click)="showInvite.set(false)" class="btn-secondary">Cancel</button>
        <button (click)="invite()" class="btn-primary flex items-center gap-1"><lucide-icon [img]="MailIcon" [size]="12" /> Send Invite</button></div>
    </g51-modal>

    <!-- Create Role Modal -->
    <g51-modal [open]="showRoleModal()" title="Create Custom Role" maxWidth="400px" (closed)="showRoleModal.set(false)">
      <div class="space-y-3">
        <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Role Name *</label><input type="text" [(ngModel)]="roleForm.name" class="input-base w-full" placeholder="e.g. Shift Supervisor" /></div>
        <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Description</label><input type="text" [(ngModel)]="roleForm.description" class="input-base w-full" /></div>
        <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Color</label>
          <div class="flex gap-2">
            @for (c of roleColors; track c) {
              <button (click)="roleForm.color = c" class="h-7 w-7 rounded-full border-2 transition-all" [style.background]="c" [style.borderColor]="roleForm.color === c ? c : 'transparent'" [style.transform]="roleForm.color === c ? 'scale(1.15)' : ''"></button>
            }
          </div></div>
      </div>
      <div modal-footer><button (click)="showRoleModal.set(false)" class="btn-secondary">Cancel</button>
        <button (click)="createRole()" class="btn-primary">Create Role</button></div>
    </g51-modal>
  `,
})
export class UsersComponent implements OnInit {
  private api = inject(ApiService); private toast = inject(ToastService); private confirmSvc = inject(ConfirmService);
  readonly auth = inject(AuthStore);
  readonly UsersIcon = Users; readonly PlusIcon = Plus; readonly TrashIcon = Trash2; readonly MailIcon = Mail;
  readonly EditIcon = Edit; readonly SaveIcon = Save; readonly ChevronDownIcon = ChevronDown; readonly ChevronUpIcon = ChevronUp;
  readonly activeTab = signal('Active Users'); readonly loading = signal(true);
  readonly showInvite = signal(false); readonly showRoleModal = signal(false);
  readonly users = signal<any[]>([]); readonly selectedRole = signal<any>(null);
  readonly expandedModules = signal<string[]>([]);
  readonly allRoles = signal<any[]>([]);
  readonly rolePermissions = signal<string[]>([]); // current role's granted permissions
  mobileRoleValue = '';
  inviteForm: any = { first_name: '', last_name: '', email: '', role: 'guard' };
  roleForm: any = { name: '', description: '', color: '#3B82F6' };
  roleColors = ['#3B82F6', '#8B5CF6', '#EC4899', '#10B981', '#F59E0B', '#EF4444', '#6366F1', '#14B8A6'];
  permissionModules = PERMISSION_MODULES;
  totalPermissions = PERMISSION_MODULES.reduce((sum, m) => sum + m.permissions.length, 0);

  // System roles with default permissions
  private systemRoles = [
    { value: 'company_admin', label: 'Company Admin', icon: '👑', color: '#1B3A5C', is_system: true, description: 'Full access to all modules and settings',
      permissions: PERMISSION_MODULES.flatMap(m => m.permissions.map(p => p.key)) },
    { value: 'supervisor', label: 'Supervisor', icon: '🎖️', color: '#3B82F6', is_system: true, description: 'Manage guards, schedules, and operations',
      permissions: ['dashboard.view', 'guards.view', 'guards.create', 'guards.edit', 'guards.documents', 'sites.view', 'scheduling.view', 'scheduling.create', 'scheduling.edit', 'scheduling.approve_swaps', 'attendance.view', 'attendance.export', 'incidents.view', 'incidents.create', 'incidents.edit', 'incidents.resolve', 'tracking.view', 'reports.view', 'reports.create', 'reports.export', 'tours.view', 'passdowns.view', 'passdowns.create', 'chat.view', 'chat.send'] },
    { value: 'dispatcher', label: 'Dispatcher', icon: '📻', color: '#8B5CF6', is_system: true, description: 'Handle dispatch, incidents, and communications',
      permissions: ['dashboard.view', 'guards.view', 'sites.view', 'incidents.view', 'incidents.create', 'incidents.edit', 'tracking.view', 'tracking.geofence', 'chat.view', 'chat.send', 'passdowns.view'] },
    { value: 'guard', label: 'Guard', icon: '🛡️', color: '#10B981', is_system: true, description: 'Field operations and reporting',
      permissions: ['dashboard.view', 'attendance.view', 'incidents.view', 'incidents.create', 'reports.view', 'reports.create', 'tours.view', 'passdowns.view', 'passdowns.acknowledge', 'chat.view', 'chat.send'] },
    { value: 'client', label: 'Client', icon: '🏢', color: '#F59E0B', is_system: true, description: 'View-only access to reports and tracking',
      permissions: ['dashboard.view', 'reports.view', 'tracking.view', 'invoicing.view', 'visitors.view', 'visitors.create'] },
  ];

  ngOnInit(): void { this.loadTab(); }

  loadTab(): void {
    this.loading.set(true);
    if (this.activeTab() === 'Active Users') {
      this.api.get<any>('/users').subscribe({ next: r => { this.users.set(r.data?.users || r.data || []); this.loading.set(false); }, error: () => this.loading.set(false) });
    } else { this.loading.set(false); }
    // Load roles
    this.api.get<any>('/users/roles').subscribe({
      next: r => {
        const custom = (r.data?.roles || []).map((cr: any) => ({ ...cr, value: cr.id || cr.name, label: cr.name, icon: '🔧', is_system: false, permissions: cr.permissions || [] }));
        this.allRoles.set([...this.systemRoles, ...custom]);
        if (!this.selectedRole() && this.allRoles().length) this.selectRole(this.allRoles()[0]);
      },
      error: () => this.allRoles.set([...this.systemRoles]),
    });
  }

  selectRole(role: any): void {
    this.selectedRole.set(role);
    this.rolePermissions.set([...(role.permissions || [])]);
    this.mobileRoleValue = role.value;
  }
  onMobileRoleChange(val: string): void { const r = this.allRoles().find(r => r.value === val); if (r) this.selectRole(r); }

  grantedCount(role: any): number { return (role.permissions || []).length; }
  moduleGrantedCount(mod: ModuleDef): number { return mod.permissions.filter(p => this.rolePermissions().includes(p.key)).length; }
  isGranted(key: string): boolean { return this.rolePermissions().includes(key); }

  togglePermission(key: string): void {
    this.rolePermissions.update(perms => perms.includes(key) ? perms.filter(p => p !== key) : [...perms, key]);
  }
  toggleModule(key: string): void { this.expandedModules.update(m => m.includes(key) ? m.filter(k => k !== key) : [...m, key]); }
  grantAll(): void { this.rolePermissions.set(PERMISSION_MODULES.flatMap(m => m.permissions.map(p => p.key))); }
  revokeAll(): void { this.rolePermissions.set([]); }
  grantModule(mod: ModuleDef): void { this.rolePermissions.update(p => [...new Set([...p, ...mod.permissions.map(pp => pp.key)])]); }
  revokeModule(mod: ModuleDef): void { const keys = mod.permissions.map(p => p.key); this.rolePermissions.update(p => p.filter(k => !keys.includes(k))); }

  savePermissions(): void {
    const role = this.selectedRole();
    if (!role) return;
    const perms = this.rolePermissions();
    if (role.is_system) {
      // Save system role overrides per tenant
      this.api.put(`/users/roles/${role.value}/permissions`, { permissions: perms }).subscribe({
        next: () => { this.toast.success('Permissions saved'); role.permissions = [...perms]; },
        error: () => this.toast.error('Failed to save'),
      });
    } else {
      this.api.put(`/users/roles/${role.value}/permissions`, { permissions: perms }).subscribe({
        next: () => { this.toast.success('Permissions saved'); role.permissions = [...perms]; },
        error: () => this.toast.error('Failed to save'),
      });
    }
  }

  changeRole(user: any): void { this.api.put(`/users/${user.id}/role`, { role: user.role }).subscribe({ next: () => this.toast.success('Role updated') }); }
  async removeUser(user: any): Promise<void> { if (await this.confirmSvc.delete(`${user.first_name} ${user.last_name}`)) this.api.delete(`/users/${user.id}`).subscribe({ next: () => { this.toast.success('User removed'); this.loadTab(); } }); }
  resendInvite(user: any): void { this.api.post(`/users/${user.id}/resend-invite`, {}).subscribe({ next: () => this.toast.success('Invite resent') }); }
  invite(): void {
    if (!this.inviteForm.email || !this.inviteForm.first_name) { this.toast.warning('Name and email required'); return; }
    this.api.post('/invitations', this.inviteForm).subscribe({ next: () => { this.showInvite.set(false); this.toast.success('Invitation sent'); this.loadTab(); } });
  }
  openRoleCreate(): void { this.roleForm = { name: '', description: '', color: '#3B82F6' }; this.showRoleModal.set(true); }
  createRole(): void {
    if (!this.roleForm.name) { this.toast.warning('Role name required'); return; }
    this.api.post('/users/roles', { ...this.roleForm, permissions: [] }).subscribe({ next: () => { this.showRoleModal.set(false); this.toast.success('Role created'); this.loadTab(); } });
  }
}
