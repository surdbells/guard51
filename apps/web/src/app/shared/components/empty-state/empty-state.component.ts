import { Component, input } from '@angular/core';
import { LucideAngularModule, Inbox } from 'lucide-angular';

@Component({
  selector: 'g51-empty-state',
  standalone: true,
  imports: [LucideAngularModule],
  template: `
    <div class="flex flex-col items-center justify-center py-16 px-4">
      <div class="h-14 w-14 rounded-full flex items-center justify-center mb-4"
        [style.background]="'var(--surface-muted)'"
        [style.color]="'var(--text-tertiary)'"
      >
        <lucide-icon [img]="icon() || InboxIcon" [size]="28" />
      </div>
      <h3 class="text-base font-semibold mb-1" [style.color]="'var(--text-primary)'">{{ title() }}</h3>
      @if (message()) {
        <p class="text-sm text-center max-w-sm" [style.color]="'var(--text-secondary)'">{{ message() }}</p>
      }
      <div class="mt-4">
        <ng-content />
      </div>
    </div>
  `,
})
export class EmptyStateComponent {
  readonly title = input('No data available');
  readonly message = input<string>();
  readonly icon = input<any>();
  readonly InboxIcon = Inbox;
}
