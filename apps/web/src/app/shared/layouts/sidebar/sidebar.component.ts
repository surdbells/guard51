import { Component, inject, input, output, computed } from '@angular/core';
import { RouterLink, RouterLinkActive } from '@angular/router';
import { NgClass } from '@angular/common';
import { TranslateModule } from '@ngx-translate/core';
import { LucideAngularModule, LayoutDashboard, Users, Building2, MapPin, Calendar, Clock, Navigation, FileText, AlertTriangle, Radio, Receipt, Wallet, MessageSquare, Settings, HelpCircle, LogOut, ChevronLeft, ChevronRight, Search, Shield, Boxes, AppWindow, BarChart3, Bell, UserCheck, Route, Car, DoorOpen, KeyRound, Award } from 'lucide-angular';
import { AuthStore } from '@core/services/auth.store';
import { FeatureService } from '@core/services/feature.service';
import { BrandingService } from '@core/services/branding.service';

interface NavItem {
  label: string;
  translateKey: string;
  icon: any;
  route: string;
  moduleKey?: string;
  roles?: string[];
}

interface NavSection {
  label: string;
  translateKey: string;
  items: NavItem[];
}

@Component({
  selector: 'g51-sidebar',
  standalone: true,
  imports: [RouterLink, RouterLinkActive, NgClass, TranslateModule, LucideAngularModule],
  template: `
    <aside
      class="fixed left-0 top-0 h-full z-40 transition-all border-r flex flex-col"
      [ngClass]="collapsed() ? 'w-[var(--spacing-sidebar-collapsed)]' : 'w-[var(--spacing-sidebar)]'"
      [style.background]="'var(--surface-sidebar)'"
      [style.borderColor]="'var(--border-default)'"
    >
      <!-- Logo -->
      <div class="h-[var(--spacing-header)] flex items-center px-4 border-b" [style.borderColor]="'var(--border-default)'">
        @if (!collapsed()) {
          <div class="flex items-center gap-2.5">
            @if (branding.logoUrl()) {
              <img [src]="branding.logoUrl()" alt="Logo" class="h-8 w-8 rounded-lg object-cover" />
            } @else {
              <div class="h-8 w-8 rounded-lg flex items-center justify-center text-white text-sm font-bold" [style.background]="branding.brandColors().primary">
                G
              </div>
            }
            <span class="text-base font-semibold tracking-tight" [style.color]="'var(--text-primary)'">
              {{ branding.orgName() }}
            </span>
          </div>
        } @else {
          <div class="mx-auto h-8 w-8 rounded-lg flex items-center justify-center text-white text-sm font-bold" [style.background]="branding.brandColors().primary">
            G
          </div>
        }
      </div>

      <!-- Search -->
      @if (!collapsed()) {
        <div class="px-3 pt-3 pb-1">
          <div class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm cursor-pointer"
            [style.background]="'var(--surface-muted)'"
            [style.color]="'var(--text-tertiary)'"
          >
            <lucide-icon [img]="SearchIcon" [size]="15" />
            <span>Search...</span>
            <kbd class="ml-auto text-[10px] px-1.5 py-0.5 rounded border"
              [style.borderColor]="'var(--border-default)'"
              [style.color]="'var(--text-tertiary)'"
            >⌘K</kbd>
          </div>
        </div>
      }

      <!-- Navigation -->
      <nav class="flex-1 overflow-y-auto py-2 px-2">
        @for (section of visibleSections(); track section.label) {
          @if (!collapsed()) {
            <div class="section-label">{{ (section.translateKey | translate) === section.translateKey ? section.label : (section.translateKey | translate) }}</div>
          } @else {
            <div class="my-2 mx-2 h-px" [style.background]="'var(--border-default)'"></div>
          }
          @for (item of section.items; track item.route) {
            <a
              [routerLink]="item.route"
              routerLinkActive="active-link"
              [routerLinkActiveOptions]="{ exact: item.route === '/dashboard' }"
              class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors group mb-0.5"
              [ngClass]="collapsed() ? 'justify-center' : ''"
              [style.color]="'var(--sidebar-text)'"
              [title]="collapsed() ? (item.translateKey | translate) : ''"
            >
              <lucide-icon [img]="item.icon" [size]="18" class="shrink-0 transition-colors" [style.color]="'var(--sidebar-icon)'" />
              @if (!collapsed()) {
                <span>{{ (item.translateKey | translate) === item.translateKey ? item.label : (item.translateKey | translate) }}</span>
              }
            </a>
          }
        }
      </nav>

      <!-- Collapse toggle -->
      <div class="px-2 py-1 border-t" [style.borderColor]="'var(--border-default)'">
        <button
          (click)="toggleCollapse.emit()"
          class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm w-full transition-colors hover:bg-[var(--surface-hover)]"
          [ngClass]="collapsed() ? 'justify-center' : ''"
          [style.color]="'var(--sidebar-text)'"
        >
          <lucide-icon [img]="collapsed() ? ChevronRightIcon : ChevronLeftIcon" [size]="18" />
          @if (!collapsed()) {
            <span>Collapse</span>
          }
        </button>
      </div>

      <!-- User profile -->
      <div class="px-3 py-3 border-t" [style.borderColor]="'var(--border-default)'">
        <div class="flex items-center gap-3" [ngClass]="collapsed() ? 'justify-center' : ''">
          <div class="h-8 w-8 rounded-full flex items-center justify-center text-xs font-semibold shrink-0"
            [style.background]="branding.brandColors().primary"
            [style.color]="'var(--text-on-brand)'"
          >
            {{ auth.userInitials() }}
          </div>
          @if (!collapsed()) {
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium truncate" [style.color]="'var(--text-primary)'">
                {{ auth.user()?.full_name }}
              </p>
              <p class="text-xs truncate" [style.color]="'var(--text-tertiary)'">
                {{ auth.user()?.email }}
              </p>
            </div>
          }
        </div>
      </div>
    </aside>

    <style>
      .active-link {
        background: var(--sidebar-active-bg) !important;
        color: var(--sidebar-text-active) !important;
      }
      .active-link lucide-icon {
        color: var(--sidebar-icon-active) !important;
      }
    </style>
  `,
})
export class SidebarComponent {
  readonly collapsed = input(false);
  readonly toggleCollapse = output();

