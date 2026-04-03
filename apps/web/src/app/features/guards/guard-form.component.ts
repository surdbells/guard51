import { Component, inject, signal, OnInit } from '@angular/core';
import { Router, ActivatedRoute } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { NgClass } from '@angular/common';
import { LucideAngularModule, Save, ArrowLeft, Upload, X } from 'lucide-angular';
import { PageHeaderComponent } from '@shared/components/page-header/page-header.component';
import { SearchableSelectComponent, SelectOption } from '@shared/components/searchable-select/searchable-select.component';
import { ApiService } from '@core/services/api.service';
import { ToastService } from '@core/services/toast.service';

@Component({
  selector: 'g51-guard-form',
  standalone: true,
  imports: [FormsModule, NgClass, LucideAngularModule, PageHeaderComponent, SearchableSelectComponent],
  template: `
    <g51-page-header [title]="isEdit() ? 'Edit Guard' : 'Add New Guard'" subtitle="Guard personnel details">
      <button class="btn-secondary flex items-center gap-2" (click)="goBack()"><lucide-icon [img]="ArrowLeftIcon" [size]="14" /> Back</button>
    </g51-page-header>

    <div class="card p-6 max-w-3xl">
      <h3 class="text-sm font-semibold mb-4" [style.color]="'var(--text-primary)'">Personal Information</h3>
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">First Name *</label>
          <input type="text" [(ngModel)]="form.first_name" class="input-base w-full" required />
          @if (submitted && errors['first_name']) { <p class="text-[10px] text-red-500 mt-0.5">{{ errors['first_name'] }}</p> }</div>
        <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Last Name *</label>
          <input type="text" [(ngModel)]="form.last_name" class="input-base w-full" required />
          @if (submitted && errors['last_name']) { <p class="text-[10px] text-red-500 mt-0.5">{{ errors['last_name'] }}</p> }</div>
        <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Employee Number</label>
          <input type="text" [(ngModel)]="form.employee_number" class="input-base w-full" placeholder="Auto-generated (GRD-0001)" />
          <p class="text-[10px] mt-0.5" [style.color]="'var(--text-tertiary)'">Leave blank to auto-generate</p></div>
      </div>
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Phone *</label>
          <input type="tel" [(ngModel)]="form.phone" class="input-base w-full" required placeholder="+234..." />
          @if (submitted && errors['phone']) { <p class="text-[10px] text-red-500 mt-0.5">{{ errors['phone'] }}</p> }</div>
        <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Email</label>
          <input type="email" [(ngModel)]="form.email" class="input-base w-full" placeholder="guard@example.com" />
          @if (submitted && errors['email']) { <p class="text-[10px] text-red-500 mt-0.5">{{ errors['email'] }}</p> }</div>
        <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Gender</label>
          <select [(ngModel)]="form.gender" class="input-base w-full">
            <option value="">Select</option><option value="male">Male</option><option value="female">Female</option></select></div>
      </div>
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Date of Birth</label>
          <input type="date" [(ngModel)]="form.date_of_birth" class="input-base w-full" /></div>
        <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Hire Date *</label>
          <input type="date" [(ngModel)]="form.hire_date" class="input-base w-full" required /></div>
        <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">State of Origin</label>
          <g51-searchable-select [(ngModel)]="form.state" [options]="stateOptions" placeholder="Select State" /></div>
      </div>
      <div class="mb-6"><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Residential Address</label>
        <textarea [(ngModel)]="form.address" rows="2" class="input-base w-full resize-none" placeholder="House number, street, area..."></textarea></div>

      <h3 class="text-sm font-semibold mb-4 mt-6" [style.color]="'var(--text-primary)'">Photo</h3>
      <div class="mb-6">
        @if (photoPreview()) {
          <div class="relative inline-block mb-2">
            <img [src]="photoPreview()" class="h-24 w-24 rounded-lg object-cover border" [style.borderColor]="'var(--border-default)'" />
            <button (click)="removePhoto()" class="absolute -top-2 -right-2 h-5 w-5 rounded-full bg-red-500 text-white flex items-center justify-center"><lucide-icon [img]="XIcon" [size]="10" /></button>
          </div>
        }
        <label class="btn-secondary inline-flex items-center gap-2 cursor-pointer text-xs">
          <lucide-icon [img]="UploadIcon" [size]="14" /> {{ photoPreview() ? 'Change Photo' : 'Upload Photo' }}
          <input type="file" accept="image/*" (change)="onPhotoSelect($event)" class="hidden" />
        </label>
      </div>

      <h3 class="text-sm font-semibold mb-4 mt-6" [style.color]="'var(--text-primary)'">Emergency Contact</h3>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
        <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Contact Name</label>
          <input type="text" [(ngModel)]="form.emergency_contact_name" class="input-base w-full" placeholder="Next of kin name" /></div>
        <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Contact Phone</label>
          <input type="tel" [(ngModel)]="form.emergency_contact_phone" class="input-base w-full" placeholder="+234..." /></div>
      </div>

      <h3 class="text-sm font-semibold mb-4 mt-6" [style.color]="'var(--text-primary)'">Payroll & Banking</h3>
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Pay Type</label>
          <select [(ngModel)]="form.pay_type" class="input-base w-full">
            <option value="">Select</option><option value="hourly">Hourly</option><option value="daily">Daily</option><option value="weekly">Weekly</option><option value="monthly">Monthly</option></select></div>
        <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Pay Rate (₦)</label>
          <input type="number" [(ngModel)]="form.pay_rate" class="input-base w-full" placeholder="0.00" /></div>
        <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Bank Name</label>
          <g51-searchable-select [(ngModel)]="form.bank_name" [options]="bankOptions" placeholder="Select Bank" /></div>
      </div>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
        <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Account Number</label>
          <input type="text" [(ngModel)]="form.bank_account_number" class="input-base w-full" maxlength="10" placeholder="10-digit NUBAN" />
          @if (submitted && errors['bank_account_number']) { <p class="text-[10px] text-red-500 mt-0.5">{{ errors['bank_account_number'] }}</p> }</div>
        <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Account Name</label>
          <input type="text" [(ngModel)]="form.bank_account_name" class="input-base w-full" /></div>
      </div>

      <div class="mb-6"><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Notes / Special Instructions</label>
        <textarea [(ngModel)]="form.notes" rows="3" class="input-base w-full resize-none" placeholder="Any special information about this guard..."></textarea></div>

      <div class="flex justify-end gap-3 pt-4 border-t" [style.borderColor]="'var(--border-default)'">
        <button (click)="goBack()" class="btn-secondary">Cancel</button>
        <button (click)="onSave()" class="btn-primary flex items-center gap-2" [disabled]="saving()">
          <lucide-icon [img]="SaveIcon" [size]="14" /> {{ isEdit() ? 'Update Guard' : 'Create Guard' }}
        </button>
      </div>
    </div>
  `,
})
export class GuardFormComponent implements OnInit {
  private api = inject(ApiService);
  private toast = inject(ToastService);
  private router = inject(Router);
  private route = inject(ActivatedRoute);
  readonly SaveIcon = Save; readonly ArrowLeftIcon = ArrowLeft; readonly UploadIcon = Upload; readonly XIcon = X;

