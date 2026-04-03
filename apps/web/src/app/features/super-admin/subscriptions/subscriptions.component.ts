import { Component, inject, signal, OnInit } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { NgClass, DecimalPipe } from '@angular/common';
import { LucideAngularModule, CreditCard, Plus, Edit, Trash2, CheckCircle, ArrowUpDown } from 'lucide-angular';
import { PageHeaderComponent } from '@shared/components/page-header/page-header.component';
import { ModalComponent } from '@shared/components/modal/modal.component';
import { EmptyStateComponent } from '@shared/components/empty-state/empty-state.component';
import { LoadingSpinnerComponent } from '@shared/components/loading-spinner/loading-spinner.component';
import { ApiService } from '@core/services/api.service';
import { ToastService } from '@core/services/toast.service';
import { ConfirmService } from '@core/services/confirm.service';

@Component({
  selector: 'g51-subscriptions',
  standalone: true,
  imports: [FormsModule, NgClass, DecimalPipe, LucideAngularModule, PageHeaderComponent, ModalComponent, EmptyStateComponent, LoadingSpinnerComponent],
  template: `
    <g51-page-header title="Plans & Subscriptions" subtitle="Manage subscription plans and active subscriptions">
      <button (click)="showPlanModal.set(true); editingPlan = null; resetPlanForm()" class="btn-primary flex items-center gap-2"><lucide-icon [img]="PlusIcon" [size]="16" /> New Plan</button>
    </g51-page-header>

    <div class="flex gap-1 mb-4">
      @for (tab of ['Plans', 'Active Subscriptions']; track tab) {
        <button (click)="activeTab.set(tab); loadTab()" class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors"
          [ngClass]="activeTab() === tab ? 'bg-[var(--color-brand-500)] text-white' : 'bg-[var(--surface-muted)]'"
          [style.color]="activeTab() !== tab ? 'var(--text-secondary)' : ''">{{ tab }}</button>
      }
    </div>

    @if (loading()) { <g51-loading /> }

    <!-- PLANS TAB -->
    @if (activeTab() === 'Plans' && !loading()) {
      @if (!plans().length) { <g51-empty-state title="No Plans" message="Create subscription plans." [icon]="CreditCardIcon" /> }
      @else {
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          @for (p of plans(); track p.id) {
            <div class="card p-5">
              <div class="flex items-center justify-between mb-2">
                <h3 class="text-base font-bold font-heading" [style.color]="'var(--text-primary)'">{{ p.name }}</h3>
                <div class="flex gap-1">
                  <button (click)="editPlan(p)" class="p-1 rounded hover:bg-gray-100"><lucide-icon [img]="EditIcon" [size]="14" [style.color]="'var(--text-tertiary)'" /></button>
                  <button (click)="deletePlan(p)" class="p-1 rounded hover:bg-red-50"><lucide-icon [img]="TrashIcon" [size]="14" [style.color]="'var(--color-danger)'" /></button>
                </div>
              </div>
              <p class="text-2xl font-bold mb-1" [style.color]="'var(--color-brand-500)'">₦{{ p.monthly_price | number:'1.0-0' }}<span class="text-xs font-normal" [style.color]="'var(--text-tertiary)'">/mo</span></p>
              <p class="text-xs mb-3" [style.color]="'var(--text-tertiary)'">{{ p.description || '' }}</p>
              <div class="space-y-1 text-xs">
                <div class="flex justify-between"><span [style.color]="'var(--text-secondary)'">Max Guards</span><span class="font-medium" [style.color]="'var(--text-primary)'">{{ p.max_guards || '∞' }}</span></div>
                <div class="flex justify-between"><span [style.color]="'var(--text-secondary)'">Max Sites</span><span class="font-medium" [style.color]="'var(--text-primary)'">{{ p.max_sites || '∞' }}</span></div>
                <div class="flex justify-between"><span [style.color]="'var(--text-secondary)'">Max Users</span><span class="font-medium" [style.color]="'var(--text-primary)'">{{ p.max_users || '∞' }}</span></div>
                <div class="flex justify-between"><span [style.color]="'var(--text-secondary)'">Tier</span><span class="font-medium" [style.color]="'var(--text-primary)'">{{ p.tier }}</span></div>
              </div>
              @if (p.included_modules?.length) {
                <div class="mt-3 pt-3 border-t" [style.borderColor]="'var(--border-default)'">
                  <p class="text-[10px] font-semibold mb-1" [style.color]="'var(--text-tertiary)'">INCLUDED MODULES</p>
                  <div class="flex flex-wrap gap-1">
                    @for (m of p.included_modules; track m) {
                      <span class="badge text-[10px] bg-emerald-50 text-emerald-600">{{ m }}</span>
                    }
                  </div>
                </div>
              }
            </div>
          }
        </div>
      }
    }

    <!-- SUBSCRIPTIONS TAB -->
    @if (activeTab() === 'Active Subscriptions' && !loading()) {
      @if (!subscriptions().length) { <g51-empty-state title="No Subscriptions" message="No active subscriptions." [icon]="CreditCardIcon" /> }
      @else {
        <div class="card overflow-hidden">
          <table class="w-full text-xs">
            <thead><tr [style.background]="'var(--surface-muted)'">
              <th class="text-left py-2.5 px-4 font-semibold" [style.color]="'var(--text-secondary)'">Company</th>
              <th class="text-left py-2.5 px-4 font-semibold" [style.color]="'var(--text-secondary)'">Plan</th>
              <th class="text-left py-2.5 px-4 font-semibold" [style.color]="'var(--text-secondary)'">Status</th>
              <th class="text-left py-2.5 px-4 font-semibold" [style.color]="'var(--text-secondary)'">Amount</th>
              <th class="text-left py-2.5 px-4 font-semibold" [style.color]="'var(--text-secondary)'">Period End</th>
              <th class="text-right py-2.5 px-4 font-semibold" [style.color]="'var(--text-secondary)'">Actions</th>
            </tr></thead>
            <tbody>
              @for (s of subscriptions(); track s.id) {
                <tr class="border-t hover:bg-[var(--surface-hover)]" [style.borderColor]="'var(--border-default)'">
                  <td class="py-2.5 px-4 font-medium" [style.color]="'var(--text-primary)'">{{ s.tenant_name || s.tenant_id?.slice(0,8) }}</td>
                  <td class="py-2.5 px-4" [style.color]="'var(--text-secondary)'">{{ s.plan_name || '—' }}</td>
                  <td class="py-2.5 px-4"><span class="badge text-[10px]" [ngClass]="s.status === 'active' ? 'bg-emerald-50 text-emerald-600' : s.status === 'past_due' ? 'bg-red-50 text-red-600' : 'bg-gray-100 text-gray-500'">{{ s.status }}</span></td>
                  <td class="py-2.5 px-4" [style.color]="'var(--text-primary)'">₦{{ s.amount | number:'1.0-0' }}</td>
                  <td class="py-2.5 px-4" [style.color]="'var(--text-tertiary)'">{{ s.current_period_end || '—' }}</td>
                  <td class="py-2.5 px-4 text-right">
                    <button (click)="cancelSub(s)" class="btn-secondary text-[10px] py-1 px-2">Cancel</button>
                  </td>
                </tr>
              }
            </tbody>
          </table>
        </div>
      }
    }

    <!-- PLAN CREATE/EDIT MODAL -->
    <g51-modal [open]="showPlanModal()" [title]="editingPlan ? 'Edit Plan' : 'Create Plan'" maxWidth="560px" (closed)="showPlanModal.set(false)">
      <div class="space-y-3">
        <div class="grid grid-cols-2 gap-3">
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Plan Name *</label><input type="text" [(ngModel)]="planForm.name" class="input-base w-full" /></div>
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Tier</label>
            <select [(ngModel)]="planForm.tier" class="input-base w-full"><option value="starter">Starter</option><option value="professional">Professional</option><option value="enterprise">Enterprise</option></select></div>
        </div>
        <div class="grid grid-cols-2 gap-3">
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Monthly Price (₦) *</label><input type="number" [(ngModel)]="planForm.monthly_price" class="input-base w-full" /></div>
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Annual Price (₦)</label><input type="number" [(ngModel)]="planForm.annual_price" class="input-base w-full" /></div>
        </div>
        <div class="grid grid-cols-3 gap-3">
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Max Guards</label><input type="number" [(ngModel)]="planForm.max_guards" class="input-base w-full" placeholder="0 = unlimited" /></div>
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Max Sites</label><input type="number" [(ngModel)]="planForm.max_sites" class="input-base w-full" placeholder="0 = unlimited" /></div>
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Max Users</label><input type="number" [(ngModel)]="planForm.max_users" class="input-base w-full" placeholder="0 = unlimited" /></div>
        </div>
        <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Description</label><textarea [(ngModel)]="planForm.description" rows="2" class="input-base w-full resize-none"></textarea></div>
        <div>
          <label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Included Modules</label>
          <div class="grid grid-cols-3 gap-2">
            @for (m of allModules; track m) {
              <label class="flex items-center gap-1.5 text-xs cursor-pointer">
                <input type="checkbox" [checked]="planForm.included_modules.includes(m)" (change)="toggleModule(m)" class="rounded" />
                <span [style.color]="'var(--text-secondary)'">{{ m }}</span>
              </label>
            }
          </div>
        </div>
      </div>
      <div modal-footer><button (click)="showPlanModal.set(false)" class="btn-secondary">Cancel</button>
        <button (click)="savePlan()" class="btn-primary">{{ editingPlan ? 'Update' : 'Create' }}</button></div>
    </g51-modal>
  `,
})
export class SubscriptionsComponent implements OnInit {
  private api = inject(ApiService); private toast = inject(ToastService);
  private confirmSvc = inject(ConfirmService);
  readonly CreditCardIcon = CreditCard; readonly PlusIcon = Plus; readonly EditIcon = Edit; readonly TrashIcon = Trash2;
  readonly activeTab = signal('Plans'); readonly loading = signal(true); readonly showPlanModal = signal(false);
  readonly plans = signal<any[]>([]); readonly subscriptions = signal<any[]>([]);
  editingPlan: any = null;
  planForm: any = { name: '', tier: 'starter', monthly_price: 0, annual_price: 0, max_guards: 0, max_sites: 0, max_users: 0, description: '', included_modules: [] as string[] };
  allModules = ['scheduling', 'attendance', 'incidents', 'dispatch', 'tours', 'parking', 'visitors', 'invoicing', 'payroll', 'analytics', 'chat', 'vehicle_patrol', 'geofencing', 'panic_button', 'custom_reports'];

