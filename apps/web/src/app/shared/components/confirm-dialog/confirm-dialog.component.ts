import { Component, input, output } from '@angular/core';
import { NgClass } from '@angular/common';
import { LucideAngularModule, AlertTriangle, Trash2, Info } from 'lucide-angular';

@Component({
  selector: 'g51-confirm-dialog',
  standalone: true,
  imports: [NgClass, LucideAngularModule],
  template: `
    @if (open()) {
      <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/40 animate-fade-in" (click)="cancel()"></div>
        <div
          class="relative w-full max-w-sm rounded-xl border p-6 animate-scale-in"
          [style.background]="'var(--surface-card)'"
          [style.borderColor]="'var(--border-default)'"
          style="box-shadow: var(--shadow-modal)"
        >
          <div class="flex flex-col items-center text-center">
            <div class="h-12 w-12 rounded-full flex items-center justify-center mb-3"
              [ngClass]="{
                'bg-red-50 text-red-500 dark:bg-red-950': variant() === 'danger',
                'bg-amber-50 text-amber-500 dark:bg-amber-950': variant() === 'warning',
                'bg-blue-50 text-blue-500 dark:bg-blue-950': variant() === 'info'
              }"
            >
              <lucide-icon [img]="variant() === 'danger' ? Trash2Icon : variant() === 'warning' ? AlertTriangleIcon : InfoIcon" [size]="24" />
            </div>
            <h3 class="text-base font-semibold mb-1" [style.color]="'var(--text-primary)'">{{ title() }}</h3>
            <p class="text-sm" [style.color]="'var(--text-secondary)'">{{ message() }}</p>
          </div>
          <div class="flex items-center gap-2 mt-5">
            <button (click)="cancel()" class="btn-secondary flex-1">{{ cancelLabel() }}</button>
            <button
              (click)="confirm()"
              class="flex-1 py-2 px-4 text-sm font-medium rounded-[var(--radius-button)] text-white transition-colors"
              [ngClass]="{
                'bg-red-500 hover:bg-red-600': variant() === 'danger',
                'bg-amber-500 hover:bg-amber-600': variant() === 'warning',
                'bg-blue-500 hover:bg-blue-600': variant() === 'info'
              }"
            >{{ confirmLabel() }}</button>
          </div>
        </div>
      </div>
    }
  `,
})
export class ConfirmDialogComponent {
  readonly open = input(false);
  readonly title = input('Are you sure?');
  readonly message = input('This action cannot be undone.');
  readonly confirmLabel = input('Confirm');
  readonly cancelLabel = input('Cancel');
  readonly variant = input<'danger' | 'warning' | 'info'>('danger');
  readonly confirmed = output();
  readonly cancelled = output();

  readonly AlertTriangleIcon = AlertTriangle;
  readonly Trash2Icon = Trash2;
  readonly InfoIcon = Info;

  confirm(): void { this.confirmed.emit(); }
  cancel(): void { this.cancelled.emit(); }
}
