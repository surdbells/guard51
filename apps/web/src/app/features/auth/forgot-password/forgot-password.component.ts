import { Component, inject, signal } from '@angular/core';
import { RouterLink } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { TranslateModule } from '@ngx-translate/core';
import { LucideAngularModule, Shield, ArrowLeft, Loader2, CheckCircle } from 'lucide-angular';
import { AuthService } from '@core/services/auth.service';

@Component({
  selector: 'g51-forgot-password',
  standalone: true,
  imports: [RouterLink, FormsModule, TranslateModule, LucideAngularModule],
  template: `
    <div class="min-h-screen flex items-center justify-center p-6" [style.background]="'var(--surface-bg)'">
      <div class="w-full max-w-md">
        <a routerLink="/auth/login" class="inline-flex items-center gap-1 text-sm font-medium mb-8" [style.color]="'var(--color-brand-500)'">
          <lucide-icon [img]="ArrowLeftIcon" [size]="16" /> Back to login
        </a>

        @if (sent()) {
          <div class="text-center">
            <div class="mx-auto h-14 w-14 rounded-full bg-emerald-50 dark:bg-emerald-950 flex items-center justify-center mb-4">
              <lucide-icon [img]="CheckCircleIcon" [size]="28" class="text-emerald-500" />
            </div>
            <h2 class="text-2xl font-bold mb-2" [style.color]="'var(--text-primary)'">Check your email</h2>
            <p class="text-sm" [style.color]="'var(--text-secondary)'">If an account with that email exists, we've sent a password reset link.</p>
          </div>
        } @else {
          <div class="flex items-center gap-2 mb-6">
            <div class="h-9 w-9 rounded-lg flex items-center justify-center" style="background: var(--color-brand-500)">
              <lucide-icon [img]="ShieldIcon" [size]="20" class="text-white" />
            </div>
            <span class="text-lg font-bold" [style.color]="'var(--text-primary)'">Guard51</span>
          </div>
          <h2 class="text-2xl font-bold mb-1" [style.color]="'var(--text-primary)'">{{ 'auth.forgot_password' | translate }}</h2>
          <p class="text-sm mb-8" [style.color]="'var(--text-secondary)'">Enter your email and we'll send you a reset link.</p>

          <form (ngSubmit)="onSubmit()" class="space-y-4">
            <div>
              <label class="block text-sm font-medium mb-1.5" [style.color]="'var(--text-primary)'">{{ 'auth.email' | translate }}</label>
              <input type="email" [(ngModel)]="email" name="email" class="input-base w-full" placeholder="you@company.com" required />
            </div>
            <button type="submit" [disabled]="loading()" class="btn-primary w-full h-10 flex items-center justify-center gap-2">
              @if (loading()) { <lucide-icon [img]="Loader2Icon" [size]="16" class="animate-spin" /> }
              Send Reset Link
            </button>
          </form>
        }
      </div>
    </div>
  `,
})
export class ForgotPasswordComponent {
  private authService = inject(AuthService);
  email = '';
  readonly loading = signal(false); readonly sent = signal(false);
  readonly ShieldIcon = Shield; readonly ArrowLeftIcon = ArrowLeft; readonly Loader2Icon = Loader2; readonly CheckCircleIcon = CheckCircle;

  onSubmit(): void {
    this.loading.set(true);
    this.authService.forgotPassword(this.email).subscribe({
      next: () => { this.loading.set(false); this.sent.set(true); },
      error: () => { this.loading.set(false); this.sent.set(true); },
    });
  }
}
