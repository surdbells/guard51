import { Injectable } from '@angular/core';

export interface G51Notification {
  id: string;
  title: string;
  body: string;
  type: 'shift_change' | 'new_task' | 'panic' | 'geofence' | 'tour' | 'general';
  data?: Record<string, any>;
  timestamp: string;
  read: boolean;
}

@Injectable({ providedIn: 'root' })
export class NotificationService {
  private notifications: G51Notification[] = [];

  /**
   * Schedule a local notification (NativeScript local-notifications plugin).
   */
  async scheduleLocal(title: string, body: string, type: G51Notification['type'] = 'general'): Promise<void> {
    // LocalNotifications.schedule([{ id: Date.now(), title, body, badge: 1 }]);
    console.log(`[Notification] ${type}: ${title} — ${body}`);
    this.notifications.unshift({
      id: Date.now().toString(), title, body, type,
      timestamp: new Date().toISOString(), read: false,
    });
  }

  /**
   * Handle incoming push notification from WebSocket.
   */
  handlePush(data: { type: string; title: string; body: string; payload?: any }): void {
    switch (data.type) {
      case 'shift_change':
        this.scheduleLocal(data.title, data.body, 'shift_change');
        break;
      case 'new_task':
        this.scheduleLocal(data.title, data.body, 'new_task');
        break;
      case 'panic':
        // Panic alerts get immediate high-priority notification
        this.scheduleLocal('🚨 PANIC ALERT', data.body, 'panic');
        break;
      case 'geofence':
        this.scheduleLocal('⚠️ Geofence Alert', data.body, 'geofence');
        break;
      default:
        this.scheduleLocal(data.title, data.body, 'general');
    }
  }

  getUnreadCount(): number { return this.notifications.filter(n => !n.read).length; }
  getAll(): G51Notification[] { return this.notifications; }
  markRead(id: string): void {
    const n = this.notifications.find(x => x.id === id);
    if (n) n.read = true;
  }
  markAllRead(): void { this.notifications.forEach(n => n.read = true); }
}
