import { Component, input, computed } from '@angular/core';

@Component({
  selector: 'g51-sparkline',
  standalone: true,
  template: `
    <svg [attr.viewBox]="'0 0 ' + width() + ' ' + height()" [style.width.px]="width()" [style.height.px]="height()">
      <path [attr.d]="areaPath()" [attr.fill]="color()" opacity="0.1" />
      <path [attr.d]="linePath()" fill="none" [attr.stroke]="color()" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
    </svg>
  `,
})
export class SparklineComponent {
  readonly data = input.required<number[]>();
  readonly width = input(100);
  readonly height = input(32);
  readonly color = input('var(--color-brand-500)');

  private readonly pad = 2;

  readonly linePath = computed(() => {
    const d = this.data();
    if (d.length < 2) return '';
    const max = Math.max(...d, 1);
    const min = Math.min(...d, 0);
    const range = max - min || 1;
    const w = this.width() - this.pad * 2;
    const h = this.height() - this.pad * 2;
    const step = w / (d.length - 1);

    return d.map((v, i) => {
      const x = this.pad + i * step;
      const y = this.pad + h * (1 - (v - min) / range);
      return `${i === 0 ? 'M' : 'L'}${x.toFixed(1)},${y.toFixed(1)}`;
    }).join(' ');
  });

  readonly areaPath = computed(() => {
    const line = this.linePath();
    if (!line) return '';
    const d = this.data();
    const w = this.width() - this.pad * 2;
    const step = w / (d.length - 1);
    const lastX = this.pad + (d.length - 1) * step;
    const bottom = this.height() - this.pad;
    return `${line} L${lastX.toFixed(1)},${bottom} L${this.pad},${bottom} Z`;
  });
}
