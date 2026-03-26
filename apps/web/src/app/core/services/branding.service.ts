import { Injectable, inject, signal, computed } from '@angular/core';
import { AuthStore } from './auth.store';

@Injectable({ providedIn: 'root' })
export class BrandingService {
  private authStore = inject(AuthStore);

  readonly brandColors = computed(() => {
    const branding = this.authStore.tenant()?.branding ?? {};
    return {
      primary: branding['primary_color'] || '#1B3A5C',
      secondary: branding['secondary_color'] || '#E8792D',
      accent: branding['accent_color'] || '#3B82F6',
    };
  });

  readonly logoUrl = computed(() => this.authStore.tenant()?.logo_url);
  readonly orgName = computed(() => this.authStore.tenant()?.name ?? 'Guard51');

  /** Apply branding CSS variables to document */
  applyBranding(): void {
    const colors = this.brandColors();
    const root = document.documentElement;
    root.style.setProperty('--color-brand-500', colors.primary);
    root.style.setProperty('--color-accent-500', colors.secondary);
  }

  /** Chart color palette for SVG charts */
  chartColors(): string[] {
    const { primary, secondary } = this.brandColors();
    return [primary, secondary, '#10B981', '#F59E0B', '#8B5CF6', '#EC4899', '#06B6D4', '#F97316'];
  }
}
