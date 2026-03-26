import { Component, input, computed } from '@angular/core';

export interface LineChartSeries {
  name: string;
  data: number[];
  color?: string;
}

@Component({
  selector: 'g51-line-chart',
  standalone: true,
  template: `
    <svg [attr.viewBox]="'0 0 ' + svgWidth() + ' ' + height()" class="w-full" [style.height.px]="height()">
      <!-- Grid lines -->
      @for (tick of yTicks(); track tick) {
        <text [attr.x]="40" [attr.y]="yScale(tick) + 4" font-size="11"
          [attr.fill]="'var(--text-tertiary)'" text-anchor="end">{{ formatValue(tick) }}</text>
        <line [attr.x1]="48" [attr.x2]="svgWidth() - 8" [attr.y1]="yScale(tick)"
          [attr.y2]="yScale(tick)" [attr.stroke]="'var(--border-default)'" stroke-width="1" />
      }

      <!-- X-axis labels -->
      @for (label of labels(); track label; let i = $index) {
        <text [attr.x]="xScale(i)" [attr.y]="height() - 8" font-size="11"
          text-anchor="middle" [attr.fill]="'var(--text-tertiary)'">{{ label }}</text>
      }

      <!-- Lines -->
      @for (series of seriesData(); track series.name) {
        <!-- Area fill -->
        <path [attr.d]="areaPath(series)" [attr.fill]="series.color || 'var(--color-brand-500)'" opacity="0.08" />
        <!-- Line -->
        <path [attr.d]="linePath(series)" fill="none" [attr.stroke]="series.color || 'var(--color-brand-500)'"
          stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
        <!-- Dots -->
        @for (val of series.data; track $index; let i = $index) {
          <circle [attr.cx]="xScale(i)" [attr.cy]="yScale(val)" r="3"
            [attr.fill]="'var(--surface-card)'" [attr.stroke]="series.color || 'var(--color-brand-500)'"
            stroke-width="2" class="hover:r-5 transition-all" />
        }
      }
    </svg>
  `,
})
export class LineChartComponent {
  readonly seriesData = input.required<LineChartSeries[]>();
  readonly labels = input.required<string[]>();
  readonly height = input(260);

  readonly leftMargin = 56;
  readonly topPad = 16;
  readonly bottomPad = 32;

  readonly svgWidth = computed(() => Math.max(this.labels().length * 64, 400));
  readonly chartH = computed(() => this.height() - this.topPad - this.bottomPad);

  readonly maxValue = computed(() => {
    const vals = this.seriesData().flatMap(s => s.data);
    return Math.ceil(Math.max(...vals, 0) * 1.15) || 100;
  });

  readonly yTicks = computed(() => {
    const m = this.maxValue();
    const step = Math.ceil(m / 5);
    return Array.from({ length: 6 }, (_, i) => i * step).filter(v => v <= m);
  });

  xScale(i: number): number {
    const count = this.labels().length || 1;
    const chartW = this.svgWidth() - this.leftMargin - 16;
    return this.leftMargin + (chartW / (count - 1 || 1)) * i;
  }

  yScale(val: number): number {
    return this.topPad + this.chartH() * (1 - val / this.maxValue());
  }

  linePath(series: LineChartSeries): string {
    return series.data.map((v, i) => `${i === 0 ? 'M' : 'L'}${this.xScale(i)},${this.yScale(v)}`).join(' ');
  }

  areaPath(series: LineChartSeries): string {
    const line = this.linePath(series);
    const lastX = this.xScale(series.data.length - 1);
    const firstX = this.xScale(0);
    const bottom = this.topPad + this.chartH();
    return `${line} L${lastX},${bottom} L${firstX},${bottom} Z`;
  }

  formatValue(v: number): string {
    if (v >= 1_000_000) return (v / 1_000_000).toFixed(1) + 'M';
    if (v >= 1_000) return (v / 1_000).toFixed(0) + 'K';
    return v.toString();
  }
}
