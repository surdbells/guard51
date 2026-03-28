import { Component, inject, signal, OnInit } from '@angular/core';
import { ActivatedRoute, RouterLink } from '@angular/router';
import { NgClass, DatePipe } from '@angular/common';
import { LucideAngularModule, ArrowLeft, FileText, CheckCircle, Eye, Download, Paperclip, Cloud } from 'lucide-angular';
import { PageHeaderComponent } from '@shared/components/page-header/page-header.component';
import { ApiService } from '@core/services/api.service';
import { ToastService } from '@core/services/toast.service';

@Component({
  selector: 'g51-dar-detail',
  standalone: true,
  imports: [RouterLink, NgClass, DatePipe, LucideAngularModule, PageHeaderComponent],
  template: `
    <g51-page-header [title]="'Report: ' + (dar()?.report_date || '')" subtitle="Daily Activity Report detail">
      <a routerLink="/reports" class="btn-secondary flex items-center gap-1.5"><lucide-icon [img]="ArrowLeftIcon" [size]="16" /> Back</a>
      <button (click)="exportPdf()" class="btn-secondary flex items-center gap-1.5">
        <lucide-icon [img]="DownloadIcon" [size]="16" /> Export PDF
      </button>
    </g51-page-header>

    @if (dar(); as report) {
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="lg:col-span-2 space-y-4">
          <!-- Status + meta -->
          <div class="card p-5">
            <div class="flex items-center gap-2 mb-4">
              <span class="badge text-xs"
                [ngClass]="report.status === 'approved' ? 'bg-emerald-50 text-emerald-600 dark:bg-emerald-950 dark:text-emerald-400'
                  : report.status === 'submitted' ? 'bg-blue-50 text-blue-600'
                  : report.status === 'reviewed' ? 'bg-purple-50 text-purple-600'
                  : 'bg-[var(--surface-muted)]'">{{ report.status_label }}</span>
              @if (report.weather) {
                <span class="text-xs" [style.color]="'var(--text-tertiary)'">☁️ {{ report.weather }}</span>
              }
            </div>
            <div class="prose prose-sm max-w-none" [style.color]="'var(--text-primary)'">
              @for (para of report.content?.split('\\n'); track $index) {
                <p class="text-sm mb-2" [style.color]="'var(--text-secondary)'">{{ para }}</p>
              }
            </div>
          </div>

          <!-- Attachments -->
          @if (report.attachment_count > 0) {
            <div class="card p-5">
              <h3 class="text-sm font-semibold mb-3 flex items-center gap-2" [style.color]="'var(--text-primary)'">
                <lucide-icon [img]="PaperclipIcon" [size]="14" /> Attachments ({{ report.attachment_count }})
              </h3>
              <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                @for (att of report.attachments; track $index) {
                  <div class="card p-3 card-hover">
                    <div class="aspect-video bg-[var(--surface-muted)] rounded mb-2 flex items-center justify-center">
                      <lucide-icon [img]="FileTextIcon" [size]="20" [style.color]="'var(--text-tertiary)'" />
                    </div>
                    <p class="text-xs font-medium truncate" [style.color]="'var(--text-primary)'">{{ att.name || att.url }}</p>
                  </div>
                }
              </div>
            </div>
          }

          <!-- Review actions -->
          @if (report.status === 'submitted') {
            <div class="card p-4 flex gap-2">
              <button (click)="review(false)" class="btn-secondary flex items-center gap-1.5">
                <lucide-icon [img]="EyeIcon" [size]="14" /> Mark Reviewed
              </button>
              <button (click)="review(true)" class="btn-primary flex items-center gap-1.5">
                <lucide-icon [img]="CheckCircleIcon" [size]="14" /> Approve
              </button>
            </div>
          }
        </div>

        <!-- Sidebar info -->
        <div class="space-y-4">
          <div class="card p-4">
            <h4 class="text-xs font-medium mb-3" [style.color]="'var(--text-tertiary)'">Report Info</h4>
            <div class="space-y-2 text-sm">
              <div><span class="text-xs" [style.color]="'var(--text-tertiary)'">Date</span>
                <p [style.color]="'var(--text-primary)'">{{ report.report_date }}</p></div>
              <div><span class="text-xs" [style.color]="'var(--text-tertiary)'">Guard</span>
                <p [style.color]="'var(--text-primary)'">{{ report.guard_id?.substring(0,8) }}...</p></div>
              <div><span class="text-xs" [style.color]="'var(--text-tertiary)'">Site</span>
                <p [style.color]="'var(--text-primary)'">{{ report.site_id?.substring(0,8) }}...</p></div>
              @if (report.submitted_at) {
                <div><span class="text-xs" [style.color]="'var(--text-tertiary)'">Submitted</span>
                  <p [style.color]="'var(--text-primary)'">{{ report.submitted_at | date:'medium' }}</p></div>
              }
              @if (report.reviewed_by) {
                <div><span class="text-xs" [style.color]="'var(--text-tertiary)'">Reviewed by</span>
                  <p [style.color]="'var(--text-primary)'">{{ report.reviewed_by?.substring(0,8) }}...</p></div>
              }
            </div>
          </div>

          <!-- Client sharing -->
          <div class="card p-4">
            <h4 class="text-xs font-medium mb-3 flex items-center gap-1.5" [style.color]="'var(--text-tertiary)'">
              <lucide-icon [img]="CloudIcon" [size]="12" /> Client Sharing
            </h4>
            <p class="text-xs mb-2" [style.color]="'var(--text-secondary)'">
              {{ report.status === 'approved' ? 'This report is visible to the client via their portal.' : 'Reports are shared with clients once approved.' }}
            </p>
            @if (report.status === 'approved') {
              <span class="badge text-[10px] bg-emerald-50 text-emerald-600 dark:bg-emerald-950 dark:text-emerald-400">Shared</span>
            } @else {
              <span class="badge text-[10px] bg-[var(--surface-muted)]">Not shared yet</span>
            }
          </div>
        </div>
      </div>
    }
  `,
})
export class DarDetailComponent implements OnInit {
  private api = inject(ApiService);
  private route = inject(ActivatedRoute);
  private toast = inject(ToastService);
  readonly ArrowLeftIcon = ArrowLeft; readonly FileTextIcon = FileText; readonly CheckCircleIcon = CheckCircle;
  readonly EyeIcon = Eye; readonly DownloadIcon = Download; readonly PaperclipIcon = Paperclip; readonly CloudIcon = Cloud;

  readonly dar = signal<any>(null);

  ngOnInit(): void {
    const id = this.route.snapshot.params['id'];
    if (!id) return;
    this.api.get<any>('/reports/dar').subscribe({
      next: res => {
        const all = res.data?.reports || [];
        const found = all.find((r: any) => r.id === id);
        if (found) this.dar.set(found);
      },
    });
  }

  review(approve: boolean): void {
    const id = this.dar()?.id;
    if (!id) return;
    this.api.post(`/reports/dar/${id}/review`, { approve }).subscribe({
      next: () => { this.toast.success(approve ? 'Report approved' : 'Report reviewed'); this.ngOnInit(); },
    });
  }

  exportPdf(): void {
    const id = this.dar()?.id;
    if (!id) return;
    this.api.get<any>(`/reports/dar/${id}/export`).subscribe({
      next: res => { this.toast.success('PDF export ready', 'HTML content generated for PDF rendering.'); },
    });
  }
}
