import { Component, input } from '@angular/core';

@Component({
  selector: 'g51-loading',
  standalone: true,
  template: `
    <div class="flex items-center justify-center" [style.padding]="fullPage() ? '4rem 0' : '1rem 0'">
      <div class="animate-spin rounded-full border-2 border-t-transparent"
        [style.borderColor]="'var(--border-strong)'"
        [style.borderTopColor]="'transparent'"
        [style.width.px]="size()"
        [style.height.px]="size()"
      ></div>
    </div>
  `,
})
export class LoadingSpinnerComponent {
  readonly size = input(32);
  readonly fullPage = input(false);
}
