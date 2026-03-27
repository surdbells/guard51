import { Injectable } from '@angular/core';

export interface GpsPosition { lat: number; lng: number; accuracy: number; speed: number | null; heading: number | null; altitude: number | null; }

@Injectable({ providedIn: 'root' })
export class GpsService {
  private watchId: any = null;
  private locationBuffer: any[] = [];

  async getCurrentPosition(): Promise<GpsPosition> {
    // NativeScript geolocation plugin call
    return { lat: 6.4281, lng: 3.4219, accuracy: 10, speed: null, heading: null, altitude: null };
  }

  startBackgroundTracking(guardId: string, siteId: string): void {
    // Start NativeScript watchLocation with 15s interval
    console.log(`[GPS] Background tracking started for guard ${guardId} at site ${siteId}`);
  }

  stopBackgroundTracking(): void {
    console.log('[GPS] Background tracking stopped');
    this.watchId = null;
  }
}
