import { Component, input, computed } from '@angular/core';

export interface AreaChartSeries { name: string; data: number[]; color?: string; }

@Component({
  selector: 'g51-area-chart',
  standalone: true,
  template: `
    <svg [attr.viewBox]="'0 0 ' + svgW() + ' ' + height()" class="w-full" [style.height.px]="height()">
      @for (tick of yTicks(); track tick) {
        <text [attr.x]="40" [attr.y]="yS(tick) + 4" font-size="11" [attr.fill]="'var(--text-tertiary)'" text-anchor="end">{{ fmt(tick) }}</text>
        <line [attr.x1]="48" [attr.x2]="svgW() - 8" [attr.y1]="yS(tick)" [attr.y2]="yS(tick)" [attr.stroke]="'var(--border-default)'" stroke-width="1" />
      }
      @for (label of labels(); track label; let i = $index) {
        <text [attr.x]="xS(i)" [attr.y]="height() - 8" font-size="11" text-anchor="middle" [attr.fill]="'var(--text-tertiary)'">{{ label }}</text>
      }
      @for (s of series(); track s.name; let si = $index) {
        <path [attr.d]="areaPath(s)" [attr.fill]="s.color || colors[si % colors.length]" opacity="0.15" />
        <path [attr.d]="linePath(s)" fill="none" [attr.stroke]="s.color || colors[si % colors.length]" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
      }
    </svg>
  `,
})
export class AreaChartComponent {
  readonly series = input.required<AreaChartSeries[]>();
  readonly labels = input.required<string[]>();
  readonly height = input(260);
  readonly colors = ['var(--color-brand-500)', 'var(--color-accent-500)', 'var(--color-success)'];
  readonly lm = 56; readonly tp = 16; readonly bp = 32;

  readonly svgW = computed(() => Math.max(this.labels().length * 64, 400));
  readonly cH = computed(() => this.height() - this.tp - this.bp);
  readonly maxV = computed(() => { const vals = this.series().flatMap(s => s.data); return Math.ceil(Math.max(...vals, 0) * 1.15) || 100; });
  readonly yTicks = computed(() => { const m = this.maxV(); const step = Math.ceil(m / 5); return Array.from({ length: 6 }, (_, i) => i * step).filter(v => v <= m); });

  xS(i: number): number { const n = this.labels().length || 1; return this.lm + ((this.svgW() - this.lm - 16) / (n - 1 || 1)) * i; }
  yS(v: number): number { return this.tp + this.cH() * (1 - v / this.maxV()); }
  linePath(s: AreaChartSeries): string { return s.data.map((v, i) => `${i === 0 ? 'M' : 'L'}${this.xS(i)},${this.yS(v)}`).join(' '); }
  areaPath(s: AreaChartSeries): string {
    const line = this.linePath(s);
    const b = this.tp + this.cH();
    return `${line} L${this.xS(s.data.length - 1)},${b} L${this.xS(0)},${b} Z`;
  }
  fmt(v: number): string { return v >= 1e6 ? (v / 1e6).toFixed(1) + 'M' : v >= 1e3 ? (v / 1e3).toFixed(0) + 'K' : v.toString(); }
}
