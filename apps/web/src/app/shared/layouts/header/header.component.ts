import { Component, inject, input } from '@angular/core';
import { NgClass } from '@angular/common';
import { TranslateModule } from '@ngx-translate/core';
import { LucideAngularModule, Search, Bell, Sun, Moon, Globe, Menu, LogOut, User, Settings } from 'lucide-angular';
import { AuthStore } from '@core/services/auth.store';
import { AuthService } from '@core/services/auth.service';
import { ThemeService } from '@core/services/theme.service';
import { BrandingService } from '@core/services/branding.service';

@Component({
  selector: 'g51-header',
  standalone: true,
  imports: [NgClass, TranslateModule, LucideAngularModule],
  template: `
    <header
      class="h-[var(--spacing-header)] flex items-center justify-between px-4 md:px-6 border-b sticky top-0 z-30"
      [style.background]="'var(--surface-header)'"
      [style.borderColor]="'var(--border-default)'"
    >
      <!-- Left: Mobile menu + Page title area -->
      <div class="flex items-center gap-3">
        <!-- Mobile hamburger (shown on mobile, hidden on desktop) -->
        <button
          class="lg:hidden p-2 rounded-lg hover:bg-[var(--surface-hover)] transition-colors"
          [style.color]="'var(--text-secondary)'"
          (click)="mobileMenuOpen = !mobileMenuOpen"
        >
          <lucide-icon [img]="MenuIcon" [size]="20" />
        </button>

        <!-- Search bar (desktop only) -->
        <div class="hidden md:flex items-center gap-2 px-3 py-1.5 rounded-lg min-w-[280px]"
          [style.background]="'var(--surface-muted)'"
        >
          <lucide-icon [img]="SearchIcon" [size]="15" [style.color]="'var(--text-tertiary)'" />
          <input
            type="text"
            [placeholder]="'common.search' | translate"
            class="bg-transparent border-none outline-none text-sm flex-1"
            [style.color]="'var(--text-primary)'"
          />
          <kbd class="text-[10px] px-1.5 py-0.5 rounded border"
            [style.borderColor]="'var(--border-default)'"
            [style.color]="'var(--text-tertiary)'"
          >⌘K</kbd>
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

        <!-- Notifications -->
        <button
          class="p-2 rounded-lg hover:bg-[var(--surface-hover)] transition-colors relative"
          [style.color]="'var(--text-secondary)'"
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
              <a class="flex items-center gap-2 px-3 py-2 text-sm hover:bg-[var(--surface-hover)] cursor-pointer"
                [style.color]="'var(--text-primary)'"
              >
                <lucide-icon [img]="UserIcon" [size]="15" />
                Profile
              </a>
              <a routerLink="/settings" class="flex items-center gap-2 px-3 py-2 text-sm hover:bg-[var(--surface-hover)] cursor-pointer"
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
export class HeaderComponent {
  readonly showUserName = input(true);

  readonly auth = inject(AuthStore);
  readonly theme = inject(ThemeService);
  readonly branding = inject(BrandingService);
  private authService = inject(AuthService);

  userMenuOpen = false;
  mobileMenuOpen = false;

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

  logout(): void {
    this.userMenuOpen = false;
    this.authService.logout();
  }
}
