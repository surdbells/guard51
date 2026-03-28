import { Component, input, computed } from '@angular/core';

export interface DonutChartData {
  label: string;
  value: number;
  color?: string;
}

@Component({
  selector: 'g51-donut-chart',
  standalone: true,
  template: `
    <div class="flex items-center gap-6">
      <svg [attr.viewBox]="'0 0 ' + size() + ' ' + size()" [style.width.px]="size()" [style.height.px]="size()">
        @for (segment of segments(); track segment.label) {
          <circle
            [attr.cx]="center()" [attr.cy]="center()" [attr.r]="radius()"
            fill="none"
            [attr.stroke]="segment.color"
            [attr.stroke-width]="strokeWidth()"
            [attr.stroke-dasharray]="segment.dashArray"
            [attr.stroke-dashoffset]="segment.dashOffset"
            stroke-linecap="round"
            class="transition-all duration-500"
            [attr.transform]="'rotate(-90, ' + center() + ', ' + center() + ')'"
          />
        }
        <!-- Center text -->
        @if (centerLabel()) {
          <text [attr.x]="center()" [attr.y]="center() - 6" text-anchor="middle"
            font-size="22" font-weight="700" [attr.fill]="'var(--text-primary)'">
            {{ centerValue() }}
          </text>
          <text [attr.x]="center()" [attr.y]="center() + 14" text-anchor="middle"
            font-size="11" [attr.fill]="'var(--text-tertiary)'">
            {{ centerLabel() }}
          </text>
        }
      </svg>

      <!-- Legend -->
      <div class="flex flex-col gap-2.5 min-w-0">
        @for (item of data(); track item.label) {
          <div class="flex items-center gap-2 text-sm">
            <div class="h-2.5 w-2.5 rounded-full shrink-0" [style.background]="item.color || defaultColors()[$index % defaultColors().length]"></div>
            <span class="truncate" [style.color]="'var(--text-secondary)'">{{ item.label }}</span>
            <span class="ml-auto font-medium tabular-nums" [style.color]="'var(--text-primary)'">{{ formatValue(item.value) }}</span>
          </div>
        }
      </div>
    </div>
  `,
})
export class DonutChartComponent {
  readonly data = input.required<DonutChartData[]>();
  readonly size = input(160);
  readonly strokeWidth = input(24);
  readonly centerLabel = input<string>();
  readonly centerValue = input<string>();

  readonly center = computed(() => this.size() / 2);
  readonly radius = computed(() => (this.size() - this.strokeWidth()) / 2);
  readonly circumference = computed(() => 2 * Math.PI * this.radius());

  readonly defaultColors = () => ['var(--color-brand-500)', 'var(--color-accent-500)', 'var(--color-success)', 'var(--color-warning)', '#8B5CF6', '#EC4899'];

  readonly total = computed(() => this.data().reduce((sum, d) => sum + d.value, 0));

  readonly segments = computed(() => {
    const circ = this.circumference();
    const total = this.total();
    let offset = 0;

    return this.data().map((d, i) => {
      const pct = total > 0 ? d.value / total : 0;
      const dashLen = circ * pct;
      const gap = circ - dashLen;
      const seg = {
        label: d.label,
        color: d.color || this.defaultColors()[i % this.defaultColors().length],
        dashArray: `${dashLen} ${gap}`,
        dashOffset: `${-offset}`,
      };
      offset += dashLen;
      return seg;
    });
  });

  formatValue(v: number): string {
    if (v >= 1_000_000) return '₦' + (v / 1_000_000).toFixed(1) + 'M';
    if (v >= 1_000) return '₦' + (v / 1_000).toFixed(0) + 'K';
    return v.toLocaleString();
  }
}
