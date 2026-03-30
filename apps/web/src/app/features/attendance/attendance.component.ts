import { Component, inject, signal, OnInit } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { NgClass, DatePipe } from '@angular/common';
import { LucideAngularModule, Clock, Users, CheckCircle, AlertTriangle, Coffee, Search } from 'lucide-angular';
import { PageHeaderComponent } from '@shared/components/page-header/page-header.component';
import { StatsCardComponent } from '@shared/components/stats-card/stats-card.component';
import { EmptyStateComponent } from '@shared/components/empty-state/empty-state.component';
import { LoadingSpinnerComponent } from '@shared/components/loading-spinner/loading-spinner.component';
import { ApiService } from '@core/services/api.service';

@Component({
  selector: 'g51-attendance',
  standalone: true,
  imports: [FormsModule, NgClass, DatePipe, LucideAngularModule, PageHeaderComponent, StatsCardComponent, EmptyStateComponent, LoadingSpinnerComponent],
  template: `
    <g51-page-header title="Attendance" subtitle="Guard clock-in/out records and attendance tracking" />
    <div class="flex items-center gap-3 mb-4">
      <input type="date" [(ngModel)]="selectedDate" (ngModelChange)="loadAttendance()" class="input-base text-xs" />
      <div class="relative flex-1 max-w-xs">
        <lucide-icon [img]="SearchIcon" [size]="14" class="absolute left-3 top-1/2 -translate-y-1/2" [style.color]="'var(--text-tertiary)'" />
        <input type="text" [(ngModel)]="search" (ngModelChange)="loadAttendance()" placeholder="Search guard..." class="input-base w-full pl-9" />
      </div>
    </div>
    @if (loading()) { <g51-loading /> } @else {
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6 stagger-children">
        <g51-stats-card label="Total Records" [value]="records().length" [icon]="ClockIcon" />
        <g51-stats-card label="Clocked In" [value]="clockedInCount()" [icon]="CheckCircleIcon" />
        <g51-stats-card label="Late" [value]="lateCount()" [icon]="AlertTriangleIcon" />
        <g51-stats-card label="On Break" [value]="onBreakCount()" [icon]="CoffeeIcon" />
      </div>
      @if (!records().length) { <g51-empty-state title="No Records" message="No attendance records for this date." [icon]="ClockIcon" /> }
      @else {
        <div class="space-y-2">
          @for (r of records(); track r.id) {
            <div class="card p-4">
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                  <div class="h-9 w-9 rounded-full flex items-center justify-center text-xs font-bold text-white" [style.background]="'var(--color-brand-500)'">{{ r.guard_initials || '??' }}</div>
                  <div>
                    <p class="text-sm font-semibold" [style.color]="'var(--text-primary)'">{{ r.guard_name || 'Guard' }}</p>
                    <p class="text-xs" [style.color]="'var(--text-tertiary)'">{{ r.site_name || 'N/A' }} · In: {{ r.clock_in_time || '-' }} · Out: {{ r.clock_out_time || '-' }}</p>
                  </div>
                </div>
                <div class="flex items-center gap-2">
                  <span class="badge text-[10px]" [ngClass]="r.status === 'clocked_in' ? 'bg-emerald-50 text-emerald-600' : r.status === 'late' ? 'bg-amber-50 text-amber-600' : 'bg-gray-100 text-gray-500'">{{ r.status }}</span>
                  @if (r.total_hours) { <span class="text-xs font-mono" [style.color]="'var(--text-secondary)'">{{ r.total_hours }}h</span> }
                </div>
              </div>
            </div>
          }
        </div>
      }
    }
  `,
})
export class AttendanceComponent implements OnInit {
  private api = inject(ApiService);
  readonly ClockIcon = Clock; readonly CheckCircleIcon = CheckCircle; readonly AlertTriangleIcon = AlertTriangle;
  readonly CoffeeIcon = Coffee; readonly SearchIcon = Search;
  readonly records = signal<any[]>([]); readonly loading = signal(true);
  readonly clockedInCount = signal(0); readonly lateCount = signal(0); readonly onBreakCount = signal(0);
  selectedDate = new Date().toISOString().slice(0, 10); search = '';
  ngOnInit(): void { this.loadAttendance(); }
  loadAttendance(): void {
    this.loading.set(true);
    const p = new URLSearchParams({ date: this.selectedDate });
    if (this.search) p.set('search', this.search);
    this.api.get<any>(`/attendance?${p}`).subscribe({
      next: res => {
        const data = res.data?.records || res.data?.items || res.data || [];
        this.records.set(data);
        this.clockedInCount.set(data.filter((r: any) => r.status === 'clocked_in').length);
        this.lateCount.set(data.filter((r: any) => r.status === 'late').length);
        this.onBreakCount.set(data.filter((r: any) => r.status === 'on_break').length);
        this.loading.set(false);
      },
      error: () => this.loading.set(false),
    });
  }
}
