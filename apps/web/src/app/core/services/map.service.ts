import { Injectable, signal } from '@angular/core';
import { environment } from '@env/environment';

export interface MapMarker {
  id: string;
  lat: number;
  lng: number;
  label?: string;
  icon?: string;
  data?: Record<string, unknown>;
}

export interface GeofenceCircle {
  id: string;
  lat: number;
  lng: number;
  radiusMeters: number;
  color?: string;
  label?: string;
}

export interface MapPosition {
  lat: number;
  lng: number;
}

@Injectable({ providedIn: 'root' })
export class MapService {
  private map: google.maps.Map | null = null;
  private markers = new Map<string, google.maps.Marker>();
  private circles = new Map<string, google.maps.Circle>();
  private apiLoaded = false;

  readonly isLoaded = signal(false);
  readonly currentCenter = signal<MapPosition>({ lat: 6.5244, lng: 3.3792 }); // Lagos default

  /**
   * Load Google Maps JavaScript API dynamically.
   */
  async loadApi(): Promise<void> {
    if (this.apiLoaded) return;

    const apiKey = environment.googleMapsApiKey;
    if (!apiKey) {
      console.warn('MapService: Google Maps API key not configured.');
      return;
    }

    return new Promise((resolve, reject) => {
      if (typeof google !== 'undefined' && google.maps) {
        this.apiLoaded = true;
        this.isLoaded.set(true);
        resolve();
        return;
      }

      const script = document.createElement('script');
      script.src = `https://maps.googleapis.com/maps/api/js?key=${apiKey}&libraries=geometry,places`;
      script.async = true;
      script.defer = true;
      script.onload = () => {
        this.apiLoaded = true;
        this.isLoaded.set(true);
        resolve();
      };
      script.onerror = () => reject(new Error('Failed to load Google Maps API'));
      document.head.appendChild(script);
    });
  }

  /**
   * Initialize map on a container element.
   */
  initMap(container: HTMLElement, options?: { center?: MapPosition; zoom?: number }): google.maps.Map | null {
    if (!this.apiLoaded) {
      console.warn('MapService: API not loaded. Call loadApi() first.');
      return null;
    }

    const center = options?.center || this.currentCenter();
    this.map = new google.maps.Map(container, {
      center,
      zoom: options?.zoom || 13,
      mapTypeControl: false,
      streetViewControl: false,
      fullscreenControl: true,
      zoomControl: true,
      styles: this.getMapStyles(),
    });

    return this.map;
  }

  /**
   * Add or update a marker on the map.
   */
  setMarker(marker: MapMarker): void {
    if (!this.map) return;

    const existing = this.markers.get(marker.id);
    const position = { lat: marker.lat, lng: marker.lng };

    if (existing) {
      existing.setPosition(position);
      if (marker.label) existing.setTitle(marker.label);
    } else {
      const gMarker = new google.maps.Marker({
        map: this.map,
        position,
        title: marker.label || '',
        animation: google.maps.Animation.DROP,
      });
      this.markers.set(marker.id, gMarker);
    }
  }

  /**
   * Remove a marker from the map.
   */
  removeMarker(id: string): void {
    const marker = this.markers.get(id);
    if (marker) {
      marker.setMap(null);
      this.markers.delete(id);
    }
  }

  /**
   * Clear all markers.
   */
  clearMarkers(): void {
    this.markers.forEach(m => m.setMap(null));
    this.markers.clear();
  }

  /**
   * Draw a geofence circle on the map.
   */
  setGeofence(geofence: GeofenceCircle): void {
    if (!this.map) return;

    const existing = this.circles.get(geofence.id);
    if (existing) {
      existing.setCenter({ lat: geofence.lat, lng: geofence.lng });
      existing.setRadius(geofence.radiusMeters);
    } else {
      const circle = new google.maps.Circle({
        map: this.map,
        center: { lat: geofence.lat, lng: geofence.lng },
        radius: geofence.radiusMeters,
        fillColor: geofence.color || '#1B3A5C',
        fillOpacity: 0.12,
        strokeColor: geofence.color || '#1B3A5C',
        strokeOpacity: 0.6,
        strokeWeight: 2,
      });
      this.circles.set(geofence.id, circle);
    }
  }

  /**
   * Remove a geofence circle.
   */
  removeGeofence(id: string): void {
    const circle = this.circles.get(id);
    if (circle) {
      circle.setMap(null);
      this.circles.delete(id);
    }
  }

  /**
   * Fit map bounds to show all current markers.
   */
  fitBoundsToMarkers(): void {
    if (!this.map || this.markers.size === 0) return;

    const bounds = new google.maps.LatLngBounds();
    this.markers.forEach(m => {
      const pos = m.getPosition();
      if (pos) bounds.extend(pos);
    });
    this.map.fitBounds(bounds, 50);
  }

  /**
   * Calculate distance between two points in meters.
   */
  distanceBetween(p1: MapPosition, p2: MapPosition): number {
    if (!this.apiLoaded) return 0;
    return google.maps.geometry.spherical.computeDistanceBetween(
      new google.maps.LatLng(p1.lat, p1.lng),
      new google.maps.LatLng(p2.lat, p2.lng),
    );
  }

  /**
   * Check if a point is inside a geofence circle.
   */
  isInsideGeofence(point: MapPosition, geofence: GeofenceCircle): boolean {
    const distance = this.distanceBetween(point, { lat: geofence.lat, lng: geofence.lng });
    return distance <= geofence.radiusMeters;
  }

  /**
   * Destroy map instance and clean up.
   */
  destroy(): void {
    this.clearMarkers();
    this.circles.forEach(c => c.setMap(null));
    this.circles.clear();
    this.map = null;
  }

  private getMapStyles(): google.maps.MapTypeStyle[] {
    // Minimal, clean map style
    return [
      { featureType: 'poi', elementType: 'labels', stylers: [{ visibility: 'off' }] },
      { featureType: 'transit', elementType: 'labels', stylers: [{ visibility: 'off' }] },
    ];
  }
}
