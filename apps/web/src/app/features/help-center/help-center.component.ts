import { Component, inject, signal, OnInit } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { NgClass } from '@angular/common';
import { LucideAngularModule, HelpCircle, Search, BookOpen, ChevronRight } from 'lucide-angular';
import { PageHeaderComponent } from '@shared/components/page-header/page-header.component';
import { EmptyStateComponent } from '@shared/components/empty-state/empty-state.component';
import { LoadingSpinnerComponent } from '@shared/components/loading-spinner/loading-spinner.component';
import { ApiService } from '@core/services/api.service';

@Component({
  selector: 'g51-help-center',
  standalone: true,
  imports: [FormsModule, NgClass, LucideAngularModule, PageHeaderComponent, EmptyStateComponent, LoadingSpinnerComponent],
  template: `
    <g51-page-header title="Help Center" subtitle="Guides, tutorials, and FAQs" />

    <div class="relative max-w-lg mb-6">
      <lucide-icon [img]="SearchIcon" [size]="16" class="absolute left-3 top-1/2 -translate-y-1/2" [style.color]="'var(--text-tertiary)'" />
      <input type="text" [(ngModel)]="search" (ngModelChange)="onSearch()" placeholder="Search articles..." class="input-base w-full pl-10 py-3" />
    </div>

    @if (loading()) { <g51-loading /> }
    @else if (selectedArticle()) {
      <div class="card p-6 max-w-3xl">
        <button (click)="selectedArticle.set(null)" class="text-xs font-medium mb-3" [style.color]="'var(--brand-500)'">← Back to articles</button>
        <h2 class="text-lg font-bold mb-1 font-heading" [style.color]="'var(--text-primary)'">{{ selectedArticle()!.title }}</h2>
        <span class="badge text-[10px] bg-gray-100 text-gray-500 mb-4">{{ selectedArticle()!.category }}</span>
        <div class="prose text-sm leading-relaxed whitespace-pre-line" [style.color]="'var(--text-secondary)'">{{ selectedArticle()!.content }}</div>
      </div>
    }
    @else {
      @if (!categories().length) { <g51-empty-state title="No Articles" message="Help articles will appear here." [icon]="BookOpenIcon" /> }
      @else {
        @for (cat of categories(); track cat.name) {
          <div class="mb-4">
            <h3 class="text-sm font-semibold font-heading mb-2" [style.color]="'var(--text-primary)'">{{ cat.name }}</h3>
            <div class="space-y-1">
              @for (a of cat.articles; track a.id) {
                <div (click)="selectedArticle.set(a)" class="card p-3 card-hover cursor-pointer flex items-center justify-between">
                  <p class="text-sm" [style.color]="'var(--text-primary)'">{{ a.title }}</p>
                  <lucide-icon [img]="ChevronIcon" [size]="14" [style.color]="'var(--text-tertiary)'" />
                </div>
              }
            </div>
          </div>
        }
      }
    }
  `,
})
export class HelpCenterComponent implements OnInit {
  private api = inject(ApiService);
  readonly SearchIcon = Search; readonly BookOpenIcon = BookOpen; readonly ChevronIcon = ChevronRight;
  readonly loading = signal(true); readonly selectedArticle = signal<any>(null);
  readonly categories = signal<{ name: string; articles: any[] }[]>([]);
  private allArticles: any[] = [];
  search = '';

  ngOnInit(): void {
    this.api.get<any>('/help/articles').subscribe({
      next: r => {
        this.allArticles = r.data?.articles || r.data || [];
        this.groupByCategory(this.allArticles);
        this.loading.set(false);
      },
      error: () => this.loading.set(false),
    });
  }

  onSearch(): void {
    const q = this.search.toLowerCase();
    const filtered = q ? this.allArticles.filter(a => a.title.toLowerCase().includes(q) || a.content.toLowerCase().includes(q)) : this.allArticles;
    this.groupByCategory(filtered);
  }

  private groupByCategory(articles: any[]): void {
    const map = new Map<string, any[]>();
    for (const a of articles) {
      if (!map.has(a.category)) map.set(a.category, []);
      map.get(a.category)!.push(a);
    }
    this.categories.set(Array.from(map.entries()).map(([name, articles]) => ({ name, articles })));
  }
}
