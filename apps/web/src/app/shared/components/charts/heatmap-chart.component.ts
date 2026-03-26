import { Component, input, computed } from '@angular/core';

export interface HeatmapData { row: string; col: string; value: number; }

@Component({
  selector: 'g51-heatmap-chart',
  standalone: true,
  template: `
    <svg [attr.viewBox]="'0 0 ' + svgW() + ' ' + svgH()" class="w-full" [style.height.px]="svgH()">
      <!-- Column labels -->
      @for (col of cols(); track col; let ci = $index) {
        <text [attr.x]="lm + ci * cellW() + cellW() / 2" [attr.y]="12" font-size="10" text-anchor="middle" [attr.fill]="'var(--text-tertiary)'">{{ col }}</text>
      }
      <!-- Row labels + cells -->
      @for (row of rows(); track row; let ri = $index) {
        <text [attr.x]="lm - 6" [attr.y]="tp + ri * cellH() + cellH() / 2 + 4" font-size="10" text-anchor="end" [attr.fill]="'var(--text-tertiary)'">{{ row }}</text>
        @for (col of cols(); track col; let ci = $index) {
          <rect [attr.x]="lm + ci * cellW()" [attr.y]="tp + ri * cellH()" [attr.width]="cellW() - 2" [attr.height]="cellH() - 2"
            [attr.fill]="cellColor(getValue(row, col))" [attr.rx]="3" class="hover:opacity-80 transition-opacity">
            <title>{{ row }} / {{ col }}: {{ getValue(row, col) }}</title>
          </rect>
        }
      }
    </svg>
  `,
})
export class HeatmapChartComponent {
  readonly data = input.required<HeatmapData[]>();
  readonly rows = input.required<string[]>();
  readonly cols = input.required<string[]>();
  readonly color = input('var(--color-brand-500)');
  readonly cellSize = input(36);

  readonly lm = 64; readonly tp = 20;
  readonly cellW = computed(() => this.cellSize());
  readonly cellH = computed(() => this.cellSize());
  readonly svgW = computed(() => this.lm + this.cols().length * this.cellW() + 8);
  readonly svgH = computed(() => this.tp + this.rows().length * this.cellH() + 8);

  readonly maxVal = computed(() => Math.max(...this.data().map(d => d.value), 1));

  getValue(row: string, col: string): number {
    return this.data().find(d => d.row === row && d.col === col)?.value ?? 0;
  }

  cellColor(val: number): string {
    const pct = val / this.maxVal();
    const alpha = Math.max(0.08, pct * 0.9);
    return `color-mix(in srgb, ${this.color()} ${Math.round(alpha * 100)}%, transparent)`;
  }
}
