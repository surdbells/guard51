import { Component, inject } from '@angular/core';
import { NgClass } from '@angular/common';
import { LucideAngularModule, AlertTriangle, Trash2, Ban, CheckCircle, Info } from 'lucide-angular';
import { ConfirmService } from '@core/services/confirm.service';

@Component({
  selector: 'g51-confirm-dialog',
  standalone: true,
  imports: [NgClass, LucideAngularModule],
  template: `
    @if (confirm.isOpen()) {
      <div class="fixed inset-0 z-[60] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/40 animate-fade-in" (click)="confirm.cancel()"></div>
        <div class="relative w-full max-w-sm rounded-xl border animate-scale-in p-6"
          [style.background]="'var(--surface-card)'" [style.borderColor]="'var(--border-default)'"
          style="box-shadow: var(--shadow-modal)">

          <!-- Icon -->
          <div class="flex justify-center mb-4">
            <div class="h-12 w-12 rounded-full flex items-center justify-center"
              [ngClass]="confirm.config().variant === 'danger' ? 'bg-red-50' : confirm.config().variant === 'warning' ? 'bg-amber-50' : confirm.config().variant === 'success' ? 'bg-emerald-50' : 'bg-blue-50'">
              <lucide-icon [img]="getIcon()" [size]="22"
                [ngClass]="confirm.config().variant === 'danger' ? 'text-red-500' : confirm.config().variant === 'warning' ? 'text-amber-500' : confirm.config().variant === 'success' ? 'text-emerald-500' : 'text-blue-500'" />
            </div>
          </div>

          <!-- Title -->
          <h3 class="text-base font-semibold text-center mb-1" [style.color]="'var(--text-primary)'">
            {{ confirm.config().title }}
          </h3>

          <!-- Message -->
          <p class="text-sm text-center mb-6" [style.color]="'var(--text-secondary)'">
            {{ confirm.config().message }}
          </p>

          <!-- Actions -->
          <div class="flex gap-3">
            <button (click)="confirm.cancel()" class="flex-1 py-2.5 px-4 rounded-lg text-sm font-medium border transition-colors"
              [style.background]="'var(--surface-card)'" [style.borderColor]="'var(--border-default)'"
              [style.color]="'var(--text-secondary)'">
              {{ confirm.config().cancelText || 'Cancel' }}
            </button>
            <button (click)="confirm.ok()" class="flex-1 py-2.5 px-4 rounded-lg text-sm font-medium transition-colors text-white"
              [ngClass]="confirm.config().variant === 'danger' ? 'bg-red-500 hover:bg-red-600' : confirm.config().variant === 'warning' ? 'bg-amber-500 hover:bg-amber-600' : confirm.config().variant === 'success' ? 'bg-emerald-500 hover:bg-emerald-600' : 'bg-[var(--color-brand-500)] hover:bg-[var(--color-brand-600)]'">
              {{ confirm.config().confirmText || 'Confirm' }}
            </button>
          </div>
        </div>
      </div>
    }
  `,
})
export class ConfirmDialogComponent {
  readonly confirm = inject(ConfirmService);
  private readonly icons = { danger: Trash2, warning: AlertTriangle, info: Info, success: CheckCircle };

  getIcon() {
    return this.icons[this.confirm.config().variant || 'warning'] || AlertTriangle;
  }
}
