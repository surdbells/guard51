import { Observable, Vibrate } from '@nativescript/core';
import { getCurrentLocation, enableLocationRequest } from '@nativescript/geolocation';
import { ApiService } from '../../services/api.service';
import { SecureStorageService } from '../../services/secure-storage.service';

export class PanicViewModel extends Observable {
  private api = new ApiService();
  private storage = new SecureStorageService();

  statusMessage = '';
  guardName = '';
  currentTime = new Date().toLocaleTimeString();

  async init(): Promise<void> {
    const user = this.storage.get('user');
    if (user) {
      const u = JSON.parse(user);
      this.set('guardName', `${u.first_name || ''} ${u.last_name || ''}`);
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
      // Vibrate to confirm
      const vibrator = new Vibrate();
      vibrator.vibrate([500, 200, 500]);

      await enableLocationRequest();
      const loc = await getCurrentLocation({ desiredAccuracy: 3, timeout: 10000 });

      await this.api.post('/panic', {
        latitude: loc.latitude,
        longitude: loc.longitude,
        accuracy: loc.horizontalAccuracy,
        timestamp: new Date().toISOString(),
      });

      this.set('statusMessage', '✅ ALERT SENT — Dispatch has been notified. Help is on the way.');
    } catch (e: any) {
      this.set('statusMessage', '⚠️ Failed to send. Check your connection and try again.');

      // Queue for offline sync
      const { OfflineQueueService } = require('../../services/offline-queue.service');
      const queue = new OfflineQueueService();
      queue.enqueue('POST', '/panic', {
        latitude: 0, longitude: 0,
        timestamp: new Date().toISOString(),
        offline: true,
      });
      this.set('statusMessage', '📱 Alert queued — will send when connection restores.');
    }
  }
}
