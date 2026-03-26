import { Component } from '@angular/core';
import { LucideAngularModule, Upload, Smartphone, Monitor, Download, Package } from 'lucide-angular';
import { PageHeaderComponent } from '@shared/components/page-header/page-header.component';
import { StatsCardComponent } from '@shared/components/stats-card/stats-card.component';

interface AppInfo { key: string; name: string; platform: string; version: string; downloads: number; updated: string; }

@Component({
  selector: 'g51-sa-apps',
  standalone: true,
  imports: [LucideAngularModule, PageHeaderComponent, StatsCardComponent],
  template: `
    <g51-page-header title="App Management" subtitle="Manage app releases, uploads, and downloads">
      <button class="btn-primary flex items-center gap-2">
        <lucide-icon [img]="UploadIcon" [size]="16" /> Upload Release
      </button>
    </g51-page-header>

    <div class="grid grid-cols-2 lg:grid-cols-3 gap-4 mb-6 stagger-children">
      <g51-stats-card label="Total Releases" value="24" [icon]="PackageIcon" />
      <g51-stats-card label="Total Downloads" value="892" [icon]="DownloadIcon" [trend]="23" trendLabel="from last month" />
      <g51-stats-card label="Active Apps" value="7" [icon]="SmartphoneIcon" />
    </div>

    <h3 class="text-base font-semibold mb-3" [style.color]="'var(--text-primary)'">Mobile Apps</h3>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3 mb-6">
      @for (app of mobileApps; track app.key) {
        <div class="card p-4 card-hover">
          <div class="flex items-center gap-3 mb-3">
            <div class="h-10 w-10 rounded-xl flex items-center justify-center" [style.background]="'var(--surface-muted)'">
              <lucide-icon [img]="SmartphoneIcon" [size]="20" [style.color]="'var(--text-secondary)'" />
            </div>
            <div>
              <h4 class="text-sm font-semibold" [style.color]="'var(--text-primary)'">{{ app.name }}</h4>
              <p class="text-xs" [style.color]="'var(--text-tertiary)'">{{ app.platform }}</p>
            </div>
          </div>
          <div class="flex items-center justify-between text-xs">
            <span [style.color]="'var(--text-secondary)'">v{{ app.version }}</span>
            <span [style.color]="'var(--text-tertiary)'">{{ app.downloads }} downloads</span>
          </div>
          <p class="text-xs mt-1" [style.color]="'var(--text-tertiary)'">Updated {{ app.updated }}</p>
        </div>
      }
    </div>

    <h3 class="text-base font-semibold mb-3" [style.color]="'var(--text-primary)'">Desktop Apps</h3>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
      @for (app of desktopApps; track app.key) {
        <div class="card p-4 card-hover">
          <div class="flex items-center gap-3 mb-3">
            <div class="h-10 w-10 rounded-xl flex items-center justify-center" [style.background]="'var(--surface-muted)'">
              <lucide-icon [img]="MonitorIcon" [size]="20" [style.color]="'var(--text-secondary)'" />
            </div>
            <div>
              <h4 class="text-sm font-semibold" [style.color]="'var(--text-primary)'">{{ app.name }}</h4>
              <p class="text-xs" [style.color]="'var(--text-tertiary)'">{{ app.platform }}</p>
            </div>
          </div>
          <div class="flex items-center justify-between text-xs">
            <span [style.color]="'var(--text-secondary)'">v{{ app.version }}</span>
            <span [style.color]="'var(--text-tertiary)'">{{ app.downloads }} downloads</span>
          </div>
        </div>
      }
    </div>
  `,
})
export class AppsComponent {
  readonly UploadIcon = Upload; readonly SmartphoneIcon = Smartphone; readonly MonitorIcon = Monitor;
  readonly DownloadIcon = Download; readonly PackageIcon = Package;

  mobileApps: AppInfo[] = [
    { key: 'guard', name: 'Guard App', platform: 'Android / iOS', version: '1.2.0', downloads: 340, updated: '3 days ago' },
    { key: 'client', name: 'Client App', platform: 'Android / iOS', version: '1.1.0', downloads: 120, updated: '1 week ago' },
    { key: 'supervisor', name: 'Supervisor App', platform: 'Android / iOS', version: '1.0.2', downloads: 85, updated: '2 weeks ago' },
    { key: 'dispatcher', name: 'Dispatcher App', platform: 'Android / iOS', version: '1.0.1', downloads: 42, updated: '1 month ago' },
  ];
  desktopApps: AppInfo[] = [
    { key: 'windows', name: 'Desktop (Windows)', platform: 'Windows 10+', version: '1.0.0', downloads: 156, updated: '1 week ago' },
    { key: 'mac', name: 'Desktop (macOS)', platform: 'macOS 12+', version: '1.0.0', downloads: 98, updated: '1 week ago' },
    { key: 'linux', name: 'Desktop (Linux)', platform: 'AppImage', version: '1.0.0', downloads: 51, updated: '1 week ago' },
  ];
}
