import { Component, inject, signal, OnInit } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { NgClass, DecimalPipe } from '@angular/common';
import { LucideAngularModule, CreditCard, Plus, Edit, Trash2, Check, X, Package } from 'lucide-angular';
import { PageHeaderComponent } from '@shared/components/page-header/page-header.component';
import { ModalComponent } from '@shared/components/modal/modal.component';
import { EmptyStateComponent } from '@shared/components/empty-state/empty-state.component';
import { LoadingSpinnerComponent } from '@shared/components/loading-spinner/loading-spinner.component';
import { ApiService } from '@core/services/api.service';
import { ToastService } from '@core/services/toast.service';

@Component({
  selector: 'g51-plans',
  standalone: true,
  imports: [FormsModule, NgClass, DecimalPipe, LucideAngularModule, PageHeaderComponent, ModalComponent, EmptyStateComponent, LoadingSpinnerComponent],
  template: `
    <g51-page-header title="Subscription Plans" subtitle="Manage pricing plans and module access">
      <button (click)="openCreate()" class="btn-primary flex items-center gap-2"><lucide-icon [img]="PlusIcon" [size]="16" /> New Plan</button>
    </g51-page-header>
    @if (loading()) { <g51-loading /> }
    @else if (!plans().length) { <g51-empty-state title="No Plans" message="Create your first subscription plan." [icon]="PackageIcon" /> }
    @else {
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @for (plan of plans(); track plan.id) {
          <div class="card p-5">
            <div class="flex justify-between items-start mb-3">
              <div>
                <p class="text-base font-bold font-heading" [style.color]="'var(--text-primary)'">{{ plan.name }}</p>
                <p class="text-xs" [style.color]="'var(--text-tertiary)'">{{ plan.description || '' }}</p>
              </div>
              <div class="flex gap-1">
                <button (click)="editPlan(plan)" class="p-1.5 rounded-lg hover:bg-[var(--surface-muted)]"><lucide-icon [img]="EditIcon" [size]="14" [style.color]="'var(--text-tertiary)'" /></button>
              </div>
            </div>
            <div class="mb-3">
              <span class="text-2xl font-bold" [style.color]="'var(--text-primary)'">₦{{ plan.monthly_price | number:'1.0-0' }}</span>
              <span class="text-xs" [style.color]="'var(--text-tertiary)'">/month</span>
            </div>
            <div class="space-y-1 mb-3">
              <p class="text-[10px] font-semibold uppercase tracking-wide" [style.color]="'var(--text-tertiary)'">Limits</p>
              <p class="text-xs" [style.color]="'var(--text-secondary)'">Guards: {{ plan.max_guards === -1 ? 'Unlimited' : plan.max_guards }}</p>
              <p class="text-xs" [style.color]="'var(--text-secondary)'">Sites: {{ plan.max_sites === -1 ? 'Unlimited' : plan.max_sites }}</p>
              <p class="text-xs" [style.color]="'var(--text-secondary)'">Users: {{ plan.max_users === -1 ? 'Unlimited' : plan.max_users }}</p>
            </div>
            <div class="space-y-1">
              <p class="text-[10px] font-semibold uppercase tracking-wide" [style.color]="'var(--text-tertiary)'">Modules</p>
              <div class="flex flex-wrap gap-1">
                @for (m of allModules; track m) {
                  <span class="badge text-[9px] px-1.5 py-0.5" [ngClass]="(plan.modules || []).includes(m) ? 'bg-emerald-50 text-emerald-600' : 'bg-gray-100 text-gray-400 line-through'">{{ m }}</span>
                }
              </div>
            </div>
            <div class="flex justify-between items-center mt-3 pt-3 border-t" [style.borderColor]="'var(--border-default)'">
              <span class="badge text-[10px]" [ngClass]="plan.is_active ? 'bg-emerald-50 text-emerald-600' : 'bg-gray-100 text-gray-500'">{{ plan.is_active ? 'Active' : 'Inactive' }}</span>
              <span class="text-[10px]" [style.color]="'var(--text-tertiary)'">{{ plan.subscriber_count || 0 }} subscribers</span>
            </div>
          </div>
        }
      </div>
    }

    <g51-modal [open]="showModal()" [title]="editing() ? 'Edit Plan' : 'Create Plan'" maxWidth="600px" (closed)="showModal.set(false)">
      <div class="space-y-3">
        <div class="grid grid-cols-2 gap-3">
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Plan Name *</label><input type="text" [(ngModel)]="form.name" class="input-base w-full" /></div>
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Monthly Price (₦) *</label><input type="number" [(ngModel)]="form.monthly_price" class="input-base w-full" /></div>
        </div>
        <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Description</label><input type="text" [(ngModel)]="form.description" class="input-base w-full" /></div>
        <div class="grid grid-cols-3 gap-3">
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Max Guards (-1=unlimited)</label><input type="number" [(ngModel)]="form.max_guards" class="input-base w-full" /></div>
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Max Sites</label><input type="number" [(ngModel)]="form.max_sites" class="input-base w-full" /></div>
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Max Users</label><input type="number" [(ngModel)]="form.max_users" class="input-base w-full" /></div>
        </div>
        <div>
          <label class="block text-xs font-medium mb-2" [style.color]="'var(--text-secondary)'">Included Modules</label>
          <div class="grid grid-cols-3 gap-1">
            @for (m of allModules; track m) {
              <label class="flex items-center gap-1.5 text-xs p-1.5 rounded-lg cursor-pointer hover:bg-[var(--surface-muted)]">
                <input type="checkbox" [checked]="form.modules.includes(m)" (change)="toggleModule(m)" class="rounded" />{{ m }}
              </label>
            }
          </div>
        </div>
        <label class="flex items-center gap-2 text-xs"><input type="checkbox" [(ngModel)]="form.is_active" class="rounded" /> Active</label>
      </div>
      <div modal-footer><button (click)="showModal.set(false)" class="btn-secondary">Cancel</button>
        <button (click)="save()" class="btn-primary">{{ editing() ? 'Update' : 'Create' }}</button></div>
    </g51-modal>
  `,
})
export class PlansComponent implements OnInit {
  private api = inject(ApiService); private toast = inject(ToastService);
  readonly PlusIcon = Plus; readonly EditIcon = Edit; readonly PackageIcon = Package;
  readonly loading = signal(true); readonly showModal = signal(false); readonly editing = signal(false);
  readonly plans = signal<any[]>([]);
  allModules = ['Dashboard', 'Guards', 'Sites', 'Clients', 'Scheduling', 'Attendance', 'Tracking', 'Incidents', 'Dispatch', 'Invoicing', 'Payroll', 'Reports', 'Visitors', 'Tours', 'Passdowns', 'Chat', 'Parking', 'Vehicle Patrol', 'Analytics', 'Licenses'];
  form: any = this.blankForm();

  blankForm() { return { name: '', description: '', monthly_price: 0, max_guards: 50, max_sites: 10, max_users: 20, modules: [...this.allModules], is_active: true, id: '' }; }

  ngOnInit(): void { this.load(); }
  load(): void { this.loading.set(true); this.api.get<any>('/admin/plans').subscribe({ next: r => { this.plans.set(r.data?.plans || r.data || []); this.loading.set(false); }, error: () => this.loading.set(false) }); }
  openCreate(): void { this.form = this.blankForm(); this.editing.set(false); this.showModal.set(true); }
  editPlan(p: any): void { this.form = { ...p, modules: p.modules || [...this.allModules] }; this.editing.set(true); this.showModal.set(true); }
  toggleModule(m: string): void { const i = this.form.modules.indexOf(m); i >= 0 ? this.form.modules.splice(i, 1) : this.form.modules.push(m); }
  save(): void {
    const obs = this.editing() ? this.api.put(`/admin/plans/${this.form.id}`, this.form) : this.api.post('/admin/plans', this.form);
    obs.subscribe({ next: () => { this.showModal.set(false); this.toast.success(this.editing() ? 'Plan updated' : 'Plan created'); this.load(); } });
  }
}
