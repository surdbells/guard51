import { ApplicationSettings } from '@nativescript/core';
import { ApiService } from './api.service';

interface QueuedAction {
  id: string;
  method: 'POST' | 'PUT';
  path: string;
  body: any;
  createdAt: string;
}

/**
 * Queues API calls when offline. Syncs when connectivity is restored.
 */
export class OfflineQueue {
  private static QUEUE_KEY = 'g51_offline_queue';

  static enqueue(method: 'POST' | 'PUT', path: string, body: any): void {
    const queue = this.getQueue();
    queue.push({
      id: Date.now().toString(36) + Math.random().toString(36).slice(2, 6),
      method, path, body,
      createdAt: new Date().toISOString(),
    });
    this.saveQueue(queue);
    console.log(`[Offline] Queued ${method} ${path}. Queue size: ${queue.length}`);
  }

  static async sync(): Promise<{ synced: number; failed: number }> {
    const queue = this.getQueue();
    if (!queue.length) return { synced: 0, failed: 0 };

    let synced = 0, failed = 0;
    const remaining: QueuedAction[] = [];

    for (const action of queue) {
      try {
        if (action.method === 'POST') {
          await ApiService.post(action.path, action.body);
        }
        synced++;
      } catch (e) {
        failed++;
        remaining.push(action); // Keep for next sync
      }
    }

    this.saveQueue(remaining);
    console.log(`[Offline] Synced: ${synced}, Failed: ${failed}, Remaining: ${remaining.length}`);
    return { synced, failed };
  }

  static getQueueSize(): number { return this.getQueue().length; }

  private static getQueue(): QueuedAction[] {
    const raw = ApplicationSettings.getString(this.QUEUE_KEY, '[]');
    try { return JSON.parse(raw); } catch { return []; }
  }

  private static saveQueue(queue: QueuedAction[]): void {
    ApplicationSettings.setString(this.QUEUE_KEY, JSON.stringify(queue));
  }
}
