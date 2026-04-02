import { Observable, Frame, Dialogs } from '@nativescript/core';
import { ApiService } from '../../services/api.service';
import { LocationService } from '../../services/location.service';
import { OfflineQueue } from '../../services/offline-queue.service';
import { SecureStorage } from '../../services/secure-storage.service';

export class DashboardViewModel extends Observable {
  clockedIn = false;
  clockInTime = '';
  siteName = '';
  todayReports = 0;
  todayIncidents = 0;
  offlineQueue = 0;

  async load() {
    this.set('offlineQueue', OfflineQueue.getQueueSize());

    try {
      // Sync offline queue first
      if (OfflineQueue.getQueueSize() > 0) {
        const result = await OfflineQueue.sync();
        this.set('offlineQueue', OfflineQueue.getQueueSize());
      }

      // Load clock status
      const status = await ApiService.get('/time-clock/status');
      if (status.data?.clocked_in) {
        this.set('clockedIn', true);
        this.set('clockInTime', status.data.clock_in_time || '');
      }
      if (status.data?.site_name) this.set('siteName', status.data.site_name);

      // Load today stats
      const today = await ApiService.get('/dashboard/today');
      if (today.data?.snapshot) {
        this.set('todayReports', today.data.snapshot.total_reports || 0);
        this.set('todayIncidents', today.data.snapshot.total_incidents || 0);
      }

      // Start GPS tracking if clocked in
      if (this.clockedIn) {
        await LocationService.requestPermission();
        LocationService.startTracking((pos) => {
          ApiService.post('/tracking/position', { latitude: pos.lat, longitude: pos.lng, accuracy: pos.accuracy }).catch(() => {
            OfflineQueue.enqueue('POST', '/tracking/position', { latitude: pos.lat, longitude: pos.lng, accuracy: pos.accuracy });
          });
        }, 15000);
      }
    } catch (e) {
      console.error('[Dashboard] Load error:', e);
    }
  }

  async onToggleClock() {
    try {
      await LocationService.requestPermission();
      const pos = await LocationService.getCurrentPosition();
      const endpoint = this.clockedIn ? '/time-clock/clock-out' : '/time-clock/clock-in';
      await ApiService.post(endpoint, { latitude: pos.lat, longitude: pos.lng });

      if (this.clockedIn) {
        this.set('clockedIn', false);
        this.set('clockInTime', '');
        LocationService.stopTracking();
      } else {
        this.set('clockedIn', true);
        this.set('clockInTime', new Date().toLocaleTimeString());
        LocationService.startTracking((p) => {
          ApiService.post('/tracking/position', { latitude: p.lat, longitude: p.lng }).catch(() => {
            OfflineQueue.enqueue('POST', '/tracking/position', { latitude: p.lat, longitude: p.lng });
          });
        }, 15000);
      }
    } catch (e: any) {
      OfflineQueue.enqueue('POST', this.clockedIn ? '/time-clock/clock-out' : '/time-clock/clock-in', {});
      Dialogs.alert({ title: 'Offline', message: 'Action queued for sync.', okButtonText: 'OK' });
    }
  }

  async onPanic() {
    const confirm = await Dialogs.confirm({ title: '🚨 PANIC ALERT', message: 'Send emergency alert to dispatch?', okButtonText: 'SEND ALERT', cancelButtonText: 'Cancel' });
    if (!confirm) return;

    try {
      const pos = await LocationService.getCurrentPosition();
      await ApiService.post('/panic/trigger', { latitude: pos.lat, longitude: pos.lng, message: 'Guard panic alert' });
      Dialogs.alert({ title: 'Alert Sent', message: 'Dispatch has been notified of your location.', okButtonText: 'OK' });
    } catch (e) {
      OfflineQueue.enqueue('POST', '/panic/trigger', { message: 'Guard panic alert (offline)' });
      Dialogs.alert({ title: 'Queued', message: 'Alert queued — will send when online.', okButtonText: 'OK' });
    }
  }

  onIncident() {
    Frame.topmost().navigate({ moduleName: 'app/views/incidents/incident-page' });
  }

  onScanCheckpoint() {
    // BarcodeScanner integration
    try {
      const scanner = require('@nativescript/barcodescanner');
      scanner.scan({ formats: 'QR_CODE', message: 'Scan checkpoint QR code' }).then((result: any) => {
        if (result.text) {
          ApiService.post('/tours/scan', { checkpoint_code: result.text }).then(() => {
            Dialogs.alert({ title: 'Scanned', message: `Checkpoint: ${result.text}`, okButtonText: 'OK' });
          });
        }
      });
    } catch { Dialogs.alert({ title: 'Error', message: 'Camera not available', okButtonText: 'OK' }); }
  }

  onPassdowns() {
    Frame.topmost().navigate({ moduleName: 'app/views/dashboard/dashboard-page' }); // TODO: passdowns page
  }

  onLogout() {
    LocationService.stopTracking();
    ApiService.clearToken();
    SecureStorage.clear();
    Frame.topmost().navigate({ moduleName: 'app/views/login/login-page', clearHistory: true });
  }
}
