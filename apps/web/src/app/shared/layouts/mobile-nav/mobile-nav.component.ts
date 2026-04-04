import { Component, inject, computed, signal } from '@angular/core';
import { RouterLink, RouterLinkActive, Router } from '@angular/router';
import { NgClass } from '@angular/common';
import { LucideAngularModule, LayoutDashboard, Shield, MapPin, FileText, Menu, Building2, Receipt, BarChart3, Settings, Users, Clock, AlertTriangle, Car, Eye, CalendarDays, MessageSquare, X, Ticket, CreditCard } from 'lucide-angular';
import { AuthStore } from '@core/services/auth.store';

@Component({
  selector: 'g51-mobile-nav',
  standalone: true,
  imports: [RouterLink, RouterLinkActive, NgClass, LucideAngularModule],
  template: `
    <nav class="fixed bottom-0 left-0 right-0 z-50 lg:hidden border-t"
      [style.background]="'var(--surface-card)'" [style.borderColor]="'var(--border-default)'"
      style="padding-bottom:env(safe-area-inset-bottom, 0px)">
      <div class="flex items-center justify-around h-[var(--spacing-mobile-nav)] px-1">
        @for (item of navItems(); track item.route) {
          @if (item.route === 'MORE') {
            <button (click)="sheetOpen.set(true)"
              class="flex flex-col items-center justify-center gap-0.5 flex-1 py-1.5 rounded-lg transition-colors"
              [style.color]="'var(--text-tertiary)'">
              <lucide-icon [img]="item.icon" [size]="20" />
              <span class="text-[10px] font-medium">{{ item.label }}</span>
            </button>
          } @else {
            <a [routerLink]="item.route" routerLinkActive="mobile-active" [routerLinkActiveOptions]="{exact: item.route === '/dashboard'}"
              class="flex flex-col items-center justify-center gap-0.5 flex-1 py-1.5 rounded-lg transition-colors" [style.color]="'var(--text-tertiary)'">
              <lucide-icon [img]="item.icon" [size]="20" />
              <span class="text-[10px] font-medium">{{ item.label }}</span>
            </a>
          }
        }
      </div>
    </nav>

    <!-- Bottom Sheet -->
    @if (sheetOpen()) {
      <div class="fixed inset-0 z-[60] lg:hidden">
        <div class="absolute inset-0 bg-black/40" (click)="sheetOpen.set(false)"></div>
        <div class="absolute bottom-0 left-0 right-0 rounded-t-2xl animate-slide-up"
          [style.background]="'var(--surface-card)'" style="max-height:75vh;padding-bottom:env(safe-area-inset-bottom, 0px)">
          <div class="flex items-center justify-between px-5 py-3 border-b" [style.borderColor]="'var(--border-default)'">
            <h3 class="text-sm font-semibold" [style.color]="'var(--text-primary)'">All Modules</h3>
            <button (click)="sheetOpen.set(false)" class="p-1 rounded-lg hover:bg-[var(--surface-muted)]"><lucide-icon [img]="XIcon" [size]="18" [style.color]="'var(--text-tertiary)'" /></button>
          </div>
          <div class="overflow-y-auto px-3 py-2" style="max-height:calc(75vh - 56px)">
            <div class="grid grid-cols-4 gap-1">
              @for (item of allModules; track item.route) {
                <a [routerLink]="item.route" (click)="sheetOpen.set(false)"
                  class="flex flex-col items-center gap-1 p-3 rounded-xl transition-colors hover:bg-[var(--surface-muted)]">
                  <div class="h-10 w-10 rounded-xl flex items-center justify-center" [style.background]="item.bg" [style.color]="item.color">
                    <lucide-icon [img]="item.icon" [size]="20" />
                  </div>
                  <span class="text-[10px] font-medium text-center leading-tight" [style.color]="'var(--text-secondary)'">{{ item.label }}</span>
                </a>
              }
            </div>
          </div>
        </div>
      </div>
    }

    <style>
      .mobile-active { color: var(--sidebar-text-active) !important; }
      @keyframes slideUp { from { transform: translateY(100%); } to { transform: translateY(0); } }
      .animate-slide-up { animation: slideUp 0.25s ease-out; }
    </style>
  `,
})
export class MobileNavComponent {
  private auth = inject(AuthStore);
  private router = inject(Router);
  readonly sheetOpen = signal(false);
  readonly XIcon = X;

  private readonly tenantItems = [
    { label: 'Home', icon: LayoutDashboard, route: '/dashboard' },
    { label: 'Guards', icon: Shield, route: '/guards' },
    { label: 'Sites', icon: MapPin, route: '/sites' },
    { label: 'Reports', icon: FileText, route: '/reports' },
    { label: 'More', icon: Menu, route: 'MORE' },
  ];

  private readonly superAdminItems = [
    { label: 'Dashboard', icon: LayoutDashboard, route: '/admin/dashboard' },
    { label: 'Tenants', icon: Building2, route: '/admin/tenants' },
    { label: 'Billing', icon: Receipt, route: '/admin/subscriptions' },
    { label: 'Analytics', icon: BarChart3, route: '/admin/analytics' },
    { label: 'More', icon: Menu, route: 'MORE' },
  ];

  readonly navItems = computed(() => this.auth.isSuperAdmin() ? this.superAdminItems : this.tenantItems);

  allModules = [
    { label: 'Dashboard', icon: LayoutDashboard, route: '/dashboard', bg: '#EFF6FF', color: '#3B82F6' },
    { label: 'Guards', icon: Shield, route: '/guards', bg: '#ECFDF5', color: '#10B981' },
    { label: 'Sites', icon: MapPin, route: '/sites', bg: '#FEF3C7', color: '#F59E0B' },
    { label: 'Clients', icon: Building2, route: '/clients', bg: '#F3E8FF', color: '#8B5CF6' },
    { label: 'Scheduling', icon: CalendarDays, route: '/scheduling', bg: '#E0F2FE', color: '#0EA5E9' },
    { label: 'Attendance', icon: Clock, route: '/attendance', bg: '#FFF7ED', color: '#F97316' },
    { label: 'Incidents', icon: AlertTriangle, route: '/incidents', bg: '#FEF2F2', color: '#EF4444' },
    { label: 'Reports', icon: FileText, route: '/reports', bg: '#F0FDF4', color: '#22C55E' },
    { label: 'Visitors', icon: Eye, route: '/visitors', bg: '#FDF4FF', color: '#D946EF' },
    { label: 'Invoicing', icon: Receipt, route: '/invoices', bg: '#ECFEFF', color: '#06B6D4' },
    { label: 'Patrol', icon: Car, route: '/vehicle-patrol', bg: '#FFF1F2', color: '#F43F5E' },
    { label: 'Chat', icon: MessageSquare, route: '/chat', bg: '#F0F9FF', color: '#0284C7' },
    { label: 'Tickets', icon: Ticket, route: '/support', bg: '#FFFBEB', color: '#D97706' },
    { label: 'Users', icon: Users, route: '/users', bg: '#F5F3FF', color: '#7C3AED' },
    { label: 'Billing', icon: CreditCard, route: '/billing', bg: '#F8FAFC', color: '#64748B' },
    { label: 'Settings', icon: Settings, route: '/settings', bg: '#F1F5F9', color: '#475569' },
  ];
}
