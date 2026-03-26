import { Component, inject, signal, OnInit } from '@angular/core';
import { RouterLink, Router, ActivatedRoute } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { LucideAngularModule, Shield, Loader2 } from 'lucide-angular';
import { AuthService } from '@core/services/auth.service';
import { ToastService } from '@core/services/toast.service';

@Component({
  selector: 'g51-reset-password',
  standalone: true,
  imports: [RouterLink, FormsModule, LucideAngularModule],
  template: `
    <div class="min-h-screen flex items-center justify-center p-6" [style.background]="'var(--surface-bg)'">
      <div class="w-full max-w-md">
        <div class="flex items-center gap-2 mb-6">
          <div class="h-9 w-9 rounded-lg flex items-center justify-center" style="background: var(--color-brand-500)">
            <lucide-icon [img]="ShieldIcon" [size]="20" class="text-white" />
          </div>
          <span class="text-lg font-bold" [style.color]="'var(--text-primary)'">Guard51</span>
        </div>
        <h2 class="text-2xl font-bold mb-1" [style.color]="'var(--text-primary)'">Set New Password</h2>
        <p class="text-sm mb-8" [style.color]="'var(--text-secondary)'">Choose a strong password for your account.</p>

        @if (error()) {
          <div class="mb-4 px-4 py-3 rounded-lg bg-red-50 dark:bg-red-950 border border-red-200 dark:border-red-800">
            <p class="text-sm text-red-600 dark:text-red-400">{{ error() }}</p>
          </div>
        }

        <form (ngSubmit)="onSubmit()" class="space-y-4">
          <div>
            <label class="block text-sm font-medium mb-1.5" [style.color]="'var(--text-primary)'">New Password</label>
            <input type="password" [(ngModel)]="password" name="password" class="input-base w-full" placeholder="Min 8 characters" required />
          </div>
          <div>
            <label class="block text-sm font-medium mb-1.5" [style.color]="'var(--text-primary)'">Confirm Password</label>
            <input type="password" [(ngModel)]="confirmPassword" name="confirmPassword" class="input-base w-full" required />
          </div>
          <button type="submit" [disabled]="loading()" class="btn-primary w-full h-10 flex items-center justify-center gap-2">
            @if (loading()) { <lucide-icon [img]="Loader2Icon" [size]="16" class="animate-spin" /> }
            Reset Password
          </button>
        </form>
      </div>
    </div>
  `,
})
export class ResetPasswordComponent implements OnInit {
  private authService = inject(AuthService);
  private router = inject(Router);
  private route = inject(ActivatedRoute);
  private toast = inject(ToastService);
  token = ''; email = ''; password = ''; confirmPassword = '';
  readonly loading = signal(false); readonly error = signal('');
  readonly ShieldIcon = Shield; readonly Loader2Icon = Loader2;

  ngOnInit(): void {
    this.token = this.route.snapshot.queryParams['token'] || '';
    this.email = this.route.snapshot.queryParams['email'] || '';
  }

  onSubmit(): void {
    if (this.password !== this.confirmPassword) { this.error.set('Passwords do not match.'); return; }
    this.loading.set(true); this.error.set('');
    this.authService.resetPassword(this.token, this.email, this.password).subscribe({
      next: () => { this.loading.set(false); this.toast.success('Password reset!', 'Please log in.'); this.router.navigate(['/auth/login']); },
      error: (err) => { this.loading.set(false); this.error.set(err.error?.message || 'Reset failed.'); },
    });
  }
}