  readonly isEdit = signal(false);
  readonly saving = signal(false);
  readonly photoPreview = signal<string | null>(null);
  private photoFile: File | null = null;
  private guardId: string | null = null;

  form: any = {
    first_name: '', last_name: '', employee_number: '', phone: '', email: '',
    gender: '', date_of_birth: '', hire_date: new Date().toISOString().slice(0, 10),
    state: '', address: '', emergency_contact_name: '', emergency_contact_phone: '',
    pay_type: '', pay_rate: '', bank_name: '', bank_account_number: '', bank_account_name: '',
    notes: '',
  };

  nigerianStates = [
    'Abia', 'Adamawa', 'Akwa Ibom', 'Anambra', 'Bauchi', 'Bayelsa', 'Benue', 'Borno',
    'Cross River', 'Delta', 'Ebonyi', 'Edo', 'Ekiti', 'Enugu', 'FCT', 'Gombe', 'Imo',
    'Jigawa', 'Kaduna', 'Kano', 'Katsina', 'Kebbi', 'Kogi', 'Kwara', 'Lagos', 'Nasarawa',
    'Niger', 'Ogun', 'Ondo', 'Osun', 'Oyo', 'Plateau', 'Rivers', 'Sokoto', 'Taraba',
    'Yobe', 'Zamfara',
  ];

