import { Component, inject, signal, OnInit } from '@angular/core';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { LucideAngularModule, ArrowLeft, Save, Loader2 } from 'lucide-angular';
import { PageHeaderComponent } from '@shared/components/page-header/page-header.component';
import { ApiService } from '@core/services/api.service';
import { ToastService } from '@core/services/toast.service';

@Component({
  selector: 'g51-client-form',
  standalone: true,
  imports: [RouterLink, FormsModule, LucideAngularModule, PageHeaderComponent],
  template: `
    <g51-page-header [title]="isEdit() ? 'Edit Client' : 'Add New Client'" subtitle="Enter client company and contact details">
      <a routerLink="/clients" class="btn-secondary flex items-center gap-1.5"><lucide-icon [img]="ArrowLeftIcon" [size]="16" /> Back</a>
    </g51-page-header>

    <form (ngSubmit)="onSubmit()" class="max-w-3xl space-y-6">
      <div class="card p-5">
        <h3 class="text-sm font-semibold mb-4" [style.color]="'var(--text-primary)'">Company Information</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div class="sm:col-span-2"><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Company Name *</label>
            <input type="text" [(ngModel)]="form.company_name" name="company_name" class="input-base w-full" required /></div>
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Contact Name *</label>
            <input type="text" [(ngModel)]="form.contact_name" name="contact_name" class="input-base w-full" required /></div>
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Contact Phone *</label>
            <input type="tel" [(ngModel)]="form.contact_phone" name="contact_phone" class="input-base w-full" required /></div>
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Contact Email *</label>
            <input type="email" [(ngModel)]="form.contact_email" name="contact_email" class="input-base w-full" required /></div>
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">City</label>
            <input type="text" [(ngModel)]="form.city" name="city" class="input-base w-full" /></div>
        </div>
      </div>

      <div class="card p-5">
        <h3 class="text-sm font-semibold mb-4" [style.color]="'var(--text-primary)'">Contract & Billing</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Contract Start</label>
            <input type="date" [(ngModel)]="form.contract_start" name="contract_start" class="input-base w-full" /></div>
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Contract End</label>
            <input type="date" [(ngModel)]="form.contract_end" name="contract_end" class="input-base w-full" /></div>
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Billing Type</label>
            <select [(ngModel)]="form.billing_type" name="billing_type" class="input-base w-full">
              <option value="">—</option><option value="hourly">Hourly</option><option value="daily">Daily</option><option value="monthly">Monthly</option><option value="contract">Contract</option></select></div>
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Billing Rate (₦)</label>
            <input type="number" [(ngModel)]="form.billing_rate" name="billing_rate" class="input-base w-full" /></div>
        </div>
      </div>

      <button type="submit" [disabled]="saving()" class="btn-primary flex items-center gap-2">
        @if (saving()) { <lucide-icon [img]="Loader2Icon" [size]="16" class="animate-spin" /> }
        <lucide-icon [img]="SaveIcon" [size]="16" /> {{ isEdit() ? 'Update Client' : 'Create Client' }}
      </button>
    </form>
  `,
})
export class ClientFormComponent implements OnInit {
  private api = inject(ApiService); private router = inject(Router);
  private route = inject(ActivatedRoute); private toast = inject(ToastService);
  readonly ArrowLeftIcon = ArrowLeft; readonly SaveIcon = Save; readonly Loader2Icon = Loader2;
  readonly isEdit = signal(false); readonly saving = signal(false);
  form: Record<string, any> = { company_name: '', contact_name: '', contact_email: '', contact_phone: '', city: '', contract_start: '', contract_end: '', billing_type: '', billing_rate: null };

  ngOnInit(): void {
    const id = this.route.snapshot.params['id'];
    if (id) { this.isEdit.set(true); this.api.get<any>(`/clients/${id}`).subscribe({ next: res => { if (res.data?.client) Object.assign(this.form, res.data.client); } }); }
  }
  onSubmit(): void {
    this.saving.set(true);
    const req = this.isEdit() ? this.api.put(`/clients/${this.route.snapshot.params['id']}`, this.form) : this.api.post('/clients', this.form);
    req.subscribe({ next: () => { this.saving.set(false); this.toast.success(this.isEdit() ? 'Client updated' : 'Client created'); this.router.navigate(['/clients']); }, error: () => this.saving.set(false) });
  }
}
