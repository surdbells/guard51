import { Observable, ObservableArray, Dialogs } from '@nativescript/core';
import { ApiService } from '../../services/api.service';

export class ToursViewModel extends Observable {
  tourName = 'Loading...';
  scannedCount = 0;
  totalCheckpoints = 0;
  progress = 0;
  checkpoints = new ObservableArray<any>([]);
  private sessionId = '';

  async init(): Promise<void> {
    try {
      const res = await ApiService.get('/tours/active');
      const tour = res.data?.tour || {};
      this.set('tourName', tour.name || 'No Active Tour');
      const cps = tour.checkpoints || [];
      this.set('totalCheckpoints', cps.length);
      this.checkpoints.splice(0, this.checkpoints.length, ...cps);
      this.sessionId = tour.session_id || '';
      this.updateProgress();
    } catch (e) {
      this.set('tourName', 'No tour assigned');
    }
  }

  async onScan(): Promise<void> {
    try {
      // Try native barcode scanner if available
      let code = '';
      try {
        const { BarcodeScanner } = require('nativescript-barcodescanner');
        const scanner = new BarcodeScanner();
        const result = await scanner.scan({
          formats: 'QR_CODE',
          message: 'Scan checkpoint QR code',
          showFlipCameraButton: true,
        });
        code = result.text;
      } catch (scanError) {
        // Fallback: manual code entry
        const result = await Dialogs.prompt({
          title: 'Enter Checkpoint Code',
          message: 'Camera not available. Enter the checkpoint code manually:',
          okButtonText: 'Submit',
          cancelButtonText: 'Cancel',
          inputType: 'text',
        });
        if (result.result && result.text) {
          code = result.text.trim();
        }
      }

      if (code) {
        const res = await ApiService.post('/tours/scan', {
          checkpoint_code: code,
          session_id: this.sessionId,
        });
        if (res.data?.checkpoint) {
          this.set('scannedCount', this.scannedCount + 1);
          this.updateProgress();
        }
      }
    } catch (e) {
      console.error('Scan error:', e);
    }
  }

  private updateProgress(): void {
    const pct = this.totalCheckpoints > 0
      ? Math.round((this.scannedCount / this.totalCheckpoints) * 100)
      : 0;
    this.set('progress', pct);
  }
}