  nigerianBanks = [
    'Access Bank', 'Citibank', 'Ecobank', 'Fidelity Bank', 'First Bank', 'First City Monument Bank',
    'Globus Bank', 'Guaranty Trust Bank', 'Heritage Bank', 'Jaiz Bank', 'Keystone Bank',
    'Kuda Bank', 'Opay', 'Palmpay', 'Polaris Bank', 'Providus Bank', 'Stanbic IBTC',
    'Standard Chartered', 'Sterling Bank', 'SunTrust Bank', 'Titan Trust Bank',
    'Union Bank', 'United Bank for Africa', 'Unity Bank', 'VFD Microfinance Bank',
    'Wema Bank', 'Zenith Bank',
  ];

  stateOptions: SelectOption[] = this.nigerianStates.map(s => ({ value: s, label: s }));
  bankOptions: SelectOption[] = this.nigerianBanks.map(b => ({ value: b, label: b }));

  ngOnInit(): void {
    this.guardId = this.route.snapshot.paramMap.get('id');
    if (this.guardId && this.guardId !== 'new') {
      this.isEdit.set(true);
      this.api.get<any>(`/guards/${this.guardId}`).subscribe({
        next: res => {
          if (res.data) {
            const g = res.data.guard || res.data;
            Object.keys(this.form).forEach(k => { if (g[k] !== undefined) this.form[k] = g[k] || ''; });
            if (g.photo_url) this.photoPreview.set(g.photo_url);
          }
        },
      });
    }
  }

  onPhotoSelect(event: Event): void {
    const file = (event.target as HTMLInputElement).files?.[0];
    if (file) {
      this.photoFile = file;
      const reader = new FileReader();
      reader.onload = () => this.photoPreview.set(reader.result as string);
      reader.readAsDataURL(file);
    }
  }

  submitted = false;
  errors: Record<string, string> = {};

  removePhoto(): void { this.photoPreview.set(null); this.photoFile = null; this.form.photo_url = ''; }

  validate(): boolean {
    this.errors = {};
    if (!this.form.first_name?.trim()) this.errors['first_name'] = 'First name is required';
    if (!this.form.last_name?.trim()) this.errors['last_name'] = 'Last name is required';
    if (!this.form.phone?.trim()) this.errors['phone'] = 'Phone number is required';
    if (this.form.phone && !/^[\+0-9]{10,15}$/.test(this.form.phone.replace(/\s/g, ''))) this.errors['phone'] = 'Invalid phone format';
    if (this.form.email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(this.form.email)) this.errors['email'] = 'Invalid email format';
    if (this.form.bank_account_number && this.form.bank_account_number.length !== 10) this.errors['bank_account_number'] = 'Must be 10 digits (NUBAN)';
    return Object.keys(this.errors).length === 0;
  }

  onSave(): void {
    this.submitted = true;
    if (!this.validate()) { this.toast.warning('Please fix the errors in the form'); return; }
    this.saving.set(true);

    // Use FormData if there's a photo file to upload
    if (this.photoFile) {
      const fd = new FormData();
      Object.entries(this.form).forEach(([k, v]) => { if (v) fd.append(k, String(v)); });
      fd.append('photo', this.photoFile);

      const url = this.isEdit() ? `/guards/${this.guardId}` : '/guards';
      const req = this.isEdit()
        ? this.api.put(url, fd)
        : this.api.post(url, fd);

      req.subscribe({
        next: () => { this.saving.set(false); this.toast.success(this.isEdit() ? 'Guard updated' : 'Guard created'); this.router.navigate(['/guards']); },
        error: () => this.saving.set(false),
      });
    } else {
      const url = this.isEdit() ? `/guards/${this.guardId}` : '/guards';
      const req = this.isEdit()
        ? this.api.put(url, this.form)
        : this.api.post(url, this.form);

      req.subscribe({
        next: () => { this.saving.set(false); this.toast.success(this.isEdit() ? 'Guard updated' : 'Guard created'); this.router.navigate(['/guards']); },
        error: () => this.saving.set(false),
      });
    }
  }

  goBack(): void { this.router.navigate(['/guards']); }
}
