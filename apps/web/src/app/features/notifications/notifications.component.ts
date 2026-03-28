import { Component, inject, signal, OnInit } from '@angular/core';
import { NgClass, DatePipe } from '@angular/common';
import { LucideAngularModule, Bell, CheckCheck, Shield, Clock, AlertTriangle, Radio, FileText, MessageSquare, Receipt, Settings } from 'lucide-angular';
import { PageHeaderComponent } from '@shared/components/page-header/page-header.component';
import { EmptyStateComponent } from '@shared/components/empty-state/empty-state.component';
import { ApiService } from '@core/services/api.service';
import { ToastService } from '@core/services/toast.service';

@Component({
  selector: 'g51-notifications',
  standalone: true,
  imports: [NgClass, DatePipe, LucideAngularModule, PageHeaderComponent, EmptyStateComponent],
  template: `
    <g51-page-header title="Notifications" [subtitle]="unreadCount() + ' unread'">
      @if (unreadCount() > 0) {
        <button (click)="markAllRead()" class="btn-secondary flex items-center gap-2">
          <lucide-icon [img]="CheckCheckIcon" [size]="16" /> Mark All Read
        </button>
      }
    </g51-page-header>

    <div class="flex gap-1 mb-6">
      @for (tab of ['All', 'Unread']; track tab) {
        <button (click)="activeTab.set(tab)" class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors"
          [ngClass]="activeTab() === tab ? 'bg-[var(--color-brand-500)] text-white' : 'bg-[var(--surface-muted)]'"
          [style.color]="activeTab() !== tab ? 'var(--text-secondary)' : ''">
          {{ tab }}
          @if (tab === 'Unread' && unreadCount() > 0) {
            <span class="ml-1 bg-white/20 text-[10px] px-1.5 py-0.5 rounded-full">{{ unreadCount() }}</span>
          }
        </button>
      }
    </div>

    <div class="space-y-1">
      @for (n of filteredNotifications(); track n.id) {
        <div class="card px-4 py-3 card-hover flex items-start gap-3 cursor-pointer" (click)="markRead(n)"
          [style.background]="n.is_read ? 'var(--surface-card)' : 'var(--surface-muted)'"
          [style.borderLeft]="n.is_read ? 'none' : '3px solid var(--color-brand-500)'">
          <!-- Type icon -->
          <div class="h-8 w-8 rounded-full flex items-center justify-center shrink-0"
            [ngClass]="n.type === 'panic' ? 'bg-red-100 dark:bg-red-950'
              : n.type === 'incident' ? 'bg-amber-100 dark:bg-amber-950'
              : n.type === 'message' ? 'bg-blue-100 dark:bg-blue-950'
              : 'bg-[var(--surface-muted)]'">
            <lucide-icon [img]="getTypeIcon(n.type)" [size]="14"
              [ngClass]="n.type === 'panic' ? 'text-red-500'
                : n.type === 'incident' ? 'text-amber-500'
                : n.type === 'message' ? 'text-blue-500'
                : ''" [style.color]="!['panic','incident','message'].includes(n.type) ? 'var(--text-tertiary)' : ''" />
          </div>
          <div class="flex-1 min-w-0">
            <div class="flex items-center justify-between mb-0.5">
              <p class="text-sm font-medium truncate" [style.color]="'var(--text-primary)'"
                [style.fontWeight]="n.is_read ? '400' : '600'">{{ n.title }}</p>
              <span class="text-[10px] shrink-0 ml-2" [style.color]="'var(--text-tertiary)'">{{ n.created_at | date:'shortTime' }}</span>
            </div>
            <p class="text-xs truncate" [style.color]="'var(--text-secondary)'">{{ n.body }}</p>
            <div class="flex items-center gap-2 mt-1">
              <span class="badge text-[9px] bg-[var(--surface-muted)]">{{ n.type_label }}</span>
              <span class="badge text-[9px] bg-[var(--surface-muted)]">{{ n.channel_label }}</span>
            </div>
          </div>
        </div>
      } @empty {
        <g51-empty-state title="No Notifications" message="You're all caught up!" [icon]="BellIcon" />
      }
    </div>
  `,
})
export class NotificationsComponent implements OnInit {
  private api = inject(ApiService); private toast = inject(ToastService);
  readonly BellIcon = Bell; readonly CheckCheckIcon = CheckCheck;
  readonly ShieldIcon = Shield; readonly ClockIcon = Clock; readonly AlertTriangleIcon = AlertTriangle;
  readonly RadioIcon = Radio; readonly FileTextIcon = FileText; readonly MessageSquareIcon = MessageSquare;
  readonly ReceiptIcon = Receipt; readonly SettingsIcon = Settings;

  readonly activeTab = signal('All');
  readonly notifications = signal<any[]>([]);
  readonly unreadCount = signal(0);

  filteredNotifications = () => {
    const tab = this.activeTab();
    const all = this.notifications();
    return tab === 'Unread' ? all.filter(n => !n.is_read) : all;
  };

  getTypeIcon(type: string) {
    const map: Record<string, any> = {
      shift_assigned: this.ClockIcon, shift_change: this.ClockIcon, clock_reminder: this.ClockIcon,
      incident: this.AlertTriangleIcon, panic: this.AlertTriangleIcon,
      dispatch: this.RadioIcon, report: this.FileTextIcon,
      message: this.MessageSquareIcon, invoice: this.ReceiptIcon, system: this.SettingsIcon,
    };
    return map[type] || this.BellIcon;
  }

  ngOnInit(): void {
    this.api.get<any>('/notifications').subscribe({
      next: res => {
        if (res.data) {
          this.notifications.set(res.data.notifications || []);
          this.unreadCount.set(res.data.unread_count || 0);
        }
      },
    });
  }

  markRead(n: any): void {
    if (n.is_read) return;
    this.api.post(`/notifications/${n.id}/read`, {}).subscribe({
      next: () => { n.is_read = true; this.unreadCount.update(c => Math.max(0, c - 1)); },
    });
  }

  markAllRead(): void {
    this.api.post('/notifications/read-all', {}).subscribe({
      next: () => { this.notifications().forEach(n => n.is_read = true); this.unreadCount.set(0); this.toast.success('All marked as read'); },
    });
  }
}
