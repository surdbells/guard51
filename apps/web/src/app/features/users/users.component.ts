import { Component, inject, signal, OnInit } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { NgClass } from '@angular/common';
import { LucideAngularModule, Users, Plus, Shield, Key, Trash2 } from 'lucide-angular';
import { PageHeaderComponent } from '@shared/components/page-header/page-header.component';
import { ModalComponent } from '@shared/components/modal/modal.component';
import { EmptyStateComponent } from '@shared/components/empty-state/empty-state.component';
import { ApiService } from '@core/services/api.service';
import { ToastService } from '@core/services/toast.service';

@Component({
  selector: 'g51-users',
  standalone: true,
  imports: [FormsModule, NgClass, LucideAngularModule, PageHeaderComponent, ModalComponent, EmptyStateComponent],
  template: `
    <g51-page-header title="Team Management" subtitle="Manage users, roles, and module permissions">
      <button (click)="showInvite.set(true)" class="btn-primary flex items-center gap-2"><lucide-icon [img]="PlusIcon" [size]="16" /> Invite User</button>
    </g51-page-header>
    <div class="space-y-2">
      @for (user of users(); track user.id) {
        <div class="card p-4 card-hover">
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
              <div class="h-9 w-9 rounded-full flex items-center justify-center text-sm font-bold text-white" [style.background]="'var(--color-brand-500)'">{{ user.first_name?.charAt(0) }}{{ user.last_name?.charAt(0) }}</div>
              <div><p class="text-sm font-semibold" [style.color]="'var(--text-primary)'">{{ user.first_name }} {{ user.last_name }}</p>
                <p class="text-xs" [style.color]="'var(--text-tertiary)'">{{ user.email }}</p></div>
            </div>
            <div class="flex items-center gap-2">
              <select [(ngModel)]="user.role" (ngModelChange)="changeRole(user.id, $event)" class="input-base text-xs py-1 px-2">
                <option value="company_admin">Admin</option><option value="supervisor">Supervisor</option>
                <option value="guard">Guard</option><option value="dispatcher">Dispatcher</option>
                <option value="client">Client</option>
              </select>
              <button (click)="showPermissions(user)" class="btn-secondary text-xs py-1 px-2 flex items-center gap-1">
                <lucide-icon [img]="KeyIcon" [size]="12" /> Permissions</button>
            </div>
          </div>
        </div>
      } @empty { <g51-empty-state title="No Team Members" message="Invite your first team member to get started." [icon]="UsersIcon" /> }
    </div>

    <!-- Permissions Modal -->
    <g51-modal [open]="showPermModal()" [title]="'Permissions: ' + (selectedUser()?.first_name || '')" maxWidth="600px" (closed)="showPermModal.set(false)">
      <div class="space-y-2">
        <p class="text-xs mb-3" [style.color]="'var(--text-secondary)'">Set granular access per module. Changes save automatically.</p>
        @for (mod of modules(); track mod) {
          <div class="flex items-center justify-between py-2 border-b" [style.borderColor]="'var(--border-default)'">
            <span class="text-xs font-medium w-28" [style.color]="'var(--text-primary)'">{{ mod }}</span>
            <div class="flex gap-2">
              @for (perm of ['View', 'Create', 'Edit', 'Delete', 'Export', 'Approve']; track perm) {
                <label class="flex items-center gap-0.5 text-[9px]" [style.color]="'var(--text-tertiary)'">
                  <input type="checkbox" class="rounded h-3 w-3" (change)="togglePerm(mod, perm.toLowerCase(), $event)" /> {{ perm }}</label>
              }
            </div>
          </div>
        }
      </div>
      <div modal-footer><button (click)="showPermModal.set(false)" class="btn-primary">Done</button></div>
    </g51-modal>

    <!-- Invite Modal -->
    <g51-modal [open]="showInvite()" title="Invite Team Member" maxWidth="420px" (closed)="showInvite.set(false)">
      <div class="space-y-3">
        <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Email *</label>
          <input type="email" [(ngModel)]="inviteForm.email" class="input-base w-full" /></div>
        <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Role *</label>
          <select [(ngModel)]="inviteForm.role" class="input-base w-full">
            <option value="supervisor">Supervisor</option><option value="guard">Guard</option>
            <option value="dispatcher">Dispatcher</option><option value="company_admin">Admin</option></select></div>
      </div>
      <div modal-footer><button (click)="showInvite.set(false)" class="btn-secondary">Cancel</button>
        <button (click)="onInvite()" class="btn-primary">Send Invite</button></div>
    </g51-modal>
  `,
})
export class UsersComponent implements OnInit {
  private api = inject(ApiService); private toast = inject(ToastService);
  readonly UsersIcon = Users; readonly PlusIcon = Plus; readonly ShieldIcon = Shield; readonly KeyIcon = Key;
  readonly users = signal<any[]>([]);
  readonly modules = signal<string[]>([]);
  readonly showInvite = signal(false);
  readonly showPermModal = signal(false);
  readonly selectedUser = signal<any>(null);
  inviteForm = { email: '', role: 'guard' };
  ngOnInit(): void {
    this.api.get<any>('/users').subscribe({ next: r => { if (r.data) this.users.set(r.data.users || []); } });
    this.api.get<any>('/users/modules').subscribe({ next: r => { if (r.data) this.modules.set(r.data.modules || []); } });
  }
  changeRole(userId: string, role: string): void {
    this.api.put('/users/' + userId + '/role', { role }).subscribe({ next: () => this.toast.success('Role updated') });
  }
  showPermissions(user: any): void { this.selectedUser.set(user); this.showPermModal.set(true); }
  togglePerm(mod: string, perm: string, event: any): void {
    const body: any = { module_key: mod, ['can_' + perm]: event.target.checked };
    this.api.post('/users/' + this.selectedUser().id + '/permissions', body).subscribe();
  }
  onInvite(): void { this.api.post('/onboarding/invitations', this.inviteForm).subscribe({
    next: () => { this.showInvite.set(false); this.toast.success('Invitation sent'); } }); }
}
