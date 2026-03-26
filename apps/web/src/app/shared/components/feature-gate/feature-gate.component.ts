import { Component, inject, input } from '@angular/core';
import { LucideAngularModule, Lock } from 'lucide-angular';
import { FeatureService } from '@core/services/feature.service';

@Component({
  selector: 'g51-feature-gate',
  standalone: true,
  imports: [LucideAngularModule],
  template: `
    @if (isEnabled()) {
      <ng-content />
    } @else {
      <div class="card p-8 flex flex-col items-center justify-center text-center">
        <div class="h-12 w-12 rounded-full flex items-center justify-center mb-3"
          [style.background]="'var(--surface-muted)'" [style.color]="'var(--text-tertiary)'"
        >
          <lucide-icon [img]="LockIcon" [size]="24" />
        </div>
        <h3 class="text-base font-semibold mb-1" [style.color]="'var(--text-primary)'">Feature Not Available</h3>
        <p class="text-sm max-w-sm" [style.color]="'var(--text-secondary)'">
          This feature is not included in your current plan. Please upgrade to access it.
        </p>
        <button class="btn-primary mt-4">Upgrade Plan</button>
      </div>
    }
  `,
})
export class FeatureGateComponent {
  readonly moduleKey = input.required<string>();
  private features = inject(FeatureService);
  readonly LockIcon = Lock;

  isEnabled(): boolean {
    return this.features.isEnabled(this.moduleKey());
  }
}
