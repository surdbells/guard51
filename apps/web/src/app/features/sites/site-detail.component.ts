import { Component, inject, signal, OnInit, AfterViewInit, ViewChild, ElementRef } from '@angular/core';
import { ActivatedRoute, RouterLink } from '@angular/router';
import { NgClass } from '@angular/common';
import { LucideAngularModule, ArrowLeft, MapPin, Edit, FileText, Shield, Users, Clock } from 'lucide-angular';
import { PageHeaderComponent } from '@shared/components/page-header/page-header.component';
import { StatsCardComponent } from '@shared/components/stats-card/stats-card.component';
import { LoadingSpinnerComponent } from '@shared/components/loading-spinner/loading-spinner.component';
import { ApiService } from '@core/services/api.service';

declare var L: any;

@Component({
  selector: 'g51-site-detail',
  standalone: true,
  imports: [RouterLink, NgClass, LucideAngularModule, PageHeaderComponent, StatsCardComponent, LoadingSpinnerComponent],
  template: `
    <g51-page-header [title]="site()?.name || 'Site'" subtitle="Site details, location, and assigned guards">
      <button class="btn-secondary flex items-center gap-2" routerLink="/sites"><lucide-icon [img]="ArrowLeftIcon" [size]="14" /> Back</button>
      <button class="btn-primary flex items-center gap-2" [routerLink]="['/sites', site()?.id, 'edit']"><lucide-icon [img]="EditIcon" [size]="14" /> Edit</button>
    </g51-page-header>

    @if (loading()) { <g51-loading /> } @else if (site()) {
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-4">
        <!-- Map -->
        <div class="card overflow-hidden" style="height: 320px;">
          @if (site()?.latitude && site()?.longitude) {
            <div #siteMap style="height: 100%; width: 100%;"></div>
          } @else {
            <div class="h-full flex items-center justify-center" [style.background]="'var(--surface-muted)'">
              <div class="text-center">
                <lucide-icon [img]="MapPinIcon" [size]="32" [style.color]="'var(--text-tertiary)'" />
                <p class="text-xs mt-2" [style.color]="'var(--text-tertiary)'">No GPS coordinates set</p>
              </div>
            </div>
          }
        </div>
        <!-- Details -->
        <div class="card p-5">
          <h3 class="text-sm font-semibold mb-3" [style.color]="'var(--text-primary)'">Site Information</h3>
          <div class="grid grid-cols-2 gap-y-3 text-xs">
            <div><span [style.color]="'var(--text-tertiary)'">Address</span><p class="font-medium" [style.color]="'var(--text-primary)'">{{ site()?.address || '—' }}</p></div>
            <div><span [style.color]="'var(--text-tertiary)'">City / State</span><p class="font-medium" [style.color]="'var(--text-primary)'">{{ site()?.city || '' }} {{ site()?.state || '' }}</p></div>
            <div><span [style.color]="'var(--text-tertiary)'">Client</span><p class="font-medium" [style.color]="'var(--text-primary)'">{{ site()?.client_name || '—' }}</p></div>
            <div><span [style.color]="'var(--text-tertiary)'">Status</span>
              <span class="badge text-[10px]" [ngClass]="site()?.status === 'active' ? 'bg-emerald-50 text-emerald-600' : 'bg-gray-100 text-gray-500'">{{ site()?.status }}</span></div>
            <div><span [style.color]="'var(--text-tertiary)'">Geofence Radius</span><p class="font-medium" [style.color]="'var(--text-primary)'">{{ site()?.geofence_radius || 100 }}m</p></div>
            <div><span [style.color]="'var(--text-tertiary)'">Contact</span><p class="font-medium" [style.color]="'var(--text-primary)'">{{ site()?.contact_name || '—' }} {{ site()?.contact_phone ? '· ' + site()?.contact_phone : '' }}</p></div>
            <div><span [style.color]="'var(--text-tertiary)'">Timezone</span><p class="font-medium" [style.color]="'var(--text-primary)'">{{ site()?.timezone || 'Africa/Lagos' }}</p></div>
            <div><span [style.color]="'var(--text-tertiary)'">GPS</span><p class="font-medium" [style.color]="'var(--text-primary)'">{{ site()?.latitude || '—' }}, {{ site()?.longitude || '—' }}</p></div>
          </div>
        </div>
      </div>
      @if (site()?.notes) {
        <div class="card p-4 mb-4"><p class="text-xs" [style.color]="'var(--text-secondary)'">{{ site()?.notes }}</p></div>
      }
    }
  `,
})
export class SiteDetailComponent implements OnInit, AfterViewInit {
  @ViewChild('siteMap') siteMapEl!: ElementRef;
  private api = inject(ApiService); private route = inject(ActivatedRoute);
  readonly ArrowLeftIcon = ArrowLeft; readonly EditIcon = Edit; readonly MapPinIcon = MapPin;
  readonly loading = signal(true); readonly site = signal<any>(null);
  private map: any = null;

  ngOnInit(): void {
    const id = this.route.snapshot.paramMap.get('id');
    if (!id) return;
    this.api.get<any>(`/sites/${id}`).subscribe({
      next: res => { this.site.set(res.data?.site || res.data); this.loading.set(false); setTimeout(() => this.initMap(), 100); },
      error: () => this.loading.set(false),
    });
  }
  ngAfterViewInit(): void {}

  private initMap(): void {
    const s = this.site();
    if (!s?.latitude || !s?.longitude || !this.siteMapEl?.nativeElement) return;
    const L = (window as any).L;
    if (!L) {
      const css = document.createElement('link'); css.rel = 'stylesheet'; css.href = 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css'; document.head.appendChild(css);
      const script = document.createElement('script'); script.src = 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js';
      script.onload = () => this.renderMap(); document.head.appendChild(script);
    } else { this.renderMap(); }
  }

  private renderMap(): void {
    const L = (window as any).L; const s = this.site();
    if (!L || !s?.latitude) return;
    this.map = L.map(this.siteMapEl.nativeElement).setView([parseFloat(s.latitude), parseFloat(s.longitude)], 16);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OSM', maxZoom: 19 }).addTo(this.map);
    L.marker([parseFloat(s.latitude), parseFloat(s.longitude)]).addTo(this.map).bindPopup(`<b>${s.name}</b><br>${s.address || ''}`).openPopup();
    if (s.geofence_radius) {
      L.circle([parseFloat(s.latitude), parseFloat(s.longitude)], { radius: s.geofence_radius, color: '#1B3A5C', fillColor: '#1B3A5C', fillOpacity: 0.1, weight: 2 }).addTo(this.map);
    }
  }
}
