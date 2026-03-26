import { Component, input } from '@angular/core';
import { NgClass } from '@angular/common';
import { LucideAngularModule, TrendingUp, TrendingDown } from 'lucide-angular';

@Component({
  selector: 'g51-stats-card',
  standalone: true,
  imports: [NgClass, LucideAngularModule],
  template: `
    <div class="card card-hover p-5 flex flex-col gap-3">
      <div class="flex items-center justify-between">
        <span class="text-sm font-medium" [style.color]="'var(--text-secondary)'">{{ label() }}</span>
        @if (icon()) {
          <div class="h-9 w-9 rounded-lg flex items-center justify-center"
            [style.background]="'var(--surface-muted)'"
            [style.color]="'var(--text-secondary)'"
          >
            <lucide-icon [img]="icon()" [size]="18" />
          </div>
        }
      </div>
      <div>
        <p class="text-2xl font-bold tracking-tight" [style.color]="'var(--text-primary)'">{{ value() }}</p>
        @if (trend() !== undefined) {
          <div class="flex items-center gap-1 mt-1">
            <lucide-icon
              [img]="trend()! >= 0 ? TrendingUpIcon : TrendingDownIcon"
              [size]="14"
              [ngClass]="trend()! >= 0 ? 'text-emerald-500' : 'text-red-500'"
            />
            <span class="text-xs font-medium"
              [ngClass]="trend()! >= 0 ? 'text-emerald-500' : 'text-red-500'"
            >{{ trend()! >= 0 ? '+' : '' }}{{ trend() }}%</span>
            @if (trendLabel()) {
              <span class="text-xs" [style.color]="'var(--text-tertiary)'">{{ trendLabel() }}</span>
            }
          </div>
        }
      </div>
    </div>
  `,
})
export class StatsCardComponent {
  readonly label = input.required<string>();
  readonly value = input.required<string | number>();
  readonly icon = input<any>();
  readonly trend = input<number>();
  readonly trendLabel = input<string>();

  readonly TrendingUpIcon = TrendingUp;
  readonly TrendingDownIcon = TrendingDown;
}
