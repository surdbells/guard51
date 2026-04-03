import { Component, inject, signal, OnInit } from '@angular/core';
import { Router, ActivatedRoute } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { LucideAngularModule, Save, ArrowLeft } from 'lucide-angular';
import { PageHeaderComponent } from '@shared/components/page-header/page-header.component';
import { SearchableSelectComponent, SelectOption } from '@shared/components/searchable-select/searchable-select.component';
import { ApiService } from '@core/services/api.service';
import { ToastService } from '@core/services/toast.service';

@Component({
  selector: 'g51-client-form',
  standalone: true,
  imports: [FormsModule, LucideAngularModule, PageHeaderComponent, SearchableSelectComponent],
  template: `
    <g51-page-header [title]="isEdit() ? 'Edit Client' : 'Add New Client'" subtitle="Client company details and contract">
      <button class="btn-secondary flex items-center gap-2" (click)="goBack()"><lucide-icon [img]="ArrowLeftIcon" [size]="14" /> Back</button>
    </g51-page-header>
    <div class="card p-6 max-w-3xl">
      <h3 class="text-sm font-semibold mb-4" [style.color]="'var(--text-primary)'">Company Information</h3>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
        <div class="sm:col-span-2"><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Company Name *</label>
          <input type="text" [(ngModel)]="form.company_name" class="input-base w-full" required /></div>
        <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Industry / Sector</label>
          <g51-searchable-select [(ngModel)]="form.industry" [options]="industryOptions" placeholder="Select industry" /></div>
        <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">City</label>
          <input type="text" [(ngModel)]="form.city" class="input-base w-full" /></div>
        <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">State</label>
          <g51-searchable-select [(ngModel)]="form.state" [options]="stateOptions" placeholder="Select state" /></div>
      </div>
      <div class="mb-6"><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Address</label>
        <textarea [(ngModel)]="form.address" rows="2" class="input-base w-full resize-none"></textarea></div>

      <h3 class="text-sm font-semibold mb-4 mt-6" [style.color]="'var(--text-primary)'">Primary Contact</h3>
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Contact Name *</label>
          <input type="text" [(ngModel)]="form.contact_name" class="input-base w-full" required /></div>
        <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Contact Phone *</label>
          <input type="tel" [(ngModel)]="form.contact_phone" class="input-base w-full" required /></div>
        <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Contact Email *</label>
          <input type="email" [(ngModel)]="form.contact_email" class="input-base w-full" required /></div>
      </div>

      <h3 class="text-sm font-semibold mb-4 mt-6" [style.color]="'var(--text-primary)'">Contract & Billing</h3>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
        <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Contract Start</label>
          <input type="date" [(ngModel)]="form.contract_start" class="input-base w-full" /></div>
        <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Contract End</label>
          <input type="date" [(ngModel)]="form.contract_end" class="input-base w-full" /></div>
        <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Billing Type</label>
          <select [(ngModel)]="form.billing_type" class="input-base w-full">
            <option value="">Select</option><option value="hourly">Hourly</option><option value="daily">Daily</option><option value="monthly">Monthly</option><option value="per_guard">Per Guard</option><option value="contract">Contract</option><option value="fixed">Fixed</option><option value="custom">Custom</option></select></div>
        <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Billing Rate (₦)</label>
          <input type="number" [(ngModel)]="form.billing_rate" class="input-base w-full" placeholder="0.00" /></div>
      </div>

      <div class="mb-6"><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Notes</label>
        <textarea [(ngModel)]="form.notes" rows="3" class="input-base w-full resize-none"></textarea></div>

      <div class="flex justify-end gap-3 pt-4 border-t" [style.borderColor]="'var(--border-default)'">
        <button (click)="goBack()" class="btn-secondary">Cancel</button>
        <button (click)="onSave()" class="btn-primary flex items-center gap-2" [disabled]="saving()">
          <lucide-icon [img]="SaveIcon" [size]="14" /> {{ isEdit() ? 'Update Client' : 'Create Client' }}
        </button>
      </div>
    </div>
  `,
})
export class ClientFormComponent implements OnInit {
  private api = inject(ApiService); private toast = inject(ToastService);
  private router = inject(Router); private route = inject(ActivatedRoute);
  readonly SaveIcon = Save; readonly ArrowLeftIcon = ArrowLeft;
  readonly isEdit = signal(false); readonly saving = signal(false);
  private clientId: string | null = null;
  form: any = { company_name: '', contact_name: '', contact_phone: '', contact_email: '', city: '', state: '', address: '', industry: '', contract_start: '', contract_end: '', billing_type: '', billing_rate: '', notes: '' };
  states = ['Abia','Adamawa','Akwa Ibom','Anambra','Bauchi','Bayelsa','Benue','Borno','Cross River','Delta','Ebonyi','Edo','Ekiti','Enugu','FCT','Gombe','Imo','Jigawa','Kaduna','Kano','Katsina','Kebbi','Kogi','Kwara','Lagos','Nasarawa','Niger','Ogun','Ondo','Osun','Oyo','Plateau','Rivers','Sokoto','Taraba','Yobe','Zamfara'];
  stateOptions: SelectOption[] = this.states.map(s => ({ value: s, label: s }));
  industryOptions: SelectOption[] = [
    { value: 'banking', label: 'Banking & Finance' }, { value: 'oil_gas', label: 'Oil & Gas' },
    { value: 'real_estate', label: 'Real Estate' }, { value: 'retail', label: 'Retail' },
    { value: 'manufacturing', label: 'Manufacturing' }, { value: 'telecom', label: 'Telecom' },
    { value: 'government', label: 'Government' }, { value: 'education', label: 'Education' },
    { value: 'healthcare', label: 'Healthcare' }, { value: 'hospitality', label: 'Hospitality' },
    { value: 'logistics', label: 'Logistics & Transport' }, { value: 'construction', label: 'Construction' },
    { value: 'technology', label: 'Technology' }, { value: 'agriculture', label: 'Agriculture' },
    { value: 'other', label: 'Other' },
  ];

  ngOnInit(): void {
    this.clientId = this.route.snapshot.paramMap.get('id');
    if (this.clientId && this.clientId !== 'new') {
      this.isEdit.set(true);
      this.api.get<any>(`/clients/${this.clientId}`).subscribe({ next: res => { if (res.data) { const c = res.data.client || res.data; Object.keys(this.form).forEach(k => { if (c[k] !== undefined) this.form[k] = c[k] || ''; }); } } });
    }
  }
  submitted = false;
  validate(): boolean { if (!this.form.company_name?.trim()) { this.toast.warning("Company name is required"); return false; } if (!this.form.contact_name?.trim()) { this.toast.warning("Contact name is required"); return false; } if (!this.form.contact_email?.trim()) { this.toast.warning("Contact email is required"); return false; } if (this.form.contact_email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(this.form.contact_email)) { this.toast.warning("Invalid email format"); return false; } return true; }
  onSave(): void { this.submitted = true; if (!this.validate()) return; this.saving.set(true); const url = this.isEdit() ? `/clients/${this.clientId}` : '/clients'; const req = this.isEdit() ? this.api.put(url, this.form) : this.api.post(url, this.form); req.subscribe({ next: () => { this.saving.set(false); this.toast.success(this.isEdit() ? 'Client updated' : 'Client created'); this.router.navigate(['/clients']); }, error: () => this.saving.set(false) }); }
  goBack(): void { this.router.navigate(['/clients']); }
}
