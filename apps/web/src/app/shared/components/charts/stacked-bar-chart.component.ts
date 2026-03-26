import { Component, input, computed } from '@angular/core';

export interface StackedBarSeries {
  name: string;
  data: number[];
  color?: string;
}

@Component({
  selector: 'g51-stacked-bar-chart',
  standalone: true,
  template: `
    <svg [attr.viewBox]="'0 0 ' + svgWidth() + ' ' + height()" class="w-full" [style.height.px]="height()">
      <!-- Y-axis -->
      @for (tick of yTicks(); track tick) {
        <text [attr.x]="40" [attr.y]="yScale(tick) + 4" font-size="11" [attr.fill]="'var(--text-tertiary)'" text-anchor="end">{{ fmtVal(tick) }}</text>
        <line [attr.x1]="48" [attr.x2]="svgWidth() - 8" [attr.y1]="yScale(tick)" [attr.y2]="yScale(tick)" [attr.stroke]="'var(--border-default)'" stroke-width="1" />
      }
      <!-- Stacked bars -->
      @for (label of labels(); track label; let i = $index) {
        @for (seg of stackSegments(i); track seg.name) {
          <rect [attr.x]="barX(i)" [attr.y]="yScale(seg.top)" [attr.width]="barW()" [attr.height]="Math.max(0, yScale(seg.bottom) - yScale(seg.top))" [attr.fill]="seg.color" [attr.rx]="seg.isTop ? 3 : 0" class="hover:opacity-80 transition-opacity" />
        }
        <text [attr.x]="barX(i) + barW() / 2" [attr.y]="height() - 8" font-size="11" text-anchor="middle" [attr.fill]="'var(--text-tertiary)'">{{ label }}</text>
      }
    </svg>
  `,
})
export class StackedBarChartComponent {
  readonly series = input.required<StackedBarSeries[]>();
  readonly labels = input.required<string[]>();
  readonly height = input(260);
  readonly defaultColors = ['var(--color-brand-500)', 'var(--color-accent-500)', 'var(--color-success)', 'var(--color-warning)', '#8B5CF6'];
  readonly Math = Math;
  readonly pad = 20; readonly leftM = 56; readonly botPad = 32;

  readonly svgWidth = computed(() => Math.max(this.labels().length * 64, 400));
  readonly chartH = computed(() => this.height() - this.pad - this.botPad);
  readonly maxStack = computed(() => {
    const len = this.labels().length;
    let max = 0;
    for (let i = 0; i < len; i++) {
      let sum = 0;
      this.series().forEach(s => sum += (s.data[i] || 0));
      if (sum > max) max = sum;
    }
    return Math.ceil(max * 1.15) || 100;
  });
  readonly yTicks = computed(() => { const m = this.maxStack(); const step = Math.ceil(m / 5); return Array.from({ length: 6 }, (_, i) => i * step).filter(v => v <= m); });

  yScale(val: number): number { return this.pad + this.chartH() * (1 - val / this.maxStack()); }
  barW = computed(() => Math.min((this.svgWidth() - this.leftM - 16) / (this.labels().length * 1.5), 48));
  barX(i: number): number {
    const gap = (this.svgWidth() - this.leftM - this.labels().length * this.barW()) / (this.labels().length + 1);
    return this.leftM + gap + i * (this.barW() + gap);
  }
  stackSegments(i: number): { name: string; bottom: number; top: number; color: string; isTop: boolean }[] {
    const segs: any[] = [];
    let acc = 0;
    this.series().forEach((s, si) => {
      const val = s.data[i] || 0;
      segs.push({ name: s.name, bottom: acc, top: acc + val, color: s.color || this.defaultColors[si % this.defaultColors.length], isTop: si === this.series().length - 1 });
      acc += val;
    });
    return segs;
  }
  fmtVal(v: number): string { return v >= 1e6 ? (v / 1e6).toFixed(1) + 'M' : v >= 1e3 ? (v / 1e3).toFixed(0) + 'K' : v.toString(); }
}
