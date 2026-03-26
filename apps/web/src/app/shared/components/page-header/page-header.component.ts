import { Component, input } from '@angular/core';

@Component({
  selector: 'g51-page-header',
  standalone: true,
  template: `
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
      <div>
        <h1 class="text-xl md:text-2xl font-semibold tracking-tight" [style.color]="'var(--text-primary)'">
          {{ title() }}
        </h1>
        @if (subtitle()) {
          <p class="text-sm mt-1" [style.color]="'var(--text-secondary)'">{{ subtitle() }}</p>
        }
      </div>
      <div class="flex items-center gap-2 shrink-0">
        <ng-content />
      </div>
    </div>
  `,
})
export class PageHeaderComponent {
  readonly title = input.required<string>();
  readonly subtitle = input<string>();
}
