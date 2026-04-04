import { Component, inject, signal, input, output, HostListener, OnInit } from '@angular/core';
import { NgClass } from '@angular/common';
import { Router } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { TranslateModule, TranslateService } from '@ngx-translate/core';
import { LucideAngularModule, Search, Bell, Sun, Moon, Globe, Menu, LogOut, User, Settings } from 'lucide-angular';
import { AuthStore } from '@core/services/auth.store';
import { AuthService } from '@core/services/auth.service';
import { ThemeService } from '@core/services/theme.service';
import { BrandingService } from '@core/services/branding.service';

@Component({
  selector: 'g51-header',
  standalone: true,
  imports: [NgClass, FormsModule, TranslateModule, LucideAngularModule],
  template: `
    <header
      class="h-[var(--spacing-header)] flex items-center justify-between px-4 md:px-6 border-b sticky top-0 z-30"
      [style.background]="'var(--surface-header)'"
      [style.borderColor]="'var(--border-default)'"
    >
      <!-- Left: Mobile menu + Search -->
      <div class="flex items-center gap-3">
        <button class="lg:hidden p-2 rounded-lg hover:bg-[var(--surface-hover)] transition-colors" [style.color]="'var(--text-secondary)'" (click)="toggleMobileMenu.emit()">
          <lucide-icon [img]="MenuIcon" [size]="20" />
        </button>

        <!-- Search bar -->
        <div class="hidden md:flex items-center gap-2 px-3 py-1.5 rounded-lg min-w-[280px] relative"
          [style.background]="'var(--surface-muted)'">
          <lucide-icon [img]="SearchIcon" [size]="15" [style.color]="'var(--text-tertiary)'" />
          <input type="text" [(ngModel)]="searchQuery" (input)="onSearch()" (focus)="searchOpen.set(true)" (keydown.escape)="searchOpen.set(false)" (keydown.enter)="navigateToFirst()"
            placeholder="Search guards, sites, clients..." class="bg-transparent border-none outline-none text-sm flex-1" [style.color]="'var(--text-primary)'" />
          <kbd class="text-[10px] px-1.5 py-0.5 rounded border" [style.borderColor]="'var(--border-default)'" [style.color]="'var(--text-tertiary)'">⌘K</kbd>

          @if (searchOpen() && searchQuery.length >= 2) {
            <div class="absolute top-full left-0 right-0 mt-1 card p-2 shadow-lg max-h-64 overflow-y-auto z-50">
              @if (searchResults().length) {
                @for (r of searchResults(); track r.route) {
                  <a (click)="navigateTo(r.route)" class="flex items-center gap-3 px-3 py-2 rounded-lg cursor-pointer hover:bg-[var(--surface-hover)] transition-colors">
                    <span class="text-[10px] font-bold px-1.5 py-0.5 rounded" [style.background]="'var(--surface-muted)'" [style.color]="'var(--text-tertiary)'">{{ r.type }}</span>
                    <span class="text-sm" [style.color]="'var(--text-primary)'">{{ r.label }}</span>
                  </a>
                }
              } @else {
                <p class="text-xs text-center py-3" [style.color]="'var(--text-tertiary)'">No results for "{{ searchQuery }}"</p>
              }
            </div>
          }
        </div>
      </div>

      <!-- Right: Actions -->
      <div class="flex items-center gap-1">
        <!-- Theme toggle -->
        <button
          (click)="theme.toggle()"
          class="p-2 rounded-lg hover:bg-[var(--surface-hover)] transition-colors"
          [style.color]="'var(--text-secondary)'"
          [title]="theme.theme() === 'light' ? 'Switch to dark mode' : 'Switch to light mode'"
        >
          <lucide-icon [img]="theme.theme() === 'light' ? MoonIcon : SunIcon" [size]="18" />
        </button>

        <!-- Language dropdown -->
        <div class="relative">
          <button (click)="langMenuOpen = !langMenuOpen" class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg hover:bg-[var(--surface-hover)] transition-colors" [style.color]="'var(--text-secondary)'" title="Language">
            <lucide-icon [img]="GlobeIcon" [size]="16" />
            <span class="text-xs font-medium hidden sm:inline">{{ currentLangLabel }}</span>
          </button>
          @if (langMenuOpen) {
            <div class="absolute right-0 top-full mt-1 w-44 rounded-xl border py-1 z-50 animate-scale-in"
              [style.background]="'var(--surface-card)'" [style.borderColor]="'var(--border-default)'" style="box-shadow: var(--shadow-lg)">
              @for (lang of languages; track lang.code) {
                <button (click)="switchLang(lang.code)" class="w-full flex items-center gap-3 px-4 py-2.5 text-xs hover:bg-[var(--surface-hover)] transition-colors"
                  [style.color]="currentLang === lang.code ? 'var(--brand-500)' : 'var(--text-primary)'"
                  [style.fontWeight]="currentLang === lang.code ? '600' : '400'">
                  <span class="text-base">{{ lang.flag }}</span>
                  <span>{{ lang.label }}</span>
                  @if (currentLang === lang.code) { <span class="ml-auto text-[10px]">✓</span> }
                </button>
              }
            </div>
          }
        </div>

        <!-- Notifications -->
        <button (click)="navigateTo('/notifications')"
          class="p-2 rounded-lg hover:bg-[var(--surface-hover)] transition-colors relative"
          [style.color]="'var(--text-secondary)'" title="Notifications"
        >
          <lucide-icon [img]="BellIcon" [size]="18" />
          <span class="absolute top-1.5 right-1.5 h-2 w-2 rounded-full bg-[var(--color-danger)]"></span>
        </button>

        <!-- User avatar / dropdown -->
        <div class="relative ml-1">
          <button
            (click)="userMenuOpen = !userMenuOpen"
            class="flex items-center gap-2 px-2 py-1.5 rounded-lg hover:bg-[var(--surface-hover)] transition-colors"
          >
            <div class="h-7 w-7 rounded-full flex items-center justify-center text-xs font-semibold"
              [style.background]="branding.brandColors().primary"
              [style.color]="'var(--text-on-brand)'"
            >
              {{ auth.userInitials() }}
            </div>
            @if (showUserName()) {
              <span class="hidden sm:block text-sm font-medium" [style.color]="'var(--text-primary)'">
                {{ auth.user()?.first_name }}
              </span>
            }
          </button>

          <!-- Dropdown -->
          @if (userMenuOpen) {
            <div
              class="absolute right-0 top-full mt-1 w-48 rounded-lg border py-1 animate-scale-in"
              [style.background]="'var(--surface-card)'"
              [style.borderColor]="'var(--border-default)'"
              style="box-shadow: var(--shadow-dropdown)"
            >
              <a (click)="navigateTo('/profile'); userMenuOpen = false" class="flex items-center gap-2 px-3 py-2 text-sm hover:bg-[var(--surface-hover)] cursor-pointer"
                [style.color]="'var(--text-primary)'"
              >
                <lucide-icon [img]="UserIcon" [size]="15" />
                Profile
              </a>
              <a (click)="navigateTo('/settings'); userMenuOpen = false" class="flex items-center gap-2 px-3 py-2 text-sm hover:bg-[var(--surface-hover)] cursor-pointer"
                [style.color]="'var(--text-primary)'"
              >
                <lucide-icon [img]="SettingsIcon" [size]="15" />
                Settings
              </a>
              <div class="my-1 h-px" [style.background]="'var(--border-default)'"></div>
              <button
                (click)="logout()"
                class="flex items-center gap-2 px-3 py-2 text-sm w-full text-left hover:bg-[var(--surface-hover)]"
                style="color: var(--color-danger)"
              >
                <lucide-icon [img]="LogOutIcon" [size]="15" />
                {{ 'nav.logout' | translate }}
              </button>
            </div>
          }
        </div>
      </div>
    </header>

    <!-- Click outside to close dropdown -->
    @if (userMenuOpen) {
      <div class="fixed inset-0 z-20" (click)="userMenuOpen = false"></div>
    }
  `,
})
export class HeaderComponent implements OnInit {
  readonly showUserName = input(true);

