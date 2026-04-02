import { Component, inject, signal, OnInit } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { NgClass, DecimalPipe } from '@angular/common';
import { LucideAngularModule, CreditCard, Plus, Edit, Trash2, Check } from 'lucide-angular';
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
    <g51-page-header title="Plan Management" subtitle="Subscription plans, pricing, and feature limits">
      <button (click)="openCreate()" class="btn-primary flex items-center gap-2"><lucide-icon [img]="PlusIcon" [size]="16" /> New Plan</button>
    </g51-page-header>

    @if (loading()) { <g51-loading /> }
    @else if (!plans().length) { <g51-empty-state title="No Plans" message="Create your first subscription plan." [icon]="CreditCardIcon" /> }
    @else {
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @for (p of plans(); track p.id) {
          <div class="card p-5">
            <div class="flex items-center justify-between mb-3">
              <h3 class="text-base font-bold font-heading" [style.color]="'var(--text-primary)'">{{ p.name }}</h3>
              <div class="flex gap-1">
                <button (click)="openEdit(p)" class="p-1.5 rounded hover:bg-[var(--surface-hover)]"><lucide-icon [img]="EditIcon" [size]="14" [style.color]="'var(--text-tertiary)'" /></button>
                <button (click)="deletePlan(p)" class="p-1.5 rounded hover:bg-red-50"><lucide-icon [img]="TrashIcon" [size]="14" [style.color]="'var(--color-danger)'" /></button>
              </div>
            </div>
            <p class="text-2xl font-bold mb-1" [style.color]="'var(--brand-500)'">₦{{ p.monthly_price | number:'1.0-0' }}<span class="text-xs font-normal" [style.color]="'var(--text-tertiary)'">/mo</span></p>
            @if (p.annual_price) { <p class="text-xs mb-3" [style.color]="'var(--text-tertiary)'">₦{{ p.annual_price | number:'1.0-0' }}/yr (save {{ p.annual_discount || 0 }}%)</p> }
            <div class="space-y-1.5 mt-3">
              <div class="flex items-center justify-between text-xs">
                <span [style.color]="'var(--text-secondary)'">Max Guards</span>
                <span class="font-semibold" [style.color]="'var(--text-primary)'">{{ p.max_guards || '∞' }}</span>
              </div>
              <div class="flex items-center justify-between text-xs">
                <span [style.color]="'var(--text-secondary)'">Max Sites</span>
                <span class="font-semibold" [style.color]="'var(--text-primary)'">{{ p.max_sites || '∞' }}</span>
              </div>
              <div class="flex items-center justify-between text-xs">
                <span [style.color]="'var(--text-secondary)'">Max Users</span>
                <span class="font-semibold" [style.color]="'var(--text-primary)'">{{ p.max_users || '∞' }}</span>
              </div>
            </div>
            @if (p.features?.length) {
              <div class="mt-3 pt-3 border-t" [style.borderColor]="'var(--border-default)'">
                <p class="text-[10px] font-semibold uppercase tracking-wide mb-1.5" [style.color]="'var(--text-tertiary)'">Included Modules</p>
                <div class="flex flex-wrap gap-1">
                  @for (f of p.features; track f) {
                    <span class="badge text-[10px] bg-[var(--brand-50)]" [style.color]="'var(--brand-700)'">{{ f }}</span>
                  }
                </div>
              </div>
            }
            <span class="badge text-[10px] mt-3" [ngClass]="p.is_active ? 'bg-emerald-50 text-emerald-600' : 'bg-gray-100 text-gray-500'">{{ p.is_active ? 'Active' : 'Inactive' }}</span>
          </div>
        }
      </div>
    }

    <g51-modal [open]="showModal()" [title]="editingPlan ? 'Edit Plan' : 'Create Plan'" maxWidth="600px" (closed)="showModal.set(false)">
      <div class="space-y-4">
        <div class="grid grid-cols-2 gap-4">
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Plan Name *</label>
            <input type="text" [(ngModel)]="form.name" class="input-base w-full" placeholder="e.g. Starter, Professional" /></div>
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Tier</label>
            <select [(ngModel)]="form.tier" class="input-base w-full"><option value="starter">Starter</option><option value="professional">Professional</option><option value="enterprise">Enterprise</option></select></div>
        </div>
        <div class="grid grid-cols-2 gap-4">
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Monthly Price (₦) *</label>
            <input type="number" [(ngModel)]="form.monthly_price" class="input-base w-full" /></div>
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Annual Price (₦)</label>
            <input type="number" [(ngModel)]="form.annual_price" class="input-base w-full" /></div>
        </div>
        <div class="grid grid-cols-3 gap-4">
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Max Guards</label>
            <input type="number" [(ngModel)]="form.max_guards" class="input-base w-full" placeholder="0 = unlimited" /></div>
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Max Sites</label>
            <input type="number" [(ngModel)]="form.max_sites" class="input-base w-full" placeholder="0 = unlimited" /></div>
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Max Users</label>
            <input type="number" [(ngModel)]="form.max_users" class="input-base w-full" placeholder="0 = unlimited" /></div>
        </div>
        <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Included Feature Modules</label>
          <div class="grid grid-cols-2 gap-1.5 max-h-40 overflow-y-auto p-2 border rounded-lg" [style.borderColor]="'var(--border-default)'">
            @for (m of allModules; track m) {
              <label class="flex items-center gap-1.5 text-xs cursor-pointer py-0.5"><input type="checkbox" [checked]="form.features.includes(m)" (change)="toggleModule(m)" class="rounded" /> {{ m }}</label>
            }
          </div>
        </div>
        <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Description</label>
          <textarea [(ngModel)]="form.description" rows="2" class="input-base w-full resize-none"></textarea></div>
        <label class="flex items-center gap-2 text-xs"><input type="checkbox" [(ngModel)]="form.is_active" class="rounded" /> Active (visible to tenants)</label>
      </div>
      <div modal-footer><button (click)="showModal.set(false)" class="btn-secondary">Cancel</button>
        <button (click)="savePlan()" class="btn-primary">{{ editingPlan ? 'Update' : 'Create' }} Plan</button></div>
    </g51-modal>
  `,
})
export class PlansComponent implements OnInit {
  private api = inject(ApiService); private toast = inject(ToastService);
  readonly CreditCardIcon = CreditCard; readonly PlusIcon = Plus; readonly EditIcon = Edit; readonly TrashIcon = Trash2;
  readonly loading = signal(true); readonly showModal = signal(false);
  readonly plans = signal<any[]>([]);
  editingPlan: any = null;
  form: any = { name: '', tier: 'starter', monthly_price: 0, annual_price: 0, max_guards: 0, max_sites: 0, max_users: 0, features: [], description: '', is_active: true };
  allModules = ['guard_management', 'site_management', 'client_management', 'scheduling', 'time_clock', 'live_tracker', 'guard_tour', 'daily_activity_report', 'incident_reporting', 'dispatcher_console', 'visitor_management', 'vehicle_patrol', 'parking', 'invoicing', 'payroll', 'analytics', 'chat', 'panic_button'];

  ngOnInit(): void { this.load(); }
  load(): void {
    this.loading.set(true);
    this.api.get<any>('/admin/plans').subscribe({ next: r => { this.plans.set(r.data?.plans || r.data || []); this.loading.set(false); }, error: () => this.loading.set(false) });
  }
  openCreate(): void { this.editingPlan = null; this.form = { name: '', tier: 'starter', monthly_price: 0, annual_price: 0, max_guards: 0, max_sites: 0, max_users: 0, features: [], description: '', is_active: true }; this.showModal.set(true); }
  openEdit(p: any): void { this.editingPlan = p; this.form = { ...p, features: [...(p.features || [])] }; this.showModal.set(true); }
  toggleModule(m: string): void { const i = this.form.features.indexOf(m); if (i >= 0) this.form.features.splice(i, 1); else this.form.features.push(m); }
  savePlan(): void {
    const url = this.editingPlan ? `/admin/plans/${this.editingPlan.id}` : '/admin/plans';
    const req = this.editingPlan ? this.api.put(url, this.form) : this.api.post(url, this.form);
    req.subscribe({ next: () => { this.showModal.set(false); this.toast.success(this.editingPlan ? 'Plan updated' : 'Plan created'); this.load(); } });
  }
  deletePlan(p: any): void { if (confirm(`Delete plan "${p.name}"?`)) { this.api.delete(`/admin/plans/${p.id}`).subscribe({ next: () => { this.toast.success('Deleted'); this.load(); } }); } }
}
