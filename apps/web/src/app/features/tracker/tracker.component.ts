import { Component, inject, signal, OnInit, OnDestroy, AfterViewInit, ElementRef, ViewChild } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { NgClass } from '@angular/common';
import { LucideAngularModule, Navigation, MapPin, Battery, Clock, AlertTriangle, RefreshCw, Search } from 'lucide-angular';
import { PageHeaderComponent } from '@shared/components/page-header/page-header.component';
import { StatsCardComponent } from '@shared/components/stats-card/stats-card.component';
import { LoadingSpinnerComponent } from '@shared/components/loading-spinner/loading-spinner.component';
import { ApiService } from '@core/services/api.service';

declare var L: any;

@Component({
  selector: 'g51-tracker',
  standalone: true,
  imports: [FormsModule, NgClass, LucideAngularModule, PageHeaderComponent, StatsCardComponent, LoadingSpinnerComponent],
  template: `
    <g51-page-header title="Live Tracker" subtitle="Real-time guard positions and geofence monitoring">
      <button class="btn-secondary flex items-center gap-2 text-xs" (click)="refreshPositions()"><lucide-icon [img]="RefreshIcon" [size]="14" /> Refresh</button>
    </g51-page-header>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-4 stagger-children">
      <g51-stats-card label="Online" [value]="stats().online" [icon]="NavigationIcon" />
      <g51-stats-card label="Moving" [value]="stats().moving" [icon]="MapPinIcon" />
      <g51-stats-card label="Idle" [value]="stats().idle" [icon]="ClockIcon" />
      <g51-stats-card label="Alerts" [value]="stats().alerts" [icon]="AlertTriangleIcon" />
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-4">
      <!-- Map -->
      <div class="lg:col-span-3 card overflow-hidden" style="height: 500px;">
        <div #mapContainer id="guard51-map" style="height: 100%; width: 100%;"></div>
      </div>

      <!-- Guard list -->
      <div class="card p-3 overflow-y-auto" style="max-height: 500px;">
        <div class="relative mb-3">
          <lucide-icon [img]="SearchIcon" [size]="14" class="absolute left-2 top-1/2 -translate-y-1/2" [style.color]="'var(--text-tertiary)'" />
          <input type="text" [(ngModel)]="searchGuard" placeholder="Search guard..." class="input-base w-full pl-8 text-xs" />
        </div>
        @for (g of filteredGuards(); track g.guard_id) {
          <div class="flex items-center gap-2 py-2 px-2 rounded-lg cursor-pointer transition-colors mb-1"
            [ngClass]="selectedGuardId() === g.guard_id ? 'bg-[var(--color-brand-500)]' : 'hover:bg-[var(--surface-hover)]'"
            [style.color]="selectedGuardId() === g.guard_id ? 'white' : 'var(--text-primary)'"
            (click)="selectGuard(g)">
            <div class="h-2 w-2 rounded-full" [ngClass]="g.is_moving ? 'bg-emerald-400' : 'bg-amber-400'"></div>
            <div class="flex-1 min-w-0">
              <p class="text-xs font-medium truncate">{{ g.guard_name || 'Guard' }}</p>
              <p class="text-[10px] opacity-70">{{ g.site_name || 'Unknown' }} · {{ g.battery_level || '?' }}%</p>
            </div>
          </div>
        }
      </div>
    </div>
  `,
  styles: [`
    :host { display: block; }
  `],
})
export class TrackerComponent implements OnInit, AfterViewInit, OnDestroy {
  @ViewChild('mapContainer') mapContainer!: ElementRef;
  private api = inject(ApiService);
  readonly NavigationIcon = Navigation; readonly MapPinIcon = MapPin; readonly ClockIcon = Clock;
  readonly AlertTriangleIcon = AlertTriangle; readonly RefreshIcon = RefreshCw; readonly SearchIcon = Search;
  readonly BatteryIcon = Battery;

  readonly stats = signal({ online: 0, moving: 0, idle: 0, alerts: 0 });
  readonly guards = signal<any[]>([]);
  readonly selectedGuardId = signal('');
  searchGuard = '';
  private map: any = null;
  private markers: any[] = [];
  private pollInterval: any;

  readonly filteredGuards = signal<any[]>([]);

  ngOnInit(): void {
    this.loadPositions();
    this.pollInterval = setInterval(() => this.loadPositions(), 30000);
  }

  ngAfterViewInit(): void {
    this.loadLeaflet();
  }

  ngOnDestroy(): void {
    if (this.pollInterval) clearInterval(this.pollInterval);
  }

  private loadLeaflet(): void {
    if ((window as any).L) { this.initMap(); return; }
    // Load Leaflet CSS
    const css = document.createElement('link');
    css.rel = 'stylesheet';
    css.href = 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css';
    document.head.appendChild(css);
    // Load Leaflet JS
    const script = document.createElement('script');
    script.src = 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js';
    script.onload = () => this.initMap();
    document.head.appendChild(script);
  }

  private initMap(): void {
    if (!this.mapContainer?.nativeElement) return;
    const L = (window as any).L;
    if (!L) return;
    // Default center: Lagos
    this.map = L.map(this.mapContainer.nativeElement).setView([6.5244, 3.3792], 12);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '&copy; OpenStreetMap contributors',
      maxZoom: 19,
    }).addTo(this.map);
    this.updateMarkers();
  }

  private updateMarkers(): void {
    if (!this.map) return;
    const L = (window as any).L;
    if (!L) return;
    // Clear old markers
    this.markers.forEach(m => this.map.removeLayer(m));
    this.markers = [];
    // Add guard markers
    for (const g of this.guards()) {
      if (g.lat && g.lng) {
        const icon = L.divIcon({
          className: 'guard-marker',
          html: `<div style="background:${g.is_moving ? '#10B981' : '#F59E0B'};width:12px;height:12px;border-radius:50%;border:2px solid white;box-shadow:0 1px 3px rgba(0,0,0,.3)"></div>`,
          iconSize: [12, 12],
        });
        const marker = L.marker([g.lat, g.lng], { icon })
          .addTo(this.map)
          .bindPopup(`<b>${g.guard_name || 'Guard'}</b><br>${g.site_name || ''}<br>Battery: ${g.battery_level || '?'}%`);
        this.markers.push(marker);
      }
    }
    // Fit bounds if we have markers
    if (this.markers.length) {
      const group = L.featureGroup(this.markers);
      this.map.fitBounds(group.getBounds().pad(0.1));
    }
  }

  loadPositions(): void {
    this.api.get<any>('/tracking/positions').subscribe({
      next: res => {
        const positions = res.data?.positions || res.data || [];
        this.guards.set(positions);
        this.filterGuards();
        this.stats.set({
          online: positions.length,
          moving: positions.filter((g: any) => g.is_moving).length,
          idle: positions.filter((g: any) => !g.is_moving).length,
          alerts: positions.filter((g: any) => g.alert).length,
        });
        this.updateMarkers();
      },
      error: () => {},
    });
  }

  refreshPositions(): void { this.loadPositions(); }

  selectGuard(g: any): void {
    this.selectedGuardId.set(g.guard_id);
    if (this.map && g.lat && g.lng) {
      this.map.setView([g.lat, g.lng], 16);
    }
  }

  filterGuards(): void {
    const q = this.searchGuard.toLowerCase();
    if (!q) { this.filteredGuards.set(this.guards()); return; }
    this.filteredGuards.set(this.guards().filter((g: any) =>
      (g.guard_name || '').toLowerCase().includes(q) || (g.site_name || '').toLowerCase().includes(q)
    ));
  }
}
