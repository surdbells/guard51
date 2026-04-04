import { Observable, Utils, Device } from '@nativescript/core';
import { getCurrentLocation, enableLocationRequest } from '@nativescript/geolocation';
import { ApiService } from '../../services/api.service';
import { SecureStorage } from '../../services/secure-storage.service';
import { OfflineQueue } from '../../services/offline-queue.service';

export class PanicViewModel extends Observable {
  statusMessage = '';
  guardName = '';
  currentTime = new Date().toLocaleTimeString();

  async init(): Promise<void> {
    const user = SecureStorage.get('user');
    if (user) {
      try {
        const u = JSON.parse(user);
        this.set('guardName', `${u.first_name || ''} ${u.last_name || ''}`);
      } catch (e) { /* ignore parse errors */ }
    }
  }

  async onPanicTap(): Promise<void> {
    await this.sendPanicAlert();
  }

  async onPanicLongPress(): Promise<void> {
    await this.sendPanicAlert();
  }

  private async sendPanicAlert(): Promise<void> {
    this.set('statusMessage', 'Sending alert...');

    try {
      // Vibrate for feedback (Android only)
      if (Device.os === 'Android') {
        try {
          const vibrator = Utils.android.getApplicationContext().getSystemService('vibrator');
          if (vibrator) vibrator.vibrate(500);
        } catch (e) { /* vibration not available */ }
      }

      await enableLocationRequest();
      const loc = await getCurrentLocation({ desiredAccuracy: 3, timeout: 10000 });

      await ApiService.post('/panic', {
        latitude: loc.latitude,
        longitude: loc.longitude,
        accuracy: loc.horizontalAccuracy,
        timestamp: new Date().toISOString(),
      });

      this.set('statusMessage', '✅ ALERT SENT — Dispatch has been notified. Help is on the way.');
    } catch (e: any) {
      this.set('statusMessage', '⚠️ Failed to send. Check your connection and try again.');
      // Queue for offline sync
      try {
        OfflineQueue.enqueue('POST', '/panic', {
          latitude: 0, longitude: 0,
          timestamp: new Date().toISOString(),
          offline: true,
        });
        this.set('statusMessage', '📱 Alert queued — will send when connection restores.');
      } catch (qe) { /* queue failed too */ }
    }
  }
}
