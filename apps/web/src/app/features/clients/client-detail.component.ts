import { Component, inject, signal, OnInit } from '@angular/core';
import { ActivatedRoute, RouterLink } from '@angular/router';
import { LucideAngularModule, ArrowLeft, Edit, Building2, Users, Phone, Mail } from 'lucide-angular';
import { PageHeaderComponent } from '@shared/components/page-header/page-header.component';
import { LoadingSpinnerComponent } from '@shared/components/loading-spinner/loading-spinner.component';
import { ApiService } from '@core/services/api.service';

@Component({
  selector: 'g51-client-detail',
  standalone: true,
  imports: [RouterLink, LucideAngularModule, PageHeaderComponent, LoadingSpinnerComponent],
  template: `
    @if (loading()) { <g51-loading [fullPage]="true" /> } @else if (client()) {
      <g51-page-header [title]="client()!.company_name" [subtitle]="client()!.contact_name">
        <a routerLink="/clients" class="btn-secondary flex items-center gap-1.5"><lucide-icon [img]="ArrowLeftIcon" [size]="16" /> Back</a>
        <a [routerLink]="'/clients/edit/' + client()!.id" class="btn-primary flex items-center gap-1.5"><lucide-icon [img]="EditIcon" [size]="16" /> Edit</a>
      </g51-page-header>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="lg:col-span-2 card p-5">
          <h3 class="text-sm font-semibold mb-4" [style.color]="'var(--text-primary)'">Client Information</h3>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
            <div><span class="block text-xs" [style.color]="'var(--text-tertiary)'">Company</span><span [style.color]="'var(--text-primary)'">{{ client()!.company_name }}</span></div>
            <div><span class="block text-xs" [style.color]="'var(--text-tertiary)'">Status</span><span class="badge bg-emerald-50 text-emerald-600 dark:bg-emerald-950 dark:text-emerald-400">{{ client()!.status_label }}</span></div>
            <div><span class="block text-xs" [style.color]="'var(--text-tertiary)'">Address</span><span [style.color]="'var(--text-primary)'">{{ client()!.address || '—' }}</span></div>
            <div><span class="block text-xs" [style.color]="'var(--text-tertiary)'">City / State</span><span [style.color]="'var(--text-primary)'">{{ client()!.city || '—' }}, {{ client()!.state || '—' }}</span></div>
            <div><span class="block text-xs" [style.color]="'var(--text-tertiary)'">Billing</span><span [style.color]="'var(--text-primary)'">{{ client()!.billing_type || '—' }} {{ client()!.billing_rate ? '₦' + client()!.billing_rate.toLocaleString() : '' }}</span></div>
            <div><span class="block text-xs" [style.color]="'var(--text-tertiary)'">Contract</span><span [style.color]="'var(--text-primary)'">{{ client()!.contract_start || '—' }} → {{ client()!.contract_end || 'Ongoing' }}</span></div>
          </div>
        </div>

        <div class="card p-5">
          <div class="flex items-center justify-between mb-3">
            <h3 class="text-sm font-semibold" [style.color]="'var(--text-primary)'">Contacts</h3>
            <button class="btn-secondary text-xs py-1 px-2">Add Contact</button>
          </div>
          @for (c of contacts(); track c.id) {
            <div class="py-2.5 border-b last:border-b-0" [style.borderColor]="'var(--border-default)'">
              <p class="text-sm font-medium" [style.color]="'var(--text-primary)'">{{ c.name }} @if (c.is_primary) { <span class="badge bg-blue-50 text-blue-600 text-[10px] ml-1">Primary</span> }</p>
              <p class="text-xs" [style.color]="'var(--text-tertiary)'">{{ c.role || '—' }}</p>
              <div class="flex items-center gap-3 mt-1 text-xs" [style.color]="'var(--text-secondary)'">
                <span class="flex items-center gap-1"><lucide-icon [img]="PhoneIcon" [size]="11" /> {{ c.phone }}</span>
                <span class="flex items-center gap-1"><lucide-icon [img]="MailIcon" [size]="11" /> {{ c.email }}</span>
              </div>
            </div>
          } @empty {
            <p class="text-sm py-4 text-center" [style.color]="'var(--text-tertiary)'">No contacts yet.</p>
          }
        </div>
      </div>
    }
  `,
})
export class ClientDetailComponent implements OnInit {
  private api = inject(ApiService);
  private route = inject(ActivatedRoute);
  readonly ArrowLeftIcon = ArrowLeft; readonly EditIcon = Edit; readonly BuildingIcon = Building2;
  readonly UsersIcon = Users; readonly PhoneIcon = Phone; readonly MailIcon = Mail;
  readonly loading = signal(true);
  readonly client = signal<any>(null);
  readonly contacts = signal<any[]>([]);

  ngOnInit(): void {
    const id = this.route.snapshot.params['id'];
    this.api.get<any>(`/clients/${id}`).subscribe({
      next: res => { this.loading.set(false); if (res.data) { this.client.set(res.data.client); this.contacts.set(res.data.contacts || []); } },
      error: () => this.loading.set(false),
    });
  }
}
