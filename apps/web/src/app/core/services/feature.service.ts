import { Injectable, inject, signal } from '@angular/core';
import { ApiService } from './api.service';

export interface FeatureModuleInfo {
  module_key: string;
  name: string;
  category: string;
  is_enabled: boolean;
  is_core: boolean;
}

@Injectable({ providedIn: 'root' })
export class FeatureService {
  private api = inject(ApiService);

  readonly modules = signal<FeatureModuleInfo[]>([]);
  readonly loaded = signal(false);

  loadModules(): void {
    this.api.get<{ modules: FeatureModuleInfo[] }>('/features/tenant').subscribe({
      next: res => {
        if (res.success && res.data) {
          this.modules.set(res.data.modules);
          this.loaded.set(true);
        }
      },
      error: () => this.loaded.set(true),
    });
  }

  isEnabled(moduleKey: string): boolean {
    if (!this.loaded()) return true; // Show all until loaded
    const mod = this.modules().find(m => m.module_key === moduleKey);
    if (!mod) return true; // If module not in list, show by default
    return mod.is_enabled || mod.is_core;
  }

  getEnabledModules(): string[] {
    return this.modules().filter(m => m.is_enabled).map(m => m.module_key);
  }

  getByCategory(category: string): FeatureModuleInfo[] {
    return this.modules().filter(m => m.category === category);
  }
}
