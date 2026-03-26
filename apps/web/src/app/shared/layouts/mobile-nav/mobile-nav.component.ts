import { Component, inject, computed } from '@angular/core';
import { RouterLink, RouterLinkActive } from '@angular/router';
import { LucideAngularModule, LayoutDashboard, Shield, MapPin, FileText, Menu, Building2, Receipt, BarChart3, Settings } from 'lucide-angular';
import { AuthStore } from '@core/services/auth.store';

interface MobileNavItem {
  label: string;
  icon: any;
  route: string;
}

@Component({
  selector: 'g51-mobile-nav',
  standalone: true,
  imports: [RouterLink, RouterLinkActive, LucideAngularModule],
  template: `
    <nav
      class="fixed bottom-0 left-0 right-0 z-50 lg:hidden border-t"
      [style.background]="'var(--surface-card)'"
      [style.borderColor]="'var(--border-default)'"
      style="padding-bottom: env(safe-area-inset-bottom, 0px)"
    >
      <div class="flex items-center justify-around h-[var(--spacing-mobile-nav)] px-1">
        @for (item of navItems(); track item.route) {
          <a
            [routerLink]="item.route"
            routerLinkActive="mobile-active"
            [routerLinkActiveOptions]="{ exact: item.route === '/dashboard' }"
            class="flex flex-col items-center justify-center gap-0.5 flex-1 py-1.5 rounded-lg transition-colors"
            [style.color]="'var(--text-tertiary)'"
          >
            <lucide-icon [img]="item.icon" [size]="20" />
            <span class="text-[10px] font-medium">{{ item.label }}</span>
          </a>
        }
      </div>
    </nav>

    <style>
      .mobile-active {
        color: var(--sidebar-text-active) !important;
      }
    </style>
  `,
})
export class MobileNavComponent {
  private auth = inject(AuthStore);

  private readonly tenantItems: MobileNavItem[] = [
    { label: 'Dashboard', icon: LayoutDashboard, route: '/dashboard' },
    { label: 'Guards', icon: Shield, route: '/guards' },
    { label: 'Sites', icon: MapPin, route: '/sites' },
    { label: 'Reports', icon: FileText, route: '/reports' },
    { label: 'More', icon: Menu, route: '/settings' },
  ];

  private readonly superAdminItems: MobileNavItem[] = [
    { label: 'Dashboard', icon: LayoutDashboard, route: '/admin/dashboard' },
    { label: 'Tenants', icon: Building2, route: '/admin/tenants' },
    { label: 'Billing', icon: Receipt, route: '/admin/subscriptions' },
    { label: 'Analytics', icon: BarChart3, route: '/admin/analytics' },
    { label: 'Settings', icon: Settings, route: '/admin/settings' },
  ];

  readonly navItems = computed(() =>
    this.auth.isSuperAdmin() ? this.superAdminItems : this.tenantItems
  );
}
