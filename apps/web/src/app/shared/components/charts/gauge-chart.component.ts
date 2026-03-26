import { Component, input, computed } from '@angular/core';

@Component({
  selector: 'g51-gauge-chart',
  standalone: true,
  template: `
    <svg [attr.viewBox]="'0 0 ' + size() + ' ' + (size() / 2 + 20)" [style.width.px]="size()">
      <!-- Background arc -->
      <path [attr.d]="arcPath()" fill="none" [attr.stroke]="'var(--surface-muted)'" [attr.stroke-width]="strokeW()" stroke-linecap="round" />
      <!-- Value arc -->
      <path [attr.d]="arcPath()" fill="none" [attr.stroke]="gaugeColor()" [attr.stroke-width]="strokeW()" stroke-linecap="round"
        [attr.stroke-dasharray]="dashArray()" [attr.stroke-dashoffset]="dashOffset()"
        class="transition-all duration-700"
      />
      <!-- Center value -->
      <text [attr.x]="center()" [attr.y]="center() + 4" text-anchor="middle"
        font-size="24" font-weight="700" [attr.fill]="'var(--text-primary)'">
        {{ value() }}{{ suffix() }}
      </text>
      @if (label()) {
        <text [attr.x]="center()" [attr.y]="center() + 22" text-anchor="middle"
          font-size="11" [attr.fill]="'var(--text-tertiary)'">
          {{ label() }}
        </text>
      }
    </svg>
  `,
})
export class GaugeChartComponent {
  readonly value = input.required<number>();
  readonly max = input(100);
  readonly size = input(160);
  readonly label = input<string>();
  readonly suffix = input('%');
  readonly color = input<string>();
  readonly strokeW = input(12);

  readonly center = computed(() => this.size() / 2);
  readonly radius = computed(() => (this.size() - this.strokeW()) / 2 - 4);

  readonly pct = computed(() => Math.min(this.value() / this.max(), 1));
  readonly arcLength = computed(() => Math.PI * this.radius());
  readonly dashArray = computed(() => `${this.arcLength()} ${this.arcLength()}`);
  readonly dashOffset = computed(() => this.arcLength() * (1 - this.pct()));

  readonly gaugeColor = computed(() => {
    if (this.color()) return this.color()!;
    const p = this.pct();
    if (p >= 0.8) return 'var(--color-success)';
    if (p >= 0.5) return 'var(--color-warning)';
    return 'var(--color-danger)';
  });

  readonly arcPath = computed(() => {
    const cx = this.center();
    const r = this.radius();
    return `M ${cx - r} ${cx} A ${r} ${r} 0 0 1 ${cx + r} ${cx}`;
  });
}