  readonly auth = inject(AuthStore);
  readonly theme = inject(ThemeService);
  readonly branding = inject(BrandingService);
  private authService = inject(AuthService);
  private router = inject(Router);
  private translate = inject(TranslateService);

  userMenuOpen = false;
  readonly toggleMobileMenu = output<void>();
  searchQuery = '';
  readonly searchOpen = signal(false);
  readonly searchResults = signal<{ type: string; label: string; route: string }[]>([]);

  // All searchable pages
  private allPages = [
    { type: 'Page', label: 'Dashboard', route: '/dashboard', keywords: 'dashboard home overview stats' },
    { type: 'Page', label: 'Guards', route: '/guards', keywords: 'guards personnel staff employees' },
    { type: 'Page', label: 'Add Guard', route: '/guards/new', keywords: 'add new guard create hire' },
    { type: 'Page', label: 'Clients', route: '/clients', keywords: 'clients companies customers' },
    { type: 'Page', label: 'Add Client', route: '/clients/new', keywords: 'add new client create' },
    { type: 'Page', label: 'Sites', route: '/sites', keywords: 'sites locations post premises' },
    { type: 'Page', label: 'Add Site', route: '/sites/new', keywords: 'add new site create location' },
    { type: 'Page', label: 'Scheduling', route: '/scheduling', keywords: 'schedule shifts roster calendar' },
    { type: 'Page', label: 'Attendance', route: '/attendance', keywords: 'attendance clock time hours' },
    { type: 'Page', label: 'Live Tracker', route: '/tracker', keywords: 'tracker gps live location map' },
    { type: 'Page', label: 'Site Tours', route: '/tours', keywords: 'tours patrol checkpoints nfc qr' },
    { type: 'Page', label: 'Reports', route: '/reports', keywords: 'reports dar daily activity watch' },
    { type: 'Page', label: 'Incidents', route: '/incidents', keywords: 'incidents alert emergency security' },
    { type: 'Page', label: 'Dispatch', route: '/dispatch', keywords: 'dispatch calls emergency response' },
    { type: 'Page', label: 'Visitors', route: '/visitors', keywords: 'visitors check-in guest management' },
    { type: 'Page', label: 'Vehicle Patrol', route: '/vehicle-patrol', keywords: 'vehicle patrol car route' },
    { type: 'Page', label: 'Parking', route: '/parking', keywords: 'parking vehicles lot area' },
    { type: 'Page', label: 'Invoices', route: '/invoices', keywords: 'invoices billing payment money' },
    { type: 'Page', label: 'Payroll', route: '/payroll', keywords: 'payroll salary wage pay guard' },
    { type: 'Page', label: 'Messenger', route: '/chat', keywords: 'chat messenger message communication' },
    { type: 'Page', label: 'Team Management', route: '/users', keywords: 'users team roles permissions invite' },
    { type: 'Page', label: 'Licenses', route: '/licenses', keywords: 'licenses certification guard expiry' },
    { type: 'Page', label: 'Security', route: '/security', keywords: 'security 2fa two-factor audit log' },
    { type: 'Page', label: 'Settings', route: '/settings', keywords: 'settings company branding notifications' },
    { type: 'Page', label: 'Analytics', route: '/analytics', keywords: 'analytics performance kpi metrics' },
    { type: 'Page', label: 'Billing', route: '/billing', keywords: 'billing subscription plan payment' },
  ];

