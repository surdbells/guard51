import { Component, inject, signal, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { NgClass } from '@angular/common';
import { LucideAngularModule, Building2, MapPin, Shield, Check, ArrowRight, ArrowLeft } from 'lucide-angular';
import { ApiService } from '@core/services/api.service';
import { ToastService } from '@core/services/toast.service';
import { AuthStore } from '@core/services/auth.store';

@Component({
  selector: 'g51-onboarding',
  standalone: true,
  imports: [FormsModule, NgClass, LucideAngularModule],
  template: `
    <div class="min-h-screen flex items-center justify-center p-4" [style.background]="'var(--surface-bg)'">
      <div class="w-full max-w-2xl">
        <!-- Progress -->
        <div class="flex items-center justify-center gap-2 mb-8">
          @for (s of steps; track s.num; let i = $index) {
            <div class="flex items-center gap-2">
              <div class="h-8 w-8 rounded-full flex items-center justify-center text-xs font-bold transition-colors"
                [ngClass]="step() >= s.num ? 'text-white' : ''"
                [style.background]="step() >= s.num ? 'var(--color-brand-500)' : 'var(--surface-muted)'"
                [style.color]="step() < s.num ? 'var(--text-tertiary)' : ''">
                @if (step() > s.num) { <lucide-icon [img]="CheckIcon" [size]="14" /> } @else { {{ s.num }} }
              </div>
              @if (i < steps.length - 1) { <div class="w-12 h-px" [style.background]="step() > s.num ? 'var(--color-brand-500)' : 'var(--border-default)'"></div> }
            </div>
          }
        </div>

        <div class="card p-8">
          <!-- Step 1: Company Info -->
          @if (step() === 1) {
            <div class="text-center mb-6">
              <lucide-icon [img]="BuildingIcon" [size]="32" class="mx-auto mb-2" [style.color]="'var(--color-brand-500)'" />
              <h2 class="text-lg font-bold" [style.color]="'var(--text-primary)'">Company Information</h2>
              <p class="text-xs" [style.color]="'var(--text-tertiary)'">Tell us about your security company</p>
            </div>
            <div class="space-y-4">
              <div class="grid grid-cols-2 gap-4">
                <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Company Name *</label>
                  <input type="text" [(ngModel)]="company.name" class="input-base w-full" /></div>
                <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">RC Number</label>
                  <input type="text" [(ngModel)]="company.rc_number" class="input-base w-full" placeholder="RC-1234567" /></div>
              </div>
              <div class="grid grid-cols-2 gap-4">
                <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Phone</label>
                  <input type="tel" [(ngModel)]="company.phone" class="input-base w-full" /></div>
                <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">State</label>
                  <select [(ngModel)]="company.state" class="input-base w-full">
                    <option value="">Select</option>
                    @for (s of states; track s) { <option [value]="s">{{ s }}</option> }
                  </select></div>
              </div>
              <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Address</label>
                <textarea [(ngModel)]="company.address" rows="2" class="input-base w-full resize-none"></textarea></div>
            </div>
          }

          <!-- Step 2: First Site -->
          @if (step() === 2) {
            <div class="text-center mb-6">
              <lucide-icon [img]="MapPinIcon" [size]="32" class="mx-auto mb-2" [style.color]="'var(--color-brand-500)'" />
              <h2 class="text-lg font-bold" [style.color]="'var(--text-primary)'">Create Your First Site</h2>
              <p class="text-xs" [style.color]="'var(--text-tertiary)'">Where do your guards post?</p>
            </div>
            <div class="space-y-4">
              <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Site Name *</label>
                <input type="text" [(ngModel)]="site.name" class="input-base w-full" placeholder="e.g. Main Office, Lekki Branch" /></div>
              <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Address</label>
                <input type="text" [(ngModel)]="site.address" class="input-base w-full" /></div>
              <div class="grid grid-cols-2 gap-4">
                <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">City</label>
                  <input type="text" [(ngModel)]="site.city" class="input-base w-full" /></div>
                <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">State</label>
                  <select [(ngModel)]="site.state" class="input-base w-full">
                    <option value="">Select</option>
                    @for (s of states; track s) { <option [value]="s">{{ s }}</option> }
                  </select></div>
              </div>
            </div>
          }

          <!-- Step 3: First Guard -->
          @if (step() === 3) {
            <div class="text-center mb-6">
              <lucide-icon [img]="ShieldIcon" [size]="32" class="mx-auto mb-2" [style.color]="'var(--color-brand-500)'" />
              <h2 class="text-lg font-bold" [style.color]="'var(--text-primary)'">Add Your First Guard</h2>
              <p class="text-xs" [style.color]="'var(--text-tertiary)'">You can add more guards later</p>
            </div>
            <div class="space-y-4">
              <div class="grid grid-cols-2 gap-4">
                <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">First Name *</label>
                  <input type="text" [(ngModel)]="guard.first_name" class="input-base w-full" /></div>
                <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Last Name *</label>
                  <input type="text" [(ngModel)]="guard.last_name" class="input-base w-full" /></div>
              </div>
              <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Phone *</label>
                <input type="tel" [(ngModel)]="guard.phone" class="input-base w-full" placeholder="+234..." /></div>
            </div>
          }

          <!-- Step 4: Done -->
          @if (step() === 4) {
            <div class="text-center py-8">
              <div class="h-16 w-16 rounded-full mx-auto mb-4 flex items-center justify-center" [style.background]="'var(--color-brand-50)'" [style.color]="'var(--color-brand-500)'">
                <lucide-icon [img]="CheckIcon" [size]="32" />
              </div>
              <h2 class="text-lg font-bold mb-2" [style.color]="'var(--text-primary)'">You're All Set!</h2>
              <p class="text-sm" [style.color]="'var(--text-secondary)'">Your security company is ready to go. You can now manage guards, sites, scheduling, and more from your dashboard.</p>
            </div>
          }

          <!-- Navigation -->
          <div class="flex justify-between mt-8 pt-4 border-t" [style.borderColor]="'var(--border-default)'">
            @if (step() > 1 && step() < 4) {
              <button (click)="prev()" class="btn-secondary flex items-center gap-1 text-xs"><lucide-icon [img]="ArrowLeftIcon" [size]="12" /> Back</button>
            } @else { <div></div> }

            @if (step() < 3) {
              <div class="flex gap-2">
                <button (click)="skipStep()" class="btn-secondary text-xs">Skip</button>
                <button (click)="next()" class="btn-primary flex items-center gap-1 text-xs">Next <lucide-icon [img]="ArrowRightIcon" [size]="12" /></button>
              </div>
            } @else if (step() === 3) {
              <div class="flex gap-2">
                <button (click)="skipStep()" class="btn-secondary text-xs">Skip</button>
                <button (click)="finishOnboarding()" class="btn-primary text-xs">Finish Setup</button>
              </div>
            } @else {
              <button (click)="goToDashboard()" class="btn-primary flex items-center gap-1 text-xs">Go to Dashboard <lucide-icon [img]="ArrowRightIcon" [size]="12" /></button>
            }
          </div>
        </div>
      </div>
    </div>
  `,
})
export class OnboardingComponent implements OnInit {
  private api = inject(ApiService); private toast = inject(ToastService);
  private router = inject(Router); private auth = inject(AuthStore);
  readonly BuildingIcon = Building2; readonly MapPinIcon = MapPin; readonly ShieldIcon = Shield;
  readonly CheckIcon = Check; readonly ArrowRightIcon = ArrowRight; readonly ArrowLeftIcon = ArrowLeft;

  readonly step = signal(1);
  steps = [{ num: 1, label: 'Company' }, { num: 2, label: 'Site' }, { num: 3, label: 'Guard' }, { num: 4, label: 'Done' }];
  company = { name: '', rc_number: '', phone: '', state: '', address: '' };
  site = { name: '', address: '', city: '', state: '' };
  guard = { first_name: '', last_name: '', phone: '' };

  states = ['Abia','Adamawa','Akwa Ibom','Anambra','Bauchi','Bayelsa','Benue','Borno','Cross River','Delta','Ebonyi','Edo','Ekiti','Enugu','FCT','Gombe','Imo','Jigawa','Kaduna','Kano','Katsina','Kebbi','Kogi','Kwara','Lagos','Nasarawa','Niger','Ogun','Ondo','Osun','Oyo','Plateau','Rivers','Sokoto','Taraba','Yobe','Zamfara'];

  ngOnInit(): void {
    this.api.get<any>('/onboarding/status').subscribe({
      next: res => {
        if (res.data?.is_onboarded) { this.router.navigateByUrl('/dashboard'); }
        if (res.data?.tenant) { this.company.name = res.data.tenant.name || ''; }
      },
      error: () => {},
    });
  }

  next(): void {
    const s = this.step();
    if (s === 1) {
      if (this.company.name) {
        this.api.put('/onboarding/company', this.company).subscribe({ next: () => this.step.set(2), error: () => this.step.set(2) });
      } else { this.toast.warning('Company name is required'); return; }
    } else if (s === 2) {
      if (this.site.name) {
        this.api.post('/sites', this.site).subscribe({ next: () => this.step.set(3), error: () => this.step.set(3) });
      } else { this.toast.warning('Site name is required'); return; }
    }
  }

  prev(): void { this.step.update(s => Math.max(1, s - 1)); }
  skipStep(): void { this.step.update(s => Math.min(4, s + 1)); }

  finishOnboarding(): void {
    if (this.guard.first_name && this.guard.last_name && this.guard.phone) {
      this.api.post('/guards', this.guard).subscribe({ next: () => this.completeOnboarding(), error: () => this.completeOnboarding() });
    } else {
      this.completeOnboarding();
    }
  }

  private completeOnboarding(): void {
    this.api.post('/onboarding/complete', {}).subscribe({ next: () => this.step.set(4), error: () => this.step.set(4) });
  }

  goToDashboard(): void { this.router.navigateByUrl('/dashboard'); }
}