  readonly auth = inject(AuthStore);
  readonly branding = inject(BrandingService);
  private features = inject(FeatureService);

  // Icons
  readonly SearchIcon = Search;
  readonly ChevronLeftIcon = ChevronLeft;
  readonly ChevronRightIcon = ChevronRight;

  private readonly allSections: NavSection[] = [
    {
      label: 'Main', translateKey: 'nav_sections.main',
      items: [
        { label: 'Dashboard', translateKey: 'nav.dashboard', icon: LayoutDashboard, route: '/dashboard' },
        { label: 'Guards', translateKey: 'nav.guards', icon: Shield, route: '/guards', moduleKey: 'guard_management' },
        { label: 'Clients', translateKey: 'nav.clients', icon: Building2, route: '/clients', moduleKey: 'client_management' },
        { label: 'Sites', translateKey: 'nav.sites', icon: MapPin, route: '/sites', moduleKey: 'site_management' },
      ],
    },
    {
      label: 'Operations', translateKey: 'nav_sections.operations',
      items: [
        { label: 'Scheduling', translateKey: 'nav.scheduling', icon: Calendar, route: '/scheduling', moduleKey: 'scheduling' },
        { label: 'Attendance', translateKey: 'nav.attendance', icon: Clock, route: '/attendance', moduleKey: 'time_clock' },
        { label: 'Live Tracker', translateKey: 'nav.tracker', icon: Navigation, route: '/tracker', moduleKey: 'live_tracker' },
        { label: 'Site Tours', translateKey: 'nav.tours', icon: Route, route: '/tours', moduleKey: 'guard_tour' },
        { label: 'Reports', translateKey: 'nav.reports', icon: FileText, route: '/reports', moduleKey: 'daily_activity_report' },
        { label: 'Incidents', translateKey: 'nav.incidents', icon: AlertTriangle, route: '/incidents', moduleKey: 'incident_reporting' },
        { label: 'Dispatch', translateKey: 'nav.dispatch', icon: Radio, route: '/dispatch', moduleKey: 'dispatcher_console' },
        { label: 'Visitors', translateKey: 'nav.visitors', icon: DoorOpen, route: '/visitors', moduleKey: 'visitor_management' },
        { label: 'Vehicle Patrol', translateKey: 'nav.vehicle_patrol', icon: Car, route: '/vehicle-patrol', moduleKey: 'vehicle_patrol' },
        { label: 'Parking', translateKey: 'nav.parking', icon: Car, route: '/parking', moduleKey: 'parking' },
      ],
    },
    {
      label: 'Finance', translateKey: 'nav_sections.finance',
      items: [
        { label: 'Invoices', translateKey: 'nav.invoices', icon: Receipt, route: '/invoices', moduleKey: 'invoicing' },
        { label: 'Payroll', translateKey: 'nav.payroll', icon: Wallet, route: '/payroll', moduleKey: 'payroll' },
      ],
    },
    {
      label: 'Communication', translateKey: 'nav_sections.communication',
      items: [
        { label: 'Messenger', translateKey: 'nav.messenger', icon: MessageSquare, route: '/chat', moduleKey: 'messenger' },
      ],
    },
    {
      label: 'Administration', translateKey: 'nav_sections.admin',
      items: [
        { label: 'Team Management', translateKey: 'nav.users', icon: UserCheck, route: '/users' },
        { label: 'Licenses', translateKey: 'nav.licenses', icon: Award, route: '/licenses' },
        { label: 'Security', translateKey: 'nav.security', icon: KeyRound, route: '/security' },
        { label: 'Settings', translateKey: 'nav.settings', icon: Settings, route: '/settings' },
      ],
    },
  ];

  private readonly superAdminSections: NavSection[] = [
    {
      label: 'Platform', translateKey: 'nav_sections.admin',
      items: [
        { label: 'Dashboard', translateKey: 'nav.dashboard', icon: LayoutDashboard, route: '/admin/dashboard' },
        { label: 'Tenants', translateKey: 'nav.clients', icon: Building2, route: '/admin/tenants' },
        { label: 'Subscriptions', translateKey: 'nav.invoices', icon: Receipt, route: '/admin/subscriptions' },
        { label: 'Payments', translateKey: 'nav.invoices', icon: Bell, route: '/admin/payments' },
        { label: 'Features', translateKey: 'nav.settings', icon: Boxes, route: '/admin/features' },
        { label: 'Apps', translateKey: 'nav.settings', icon: AppWindow, route: '/admin/apps' },
        { label: 'Analytics', translateKey: 'nav.reports', icon: BarChart3, route: '/admin/analytics' },
        { label: 'Settings', translateKey: 'nav.settings', icon: Settings, route: '/admin/settings' },
      ],
    },
  ];

  readonly visibleSections = computed(() => {
    if (this.auth.isSuperAdmin()) {
      return this.superAdminSections;
    }

    return this.allSections
      .map(section => ({
        ...section,
        items: section.items.filter(item => {
          if (item.roles && !item.roles.includes(this.auth.userRole() ?? '')) return false;
          if (item.moduleKey && !this.features.isEnabled(item.moduleKey)) return false;
          return true;
        }),
      }))
      .filter(section => section.items.length > 0);
  });
}
