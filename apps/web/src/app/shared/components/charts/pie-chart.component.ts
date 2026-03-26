import { Component, input, computed } from '@angular/core';

export interface PieChartData { label: string; value: number; color?: string; }

@Component({
  selector: 'g51-pie-chart',
  standalone: true,
  template: `
    <div class="flex items-center gap-6">
      <svg [attr.viewBox]="'0 0 ' + size() + ' ' + size()" [style.width.px]="size()" [style.height.px]="size()">
        @for (slice of slices(); track slice.label) {
          <path [attr.d]="slice.path" [attr.fill]="slice.color" class="hover:opacity-80 transition-opacity" stroke="var(--surface-card)" stroke-width="2" />
        }
      </svg>
      <div class="flex flex-col gap-2 min-w-0">
        @for (item of data(); track item.label; let i = $index) {
          <div class="flex items-center gap-2 text-sm">
            <div class="h-2.5 w-2.5 rounded-full shrink-0" [style.background]="item.color || colors[i % colors.length]"></div>
            <span class="truncate" [style.color]="'var(--text-secondary)'">{{ item.label }}</span>
            <span class="ml-auto font-medium tabular-nums" [style.color]="'var(--text-primary)'">{{ pct(item.value) }}%</span>
          </div>
        }
      </div>
    </div>
  `,
})
export class PieChartComponent {
  readonly data = input.required<PieChartData[]>();
  readonly size = input(160);
  readonly colors = ['var(--color-brand-500)', 'var(--color-accent-500)', 'var(--color-success)', 'var(--color-warning)', '#8B5CF6', '#EC4899'];

  readonly total = computed(() => this.data().reduce((s, d) => s + d.value, 0));
  readonly cx = computed(() => this.size() / 2);
  readonly r = computed(() => this.size() / 2 - 2);

  pct(v: number): string { return this.total() > 0 ? ((v / this.total()) * 100).toFixed(1) : '0'; }

  readonly slices = computed(() => {
    const cx = this.cx(), r = this.r(), total = this.total();
    let angle = -Math.PI / 2;
    return this.data().map((d, i) => {
      const pct = total > 0 ? d.value / total : 0;
      const da = pct * 2 * Math.PI;
      const x1 = cx + r * Math.cos(angle), y1 = cx + r * Math.sin(angle);
      const x2 = cx + r * Math.cos(angle + da), y2 = cx + r * Math.sin(angle + da);
      const large = da > Math.PI ? 1 : 0;
      const path = `M${cx},${cx} L${x1.toFixed(2)},${y1.toFixed(2)} A${r},${r} 0 ${large} 1 ${x2.toFixed(2)},${y2.toFixed(2)} Z`;
      angle += da;
      return { label: d.label, path, color: d.color || this.colors[i % this.colors.length] };
    });
  });
}
