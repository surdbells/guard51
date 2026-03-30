import { Component, signal, inject, OnInit } from '@angular/core';
import { RouterOutlet } from '@angular/router';
import { NgClass } from '@angular/common';
import { SidebarComponent } from '../sidebar/sidebar.component';
import { HeaderComponent } from '../header/header.component';
import { MobileNavComponent } from '../mobile-nav/mobile-nav.component';
import { ChatFabComponent } from '../../components/chat-fab/chat-fab.component';
import { FeatureService } from '@core/services/feature.service';
import { BrandingService } from '@core/services/branding.service';
import { AuthStore } from '@core/services/auth.store';

@Component({
  selector: 'g51-shell',
  standalone: true,
  imports: [RouterOutlet, NgClass, SidebarComponent, HeaderComponent, MobileNavComponent, ChatFabComponent],
  template: `
    <!-- Desktop sidebar (hidden on mobile) -->
    <div class="hidden lg:block">
      <g51-sidebar
        [collapsed]="sidebarCollapsed()"
        (toggleCollapse)="sidebarCollapsed.set(!sidebarCollapsed())"
      />
    </div>

    <!-- Main content area -->
    <div
      class="min-h-screen transition-all duration-[var(--duration-normal)]"
      [ngClass]="sidebarCollapsed()
        ? 'lg:ml-[var(--spacing-sidebar-collapsed)]'
        : 'lg:ml-[var(--spacing-sidebar)]'"
    >
      <!-- Header -->
      <g51-header />

      <!-- Page content -->
      <main class="p-4 md:p-6 pb-24 lg:pb-6" [style.background]="'var(--surface-bg)'">
        <router-outlet />
      </main>
    </div>

    <!-- Mobile bottom nav (hidden on desktop) -->
    <g51-mobile-nav />

    <!-- Floating chat button -->
    <g51-chat-fab />

    <!-- Mobile sidebar overlay -->
    @if (mobileSidebarOpen()) {
      <div class="fixed inset-0 z-50 lg:hidden">
        <div class="absolute inset-0 bg-black/40" (click)="mobileSidebarOpen.set(false)"></div>
        <div class="relative w-[var(--spacing-sidebar)] h-full">
          <g51-sidebar [collapsed]="false" (toggleCollapse)="mobileSidebarOpen.set(false)" />
        </div>
      </div>
    }
  `,
})
export class ShellComponent implements OnInit {
  private features = inject(FeatureService);
  private branding = inject(BrandingService);
  private auth = inject(AuthStore);

  readonly sidebarCollapsed = signal(false);
  readonly mobileSidebarOpen = signal(false);

  ngOnInit(): void {
    // Load feature modules for sidebar gating
    if (!this.auth.isSuperAdmin()) {
      this.features.loadModules();
    }

    // Apply tenant branding colors
    this.branding.applyBranding();

    // Restore sidebar state
    const saved = localStorage.getItem('g51_sidebar_collapsed');
    if (saved === 'true') {
      this.sidebarCollapsed.set(true);
    }
  }
}
