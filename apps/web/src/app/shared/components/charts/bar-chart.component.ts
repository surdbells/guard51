import { Component, input, computed, ElementRef, inject } from '@angular/core';

export interface BarChartData {
  label: string;
  value: number;
  color?: string;
}

@Component({
  selector: 'g51-bar-chart',
  standalone: true,
  template: `
    <svg [attr.viewBox]="viewBox()" class="w-full" [style.height.px]="height()">
      <!-- Y-axis labels -->
      @for (tick of yTicks(); track tick) {
        <text [attr.x]="padding" [attr.y]="yScale(tick) + 4" font-size="11"
          [attr.fill]="'var(--text-tertiary)'" text-anchor="end">
          {{ formatValue(tick) }}
        </text>
        <line [attr.x1]="leftMargin" [attr.x2]="chartWidth() + leftMargin" [attr.y1]="yScale(tick)"
          [attr.y2]="yScale(tick)" [attr.stroke]="'var(--border-default)'" stroke-width="1" />
      }

      <!-- Bars -->
      @for (item of data(); track item.label; let i = $index) {
        <rect
          [attr.x]="barX(i)"
          [attr.y]="yScale(item.value)"
          [attr.width]="barWidth()"
          [attr.height]="chartHeight() - yScale(item.value) + topPadding"
          [attr.fill]="item.color || colors()[$index % colors().length]"
          [attr.rx]="3"
          class="transition-all duration-300 hover:opacity-80"
        />
        <!-- X-axis label -->
        <text
          [attr.x]="barX(i) + barWidth() / 2"
          [attr.y]="height() - 8"
          font-size="11" text-anchor="middle"
          [attr.fill]="'var(--text-tertiary)'"
        >{{ item.label }}</text>
      }
    </svg>
  `,
})
export class BarChartComponent {
  readonly data = input.required<BarChartData[]>();
  readonly height = input(260);
  readonly colors = input(['var(--color-brand-500)', 'var(--color-accent-500)', 'var(--color-success)', 'var(--color-warning)']);

  readonly padding = 40;
  readonly leftMargin = 48;
  readonly topPadding = 20;
  readonly bottomPadding = 32;
  readonly chartHeight = computed(() => this.height() - this.topPadding - this.bottomPadding);

  readonly viewBox = computed(() => `0 0 ${this.chartWidth() + this.leftMargin + 16} ${this.height()}`);
  readonly chartWidth = computed(() => Math.max(this.data().length * 60, 300));

  readonly maxValue = computed(() => {
    const max = Math.max(...this.data().map(d => d.value), 0);
    return Math.ceil(max * 1.15) || 100;
  });

  readonly yTicks = computed(() => {
    const m = this.maxValue();
    const step = Math.ceil(m / 5);
    const ticks = [];
    for (let i = 0; i <= m; i += step) ticks.push(i);
    return ticks;
  });

  barWidth = computed(() => {
    const count = this.data().length || 1;
    const totalBarArea = this.chartWidth() - count * 8;
    return Math.min(totalBarArea / count, 48);
  });

  barX(i: number): number {
    const gap = (this.chartWidth() - this.data().length * this.barWidth()) / (this.data().length + 1);
    return this.leftMargin + gap + i * (this.barWidth() + gap);
  }

  yScale(value: number): number {
    return this.topPadding + (this.chartHeight() * (1 - value / this.maxValue()));
  }

  formatValue(v: number): string {
    if (v >= 1_000_000) return (v / 1_000_000).toFixed(1) + 'M';
    if (v >= 1_000) return (v / 1_000).toFixed(0) + 'K';
    return v.toString();
  }
}
