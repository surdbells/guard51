import { Component, inject, signal, OnInit } from '@angular/core';
import { NgClass } from '@angular/common';
import { LucideAngularModule, Clock, Users, AlertTriangle, CheckCircle, Coffee, Settings } from 'lucide-angular';
import { PageHeaderComponent } from '@shared/components/page-header/page-header.component';
import { StatsCardComponent } from '@shared/components/stats-card/stats-card.component';
import { GaugeChartComponent } from '@shared/components/charts/gauge-chart.component';
import { HeatmapChartComponent, HeatmapData } from '@shared/components/charts/heatmap-chart.component';
import { ApiService } from '@core/services/api.service';

@Component({
  selector: 'g51-attendance',
  standalone: true,
  imports: [NgClass, LucideAngularModule, PageHeaderComponent, StatsCardComponent, GaugeChartComponent, HeatmapChartComponent],
  template: `
    <g51-page-header title="Attendance & Time Clock" subtitle="Monitor clock-ins, attendance records, and breaks">
      <button class="btn-secondary flex items-center gap-2" (click)="activeTab.set('breaks')">
        <lucide-icon [img]="CoffeeIcon" [size]="16" /> Break Config
      </button>
    </g51-page-header>

    <!-- Tabs -->
    <div class="flex gap-1 mb-6">
      @for (tab of tabs; track tab.key) {
        <button (click)="activeTab.set(tab.key)" class="px-4 py-2 rounded-lg text-sm font-medium transition-colors"
          [ngClass]="activeTab() === tab.key ? 'bg-[var(--color-brand-500)] text-white' : 'bg-[var(--surface-muted)]'"
          [style.color]="activeTab() !== tab.key ? 'var(--text-secondary)' : ''">{{ tab.label }}</button>
      }
    </div>

    @if (activeTab() === 'dashboard') {
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6 stagger-children">
        <g51-stats-card label="Clocked In Now" [value]="liveStats().clockedIn" [icon]="ClockIcon" />
        <g51-stats-card label="On Time Today" [value]="liveStats().onTime" [icon]="CheckCircleIcon" />
        <g51-stats-card label="Late Today" [value]="liveStats().late" [icon]="AlertTriangleIcon" />
        <g51-stats-card label="On Break" [value]="liveStats().onBreak" [icon]="CoffeeIcon" />
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
        <div class="card p-5 flex flex-col items-center">
          <h3 class="text-sm font-semibold mb-3" [style.color]="'var(--text-primary)'">Today's Attendance Rate</h3>
          <g51-gauge-chart [value]="liveStats().rate" [max]="100" [size]="180" label="Attendance" />
        </div>

        <div class="lg:col-span-2 card p-5">
          <h3 class="text-sm font-semibold mb-3" [style.color]="'var(--text-primary)'">Who's On Shift</h3>
          <div class="space-y-2 max-h-[280px] overflow-y-auto">
            @for (guard of activeGuards; track guard.id) {
              <div class="flex items-center justify-between py-2 border-b last:border-b-0" [style.borderColor]="'var(--border-default)'">
                <div class="flex items-center gap-3">
                  <div class="h-8 w-8 rounded-full flex items-center justify-center text-xs font-semibold"
                    style="background: var(--color-brand-500); color: var(--text-on-brand)">{{ guard.initials }}</div>
                  <div>
                    <p class="text-sm font-medium" [style.color]="'var(--text-primary)'">{{ guard.name }}</p>
                    <p class="text-xs" [style.color]="'var(--text-tertiary)'">{{ guard.site }} • Since {{ guard.clockIn }}</p>
                  </div>
                </div>
                <span class="badge text-[10px]"
                  [ngClass]="guard.status === 'on_time' ? 'bg-emerald-50 text-emerald-600 dark:bg-emerald-950 dark:text-emerald-400'
                    : guard.status === 'late' ? 'bg-amber-50 text-amber-600' : 'bg-blue-50 text-blue-600'">
                  {{ guard.statusLabel }}
                </span>
              </div>
            }
          </div>
        </div>
      </div>

      <div class="card p-5">
        <h3 class="text-sm font-semibold mb-3" [style.color]="'var(--text-primary)'">Attendance Heatmap (by Guard × Day)</h3>
        <g51-heatmap-chart [data]="heatmapData" [rows]="heatmapGuards" [cols]="heatmapDays" [cellSize]="34" />
      </div>
    }

    @if (activeTab() === 'records') {
      <div class="card p-5">
        <h3 class="text-sm font-semibold mb-3" [style.color]="'var(--text-primary)'">Attendance Records</h3>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b" [style.borderColor]="'var(--border-default)'">
                <th class="px-3 py-2 text-left font-medium" [style.color]="'var(--text-secondary)'">Guard</th>
                <th class="px-3 py-2 text-left font-medium" [style.color]="'var(--text-secondary)'">Date</th>
                <th class="px-3 py-2 text-left font-medium" [style.color]="'var(--text-secondary)'">Status</th>
                <th class="px-3 py-2 text-right font-medium" [style.color]="'var(--text-secondary)'">Hours</th>
                <th class="px-3 py-2 text-right font-medium" [style.color]="'var(--text-secondary)'">Late (min)</th>
              </tr>
            </thead>
            <tbody>
              @for (r of attendanceRecords; track r.id) {
                <tr class="border-b last:border-b-0" [style.borderColor]="'var(--border-default)'">
                  <td class="px-3 py-2" [style.color]="'var(--text-primary)'">{{ r.guard }}</td>
                  <td class="px-3 py-2" [style.color]="'var(--text-secondary)'">{{ r.date }}</td>
                  <td class="px-3 py-2"><span class="badge text-[10px]"
                    [ngClass]="r.status === 'present' ? 'bg-emerald-50 text-emerald-600 dark:bg-emerald-950 dark:text-emerald-400'
                      : r.status === 'late' ? 'bg-amber-50 text-amber-600' : 'bg-red-50 text-red-600'">{{ r.status }}</span></td>
                  <td class="px-3 py-2 text-right tabular-nums" [style.color]="'var(--text-primary)'">{{ r.hours }}h</td>
                  <td class="px-3 py-2 text-right tabular-nums" [style.color]="r.late > 0 ? 'var(--color-danger)' : 'var(--text-tertiary)'">{{ r.late }}</td>
                </tr>
              }
            </tbody>
          </table>
        </div>
      </div>
    }

    @if (activeTab() === 'reconcile') {
      <div class="card p-5">
        <h3 class="text-sm font-semibold mb-3" [style.color]="'var(--text-primary)'">Unreconciled Records</h3>
        <p class="text-sm" [style.color]="'var(--text-tertiary)'">Records that need admin review and approval.</p>
        <div class="mt-4 space-y-2">
          @for (r of unreconciledRecords; track r.id) {
            <div class="flex items-center justify-between py-3 border-b" [style.borderColor]="'var(--border-default)'">
              <div>
                <p class="text-sm font-medium" [style.color]="'var(--text-primary)'">{{ r.guard }} — {{ r.date }}</p>
                <p class="text-xs" [style.color]="'var(--text-tertiary)'">{{ r.site }} • {{ r.status }} • {{ r.hours }}h worked</p>
              </div>
              <div class="flex gap-2">
                <button class="btn-primary text-xs py-1 px-3">Approve</button>
                <button class="btn-secondary text-xs py-1 px-3">Adjust</button>
              </div>
            </div>
          } @empty {
            <p class="text-sm py-8 text-center" [style.color]="'var(--text-tertiary)'">All records are reconciled.</p>
          }
        </div>
      </div>
    }

    @if (activeTab() === 'breaks') {
      <div class="card p-5">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-sm font-semibold" [style.color]="'var(--text-primary)'">Break Configurations</h3>
          <button class="btn-primary text-xs py-1.5 px-3 flex items-center gap-1"><lucide-icon [img]="SettingsIcon" [size]="12" /> Add Break Type</button>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
          @for (bc of breakConfigs; track bc.name) {
            <div class="card p-4">
              <h4 class="text-sm font-semibold" [style.color]="'var(--text-primary)'">{{ bc.name }}</h4>
              <div class="flex items-center gap-3 mt-2 text-xs" [style.color]="'var(--text-secondary)'">
                <span>{{ bc.duration }} min</span>
                <span class="badge" [class]="bc.type === 'paid' ? 'bg-emerald-50 text-emerald-600 dark:bg-emerald-950 dark:text-emerald-400' : 'bg-[var(--surface-muted)]'">{{ bc.type }}</span>
              </div>
            </div>
          }
        </div>
      </div>
    }
  `,
})
export class AttendanceComponent implements OnInit {
  private api = inject(ApiService);
  readonly ClockIcon = Clock; readonly UsersIcon = Users; readonly AlertTriangleIcon = AlertTriangle;
  readonly CheckCircleIcon = CheckCircle; readonly CoffeeIcon = Coffee; readonly SettingsIcon = Settings;

