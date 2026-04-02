import { Component, inject, signal, OnInit } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { NgClass } from '@angular/common';
import { LucideAngularModule, Clock, CheckCircle, AlertTriangle, Coffee, Search, Calendar, Download } from 'lucide-angular';
import { PageHeaderComponent } from '@shared/components/page-header/page-header.component';
import { StatsCardComponent } from '@shared/components/stats-card/stats-card.component';
import { EmptyStateComponent } from '@shared/components/empty-state/empty-state.component';
import { LoadingSpinnerComponent } from '@shared/components/loading-spinner/loading-spinner.component';
import { ApiService } from '@core/services/api.service';
import { exportToCsv } from '@core/utils/csv-export';

@Component({
  selector: 'g51-attendance',
  standalone: true,
  imports: [FormsModule, NgClass, LucideAngularModule, PageHeaderComponent, StatsCardComponent, EmptyStateComponent, LoadingSpinnerComponent],
  template: `
    <g51-page-header title="Attendance" subtitle="Guard clock-in/out records and reconciliation">
      <button (click)="exportData()" class="btn-secondary text-xs flex items-center gap-1"><lucide-icon [img]="DownloadIcon" [size]="14" /> Export</button>
    </g51-page-header>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-4 stagger-children">
      <g51-stats-card label="Clocked In" [value]="stats().clocked_in" [icon]="ClockIcon" />
      <g51-stats-card label="On Time" [value]="stats().on_time" [icon]="CheckCircleIcon" />
      <g51-stats-card label="Late" [value]="stats().late" [icon]="AlertTriangleIcon" />
      <g51-stats-card label="On Break" [value]="stats().on_break" [icon]="CoffeeIcon" />
    </div>

    <div class="flex items-center gap-3 mb-4 flex-wrap">
      <div class="relative flex-1 max-w-sm">
        <lucide-icon [img]="SearchIcon" [size]="14" class="absolute left-3 top-1/2 -translate-y-1/2" [style.color]="'var(--text-tertiary)'" />
        <input type="text" [(ngModel)]="search" (ngModelChange)="loadAttendance()" placeholder="Search guard..." class="input-base w-full pl-9" />
      </div>
      <input type="date" [(ngModel)]="selectedDate" (ngModelChange)="loadAttendance()" class="input-base text-xs" />
      <select [(ngModel)]="statusFilter" (ngModelChange)="loadAttendance()" class="input-base text-xs py-2">
        <option value="">All Status</option><option value="present">Present</option><option value="late">Late</option><option value="absent">Absent</option>
      </select>
    </div>

    @if (loading()) { <g51-loading /> }
    @else if (!records().length) { <g51-empty-state title="No Records" message="No attendance data for this date." [icon]="ClockIcon" /> }
    @else {
      <div class="card overflow-hidden">
        <table class="w-full text-xs">
          <thead><tr [style.background]="'var(--surface-muted)'">
            <th class="text-left py-2.5 px-4 font-semibold" [style.color]="'var(--text-secondary)'">Guard</th>
            <th class="text-left py-2.5 px-4 font-semibold" [style.color]="'var(--text-secondary)'">Site</th>
            <th class="text-left py-2.5 px-4 font-semibold" [style.color]="'var(--text-secondary)'">Clock In</th>
            <th class="text-left py-2.5 px-4 font-semibold" [style.color]="'var(--text-secondary)'">Clock Out</th>
            <th class="text-left py-2.5 px-4 font-semibold" [style.color]="'var(--text-secondary)'">Hours</th>
            <th class="text-left py-2.5 px-4 font-semibold" [style.color]="'var(--text-secondary)'">Status</th>
          </tr></thead>
          <tbody>
            @for (r of records(); track r.id || $index) {
              <tr class="border-t hover:bg-[var(--surface-hover)]" [style.borderColor]="'var(--border-default)'">
                <td class="py-2.5 px-4 font-medium" [style.color]="'var(--text-primary)'">{{ r.guard_name || 'Guard' }}</td>
                <td class="py-2.5 px-4" [style.color]="'var(--text-secondary)'">{{ r.site_name || '—' }}</td>
                <td class="py-2.5 px-4" [style.color]="'var(--text-primary)'">{{ r.clock_in_time || '—' }}</td>
                <td class="py-2.5 px-4" [style.color]="'var(--text-primary)'">{{ r.clock_out_time || '—' }}</td>
                <td class="py-2.5 px-4 font-medium" [style.color]="'var(--text-primary)'">{{ r.total_hours || '—' }}</td>
                <td class="py-2.5 px-4">
                  <span class="badge text-[10px]" [ngClass]="r.status === 'present' || r.status === 'clocked_in' ? 'bg-emerald-50 text-emerald-600' : r.status === 'late' ? 'bg-amber-50 text-amber-600' : 'bg-red-50 text-red-600'">{{ r.status }}</span>
                </td>
              </tr>
            }
          </tbody>
        </table>
      </div>
    }
  `,
})
export class AttendanceComponent implements OnInit {
  private api = inject(ApiService);
  readonly ClockIcon = Clock; readonly CheckCircleIcon = CheckCircle; readonly AlertTriangleIcon = AlertTriangle;
  readonly CoffeeIcon = Coffee; readonly SearchIcon = Search; readonly DownloadIcon = Download;
  readonly records = signal<any[]>([]); readonly loading = signal(true);
  readonly stats = signal<any>({ clocked_in: 0, on_time: 0, late: 0, on_break: 0 });
  selectedDate = new Date().toISOString().slice(0, 10); search = ''; statusFilter = '';

  ngOnInit(): void { this.loadAttendance(); }

  loadAttendance(): void {
    this.loading.set(true);
    this.api.get<any>(`/attendance?date=${this.selectedDate}&search=${this.search}&status=${this.statusFilter}`).subscribe({
      next: res => {
        const data = res.data?.records || res.data || [];
        this.records.set(data);
        this.stats.set({
          clocked_in: data.filter((r: any) => r.status === 'clocked_in' || r.status === 'present').length,
          on_time: data.filter((r: any) => r.status === 'present').length,
          late: data.filter((r: any) => r.status === 'late').length,
          on_break: data.filter((r: any) => r.status === 'on_break').length,
        });
        this.loading.set(false);
      },
      error: () => this.loading.set(false),
    });
  }

  exportData(): void {
    exportToCsv('attendance-' + this.selectedDate, this.records(), [
      { key: 'guard_name', label: 'Guard' }, { key: 'site_name', label: 'Site' },
      { key: 'clock_in_time', label: 'Clock In' }, { key: 'clock_out_time', label: 'Clock Out' },
      { key: 'total_hours', label: 'Hours' }, { key: 'status', label: 'Status' },
    ]);
  }
}
