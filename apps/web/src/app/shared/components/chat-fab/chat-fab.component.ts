import { Component, inject, signal, OnInit, OnDestroy } from '@angular/core';
import { Router } from '@angular/router';
import { NgClass } from '@angular/common';
import { LucideAngularModule, MessageSquare, X } from 'lucide-angular';
import { ApiService } from '@core/services/api.service';
import { AuthStore } from '@core/services/auth.store';

@Component({
  selector: 'g51-chat-fab',
  standalone: true,
  imports: [NgClass, LucideAngularModule],
  template: `
    @if (auth.isAuthenticated() && !auth.isSuperAdmin()) {
      <div class="fixed bottom-6 right-6 z-50">
        <button (click)="openChat()" class="relative h-14 w-14 rounded-full flex items-center justify-center shadow-lg transition-all duration-200 hover:scale-105 active:scale-95"
          [style.background]="'var(--color-brand-500)'" [style.color]="'white'">
          <lucide-icon [img]="MessageIcon" [size]="24" />
          @if (unreadCount() > 0) {
            <span class="absolute -top-1 -right-1 h-5 min-w-5 px-1 rounded-full bg-red-500 text-white text-[10px] font-bold flex items-center justify-center animate-bounce">
              {{ unreadCount() > 99 ? '99+' : unreadCount() }}
            </span>
          }
        </button>
      </div>
    }
    <audio #notifSound preload="auto" src="data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdH+Jj4yGfHFkWVdcZW93f4SKi4eCenJpY2BhZ293gIiNj42IfXRsZmRlaHJ7g4mNj42Jg3x1b2tqbHJ6goeKi4qHgnx2cG1scHZ9g4iKioqHg3x3cm9ucnZ8gYaJioqJhoJ9eHRycnV5fYKGiImJiIWBfXl2dHV4fIGFh4iIh4WCfnt4dnd5fICAg4WGhoWDgH57eXh4en1/goSFhYWDgX99e3p5en1/gYOEhISDgX99e3p6e31/gIKDhISDgYB+fHt7e3x+gIGDg4OCgYB+fXx7fH1/gIGCg4OCgYB+fXx8fH1+gIGBgoKBgH9+fXx8fX5/gIGBgoKBgH9+fXx8fX5/gIGBgoKBgH9+fXx8fX5/gIGBgYGAgH9+fXx8fX5/gIGBgYGAgH9+fn1+fn9/gIGBgYGAgH9+fn1+fn9/gICBgYGAgH9/fn5+fn9/gICBgYCAgH9/fn5+fn9/gICAgYCAgH9/fn5+fn9/gICAgYCAgH9/fn5+fn9/gICAgICAgH9/fn5+fn9/gICAgICAgH9/f39/f39/gICAgICAgH9/f39/f39/gICAgICA"></audio>
  `,
})
export class ChatFabComponent implements OnInit, OnDestroy {
  readonly auth = inject(AuthStore);
  private api = inject(ApiService);
  private router = inject(Router);
  readonly MessageIcon = MessageSquare; readonly XIcon = X;
  readonly unreadCount = signal(0);
  private pollInterval: any;
  private lastNotifCount = 0;
  private notifSound: HTMLAudioElement | null = null;

  ngOnInit(): void {
    if (!this.auth.isAuthenticated() || this.auth.isSuperAdmin()) return;
    this.pollUnread();
    this.pollInterval = setInterval(() => this.pollUnread(), 30000);
  }

  ngOnDestroy(): void { if (this.pollInterval) clearInterval(this.pollInterval); }

  openChat(): void { this.router.navigate(['/chat']); }

  private pollUnread(): void {
    this.api.get<any>('/chat/unread-count').subscribe({
      next: res => {
        const count = res.data?.unread_count || res.data?.count || 0;
        if (count > this.lastNotifCount && this.lastNotifCount > 0) {
          this.playNotifSound();
        }
        this.lastNotifCount = count;
        this.unreadCount.set(count);
      },
    });
  }

  private playNotifSound(): void {
    try {
      if (!this.notifSound) {
        this.notifSound = new Audio();
        // Short notification ping
        this.notifSound.src = 'data:audio/mp3;base64,SUQzBAAAAAAAI1RTU0UAAAAPAAADTGF2ZjU4Ljc2LjEwMAAAAAAAAAAAAAAA//tQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAWGluZwAAAA8AAAACAAADhAC7u7u7u7u7u7u7u7u7u7u7u7u7u7u7u7u7u7u7u7u7u7u7u7u7u7u7u7u7u7u7u7u7u7u7u7v////////////////////////////////AAAAAAAAAAAAAAAAAAAAAAAAAAAAAA//tQxAAAAAANIAAAAACIAAANIAAAARMQU1FMy4xMDBVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVV//tQxBgAAADSAAAAAAAAANIAAAAATEFNRTMuMTAwVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVQ==';
      }
      this.notifSound.currentTime = 0;
      this.notifSound.volume = 0.3;
      this.notifSound.play().catch(() => {});
    } catch {}
  }
}
