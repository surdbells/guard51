import { Injectable, signal } from '@angular/core';

export interface ConfirmConfig {
  title: string;
  message: string;
  confirmText?: string;
  cancelText?: string;
  variant?: 'danger' | 'warning' | 'info' | 'success';
}

@Injectable({ providedIn: 'root' })
export class ConfirmService {
  readonly isOpen = signal(false);
  readonly config = signal<ConfirmConfig>({ title: '', message: '' });
  private resolver: ((value: boolean) => void) | null = null;

  /**
   * Show confirmation dialog.
   * Returns a Promise<boolean> — true if confirmed, false if cancelled.
   *
   * Usage:
   *   const ok = await this.confirm.show({ title: 'Delete?', message: '...', variant: 'danger' });
   *   if (ok) { ... }
   */
  show(config: ConfirmConfig): Promise<boolean> {
    this.config.set({ confirmText: 'Confirm', cancelText: 'Cancel', variant: 'warning', ...config });
    this.isOpen.set(true);
    return new Promise(resolve => { this.resolver = resolve; });
  }

  ok(): void { this.isOpen.set(false); this.resolver?.(true); this.resolver = null; }
  cancel(): void { this.isOpen.set(false); this.resolver?.(false); this.resolver = null; }

  // Convenience methods
  delete(itemName: string): Promise<boolean> {
    return this.show({ title: 'Delete ' + itemName + '?', message: 'This action cannot be undone. All associated data will be permanently removed.', confirmText: 'Delete', variant: 'danger' });
  }

  suspend(itemName: string): Promise<boolean> {
    return this.show({ title: 'Suspend ' + itemName + '?', message: 'This will immediately revoke access. You can reactivate later.', confirmText: 'Suspend', variant: 'warning' });
  }

  deactivate(itemName: string): Promise<boolean> {
    return this.show({ title: 'Deactivate ' + itemName + '?', message: 'This will disable the item. You can re-enable later.', confirmText: 'Deactivate', variant: 'warning' });
  }
}
