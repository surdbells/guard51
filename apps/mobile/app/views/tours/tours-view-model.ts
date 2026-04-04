import { Observable, ObservableArray } from '@nativescript/core';
import { requestCameraPermissions } from '@nativescript/camera';
import { BarcodeScanner } from '@nativescript/barcodescanner';
import { ApiService } from '../../services/api.service';
export class ToursViewModel extends Observable {
  private api = new ApiService();
  tourName = 'Loading...'; scannedCount = 0; totalCheckpoints = 0; progress = 0;
  checkpoints = new ObservableArray<any>([]);
  private sessionId = '';
  async init(): Promise<void> {
    try {
      const res = await this.api.get('/tours/active');
      const tour = res.data?.tour || {};
      this.set('tourName', tour.name || 'No Active Tour');
      const cps = tour.checkpoints || [];
      this.set('totalCheckpoints', cps.length);
      this.checkpoints.splice(0, this.checkpoints.length, ...cps);
      this.set('sessionId', tour.session_id || '');
      this.updateProgress();
    } catch (e) { this.set('tourName', 'No tour assigned'); }
  }
  async onScan(): Promise<void> {
    try {
      await requestCameraPermissions();
      const scanner = new BarcodeScanner();
      const result = await scanner.scan({ formats: 'QR_CODE', message: 'Scan checkpoint QR code', showFlipCameraButton: true });
      if (result.text) {
        const res = await this.api.post('/tours/scan', { checkpoint_code: result.text, session_id: this.sessionId });
        if (res.data?.checkpoint) {
          const idx = this.checkpoints.indexOf((c: any) => c.id === res.data.checkpoint.id);
          if (idx >= 0) { this.checkpoints.setItem(idx, { ...this.checkpoints.getItem(idx), scanned: true, scannedAt: new Date().toLocaleTimeString() }); }
          this.set('scannedCount', this.scannedCount + 1);
          this.updateProgress();
        }
      }
    } catch (e) { console.error('Scan error:', e); }
  }
  private updateProgress(): void {
    this.set('progress', this.totalCheckpoints ? Math.round((this.scannedCount / this.totalCheckpoints) * 100) : 0);
  }
}
