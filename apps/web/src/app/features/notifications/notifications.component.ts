import { Component, inject, signal, OnInit } from '@angular/core';
import { NgClass } from '@angular/common';
import { LucideAngularModule, Bell, CheckCheck, Shield, Clock, AlertTriangle, FileText, MessageSquare } from 'lucide-angular';
import { PageHeaderComponent } from '@shared/components/page-header/page-header.component';
import { EmptyStateComponent } from '@shared/components/empty-state/empty-state.component';
import { LoadingSpinnerComponent } from '@shared/components/loading-spinner/loading-spinner.component';
import { ApiService } from '@core/services/api.service';
import { ToastService } from '@core/services/toast.service';

@Component({
  selector: 'g51-notifications',
  standalone: true,
  imports: [NgClass, LucideAngularModule, PageHeaderComponent, EmptyStateComponent, LoadingSpinnerComponent],
  template: `
    <g51-page-header title="Notifications" subtitle="Alerts, updates, and system notifications">
      @if (notifications().length) {
        <button (click)="markAllRead()" class="btn-secondary text-xs flex items-center gap-1"><lucide-icon [img]="CheckAllIcon" [size]="14" /> Mark All Read</button>
      }
    </g51-page-header>

    <div class="tab-pills">
      @for (tab of ['All', 'Unread']; track tab) {
        <button (click)="activeTab.set(tab); loadNotifications()" class="tab-pill" [ngClass]="activeTab() === tab ? 'active' : ''">{{ tab }} {{ tab === 'Unread' && unreadCount() > 0 ? '(' + unreadCount() + ')' : '' }}</button>
      }
    </div>

    @if (loading()) { <g51-loading /> }
    @else if (!notifications().length) { <g51-empty-state title="No Notifications" message="You're all caught up." [icon]="BellIcon" /> }
    @else {
      <div class="space-y-1">
        @for (n of notifications(); track n.id) {
          <div class="card p-3 flex items-start gap-3 cursor-pointer transition-colors"
            [ngClass]="!n.is_read ? 'border-l-2' : ''" [style.borderLeftColor]="!n.is_read ? 'var(--brand-500)' : ''"
            (click)="markRead(n)">
            <div class="h-8 w-8 rounded-lg flex items-center justify-center shrink-0"
              [ngClass]="n.type === 'panic' || n.type === 'incident' ? 'bg-red-50' : n.type === 'shift' ? 'bg-blue-50' : 'bg-gray-50'">
              <lucide-icon [img]="getIcon(n.type)" [size]="14" [style.color]="n.type === 'panic' || n.type === 'incident' ? 'var(--color-danger)' : 'var(--text-tertiary)'" />
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm" [ngClass]="!n.is_read ? 'font-semibold' : ''" [style.color]="'var(--text-primary)'">{{ n.title }}</p>
              <p class="text-xs" [style.color]="'var(--text-secondary)'">{{ n.body }}</p>
              <p class="text-[10px] mt-0.5" [style.color]="'var(--text-tertiary)'">{{ n.created_at }}</p>
            </div>
          </div>
        }
      </div>
    }
  `,
})
export class NotificationsComponent implements OnInit {
  private api = inject(ApiService); private toast = inject(ToastService);
  readonly BellIcon = Bell; readonly CheckAllIcon = CheckCheck; readonly ShieldIcon = Shield;
  readonly ClockIcon = Clock; readonly AlertTriangleIcon = AlertTriangle; readonly FileTextIcon = FileText;
  readonly activeTab = signal('All'); readonly loading = signal(true);
  readonly notifications = signal<any[]>([]); readonly unreadCount = signal(0);

  ngOnInit(): void { this.loadNotifications(); }
  loadNotifications(): void {
    this.loading.set(true);
    this.api.get<any>('/notifications').subscribe({
      next: r => {
        let notifs = r.data?.notifications || r.data || [];
        if (this.activeTab() === 'Unread') notifs = notifs.filter((n: any) => !n.is_read);
        this.notifications.set(notifs);
        this.unreadCount.set(notifs.filter((n: any) => !n.is_read).length);
        this.loading.set(false);
      },
      error: () => this.loading.set(false),
    });
  }
  markRead(n: any): void {
    if (n.is_read) return;
    this.api.post(`/notifications/${n.id}/read`, {}).subscribe({ next: () => { n.is_read = true; this.unreadCount.update(c => Math.max(0, c - 1)); } });
  }
  markAllRead(): void { this.api.post('/notifications/read-all', {}).subscribe({ next: () => { this.toast.success('All marked read'); this.loadNotifications(); } }); }
  getIcon(type: string): any {
    if (type === 'panic' || type === 'incident') return this.AlertTriangleIcon;
    if (type === 'shift') return this.ClockIcon;
    return this.BellIcon;
  }
}
