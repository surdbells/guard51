import { Component, inject, signal, OnInit } from '@angular/core';
import { ActivatedRoute, RouterLink } from '@angular/router';
import { NgClass } from '@angular/common';
import { LucideAngularModule, ArrowLeft, Edit, Shield, FileText, Award, AlertTriangle, CheckCircle, Upload } from 'lucide-angular';
import { PageHeaderComponent } from '@shared/components/page-header/page-header.component';
import { LoadingSpinnerComponent } from '@shared/components/loading-spinner/loading-spinner.component';
import { EmptyStateComponent } from '@shared/components/empty-state/empty-state.component';
import { ApiService } from '@core/services/api.service';

@Component({
  selector: 'g51-guard-detail',
  standalone: true,
  imports: [RouterLink, NgClass, LucideAngularModule, PageHeaderComponent, LoadingSpinnerComponent, EmptyStateComponent],
  template: `
    @if (loading()) { <g51-loading [fullPage]="true" /> } @else if (guard()) {
      <g51-page-header [title]="guard()!.full_name" [subtitle]="'Employee #' + guard()!.employee_number">
        <a routerLink="/guards" class="btn-secondary flex items-center gap-1.5"><lucide-icon [img]="ArrowLeftIcon" [size]="16" /> Back</a>
        <a [routerLink]="'/guards/edit/' + guard()!.id" class="btn-primary flex items-center gap-1.5"><lucide-icon [img]="EditIcon" [size]="16" /> Edit</a>
      </g51-page-header>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
        <!-- Profile card -->
        <div class="card p-5">
          <div class="flex flex-col items-center text-center mb-4">
            <div class="h-20 w-20 rounded-full flex items-center justify-center text-2xl font-bold mb-3" style="background: var(--color-brand-500); color: var(--text-on-brand)">
              {{ guard()!.first_name.charAt(0) }}{{ guard()!.last_name.charAt(0) }}
            </div>
            <h3 class="text-base font-semibold" [style.color]="'var(--text-primary)'">{{ guard()!.full_name }}</h3>
            <span class="badge mt-1" [ngClass]="guard()!.status === 'active' ? 'bg-emerald-50 text-emerald-600 dark:bg-emerald-950 dark:text-emerald-400' : 'bg-amber-50 text-amber-600'">{{ guard()!.status_label }}</span>
          </div>
          <div class="space-y-2 text-sm">
            <div class="flex justify-between"><span [style.color]="'var(--text-tertiary)'">Phone</span><span [style.color]="'var(--text-primary)'">{{ guard()!.phone }}</span></div>
            <div class="flex justify-between"><span [style.color]="'var(--text-tertiary)'">Email</span><span [style.color]="'var(--text-primary)'">{{ guard()!.email || '—' }}</span></div>
            <div class="flex justify-between"><span [style.color]="'var(--text-tertiary)'">Hire Date</span><span [style.color]="'var(--text-primary)'">{{ guard()!.hire_date }}</span></div>
            <div class="flex justify-between"><span [style.color]="'var(--text-tertiary)'">Pay</span><span [style.color]="'var(--text-primary)'">{{ guard()!.pay_type || '—' }} {{ guard()!.pay_rate ? '₦' + guard()!.pay_rate.toLocaleString() : '' }}</span></div>
          </div>
        </div>

        <!-- Info -->
        <div class="lg:col-span-2 space-y-4">
          <!-- Personal details -->
          <div class="card p-5">
            <h3 class="text-sm font-semibold mb-3" [style.color]="'var(--text-primary)'">Personal Details</h3>
            <div class="grid grid-cols-2 gap-3 text-sm">
              <div><span class="block text-xs" [style.color]="'var(--text-tertiary)'">Gender</span><span [style.color]="'var(--text-primary)'">{{ guard()!.gender || '—' }}</span></div>
              <div><span class="block text-xs" [style.color]="'var(--text-tertiary)'">Date of Birth</span><span [style.color]="'var(--text-primary)'">{{ guard()!.date_of_birth || '—' }}</span></div>
              <div><span class="block text-xs" [style.color]="'var(--text-tertiary)'">Address</span><span [style.color]="'var(--text-primary)'">{{ guard()!.address || '—' }}</span></div>
              <div><span class="block text-xs" [style.color]="'var(--text-tertiary)'">City / State</span><span [style.color]="'var(--text-primary)'">{{ guard()!.city || '—' }}, {{ guard()!.state || '—' }}</span></div>
              <div><span class="block text-xs" [style.color]="'var(--text-tertiary)'">Emergency Contact</span><span [style.color]="'var(--text-primary)'">{{ guard()!.emergency_contact_name || '—' }}</span></div>
              <div><span class="block text-xs" [style.color]="'var(--text-tertiary)'">Emergency Phone</span><span [style.color]="'var(--text-primary)'">{{ guard()!.emergency_contact_phone || '—' }}</span></div>
            </div>
          </div>

          <!-- Skills -->
          <div class="card p-5">
            <div class="flex items-center justify-between mb-3">
              <h3 class="text-sm font-semibold" [style.color]="'var(--text-primary)'">Skills & Certifications</h3>
              <button class="btn-secondary text-xs py-1 px-2"><lucide-icon [img]="AwardIcon" [size]="12" /> Assign Skill</button>
            </div>
            @if (skills().length > 0) {
              <div class="flex flex-wrap gap-2">
                @for (s of skills(); track s.id) {
                  <span class="badge bg-[var(--surface-muted)]" [style.color]="'var(--text-primary)'">{{ s.name }}
                    @if (s.is_expired) { <span class="text-red-500 ml-1">expired</span> }
                  </span>
                }
              </div>
            } @else {
              <p class="text-sm" [style.color]="'var(--text-tertiary)'">No skills assigned.</p>
            }
          </div>

          <!-- Documents -->
          <div class="card p-5">
            <div class="flex items-center justify-between mb-3">
              <h3 class="text-sm font-semibold" [style.color]="'var(--text-primary)'">
                Documents
                @if (docAlerts() > 0) { <span class="ml-1 badge bg-red-50 text-red-600 dark:bg-red-950 dark:text-red-400">{{ docAlerts() }} alert(s)</span> }
              </h3>
              <button class="btn-secondary text-xs py-1 px-2"><lucide-icon [img]="UploadIcon" [size]="12" /> Upload</button>
            </div>
            @if (documents().length > 0) {
              <div class="space-y-2">
                @for (doc of documents(); track doc.id) {
                  <div class="flex items-center justify-between py-2 border-b last:border-b-0" [style.borderColor]="'var(--border-default)'">
                    <div class="flex items-center gap-2">
                      <lucide-icon [img]="FileTextIcon" [size]="16" [style.color]="'var(--text-tertiary)'" />
                      <div>
                        <p class="text-sm" [style.color]="'var(--text-primary)'">{{ doc.title }}</p>
                        <p class="text-xs" [style.color]="'var(--text-tertiary)'">{{ doc.document_type_label }} • {{ doc.expiry_date || 'No expiry' }}</p>
                      </div>
                    </div>
                    <div class="flex items-center gap-2">
                      @if (doc.is_expired) { <span class="badge bg-red-50 text-red-600 text-xs">Expired</span> }
                      @else if (doc.is_expiring_soon) { <span class="badge bg-amber-50 text-amber-600 text-xs">Expiring</span> }
                      @if (doc.is_verified) { <lucide-icon [img]="CheckCircleIcon" [size]="16" class="text-emerald-500" /> }
                    </div>
                  </div>
                }
              </div>
            } @else {
              <p class="text-sm" [style.color]="'var(--text-tertiary)'">No documents uploaded.</p>
            }
          </div>
        </div>
      </div>
    }
  `,
})
export class GuardDetailComponent implements OnInit {
  private api = inject(ApiService);
  private route = inject(ActivatedRoute);
  readonly ArrowLeftIcon = ArrowLeft; readonly EditIcon = Edit; readonly ShieldIcon = Shield;
  readonly FileTextIcon = FileText; readonly AwardIcon = Award; readonly AlertTriangleIcon = AlertTriangle;
  readonly CheckCircleIcon = CheckCircle; readonly UploadIcon = Upload;
  readonly loading = signal(true);
  readonly guard = signal<any>(null);
  readonly skills = signal<any[]>([]);
  readonly documents = signal<any[]>([]);
  readonly docAlerts = signal(0);

  ngOnInit(): void {
    const id = this.route.snapshot.params['id'];
    this.api.get<any>(`/guards/${id}`).subscribe({
      next: res => { this.loading.set(false); if (res.data) { this.guard.set(res.data.guard); this.skills.set(res.data.skills || []); this.documents.set(res.data.documents || []); this.docAlerts.set(res.data.document_alerts || 0); } },
      error: () => this.loading.set(false),
    });
  }
}
