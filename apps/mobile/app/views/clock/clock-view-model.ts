import { Observable } from '@nativescript/core';
import { getCurrentLocation, enableLocationRequest } from '@nativescript/geolocation';
import { ApiService } from '../../services/api.service';

export class ClockViewModel extends Observable {
  
  currentStatus = 'Not Clocked In';
  statusTime = '';
  siteName = 'Loading...';
  isClockedIn = false;
  gpsStatus = 'Acquiring GPS...';
  latitude = 0;
  longitude = 0;
  gpsAccuracy = 0;

  async init(): Promise<void> {
    try {
      await enableLocationRequest();
      const loc = await getCurrentLocation({ desiredAccuracy: 3, timeout: 15000 });
      this.set('latitude', loc.latitude.toFixed(6));
      this.set('longitude', loc.longitude.toFixed(6));
      this.set('gpsAccuracy', Math.round(loc.horizontalAccuracy));
      this.set('gpsStatus', 'GPS Ready');
    } catch (e) {
      this.set('gpsStatus', 'GPS unavailable');
    }

    // Check current clock status
    try {
      const res = await ApiService.get('/time-clock/status');
      if (res.data?.is_clocked_in) {
        this.set('isClockedIn', true);
        this.set('currentStatus', 'Clocked In');
        this.set('statusTime', `Since ${res.data.clock_in_time || ''}`);
        this.set('siteName', res.data.site_name || '');
      }
    } catch (e) { /* first time */ }
  }

  async onClockTap(): Promise<void> {
    try {
      const loc = await getCurrentLocation({ desiredAccuracy: 3, timeout: 10000 });
      const endpoint = this.isClockedIn ? '/time-clock/clock-out' : '/time-clock/clock-in';
      const body = { latitude: loc.latitude, longitude: loc.longitude };
      const res = await ApiService.post(endpoint, body);

      if (this.isClockedIn) {
        this.set('isClockedIn', false);
        this.set('currentStatus', 'Clocked Out');
        this.set('statusTime', new Date().toLocaleTimeString());
      } else {
        this.set('isClockedIn', true);
        this.set('currentStatus', 'Clocked In');
        this.set('statusTime', `Since ${new Date().toLocaleTimeString()}`);
        this.set('siteName', res.data?.site_name || '');
      }
    } catch (e: any) {
      console.error('Clock error:', e);
    }
  }
}
