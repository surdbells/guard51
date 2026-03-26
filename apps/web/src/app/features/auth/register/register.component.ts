import { Component, inject, signal } from '@angular/core';
import { RouterLink, Router } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { TranslateModule } from '@ngx-translate/core';
import { LucideAngularModule, Shield, Eye, EyeOff, Loader2 } from 'lucide-angular';
import { AuthService } from '@core/services/auth.service';

@Component({
  selector: 'g51-register',
  standalone: true,
  imports: [RouterLink, FormsModule, TranslateModule, LucideAngularModule],
  template: `
    <div class="min-h-screen flex" [style.background]="'var(--surface-bg)'">
      <div class="hidden lg:flex lg:w-[45%] flex-col justify-between p-10 relative overflow-hidden"
        style="background: linear-gradient(135deg, var(--color-brand-700), var(--color-brand-500))">
        <div>
          <div class="flex items-center gap-3 mb-16">
            <div class="h-10 w-10 rounded-xl bg-white/20 flex items-center justify-center">
              <lucide-icon [img]="ShieldIcon" [size]="22" class="text-white" />
            </div>
            <span class="text-xl font-bold text-white">Guard51</span>
          </div>
          <h1 class="text-4xl font-bold text-white leading-tight mb-4">Start Managing<br/>Your Security Team</h1>
          <p class="text-white/70 text-lg max-w-md">Create your account in 60 seconds. No credit card required — start with a 14-day free trial.</p>
        </div>
        <p class="text-white/40 text-sm">&copy; {{ currentYear }} DOSTHQ Limited</p>
        <div class="absolute -right-32 -top-32 w-96 h-96 rounded-full bg-white/5"></div>
      </div>

      <div class="flex-1 flex items-center justify-center p-6 sm:p-12">
        <div class="w-full max-w-md">
          <div class="flex items-center gap-2 mb-8 lg:hidden">
            <div class="h-9 w-9 rounded-lg flex items-center justify-center" style="background: var(--color-brand-500)">
              <lucide-icon [img]="ShieldIcon" [size]="20" class="text-white" />
            </div>
            <span class="text-lg font-bold" [style.color]="'var(--text-primary)'">Guard51</span>
          </div>
          <h2 class="text-2xl font-bold mb-1" [style.color]="'var(--text-primary)'">{{ 'auth.register' | translate }}</h2>
          <p class="text-sm mb-8" [style.color]="'var(--text-secondary)'">Get started with your free trial</p>

          @if (error()) {
            <div class="mb-4 px-4 py-3 rounded-lg bg-red-50 dark:bg-red-950 border border-red-200 dark:border-red-800">
              <p class="text-sm text-red-600 dark:text-red-400">{{ error() }}</p>
            </div>
          }

          <form (ngSubmit)="onRegister()" class="space-y-4">
            <div>
              <label class="block text-sm font-medium mb-1.5" [style.color]="'var(--text-primary)'">{{ 'auth.company_name' | translate }}</label>
              <input type="text" [(ngModel)]="companyName" name="companyName" class="input-base w-full" placeholder="ShieldForce Security" required />
            </div>
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block text-sm font-medium mb-1.5" [style.color]="'var(--text-primary)'">{{ 'auth.first_name' | translate }}</label>
                <input type="text" [(ngModel)]="firstName" name="firstName" class="input-base w-full" required />
              </div>
              <div>
                <label class="block text-sm font-medium mb-1.5" [style.color]="'var(--text-primary)'">{{ 'auth.last_name' | translate }}</label>
                <input type="text" [(ngModel)]="lastName" name="lastName" class="input-base w-full" required />
              </div>
            </div>
            <div>
              <label class="block text-sm font-medium mb-1.5" [style.color]="'var(--text-primary)'">{{ 'auth.email' | translate }}</label>
              <input type="email" [(ngModel)]="email" name="email" class="input-base w-full" placeholder="you@company.com" required />
            </div>
            <div>
              <label class="block text-sm font-medium mb-1.5" [style.color]="'var(--text-primary)'">{{ 'auth.phone' | translate }}</label>
              <input type="tel" [(ngModel)]="phone" name="phone" class="input-base w-full" placeholder="+234 801 234 5678" />
            </div>
            <div>
              <label class="block text-sm font-medium mb-1.5" [style.color]="'var(--text-primary)'">{{ 'auth.password' | translate }}</label>
              <div class="relative">
                <input [type]="showPw() ? 'text' : 'password'" [(ngModel)]="password" name="password" class="input-base w-full pr-10" placeholder="Min 8 characters, uppercase, lowercase, digit" required />
                <button type="button" (click)="showPw.set(!showPw())" class="absolute right-3 top-1/2 -translate-y-1/2" [style.color]="'var(--text-tertiary)'">
                  <lucide-icon [img]="showPw() ? EyeOffIcon : EyeIcon" [size]="16" />
                </button>
              </div>
            </div>
            <button type="submit" [disabled]="loading()" class="btn-primary w-full h-10 flex items-center justify-center gap-2">
              @if (loading()) { <lucide-icon [img]="Loader2Icon" [size]="16" class="animate-spin" /> }
              {{ 'auth.register' | translate }}
            </button>
          </form>
          <p class="text-sm text-center mt-6" [style.color]="'var(--text-secondary)'">
            {{ 'auth.has_account' | translate }} <a routerLink="/auth/login" class="font-medium" [style.color]="'var(--color-brand-500)'">{{ 'auth.sign_in' | translate }}</a>
          </p>
        </div>
      </div>
    </div>
  `,
})
export class RegisterComponent {
  private authService = inject(AuthService);
  private router = inject(Router);
  companyName = ''; firstName = ''; lastName = ''; email = ''; phone = ''; password = '';
  readonly loading = signal(false); readonly showPw = signal(false); readonly error = signal('');
  readonly currentYear = new Date().getFullYear();
  readonly ShieldIcon = Shield; readonly EyeIcon = Eye; readonly EyeOffIcon = EyeOff; readonly Loader2Icon = Loader2;

  onRegister(): void {
    this.loading.set(true); this.error.set('');
    this.authService.register({
      company_name: this.companyName, first_name: this.firstName, last_name: this.lastName,
      email: this.email, password: this.password, phone: this.phone || undefined,
    }).subscribe({
      next: (res) => { this.loading.set(false); if (res.success) this.router.navigate(['/dashboard']); },
      error: (err) => { this.loading.set(false); this.error.set(err.error?.message || 'Registration failed.'); },
    });
  }
}
