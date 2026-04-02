import { Observable, Frame, Dialogs } from '@nativescript/core';
import { ApiService } from '../../services/api.service';
import { LocationService, GpsPosition } from '../../services/location.service';
import { OfflineQueue } from '../../services/offline-queue.service';

export class IncidentViewModel extends Observable {
  incidentTypes = ['Theft', 'Assault', 'Trespassing', 'Property Damage', 'Fire', 'Medical', 'Suspicious Activity', 'Vandalism', 'Other'];
  severities = ['Low', 'Medium', 'High', 'Critical'];
  selectedTypeIndex = 0;
  selectedSeverityIndex = 1;
  title = '';
  description = '';
  photoPath = '';
  gpsStatus = 'Acquiring...';
  submitting = false;
  private gps: GpsPosition | null = null;

  async init() {
    try {
      await LocationService.requestPermission();
      this.gps = await LocationService.getCurrentPosition();
      this.set('gpsStatus', `${this.gps.lat.toFixed(5)}, ${this.gps.lng.toFixed(5)} (±${this.gps.accuracy.toFixed(0)}m)`);
    } catch {
      this.set('gpsStatus', 'Unavailable');
    }
  }

  async onTakePhoto() {
    try {
      const camera = require('@nativescript/camera');
      const ok = await camera.requestPermissions();
      if (!ok) return;
      const img = await camera.takePicture({ width: 1024, height: 768, keepAspectRatio: true, saveToGallery: false });
      this.set('photoPath', img.android || img.ios || '');
    } catch (e) {
      console.error('[Camera]', e);
    }
  }

  async onSubmit() {
    if (!this.title || !this.description) {
      Dialogs.alert({ title: 'Required', message: 'Title and description are required.', okButtonText: 'OK' });
      return;
    }

    this.set('submitting', true);
    const payload = {
      incident_type: this.incidentTypes[this.selectedTypeIndex].toLowerCase().replace(/ /g, '_'),
      severity: this.severities[this.selectedSeverityIndex].toLowerCase(),
      title: this.title,
      description: this.description,
      latitude: this.gps?.lat,
      longitude: this.gps?.lng,
    };

    try {
      await ApiService.post('/incidents', payload);
      Dialogs.alert({ title: 'Submitted', message: 'Incident report submitted.', okButtonText: 'OK' });
      Frame.topmost().goBack();
    } catch {
      OfflineQueue.enqueue('POST', '/incidents', payload);
      Dialogs.alert({ title: 'Queued', message: 'Report saved offline. Will sync when online.', okButtonText: 'OK' });
      Frame.topmost().goBack();
    } finally {
      this.set('submitting', false);
    }
  }

  onBack() { Frame.topmost().goBack(); }
}
