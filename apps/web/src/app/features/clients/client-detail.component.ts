import { Component, inject, signal, OnInit } from '@angular/core';
import { ActivatedRoute, RouterLink } from '@angular/router';
import { NgClass, DecimalPipe } from '@angular/common';
import { LucideAngularModule, ArrowLeft, Edit, Building2, MapPin, Receipt, FileText } from 'lucide-angular';
import { PageHeaderComponent } from '@shared/components/page-header/page-header.component';
import { LoadingSpinnerComponent } from '@shared/components/loading-spinner/loading-spinner.component';
import { EmptyStateComponent } from '@shared/components/empty-state/empty-state.component';
import { ApiService } from '@core/services/api.service';

@Component({
  selector: 'g51-client-detail',
  standalone: true,
  imports: [RouterLink, NgClass, DecimalPipe, LucideAngularModule, PageHeaderComponent, LoadingSpinnerComponent, EmptyStateComponent],
  template: `
    <g51-page-header [title]="client()?.company_name || 'Client'" subtitle="Client profile, sites, and billing">
      <button class="btn-secondary flex items-center gap-2" routerLink="/clients"><lucide-icon [img]="ArrowLeftIcon" [size]="14" /> Back</button>
      <button class="btn-primary flex items-center gap-2" [routerLink]="['/clients/edit', client()?.id]"><lucide-icon [img]="EditIcon" [size]="14" /> Edit</button>
    </g51-page-header>

    @if (loading()) { <g51-loading /> } @else if (client()) {
      <div class="tab-pills">
        @for (tab of ['Profile', 'Sites', 'Invoices']; track tab) {
          <button (click)="activeTab.set(tab)" class="tab-pill" [ngClass]="activeTab() === tab ? 'active' : ''">{{ tab }}</button>
        }
      </div>

      @if (activeTab() === 'Profile') {
        <div class="card p-5">
          <div class="grid grid-cols-2 lg:grid-cols-3 gap-y-3 gap-x-6 text-xs">
            <div><span [style.color]="'var(--text-tertiary)'">Company Name</span><p class="font-medium" [style.color]="'var(--text-primary)'">{{ client()?.company_name }}</p></div>
            <div><span [style.color]="'var(--text-tertiary)'">Contact</span><p class="font-medium" [style.color]="'var(--text-primary)'">{{ client()?.contact_name }}</p></div>
            <div><span [style.color]="'var(--text-tertiary)'">Email</span><p class="font-medium" [style.color]="'var(--text-primary)'">{{ client()?.contact_email }}</p></div>
            <div><span [style.color]="'var(--text-tertiary)'">Phone</span><p class="font-medium" [style.color]="'var(--text-primary)'">{{ client()?.contact_phone }}</p></div>
            <div><span [style.color]="'var(--text-tertiary)'">City / State</span><p class="font-medium" [style.color]="'var(--text-primary)'">{{ client()?.city || '' }} {{ client()?.state || '' }}</p></div>
            <div><span [style.color]="'var(--text-tertiary)'">Status</span>
              <span class="badge text-[10px]" [ngClass]="client()?.status === 'active' ? 'bg-emerald-50 text-emerald-600' : 'bg-gray-100 text-gray-500'">{{ client()?.status }}</span></div>
            <div><span [style.color]="'var(--text-tertiary)'">Billing Type</span><p class="font-medium" [style.color]="'var(--text-primary)'">{{ client()?.billing_type || '—' }}</p></div>
            <div><span [style.color]="'var(--text-tertiary)'">Billing Rate</span><p class="font-medium" [style.color]="'var(--text-primary)'">{{ client()?.billing_rate ? '₦' + (client()?.billing_rate | number:'1.0-0') : '—' }}</p></div>
            <div><span [style.color]="'var(--text-tertiary)'">Contract</span><p class="font-medium" [style.color]="'var(--text-primary)'">{{ client()?.contract_start || '—' }} → {{ client()?.contract_end || '—' }}</p></div>
          </div>
        </div>
      }

      @if (activeTab() === 'Sites') {
        @if (!sites().length) { <g51-empty-state title="No Sites" message="No sites assigned to this client." [icon]="MapPinIcon" /> }
        @else {
          <div class="space-y-2">
            @for (s of sites(); track s.id) {
              <a [routerLink]="['/sites', s.id]" class="card p-4 card-hover block">
                <div class="flex items-center justify-between">
                  <div><p class="text-sm font-semibold" [style.color]="'var(--text-primary)'">{{ s.name }}</p>
                    <p class="text-xs" [style.color]="'var(--text-tertiary)'">{{ s.address || '' }} · {{ s.city || '' }}</p></div>
                  <span class="badge text-[10px]" [ngClass]="s.status === 'active' ? 'bg-emerald-50 text-emerald-600' : 'bg-gray-100 text-gray-500'">{{ s.status }}</span>
                </div>
              </a>
            }
          </div>
        }
      }

      @if (activeTab() === 'Invoices') {
        @if (!invoices().length) { <g51-empty-state title="No Invoices" message="No invoices for this client." [icon]="ReceiptIcon" /> }
        @else {
          <div class="space-y-2">
            @for (inv of invoices(); track inv.id) {
              <a [routerLink]="['/invoices', inv.id]" class="card p-4 card-hover block">
                <div class="flex items-center justify-between">
                  <div><p class="text-sm font-semibold" [style.color]="'var(--text-primary)'">{{ inv.invoice_number }}</p>
                    <p class="text-xs" [style.color]="'var(--text-tertiary)'">{{ inv.issue_date }} · Due: {{ inv.due_date }}</p></div>
                  <div class="text-right">
                    <p class="text-sm font-bold" [style.color]="'var(--text-primary)'">₦{{ inv.total_amount | number:'1.0-0' }}</p>
                    <span class="badge text-[10px]" [ngClass]="inv.status === 'paid' ? 'bg-emerald-50 text-emerald-600' : inv.status === 'overdue' ? 'bg-red-50 text-red-600' : 'bg-amber-50 text-amber-600'">{{ inv.status }}</span>
                  </div>
                </div>
              </a>
            }
          </div>
        }
      }
    }
  `,
})
export class ClientDetailComponent implements OnInit {
  private api = inject(ApiService); private route = inject(ActivatedRoute);
  readonly ArrowLeftIcon = ArrowLeft; readonly EditIcon = Edit; readonly MapPinIcon = MapPin; readonly ReceiptIcon = Receipt;
  readonly loading = signal(true); readonly client = signal<any>(null);
  readonly sites = signal<any[]>([]); readonly invoices = signal<any[]>([]);
  readonly activeTab = signal('Profile');

  ngOnInit(): void {
    const id = this.route.snapshot.paramMap.get('id');
    if (!id) return;
    this.api.get<any>(`/clients/${id}`).subscribe({
      next: res => { this.client.set(res.data?.client || res.data); this.loading.set(false); },
      error: () => this.loading.set(false),
    });
    this.api.get<any>(`/sites?client_id=${id}`).subscribe({ next: res => this.sites.set(res.data?.sites || res.data || []), error: () => {} });
    this.api.get<any>(`/invoices?client_id=${id}`).subscribe({ next: res => this.invoices.set(res.data?.invoices || res.data || []), error: () => {} });
  }
}