  // Icons
  readonly SearchIcon = Search;
  readonly BellIcon = Bell;
  readonly SunIcon = Sun;
  readonly MoonIcon = Moon;
  readonly GlobeIcon = Globe;
  readonly MenuIcon = Menu;
  readonly LogOutIcon = LogOut;
  readonly UserIcon = User;
  readonly SettingsIcon = Settings;

  @HostListener('document:keydown', ['$event'])
  onKeydown(e: KeyboardEvent): void {
    if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
      e.preventDefault();
      const input = document.querySelector('header input[type="text"]') as HTMLInputElement;
      input?.focus();
      this.searchOpen.set(true);
    }
    if (e.key === 'Escape') this.searchOpen.set(false);
  }

  @HostListener('document:click', ['$event'])
  onDocClick(e: Event): void {
    const el = e.target as HTMLElement;
    if (!el.closest('header .relative')) {
      this.searchOpen.set(false);
      this.langMenuOpen = false;
    }
  }

  ngOnInit(): void {
    this.currentLang = localStorage.getItem('g51_lang') || this.translate.currentLang || 'en';
  }

  onSearch(): void {
    if (this.searchQuery.length < 2) { this.searchResults.set([]); return; }
    const q = this.searchQuery.toLowerCase();
    const results = this.allPages.filter(p => p.label.toLowerCase().includes(q) || p.keywords.includes(q)).slice(0, 8);
    this.searchResults.set(results);
  }

  navigateToFirst(): void {
    const r = this.searchResults();
    if (r.length) this.navigateTo(r[0].route);
  }

  navigateTo(route: string): void {
    this.searchOpen.set(false);
    this.searchQuery = '';
    this.router.navigateByUrl(route);
  }

  langMenuOpen = false;
  currentLang = 'en';
  languages = [
    { code: 'en', label: 'English', flag: '🇬🇧' },
    { code: 'pcm', label: 'Nigerian Pidgin', flag: '🇳🇬' },
  ];
  get currentLangLabel(): string { return this.languages.find(l => l.code === this.currentLang)?.label || 'English'; }

  switchLang(code: string): void {
    this.currentLang = code;
    this.translate.use(code);
    localStorage.setItem('g51_lang', code);
    this.langMenuOpen = false;
  }

  logout(): void {
    this.userMenuOpen = false;
    this.authService.logout();
  }
}
