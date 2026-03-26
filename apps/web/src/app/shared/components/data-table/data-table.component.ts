import { Component, input, output, computed } from '@angular/core';
import { NgClass } from '@angular/common';
import { LucideAngularModule, ChevronLeft, ChevronRight, ChevronsLeft, ChevronsRight } from 'lucide-angular';

export interface TableColumn {
  key: string;
  label: string;
  width?: string;
  align?: 'left' | 'center' | 'right';
  sortable?: boolean;
}

@Component({
  selector: 'g51-data-table',
  standalone: true,
  imports: [NgClass, LucideAngularModule],
  template: `
    <div class="card overflow-hidden">
      <!-- Table -->
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="border-b" [style.borderColor]="'var(--border-default)'">
              @for (col of columns(); track col.key) {
                <th
                  class="px-4 py-3 text-left font-medium whitespace-nowrap"
                  [style.color]="'var(--text-secondary)'"
                  [style.width]="col.width || 'auto'"
                  [ngClass]="{ 'text-center': col.align === 'center', 'text-right': col.align === 'right' }"
                >
                  {{ col.label }}
                </th>
              }
              @if (hasActions()) {
                <th class="px-4 py-3 text-right font-medium" [style.color]="'var(--text-secondary)'" style="width: 80px">
                  Actions
                </th>
              }
            </tr>
          </thead>
          <tbody>
            @for (row of rows(); track trackBy() ? row[trackBy()!] : $index) {
              <tr class="border-b last:border-b-0 hover:bg-[var(--surface-hover)] transition-colors"
                [style.borderColor]="'var(--border-default)'"
              >
                @for (col of columns(); track col.key) {
                  <td class="px-4 py-3" [style.color]="'var(--text-primary)'"
                    [ngClass]="{ 'text-center': col.align === 'center', 'text-right': col.align === 'right' }"
                  >
                    {{ row[col.key] }}
                  </td>
                }
                @if (hasActions()) {
                  <td class="px-4 py-3 text-right">
                    <ng-content select="[table-actions]" />
                  </td>
                }
              </tr>
            } @empty {
              <tr>
                <td [attr.colspan]="columns().length + (hasActions() ? 1 : 0)" class="px-4 py-12 text-center"
                  [style.color]="'var(--text-tertiary)'"
                >
                  {{ emptyMessage() }}
                </td>
              </tr>
            }
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      @if (totalPages() > 1) {
        <div class="flex items-center justify-between px-4 py-3 border-t" [style.borderColor]="'var(--border-default)'">
          <span class="text-xs" [style.color]="'var(--text-tertiary)'">
            Showing {{ (currentPage() - 1) * perPage() + 1 }}–{{ Math.min(currentPage() * perPage(), total()) }} of {{ total() }}
          </span>
          <div class="flex items-center gap-1">
            <button (click)="pageChange.emit(1)" [disabled]="currentPage() <= 1"
              class="p-1.5 rounded hover:bg-[var(--surface-hover)] disabled:opacity-30"
              [style.color]="'var(--text-secondary)'"
            >
              <lucide-icon [img]="ChevronsLeftIcon" [size]="16" />
            </button>
            <button (click)="pageChange.emit(currentPage() - 1)" [disabled]="currentPage() <= 1"
              class="p-1.5 rounded hover:bg-[var(--surface-hover)] disabled:opacity-30"
              [style.color]="'var(--text-secondary)'"
            >
              <lucide-icon [img]="ChevronLeftIcon" [size]="16" />
            </button>
            <span class="px-3 text-xs font-medium" [style.color]="'var(--text-primary)'">
              {{ currentPage() }} / {{ totalPages() }}
            </span>
            <button (click)="pageChange.emit(currentPage() + 1)" [disabled]="currentPage() >= totalPages()"
              class="p-1.5 rounded hover:bg-[var(--surface-hover)] disabled:opacity-30"
              [style.color]="'var(--text-secondary)'"
            >
              <lucide-icon [img]="ChevronRightIcon" [size]="16" />
            </button>
            <button (click)="pageChange.emit(totalPages())" [disabled]="currentPage() >= totalPages()"
              class="p-1.5 rounded hover:bg-[var(--surface-hover)] disabled:opacity-30"
              [style.color]="'var(--text-secondary)'"
            >
              <lucide-icon [img]="ChevronsRightIcon" [size]="16" />
            </button>
          </div>
        </div>
      }
    </div>
  `,
})
export class DataTableComponent {
  readonly columns = input.required<TableColumn[]>();
  readonly rows = input.required<Record<string, any>[]>();
  readonly total = input(0);
  readonly currentPage = input(1);
  readonly perPage = input(20);
  readonly trackBy = input<string>();
  readonly hasActions = input(false);
  readonly emptyMessage = input('No data available');
  readonly pageChange = output<number>();

  readonly Math = Math;
  readonly totalPages = computed(() => Math.ceil(this.total() / this.perPage()) || 1);

  readonly ChevronLeftIcon = ChevronLeft;
  readonly ChevronRightIcon = ChevronRight;
  readonly ChevronsLeftIcon = ChevronsLeft;
  readonly ChevronsRightIcon = ChevronsRight;
}