  readonly activeTab = signal('dashboard');
  readonly liveStats = signal({ clockedIn: 18, onTime: 16, late: 2, onBreak: 1, rate: 92 });

  tabs = [
    { key: 'dashboard', label: 'Live Dashboard' },
    { key: 'records', label: 'Records' },
    { key: 'reconcile', label: 'Reconcile' },
    { key: 'breaks', label: 'Break Config' },
  ];

  activeGuards = [
    { id: '1', name: 'Musa Ibrahim', initials: 'MI', site: 'Lekki Phase 1', clockIn: '05:55 AM', status: 'on_time', statusLabel: 'On Time' },
    { id: '2', name: 'Chika Nwosu', initials: 'CN', site: 'Victoria Island HQ', clockIn: '06:02 AM', status: 'on_time', statusLabel: 'On Time' },
    { id: '3', name: 'Adebayo Okonkwo', initials: 'AO', site: 'Ikeja Mall', clockIn: '06:25 AM', status: 'late', statusLabel: 'Late (25 min)' },
    { id: '4', name: 'Funmi Adeyemi', initials: 'FA', site: 'Lekki Phase 1', clockIn: '05:50 AM', status: 'break', statusLabel: 'On Break' },
  ];

  attendanceRecords = [
    { id: '1', guard: 'Musa Ibrahim', date: '2026-03-27', status: 'present', hours: 11.5, late: 0 },
    { id: '2', guard: 'Chika Nwosu', date: '2026-03-27', status: 'present', hours: 12.0, late: 0 },
    { id: '3', guard: 'Adebayo Okonkwo', date: '2026-03-27', status: 'late', hours: 11.0, late: 25 },
    { id: '4', guard: 'Kelechi Eze', date: '2026-03-27', status: 'absent', hours: 0, late: 0 },
  ];

  unreconciledRecords = [
    { id: '1', guard: 'Kelechi Eze', date: '2026-03-26', site: 'Ikeja Mall', status: 'absent', hours: 0 },
    { id: '2', guard: 'Adebayo Okonkwo', date: '2026-03-25', site: 'V.I. HQ', status: 'late', hours: 10.5 },
  ];

  breakConfigs = [
    { name: 'Lunch Break', duration: 60, type: 'paid' },
    { name: 'Short Break', duration: 15, type: 'paid' },
    { name: 'Prayer Break', duration: 20, type: 'unpaid' },
  ];

  heatmapGuards = ['Musa I.', 'Chika N.', 'Adebayo O.', 'Funmi A.', 'Kelechi E.'];
  heatmapDays = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
  heatmapData: HeatmapData[] = this.generateHeatmap();

  private generateHeatmap(): HeatmapData[] {
    const data: HeatmapData[] = [];
    this.heatmapGuards.forEach(g => this.heatmapDays.forEach(d => {
      const present = Math.random() > 0.15;
      data.push({ row: g, col: d, value: present ? 80 + Math.floor(Math.random() * 20) : Math.floor(Math.random() * 30) });
    }));
    return data;
  }

  ngOnInit(): void {}
}
