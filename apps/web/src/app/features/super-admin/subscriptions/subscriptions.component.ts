import { Component, inject, signal, OnInit } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { NgClass, DecimalPipe } from '@angular/common';
import { LucideAngularModule, CreditCard, Plus, Trash2, Copy } from 'lucide-angular';
import { PageHeaderComponent } from '@shared/components/page-header/page-header.component';
import { ModalComponent } from '@shared/components/modal/modal.component';
import { LoadingSpinnerComponent } from '@shared/components/loading-spinner/loading-spinner.component';
import { ApiService } from '@core/services/api.service';
import { ToastService } from '@core/services/toast.service';

@Component({
  selector: 'g51-sa-subscriptions',
  standalone: true,
  imports: [FormsModule, NgClass, DecimalPipe, LucideAngularModule, PageHeaderComponent, ModalComponent, LoadingSpinnerComponent],
  template: `
    <g51-page-header title="Subscription Plans" subtitle="Manage pricing tiers">
      <button class="btn-primary flex items-center gap-2" (click)="showCreate.set(true)"><lucide-icon [img]="PlusIcon" [size]="16" /> Add Plan</button>
    </g51-page-header>
    @if (loading()) { <g51-loading /> } @else {
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @for (p of plans(); track p.id) {
          <div class="card p-5">
            <div class="flex items-center justify-between mb-3">
              <h3 class="text-base font-bold" [style.color]="'var(--text-primary)'">{{ p.name }}</h3>
              <span class="badge text-[10px]" [ngClass]="p.is_active ? 'bg-emerald-50 text-emerald-600' : 'bg-gray-100 text-gray-500'">{{ p.is_active ? 'Active' : 'Inactive' }}</span>
            </div>
            <p class="text-2xl font-bold mb-1" [style.color]="'var(--color-brand-500)'">₦{{ p.price_monthly | number:'1.0-0' }}<span class="text-xs font-normal" [style.color]="'var(--text-tertiary)'">/mo</span></p>
            <p class="text-xs mb-3" [style.color]="'var(--text-tertiary)'">{{ p.tier }} · Max {{ p.max_guards || '∞' }} guards · {{ p.max_sites || '∞' }} sites</p>
            <div class="flex gap-1">
              <button (click)="duplicate(p)" class="btn-secondary text-xs py-1 px-2"><lucide-icon [img]="CopyIcon" [size]="12" /></button>
              <button (click)="confirmDelete(p)" class="btn-secondary text-xs py-1 px-2 text-red-500"><lucide-icon [img]="TrashIcon" [size]="12" /></button>
            </div>
          </div>
        }
      </div>
    }
    <g51-modal [open]="showCreate()" title="Create Plan" maxWidth="480px" (closed)="showCreate.set(false)">
      <div class="space-y-3">
        <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Plan Name *</label><input type="text" [(ngModel)]="form.name" class="input-base w-full" /></div>
        <div class="grid grid-cols-2 gap-3">
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Monthly Price (₦)</label><input type="number" [(ngModel)]="form.price_monthly" class="input-base w-full" /></div>
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Tier</label>
            <select [(ngModel)]="form.tier" class="input-base w-full"><option value="starter">Starter</option><option value="professional">Professional</option><option value="business">Business</option><option value="enterprise">Enterprise</option></select></div>
        </div>
        <div class="grid grid-cols-2 gap-3">
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Max Guards</label><input type="number" [(ngModel)]="form.max_guards" class="input-base w-full" /></div>
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Max Sites</label><input type="number" [(ngModel)]="form.max_sites" class="input-base w-full" /></div>
        </div>
      </div>
      <div modal-footer><button (click)="showCreate.set(false)" class="btn-secondary">Cancel</button><button (click)="onCreate()" class="btn-primary">Create</button></div>
    </g51-modal>
  `,
})
export class SubscriptionsComponent implements OnInit {
  private api = inject(ApiService); private toast = inject(ToastService);
  readonly CreditCardIcon = CreditCard; readonly PlusIcon = Plus; readonly TrashIcon = Trash2; readonly CopyIcon = Copy;
  readonly plans = signal<any[]>([]); readonly loading = signal(true); readonly showCreate = signal(false);
  form: any = { name: '', price_monthly: 0, tier: 'starter', max_guards: 10, max_sites: 5 };
  ngOnInit(): void { this.load(); }
  load(): void { this.loading.set(true); this.api.get<any>('/admin/subscriptions/plans').subscribe({ next: res => { this.plans.set(res.data?.plans || res.data || []); this.loading.set(false); }, error: () => this.loading.set(false) }); }
  onCreate(): void { this.api.post('/admin/subscriptions/plans', this.form).subscribe({ next: () => { this.showCreate.set(false); this.toast.success('Plan created'); this.load(); } }); }
  duplicate(p: any): void { this.api.post(`/admin/subscriptions/plans/${p.id}/duplicate`, {}).subscribe({ next: () => { this.toast.success('Plan duplicated'); this.load(); } }); }
  confirmDelete(p: any): void { if (confirm(`Delete plan "${p.name}"?`)) { this.api.delete(`/admin/subscriptions/plans/${p.id}`).subscribe({ next: () => { this.toast.success('Plan deleted'); this.load(); } }); } }
}
