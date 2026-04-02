import * as geolocation from '@nativescript/geolocation';
import { CoreTypes } from '@nativescript/core';

export interface GpsPosition {
  lat: number;
  lng: number;
  accuracy: number;
  altitude: number;
  timestamp: number;
}

export class LocationService {
  private static watchId: number | null = null;

  static async requestPermission(): Promise<boolean> {
    const enabled = await geolocation.isEnabled();
    if (!enabled) {
      await geolocation.enableLocationRequest(true, true);
    }
    return geolocation.isEnabled();
  }

  static async getCurrentPosition(): Promise<GpsPosition> {
    const loc = await geolocation.getCurrentLocation({
      desiredAccuracy: CoreTypes.Accuracy.high,
      maximumAge: 5000,
      timeout: 10000,
    });
    return {
      lat: loc.latitude,
      lng: loc.longitude,
      accuracy: loc.horizontalAccuracy,
      altitude: loc.altitude,
      timestamp: loc.timestamp.getTime(),
    };
  }

  static startTracking(callback: (pos: GpsPosition) => void, intervalMs: number = 15000): void {
    this.stopTracking();
    this.watchId = geolocation.watchLocation(
      (loc) => {
        callback({
          lat: loc.latitude, lng: loc.longitude,
          accuracy: loc.horizontalAccuracy, altitude: loc.altitude,
          timestamp: loc.timestamp.getTime(),
        });
      },
      (err) => console.error('[GPS] Error:', err.message),
      { desiredAccuracy: CoreTypes.Accuracy.high, updateDistance: 10, minimumUpdateTime: intervalMs }
    );
  }

  static stopTracking(): void {
    if (this.watchId !== null) {
      geolocation.clearWatch(this.watchId);
      this.watchId = null;
    }
  }
}
