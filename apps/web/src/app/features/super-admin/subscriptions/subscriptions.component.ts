import { Component, inject, signal, OnInit } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { NgClass, DecimalPipe } from '@angular/common';
import { LucideAngularModule, CreditCard, Plus, Edit, Trash2, Check, Crown, Zap, Building2, Users, MapPin, Shield, Package, Star } from 'lucide-angular';
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
    <g51-page-header title="Subscriptions & Plans" subtitle="Manage pricing plans and active subscriptions">
      <button (click)="openPlanCreate()" class="btn-primary flex items-center gap-2"><lucide-icon [img]="PlusIcon" [size]="16" /> New Plan</button>
    </g51-page-header>

    <div class="flex gap-1 mb-6">
      @for (tab of ['Plans', 'Active Subscriptions']; track tab) {
        <button (click)="activeTab.set(tab); loadTab()" class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors"
          [ngClass]="activeTab() === tab ? 'bg-[var(--color-brand-500)] text-white' : 'bg-[var(--surface-muted)]'"
          [style.color]="activeTab() !== tab ? 'var(--text-secondary)' : ''">{{ tab }}</button>
      }
    </div>

    @if (loading()) { <g51-loading /> }

    <!-- PLANS TAB — Modern pricing cards -->
    @if (activeTab() === 'Plans' && !loading()) {
      @if (!plans().length) { <g51-empty-state title="No Plans" message="Create your first subscription plan." [icon]="PackageIcon" /> }
      @else {
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
          @for (plan of plans(); track plan.id; let i = $index) {
            <div class="card overflow-hidden relative" [ngClass]="plan.is_popular ? 'ring-2 ring-[var(--color-brand-500)]' : ''">
              @if (plan.is_popular) {
                <div class="absolute top-0 right-0 px-3 py-1 text-[10px] font-bold text-white rounded-bl-lg" [style.background]="'var(--color-brand-500)'">POPULAR</div>
              }
              <div class="p-5 pb-4">
                <div class="flex items-center gap-2 mb-2">
                  <div class="h-9 w-9 rounded-lg flex items-center justify-center" [style.background]="planColors[i % planColors.length] + '15'" [style.color]="planColors[i % planColors.length]">
                    <lucide-icon [img]="planIcons[i % planIcons.length]" [size]="18" />
                  </div>
                  <div>
                    <p class="text-base font-bold font-heading" [style.color]="'var(--text-primary)'">{{ plan.name }}</p>
                    <p class="text-[10px]" [style.color]="'var(--text-tertiary)'">{{ plan.description || '' }}</p>
                  </div>
                </div>
                <div class="flex items-baseline gap-1 mt-3 mb-4">
                  <span class="text-3xl font-bold font-heading" [style.color]="'var(--text-primary)'">₦{{ plan.monthly_price | number:'1.0-0' }}</span>
                  <span class="text-xs" [style.color]="'var(--text-tertiary)'">/month</span>
                </div>
                <div class="space-y-2 mb-4">
                  <div class="flex items-center gap-2 text-xs" [style.color]="'var(--text-secondary)'">
                    <lucide-icon [img]="ShieldIcon" [size]="13" [style.color]="planColors[i % planColors.length]" /> Up to {{ plan.max_guards === -1 ? 'Unlimited' : plan.max_guards }} guards
                  </div>
                  <div class="flex items-center gap-2 text-xs" [style.color]="'var(--text-secondary)'">
                    <lucide-icon [img]="MapPinIcon" [size]="13" [style.color]="planColors[i % planColors.length]" /> Up to {{ plan.max_sites === -1 ? 'Unlimited' : plan.max_sites }} sites
                  </div>
                  <div class="flex items-center gap-2 text-xs" [style.color]="'var(--text-secondary)'">
                    <lucide-icon [img]="UsersIcon" [size]="13" [style.color]="planColors[i % planColors.length]" /> Up to {{ plan.max_users === -1 ? 'Unlimited' : plan.max_users }} users
                  </div>
                </div>
              </div>
              <!-- Included modules -->
              <div class="border-t px-5 py-3" [style.borderColor]="'var(--border-default)'" [style.background]="'var(--surface-muted)'">
                <p class="text-[10px] font-semibold uppercase tracking-wider mb-2" [style.color]="'var(--text-tertiary)'">Included Modules</p>
                <div class="flex flex-wrap gap-1">
                  @for (m of (plan.modules || []); track m) {
                    <span class="text-[9px] px-1.5 py-0.5 rounded-full font-medium" [style.background]="planColors[i % planColors.length] + '15'" [style.color]="planColors[i % planColors.length]">{{ m }}</span>
                  }
                </div>
              </div>
              <!-- Footer -->
              <div class="border-t px-5 py-3 flex items-center justify-between" [style.borderColor]="'var(--border-default)'">
                <span class="text-[10px]" [style.color]="'var(--text-tertiary)'">{{ plan.subscriber_count || 0 }} subscribers</span>
                <div class="flex gap-1">
                  <button (click)="editPlan(plan)" class="p-1.5 rounded-lg hover:bg-[var(--surface-hover)]"><lucide-icon [img]="EditIcon" [size]="14" [style.color]="'var(--text-tertiary)'" /></button>
                  <button (click)="deletePlan(plan)" class="p-1.5 rounded-lg hover:bg-red-50"><lucide-icon [img]="TrashIcon" [size]="14" class="text-red-400" /></button>
                </div>
              </div>
            </div>
          }
        </div>
      }
    }

    <!-- ACTIVE SUBSCRIPTIONS TAB -->
    @if (activeTab() === 'Active Subscriptions' && !loading()) {
      @if (!subscriptions().length) { <g51-empty-state title="No Subscriptions" message="No active subscriptions." [icon]="CreditCardIcon" /> }
      @else {
        <div class="card overflow-hidden">
          <table class="w-full text-xs">
            <thead><tr [style.background]="'var(--surface-muted)'">
              <th class="text-left py-2.5 px-4 font-semibold" [style.color]="'var(--text-secondary)'">Company</th>
              <th class="text-left py-2.5 px-4 font-semibold" [style.color]="'var(--text-secondary)'">Plan</th>
              <th class="text-left py-2.5 px-4 font-semibold" [style.color]="'var(--text-secondary)'">Amount</th>
              <th class="text-left py-2.5 px-4 font-semibold" [style.color]="'var(--text-secondary)'">Status</th>
              <th class="text-left py-2.5 px-4 font-semibold" [style.color]="'var(--text-secondary)'">Since</th>
              <th class="text-center py-2.5 px-4 font-semibold" [style.color]="'var(--text-secondary)'">Actions</th>
            </tr></thead>
            <tbody>
              @for (s of subscriptions(); track s.id) {
                <tr class="border-t hover:bg-[var(--surface-hover)]" [style.borderColor]="'var(--border-default)'">
                  <td class="py-2.5 px-4 font-medium" [style.color]="'var(--text-primary)'">{{ s.tenant_name || s.tenant_id?.slice(0,8) }}</td>
                  <td class="py-2.5 px-4" [style.color]="'var(--text-secondary)'">{{ s.plan_name || '—' }}</td>
                  <td class="py-2.5 px-4 font-medium" [style.color]="'var(--text-primary)'">₦{{ s.amount || s.monthly_price | number:'1.0-0' }}</td>
                  <td class="py-2.5 px-4"><span class="badge text-[10px]" [ngClass]="s.status === 'active' ? 'bg-emerald-50 text-emerald-600' : s.status === 'trialing' ? 'bg-blue-50 text-blue-600' : 'bg-red-50 text-red-600'">{{ s.status }}</span></td>
                  <td class="py-2.5 px-4" [style.color]="'var(--text-tertiary)'">{{ s.created_at?.slice(0,10) || s.start_date || '' }}</td>
                  <td class="py-2.5 px-4 text-center">
                    @if (s.status === 'active' || s.status === 'trialing') {
                      <button (click)="cancelSub(s)" class="text-red-500 text-[10px] font-medium hover:underline">Cancel</button>
                    }
                  </td>
                </tr>
              }
            </tbody>
          </table>
        </div>
      }
    }

    <!-- Plan Create/Edit Modal -->
    <g51-modal [open]="showPlanModal()" [title]="editingPlan() ? 'Edit Plan' : 'Create New Plan'" maxWidth="600px" (closed)="showPlanModal.set(false)">
      <div class="space-y-3">
        <div class="grid grid-cols-2 gap-3">
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Plan Name *</label><input type="text" [(ngModel)]="planForm.name" class="input-base w-full" placeholder="e.g. Starter" /></div>
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Monthly Price (₦) *</label><input type="number" [(ngModel)]="planForm.monthly_price" class="input-base w-full" /></div>
        </div>
        <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Description</label><input type="text" [(ngModel)]="planForm.description" class="input-base w-full" placeholder="Brief plan description" /></div>
        <div class="grid grid-cols-3 gap-3">
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Max Guards</label><input type="number" [(ngModel)]="planForm.max_guards" class="input-base w-full" /><p class="text-[10px] mt-0.5" [style.color]="'var(--text-tertiary)'">-1 = unlimited</p></div>
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Max Sites</label><input type="number" [(ngModel)]="planForm.max_sites" class="input-base w-full" /></div>
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Max Users</label><input type="number" [(ngModel)]="planForm.max_users" class="input-base w-full" /></div>
        </div>
        <div>
          <label class="block text-xs font-medium mb-2" [style.color]="'var(--text-secondary)'">Included Modules</label>
          <div class="grid grid-cols-3 gap-1">
            @for (m of allModules; track m) {
              <label class="flex items-center gap-1.5 text-xs p-1.5 rounded cursor-pointer hover:bg-[var(--surface-muted)]">
                <input type="checkbox" [checked]="planForm.modules.includes(m)" (change)="toggleModule(m)" class="rounded" /> {{ m }}
              </label>
            }
          </div>
        </div>
        <div class="flex gap-4">
          <label class="flex items-center gap-2 text-xs"><input type="checkbox" [(ngModel)]="planForm.is_active" class="rounded" /> Active</label>
          <label class="flex items-center gap-2 text-xs"><input type="checkbox" [(ngModel)]="planForm.is_popular" class="rounded" /> Mark as Popular</label>
        </div>
      </div>
      <div modal-footer><button (click)="showPlanModal.set(false)" class="btn-secondary">Cancel</button>
        <button (click)="savePlan()" class="btn-primary">{{ editingPlan() ? 'Update Plan' : 'Create Plan' }}</button></div>
    </g51-modal>
  `,
})
export class SubscriptionsComponent implements OnInit {
  private api = inject(ApiService); private toast = inject(ToastService); private confirmSvc = inject(ConfirmService);
  readonly CreditCardIcon = CreditCard; readonly PlusIcon = Plus; readonly EditIcon = Edit; readonly TrashIcon = Trash2;
  readonly CheckIcon = Check; readonly PackageIcon = Package; readonly ShieldIcon = Shield; readonly MapPinIcon = MapPin; readonly UsersIcon = Users;
  readonly activeTab = signal('Plans'); readonly loading = signal(true);
  readonly showPlanModal = signal(false); readonly editingPlan = signal(false);
  readonly plans = signal<any[]>([]); readonly subscriptions = signal<any[]>([]);
  planColors = ['#3B82F6', '#8B5CF6', '#F59E0B', '#10B981', '#EF4444', '#EC4899'];
  planIcons = [Zap, Crown, Star, Building2, Shield, Package];
  allModules = ['Dashboard', 'Guards', 'Sites', 'Clients', 'Scheduling', 'Attendance', 'Tracking', 'Incidents', 'Dispatch', 'Invoicing', 'Payroll', 'Reports', 'Visitors', 'Tours', 'Passdowns', 'Chat', 'Parking', 'Vehicle Patrol', 'Analytics', 'Licenses'];
  planForm: any = this.blankPlanForm();

  blankPlanForm() { return { name: '', description: '', monthly_price: 0, max_guards: 50, max_sites: 10, max_users: 20, modules: [...this.allModules], is_active: true, is_popular: false, id: '' }; }

  ngOnInit(): void { this.loadTab(); }

  loadTab(): void {
    this.loading.set(true);
    if (this.activeTab() === 'Plans') {
      this.api.get<any>('/admin/plans').subscribe({ next: r => { this.plans.set(r.data?.plans || r.data || []); this.loading.set(false); }, error: () => this.loading.set(false) });
    } else {
      this.api.get<any>('/admin/subscriptions').subscribe({ next: r => { this.subscriptions.set(r.data?.subscriptions || r.data || []); this.loading.set(false); }, error: () => this.loading.set(false) });
    }
  }

  openPlanCreate(): void { this.planForm = this.blankPlanForm(); this.editingPlan.set(false); this.showPlanModal.set(true); }
  editPlan(p: any): void { this.planForm = { ...p, modules: p.modules || [...this.allModules] }; this.editingPlan.set(true); this.showPlanModal.set(true); }
  toggleModule(m: string): void { const i = this.planForm.modules.indexOf(m); i >= 0 ? this.planForm.modules.splice(i, 1) : this.planForm.modules.push(m); }

  savePlan(): void {
    const obs = this.editingPlan() ? this.api.put(`/admin/plans/${this.planForm.id}`, this.planForm) : this.api.post('/admin/plans', this.planForm);
    obs.subscribe({ next: () => { this.showPlanModal.set(false); this.toast.success(this.editingPlan() ? 'Plan updated' : 'Plan created'); this.loadTab(); } });
  }

  async deletePlan(p: any): Promise<void> { if (await this.confirmSvc.delete(p.name)) this.api.delete(`/admin/plans/${p.id}`).subscribe({ next: () => { this.toast.success('Deleted'); this.loadTab(); } }); }
  async cancelSub(s: any): Promise<void> { if (await this.confirmSvc.show({ title: 'Cancel Subscription?', message: `This will end ${s.tenant_name || 'this company'}'s subscription immediately.`, confirmText: 'Cancel Subscription', variant: 'danger' })) this.api.post(`/admin/subscriptions/${s.id}/cancel`, {}).subscribe({ next: () => { this.toast.success('Cancelled'); this.loadTab(); } }); }
}
