import { Injectable } from '@angular/core';

@Injectable({ providedIn: 'root' })
export class OfflineService {
  private queue: any[] = [];

  enqueue(action: { type: string; payload: any; timestamp: string }): void { this.queue.push(action); }
  getQueue(): any[] { return this.queue; }
  clearQueue(): void { this.queue = []; }
  cache(key: string, data: any): void { /* ApplicationSettings.setString */ }
  getCached<T>(key: string): T | null { return null; }
}
