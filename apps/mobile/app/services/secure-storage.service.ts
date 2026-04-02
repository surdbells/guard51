import { ApplicationSettings } from '@nativescript/core';

/**
 * Secure storage using ApplicationSettings (encrypted on Android/iOS).
 * For production, replace with @nicklason/nativescript-secure-storage.
 */
export class SecureStorage {
  static set(key: string, value: string): void { ApplicationSettings.setString(`g51_${key}`, value); }
  static get(key: string): string | null { return ApplicationSettings.getString(`g51_${key}`, null as any) || null; }
  static remove(key: string): void { ApplicationSettings.remove(`g51_${key}`); }
  static clear(): void { ['access_token', 'refresh_token', 'user'].forEach(k => this.remove(k)); }
}
