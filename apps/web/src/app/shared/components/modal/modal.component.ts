import { Component, input, output } from '@angular/core';
import { LucideAngularModule, X } from 'lucide-angular';

@Component({
  selector: 'g51-modal',
  standalone: true,
  imports: [LucideAngularModule],
  template: `
    @if (open()) {
      <!-- Backdrop -->
      <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/40 animate-fade-in" (click)="dismiss()"></div>

        <!-- Modal panel -->
        <div
          class="relative w-full rounded-xl border animate-scale-in"
          [style.background]="'var(--surface-card)'"
          [style.borderColor]="'var(--border-default)'"
          [style.maxWidth]="maxWidth()"
          style="box-shadow: var(--shadow-modal)"
        >
          <!-- Header -->
          @if (title()) {
            <div class="flex items-center justify-between px-6 py-4 border-b" [style.borderColor]="'var(--border-default)'">
              <h2 class="text-lg font-semibold" [style.color]="'var(--text-primary)'">{{ title() }}</h2>
              <button
                (click)="dismiss()"
                class="p-1.5 rounded-lg hover:bg-[var(--surface-hover)] transition-colors"
                [style.color]="'var(--text-tertiary)'"
              >
                <lucide-icon [img]="XIcon" [size]="18" />
              </button>
            </div>
          }

          <!-- Body -->
          <div class="px-6 py-4">
            <ng-content />
          </div>

          <!-- Footer -->
          <div class="px-6 py-4 border-t flex items-center justify-end gap-2" [style.borderColor]="'var(--border-default)'">
            <ng-content select="[modal-footer]" />
          </div>
        </div>
      </div>
    }
  `,
})
export class ModalComponent {
  readonly open = input(false);
  readonly title = input<string>();
  readonly maxWidth = input('480px');
  readonly closed = output();

  readonly XIcon = X;

  dismiss(): void {
    this.closed.emit();
  }
}
