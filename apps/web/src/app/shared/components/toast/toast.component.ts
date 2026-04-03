import { Component, inject } from '@angular/core';
import { LucideAngularModule, X, CheckCircle, AlertTriangle, AlertCircle, Info } from 'lucide-angular';
import { ToastService, Toast } from '@core/services/toast.service';

@Component({
  selector: 'g51-toast',
  standalone: true,
  imports: [LucideAngularModule],
  template: `
    <div class="fixed top-4 right-4 z-[9999] flex flex-col gap-2 max-w-sm w-full pointer-events-none">
      @for (toast of toastService.toasts(); track toast.id) {
        <div
          class="pointer-events-auto animate-slide-in-right rounded-xl px-4 py-3 flex items-start gap-3"
          [style]="getStyle(toast)"
        >
          <div class="shrink-0 mt-0.5" [style.color]="getIconColor(toast)">
            @switch (toast.type) {
              @case ('success') { <lucide-icon [img]="CheckCircleIcon" [size]="18" /> }
              @case ('error') { <lucide-icon [img]="AlertCircleIcon" [size]="18" /> }
              @case ('warning') { <lucide-icon [img]="AlertTriangleIcon" [size]="18" /> }
              @case ('info') { <lucide-icon [img]="InfoIcon" [size]="18" /> }
            }
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-sm font-medium" [style.color]="getTextColor(toast)">{{ toast.title }}</p>
            @if (toast.message) {
              <p class="text-xs mt-0.5 opacity-80" [style.color]="getTextColor(toast)">{{ toast.message }}</p>
            }
          </div>
          <button (click)="toastService.dismiss(toast.id)"
            class="shrink-0 p-0.5 rounded transition-colors opacity-60 hover:opacity-100"
            [style.color]="getTextColor(toast)">
            <lucide-icon [img]="XIcon" [size]="14" />
          </button>
        </div>
      }
    </div>
  `,
})
export class ToastComponent {
  readonly toastService = inject(ToastService);
  readonly XIcon = X; readonly CheckCircleIcon = CheckCircle;
  readonly AlertTriangleIcon = AlertTriangle; readonly AlertCircleIcon = AlertCircle; readonly InfoIcon = Info;

  getStyle(t: Toast): string {
    const styles: Record<string, string> = {
      success: 'background:#F0FDF4;border:1px solid #86EFAC;box-shadow:0 4px 24px rgba(0,0,0,0.08)',
      error: 'background:#FEF2F2;border:1px solid #FCA5A5;box-shadow:0 4px 24px rgba(0,0,0,0.08)',
      warning: 'background:#FFFBEB;border:1px solid #FCD34D;box-shadow:0 4px 24px rgba(0,0,0,0.08)',
      info: 'background:#EFF6FF;border:1px solid #93C5FD;box-shadow:0 4px 24px rgba(0,0,0,0.08)',
    };
    return styles[t.type] || styles.info;
  }
  getIconColor(t: Toast): string {
    const map: Record<string, string> = { success: '#16A34A', error: '#DC2626', warning: '#D97706', info: '#2563EB' };
    return map[t.type] || '#6B7280';
  }
  getTextColor(t: Toast): string {
    const map: Record<string, string> = { success: '#14532D', error: '#7F1D1D', warning: '#78350F', info: '#1E3A8A' };
    return map[t.type] || '#1F2937';
  }
}
