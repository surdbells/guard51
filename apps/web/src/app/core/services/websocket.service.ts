import { Injectable, inject, signal } from '@angular/core';
import { AuthStore } from './auth.store';
import { environment } from '@env/environment';

export interface WsMessage {
  type: string;
  [key: string]: any;
}

/**
 * WebSocket service for real-time guard tracking, panic alerts, and dispatch.
 * Auto-reconnects with exponential backoff.
 */
@Injectable({ providedIn: 'root' })
export class WebSocketService {
  private auth = inject(AuthStore);
  private ws: WebSocket | null = null;
  private reconnectAttempts = 0;
  private maxReconnect = 10;
  private reconnectTimer: any;
  private listeners = new Map<string, ((data: any) => void)[]>();

  readonly connected = signal(false);

  connect(): void {
    const token = this.auth.accessToken();
    if (!token) return;

    const wsUrl = environment.wsUrl || environment.apiUrl.replace('https://', 'wss://').replace('http://', 'ws://') + '/ws';
    this.ws = new WebSocket(`${wsUrl}?token=${token}`);

    this.ws.onopen = () => {
      this.connected.set(true);
      this.reconnectAttempts = 0;
      console.log('[WS] Connected');
    };

    this.ws.onmessage = (event) => {
      try {
        const msg: WsMessage = JSON.parse(event.data);
        const handlers = this.listeners.get(msg.type) || [];
        handlers.forEach(fn => fn(msg));
      } catch {}
    };

    this.ws.onclose = () => {
      this.connected.set(false);
      this.scheduleReconnect();
    };

    this.ws.onerror = () => { this.ws?.close(); };
  }

  disconnect(): void {
    clearTimeout(this.reconnectTimer);
    this.ws?.close();
    this.ws = null;
    this.connected.set(false);
  }

  send(msg: WsMessage): void {
    if (this.ws?.readyState === WebSocket.OPEN) {
      this.ws.send(JSON.stringify(msg));
    }
  }

  /** Send guard GPS position */
  sendLocation(lat: number, lng: number, accuracy?: number, battery?: number): void {
    this.send({ type: 'location_update', lat, lng, accuracy, battery });
  }

  /** Trigger panic alert */
  sendPanic(lat: number, lng: number, message?: string): void {
    this.send({ type: 'panic', lat, lng, message });
  }

  /** Subscribe to message type */
  on(type: string, handler: (data: any) => void): void {
    if (!this.listeners.has(type)) this.listeners.set(type, []);
    this.listeners.get(type)!.push(handler);
  }

  /** Remove handler */
  off(type: string, handler: (data: any) => void): void {
    const handlers = this.listeners.get(type);
    if (handlers) {
      this.listeners.set(type, handlers.filter(h => h !== handler));
    }
  }

  private scheduleReconnect(): void {
    if (this.reconnectAttempts >= this.maxReconnect) return;
    const delay = Math.min(1000 * Math.pow(2, this.reconnectAttempts), 30000);
    this.reconnectAttempts++;
    console.log(`[WS] Reconnecting in ${delay}ms (attempt ${this.reconnectAttempts})`);
    this.reconnectTimer = setTimeout(() => this.connect(), delay);
  }
}
