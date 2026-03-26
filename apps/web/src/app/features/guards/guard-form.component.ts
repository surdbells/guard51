import { Component, inject, signal, OnInit } from '@angular/core';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { LucideAngularModule, ArrowLeft, Save, Loader2 } from 'lucide-angular';
import { PageHeaderComponent } from '@shared/components/page-header/page-header.component';
import { ApiService } from '@core/services/api.service';
import { ToastService } from '@core/services/toast.service';

@Component({
  selector: 'g51-guard-form',
  standalone: true,
  imports: [RouterLink, FormsModule, LucideAngularModule, PageHeaderComponent],
  template: `
    <g51-page-header [title]="isEdit() ? 'Edit Guard' : 'Add New Guard'" subtitle="Enter guard details">
      <a routerLink="/guards" class="btn-secondary flex items-center gap-1.5"><lucide-icon [img]="ArrowLeftIcon" [size]="16" /> Back</a>
    </g51-page-header>

    <form (ngSubmit)="onSubmit()" class="max-w-3xl space-y-6">
      <div class="card p-5">
        <h3 class="text-sm font-semibold mb-4" [style.color]="'var(--text-primary)'">Basic Information</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Employee Number *</label>
            <input type="text" [(ngModel)]="form.employee_number" name="employee_number" class="input-base w-full" required /></div>
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Phone *</label>
            <input type="tel" [(ngModel)]="form.phone" name="phone" class="input-base w-full" required /></div>
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">First Name *</label>
            <input type="text" [(ngModel)]="form.first_name" name="first_name" class="input-base w-full" required /></div>
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Last Name *</label>
            <input type="text" [(ngModel)]="form.last_name" name="last_name" class="input-base w-full" required /></div>
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Email</label>
            <input type="email" [(ngModel)]="form.email" name="email" class="input-base w-full" /></div>
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Hire Date</label>
            <input type="date" [(ngModel)]="form.hire_date" name="hire_date" class="input-base w-full" /></div>
        </div>
      </div>

      <div class="card p-5">
        <h3 class="text-sm font-semibold mb-4" [style.color]="'var(--text-primary)'">Personal Details</h3>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Gender</label>
            <select [(ngModel)]="form.gender" name="gender" class="input-base w-full">
              <option value="">—</option><option value="male">Male</option><option value="female">Female</option></select></div>
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Date of Birth</label>
            <input type="date" [(ngModel)]="form.date_of_birth" name="dob" class="input-base w-full" /></div>
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">State</label>
            <input type="text" [(ngModel)]="form.state" name="state" class="input-base w-full" /></div>
        </div>
        <div class="mt-4"><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Address</label>
          <textarea [(ngModel)]="form.address" name="address" rows="2" class="input-base w-full resize-none"></textarea></div>
      </div>

      <div class="card p-5">
        <h3 class="text-sm font-semibold mb-4" [style.color]="'var(--text-primary)'">Pay & Banking</h3>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Pay Type</label>
            <select [(ngModel)]="form.pay_type" name="pay_type" class="input-base w-full">
              <option value="">—</option><option value="hourly">Hourly</option><option value="daily">Daily</option><option value="monthly">Monthly</option></select></div>
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Pay Rate (₦)</label>
            <input type="number" [(ngModel)]="form.pay_rate" name="pay_rate" class="input-base w-full" /></div>
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Bank Name</label>
            <input type="text" [(ngModel)]="form.bank_name" name="bank_name" class="input-base w-full" /></div>
        </div>
      </div>

      <div class="card p-5">
        <h3 class="text-sm font-semibold mb-4" [style.color]="'var(--text-primary)'">Emergency Contact</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Contact Name</label>
            <input type="text" [(ngModel)]="form.emergency_contact_name" name="ec_name" class="input-base w-full" /></div>
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Contact Phone</label>
            <input type="tel" [(ngModel)]="form.emergency_contact_phone" name="ec_phone" class="input-base w-full" /></div>
        </div>
      </div>

      <button type="submit" [disabled]="saving()" class="btn-primary flex items-center gap-2">
        @if (saving()) { <lucide-icon [img]="Loader2Icon" [size]="16" class="animate-spin" /> }
        <lucide-icon [img]="SaveIcon" [size]="16" /> {{ isEdit() ? 'Update Guard' : 'Create Guard' }}
      </button>
    </form>
  `,
})
export class GuardFormComponent implements OnInit {
  private api = inject(ApiService); private router = inject(Router);
  private route = inject(ActivatedRoute); private toast = inject(ToastService);
  readonly ArrowLeftIcon = ArrowLeft; readonly SaveIcon = Save; readonly Loader2Icon = Loader2;
  readonly isEdit = signal(false); readonly saving = signal(false);
  form: Record<string, any> = { employee_number: '', first_name: '', last_name: '', phone: '', email: '', hire_date: '', gender: '', date_of_birth: '', address: '', state: '', pay_type: '', pay_rate: null, bank_name: '', emergency_contact_name: '', emergency_contact_phone: '' };

  ngOnInit(): void {
    const id = this.route.snapshot.params['id'];
    if (id) { this.isEdit.set(true); this.api.get<any>(`/guards/${id}`).subscribe({ next: res => { if (res.data?.guard) Object.assign(this.form, res.data.guard); } }); }
  }
  onSubmit(): void {
    this.saving.set(true);
    const req = this.isEdit() ? this.api.put(`/guards/${this.route.snapshot.params['id']}`, this.form) : this.api.post('/guards', this.form);
    req.subscribe({ next: () => { this.saving.set(false); this.toast.success(this.isEdit() ? 'Guard updated' : 'Guard created'); this.router.navigate(['/guards']); }, error: () => this.saving.set(false) });
  }
}
