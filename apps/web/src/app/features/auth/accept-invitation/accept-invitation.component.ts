import { Component, inject, signal, OnInit } from '@angular/core';
import { Router, ActivatedRoute } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { LucideAngularModule, Shield, Loader2 } from 'lucide-angular';
import { ApiService } from '@core/services/api.service';
import { ToastService } from '@core/services/toast.service';

@Component({
  selector: 'g51-accept-invitation',
  standalone: true,
  imports: [FormsModule, LucideAngularModule],
  template: `
    <div class="min-h-screen flex items-center justify-center p-6" [style.background]="'var(--surface-bg)'">
      <div class="w-full max-w-md">
        <div class="flex items-center gap-2 mb-6">
          <div class="h-9 w-9 rounded-lg flex items-center justify-center" style="background: var(--color-brand-500)">
            <lucide-icon [img]="ShieldIcon" [size]="20" class="text-white" />
          </div>
          <span class="text-lg font-bold" [style.color]="'var(--text-primary)'">Guard51</span>
        </div>
        <h2 class="text-2xl font-bold mb-1" [style.color]="'var(--text-primary)'">Accept Invitation</h2>
        <p class="text-sm mb-8" [style.color]="'var(--text-secondary)'">Create your account to join the team.</p>

        @if (error()) {
          <div class="mb-4 px-4 py-3 rounded-lg bg-red-50 dark:bg-red-950 border border-red-200 dark:border-red-800">
            <p class="text-sm text-red-600 dark:text-red-400">{{ error() }}</p>
          </div>
        }

        <form (ngSubmit)="onSubmit()" class="space-y-4">
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-sm font-medium mb-1.5" [style.color]="'var(--text-primary)'">First Name</label>
              <input type="text" [(ngModel)]="firstName" name="firstName" class="input-base w-full" required />
            </div>
            <div>
              <label class="block text-sm font-medium mb-1.5" [style.color]="'var(--text-primary)'">Last Name</label>
              <input type="text" [(ngModel)]="lastName" name="lastName" class="input-base w-full" required />
            </div>
          </div>
          <div>
            <label class="block text-sm font-medium mb-1.5" [style.color]="'var(--text-primary)'">Email</label>
            <input type="email" [value]="email" class="input-base w-full bg-[var(--surface-muted)]" readonly />
          </div>
          <div>
            <label class="block text-sm font-medium mb-1.5" [style.color]="'var(--text-primary)'">Password</label>
            <input type="password" [(ngModel)]="password" name="password" class="input-base w-full" placeholder="Create a strong password" required />
          </div>
          <button type="submit" [disabled]="loading()" class="btn-primary w-full h-10 flex items-center justify-center gap-2">
            @if (loading()) { <lucide-icon [img]="Loader2Icon" [size]="16" class="animate-spin" /> }
            Create Account
          </button>
        </form>
      </div>
    </div>
  `,
})
export class AcceptInvitationComponent implements OnInit {
  private api = inject(ApiService);
  private router = inject(Router);
  private route = inject(ActivatedRoute);
  private toast = inject(ToastService);
  token = ''; email = ''; firstName = ''; lastName = ''; password = '';
  readonly loading = signal(false); readonly error = signal('');
  readonly ShieldIcon = Shield; readonly Loader2Icon = Loader2;

  ngOnInit(): void {
    this.token = this.route.snapshot.queryParams['token'] || '';
    this.email = this.route.snapshot.queryParams['email'] || '';
  }

  onSubmit(): void {
    this.loading.set(true); this.error.set('');
    this.api.post('/invitations/accept', {
      token: this.token, email: this.email, password: this.password,
      first_name: this.firstName, last_name: this.lastName,
    }).subscribe({
      next: () => { this.loading.set(false); this.toast.success('Account created!'); this.router.navigate(['/auth/login']); },
      error: (err) => { this.loading.set(false); this.error.set(err.error?.message || 'Failed to accept invitation.'); },
    });
  }
}