  ngOnInit(): void { this.loadTab(); }
  loadTab(): void {
    this.loading.set(true);
    if (this.activeTab() === 'Plans') {
      this.api.get<any>('/admin/plans').subscribe({ next: r => { this.plans.set(r.data?.plans || r.data || []); this.loading.set(false); }, error: () => this.loading.set(false) });
    } else {
      this.api.get<any>('/admin/subscriptions').subscribe({ next: r => { this.subscriptions.set(r.data?.subscriptions || r.data || []); this.loading.set(false); }, error: () => this.loading.set(false) });
    }
  }
  resetPlanForm(): void { this.planForm = { name: '', tier: 'starter', monthly_price: 0, annual_price: 0, max_guards: 0, max_sites: 0, max_users: 0, description: '', included_modules: [] }; }
  editPlan(p: any): void { this.editingPlan = p; this.planForm = { ...p, included_modules: [...(p.included_modules || [])] }; this.showPlanModal.set(true); }
  toggleModule(m: string): void { const idx = this.planForm.included_modules.indexOf(m); if (idx >= 0) this.planForm.included_modules.splice(idx, 1); else this.planForm.included_modules.push(m); }
  savePlan(): void {
    const url = this.editingPlan ? `/admin/plans/${this.editingPlan.id}` : '/admin/plans';
    const req = this.editingPlan ? this.api.put(url, this.planForm) : this.api.post(url, this.planForm);
    req.subscribe({ next: () => { this.showPlanModal.set(false); this.toast.success(this.editingPlan ? 'Plan updated' : 'Plan created'); this.loadTab(); } });
  }
  async deletePlan(p: any): Promise<void> { if (await this.confirmSvc.delete(`Delete plan "${p.name}"?`)) this.api.delete(`/admin/plans/${p.id}`).subscribe({ next: () => { this.toast.success('Deleted'); this.loadTab(); } }); }
  async cancelSub(s: any): Promise<void> { if (await this.confirmSvc.show({ title: 'Cancel Subscription?', message: 'This will end the subscription immediately.', confirmText: 'Cancel Subscription', variant: 'danger' })) this.api.post(`/admin/subscriptions/${s.id}/cancel`, {}).subscribe({ next: () => { this.toast.success('Cancelled'); this.loadTab(); } }); }
}
