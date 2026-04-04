import { Injectable, inject, ApplicationRef } from '@angular/core';
import { SwUpdate, VersionReadyEvent } from '@angular/service-worker';
import { filter, first } from 'rxjs';
import { ToastService } from './toast.service';

@Injectable({ providedIn: 'root' })
export class SwUpdateService {
  private swUpdate = inject(SwUpdate);
  private toast = inject(ToastService);
  private appRef = inject(ApplicationRef);

  init(): void {
    if (!this.swUpdate.isEnabled) return;

    // Check for updates after app is stable
    this.appRef.isStable.pipe(first(s => s)).subscribe(() => {
      this.swUpdate.checkForUpdate();
    });

    // Prompt user on new version
    this.swUpdate.versionUpdates
      .pipe(filter((evt): evt is VersionReadyEvent => evt.type === 'VERSION_READY'))
      .subscribe(() => {
        this.toast.info('New version available', 'Reloading to update...');
        setTimeout(() => {
          this.swUpdate.activateUpdate().then(() => document.location.reload());
        }, 2000);
      });

    // Check every 30 minutes
    setInterval(() => this.swUpdate.checkForUpdate(), 30 * 60 * 1000);
  }
}
