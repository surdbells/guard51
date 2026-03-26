import { Component, inject } from '@angular/core';
import { NgClass } from '@angular/common';
import { LucideAngularModule, X, CheckCircle, AlertTriangle, AlertCircle, Info } from 'lucide-angular';
import { ToastService, Toast } from '@core/services/toast.service';

@Component({
  selector: 'g51-toast',
  standalone: true,
  imports: [NgClass, LucideAngularModule],
  template: `
    <div class="fixed top-4 right-4 z-[9999] flex flex-col gap-2 max-w-sm w-full pointer-events-none">
      @for (toast of toastService.toasts(); track toast.id) {
        <div
          class="pointer-events-auto animate-slide-in-right rounded-[var(--radius-card)] border px-4 py-3 shadow-lg flex items-start gap-3"
          [ngClass]="{
            'bg-emerald-50 border-emerald-200 dark:bg-emerald-950 dark:border-emerald-800': toast.type === 'success',
            'bg-red-50 border-red-200 dark:bg-red-950 dark:border-red-800': toast.type === 'error',
            'bg-amber-50 border-amber-200 dark:bg-amber-950 dark:border-amber-800': toast.type === 'warning',
            'bg-blue-50 border-blue-200 dark:bg-blue-950 dark:border-blue-800': toast.type === 'info'
          }"
        >
          <!-- Icon -->
          <div class="shrink-0 mt-0.5" [ngClass]="{
            'text-emerald-600 dark:text-emerald-400': toast.type === 'success',
            'text-red-600 dark:text-red-400': toast.type === 'error',
            'text-amber-600 dark:text-amber-400': toast.type === 'warning',
            'text-blue-600 dark:text-blue-400': toast.type === 'info'
          }">
            @switch (toast.type) {
              @case ('success') { <lucide-icon [img]="CheckCircleIcon" [size]="18" /> }
              @case ('error') { <lucide-icon [img]="AlertCircleIcon" [size]="18" /> }
              @case ('warning') { <lucide-icon [img]="AlertTriangleIcon" [size]="18" /> }
              @case ('info') { <lucide-icon [img]="InfoIcon" [size]="18" /> }
            }
          </div>

          <!-- Content -->
          <div class="flex-1 min-w-0">
            <p class="text-sm font-medium" style="color: var(--text-primary)">{{ toast.title }}</p>
            @if (toast.message) {
              <p class="text-xs mt-0.5" style="color: var(--text-secondary)">{{ toast.message }}</p>
            }
          </div>

          <!-- Dismiss -->
          <button
            (click)="toastService.dismiss(toast.id)"
            class="shrink-0 p-0.5 rounded hover:bg-black/5 dark:hover:bg-white/5 transition-colors"
            style="color: var(--text-tertiary)"
          >
            <lucide-icon [img]="XIcon" [size]="14" />
          </button>
        </div>
      }
    </div>
  `,
})
export class ToastComponent {
  readonly toastService = inject(ToastService);

  readonly XIcon = X;
  readonly CheckCircleIcon = CheckCircle;
  readonly AlertTriangleIcon = AlertTriangle;
  readonly AlertCircleIcon = AlertCircle;
  readonly InfoIcon = Info;
}
