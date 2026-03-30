import { Component, inject, signal, OnInit } from '@angular/core';
import { NgClass } from '@angular/common';
import { LucideAngularModule, Clock, MapPin, FileText, AlertTriangle, LogIn, LogOut } from 'lucide-angular';
import { PageHeaderComponent } from '@shared/components/page-header/page-header.component';
import { LoadingSpinnerComponent } from '@shared/components/loading-spinner/loading-spinner.component';
import { ApiService } from '@core/services/api.service';
import { ToastService } from '@core/services/toast.service';
import { AuthStore } from '@core/services/auth.store';

@Component({
  selector: 'g51-guard-portal',
  standalone: true,
  imports: [NgClass, LucideAngularModule, PageHeaderComponent, LoadingSpinnerComponent],
  template: `
    <g51-page-header title="My Dashboard" [subtitle]="'Welcome, ' + (auth.user()?.first_name || 'Guard')" />
    @if (loading()) { <g51-loading /> } @else {
      <div class="grid grid-cols-2 gap-4 mb-6">
        <div class="card p-5 text-center">
          <lucide-icon [img]="ClockIcon" [size]="24" class="mx-auto mb-2" [style.color]="'var(--color-brand-500)'" />
          @if (clockedIn()) {
            <p class="text-sm font-semibold text-emerald-600 mb-1">Clocked In</p>
            <p class="text-xs" [style.color]="'var(--text-tertiary)'">Since {{ clockInTime() }}</p>
            <button (click)="clockOut()" class="btn-danger mt-3 w-full flex items-center justify-center gap-2"><lucide-icon [img]="LogOutIcon" [size]="14" /> Clock Out</button>
          } @else {
            <p class="text-sm font-semibold mb-1" [style.color]="'var(--text-secondary)'">Not Clocked In</p>
            <button (click)="clockIn()" class="btn-primary mt-3 w-full flex items-center justify-center gap-2"><lucide-icon [img]="LogInIcon" [size]="14" /> Clock In</button>
          }
        </div>
        <div class="card p-5 text-center">
          <lucide-icon [img]="MapPinIcon" [size]="24" class="mx-auto mb-2" [style.color]="'var(--color-brand-500)'" />
          <p class="text-sm font-semibold" [style.color]="'var(--text-primary)'">{{ assignedSite() || 'No assignment' }}</p>
          <p class="text-xs" [style.color]="'var(--text-tertiary)'">Current post site</p>
        </div>
      </div>
      <div class="grid grid-cols-2 gap-4">
        <div class="card p-4 text-center">
          <lucide-icon [img]="FileTextIcon" [size]="20" class="mx-auto mb-1" [style.color]="'var(--text-tertiary)'" />
          <p class="text-lg font-bold" [style.color]="'var(--text-primary)'">{{ todayReports() }}</p>
          <p class="text-[10px]" [style.color]="'var(--text-tertiary)'">Reports Today</p>
        </div>
        <div class="card p-4 text-center">
          <lucide-icon [img]="AlertTriangleIcon" [size]="20" class="mx-auto mb-1" [style.color]="'var(--color-danger)'" />
          <p class="text-lg font-bold" [style.color]="'var(--text-primary)'">{{ todayIncidents() }}</p>
          <p class="text-[10px]" [style.color]="'var(--text-tertiary)'">Incidents Today</p>
        </div>
      </div>
    }
  `,
})
export class GuardPortalComponent implements OnInit {
  readonly auth = inject(AuthStore);
  private api = inject(ApiService); private toast = inject(ToastService);
  readonly ClockIcon = Clock; readonly MapPinIcon = MapPin; readonly FileTextIcon = FileText;
  readonly AlertTriangleIcon = AlertTriangle; readonly LogInIcon = LogIn; readonly LogOutIcon = LogOut;
  readonly loading = signal(true); readonly clockedIn = signal(false); readonly clockInTime = signal('');
  readonly assignedSite = signal(''); readonly todayReports = signal(0); readonly todayIncidents = signal(0);
  ngOnInit(): void {
    this.api.get<any>('/dashboard/today').subscribe({
      next: res => { if (res.data?.snapshot) { this.todayReports.set(res.data.snapshot.total_reports || 0); this.todayIncidents.set(res.data.snapshot.total_incidents || 0); } this.loading.set(false); },
      error: () => this.loading.set(false),
    });
  }
  clockIn(): void {
    navigator.geolocation?.getCurrentPosition(pos => {
      this.api.post('/time-clock/clock-in', { latitude: pos.coords.latitude, longitude: pos.coords.longitude }).subscribe({
        next: () => { this.clockedIn.set(true); this.clockInTime.set(new Date().toLocaleTimeString()); this.toast.success('Clocked in'); },
      });
    }, () => { this.api.post('/time-clock/clock-in', {}).subscribe({ next: () => { this.clockedIn.set(true); this.clockInTime.set(new Date().toLocaleTimeString()); this.toast.success('Clocked in'); } }); });
  }
  clockOut(): void {
    navigator.geolocation?.getCurrentPosition(pos => {
      this.api.post('/time-clock/clock-out', { latitude: pos.coords.latitude, longitude: pos.coords.longitude }).subscribe({
        next: () => { this.clockedIn.set(false); this.toast.success('Clocked out'); },
      });
    }, () => { this.api.post('/time-clock/clock-out', {}).subscribe({ next: () => { this.clockedIn.set(false); this.toast.success('Clocked out'); } }); });
  }
}
