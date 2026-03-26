import { Injectable, signal, effect } from '@angular/core';

export type Theme = 'light' | 'dark' | 'system';

@Injectable({ providedIn: 'root' })
export class ThemeService {
  private readonly STORAGE_KEY = 'g51_theme';

  /** Current resolved theme (always 'light' or 'dark') */
  readonly theme = signal<'light' | 'dark'>('light');

  /** User preference (includes 'system' option) */
  readonly preference = signal<Theme>('light');

  init(): void {
    const saved = (localStorage.getItem(this.STORAGE_KEY) as Theme) || 'light';
    this.setTheme(saved);

    // Listen for system theme changes
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
      if (this.preference() === 'system') {
        this.applyTheme(e.matches ? 'dark' : 'light');
      }
    });
  }

  setTheme(pref: Theme): void {
    this.preference.set(pref);
    localStorage.setItem(this.STORAGE_KEY, pref);

    if (pref === 'system') {
      const isDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
      this.applyTheme(isDark ? 'dark' : 'light');
    } else {
      this.applyTheme(pref);
    }
  }

  toggle(): void {
    this.setTheme(this.theme() === 'light' ? 'dark' : 'light');
  }

  private applyTheme(resolved: 'light' | 'dark'): void {
    this.theme.set(resolved);
    const root = document.documentElement;
    if (resolved === 'dark') {
      root.classList.add('dark');
    } else {
      root.classList.remove('dark');
    }
  }
}
