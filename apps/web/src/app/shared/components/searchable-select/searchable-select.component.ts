import { Component, input, output, signal, HostListener, ElementRef, inject, forwardRef } from '@angular/core';
import { FormsModule, NG_VALUE_ACCESSOR, ControlValueAccessor } from '@angular/forms';
import { LucideAngularModule, ChevronDown, Search, X, Check } from 'lucide-angular';

export interface SelectOption {
  value: string;
  label: string;
  sublabel?: string;
  icon?: string;
}

@Component({
  selector: 'g51-searchable-select',
  standalone: true,
  imports: [FormsModule, LucideAngularModule],
  providers: [{ provide: NG_VALUE_ACCESSOR, useExisting: forwardRef(() => SearchableSelectComponent), multi: true }],
  template: `
    <div class="relative" [class.z-30]="isOpen()">
      <!-- Trigger -->
      <button type="button" (click)="toggle()" class="input-base w-full flex items-center justify-between gap-2 text-left cursor-pointer"
        [style.color]="selectedOption() ? 'var(--text-primary)' : 'var(--text-tertiary)'">
        <span class="truncate text-xs">{{ selectedOption()?.label || placeholder() }}</span>
        <lucide-icon [img]="ChevronIcon" [size]="14" [style.color]="'var(--text-tertiary)'" class="shrink-0 transition-transform" [class.rotate-180]="isOpen()" />
      </button>

      <!-- Dropdown -->
      @if (isOpen()) {
        <div class="absolute left-0 right-0 top-full mt-1 rounded-xl border py-1 animate-scale-in overflow-hidden"
          [style.background]="'var(--surface-card)'" [style.borderColor]="'var(--border-default)'" style="box-shadow:var(--shadow-lg);max-height:240px">
          <!-- Search input -->
          <div class="px-2 py-1.5 border-b" [style.borderColor]="'var(--border-default)'">
            <div class="relative">
              <lucide-icon [img]="SearchIcon" [size]="12" class="absolute left-2.5 top-1/2 -translate-y-1/2" [style.color]="'var(--text-tertiary)'" />
              <input type="text" [(ngModel)]="searchQuery" (ngModelChange)="onSearch()" placeholder="Search..."
                class="w-full pl-7 pr-6 py-1.5 text-xs rounded-lg border-none outline-none" [style.background]="'var(--surface-muted)'" [style.color]="'var(--text-primary)'" />
              @if (searchQuery) {
                <button (click)="searchQuery = ''; onSearch()" class="absolute right-2 top-1/2 -translate-y-1/2"><lucide-icon [img]="XIcon" [size]="11" [style.color]="'var(--text-tertiary)'" /></button>
              }
            </div>
          </div>

          <!-- Options -->
          <div class="overflow-y-auto" style="max-height:180px">
            @if (allowEmpty()) {
              <button type="button" (click)="selectOption(null)" class="w-full flex items-center gap-2 px-3 py-2 text-xs hover:bg-[var(--surface-hover)] transition-colors"
                [style.color]="!value() ? 'var(--brand-500)' : 'var(--text-tertiary)'">
                <span>{{ emptyLabel() }}</span>
              </button>
            }
            @for (opt of filteredOptions(); track opt.value) {
              <button type="button" (click)="selectOption(opt)" class="w-full flex items-center gap-2 px-3 py-2 text-xs hover:bg-[var(--surface-hover)] transition-colors"
                [style.color]="value() === opt.value ? 'var(--brand-500)' : 'var(--text-primary)'"
                [style.fontWeight]="value() === opt.value ? '600' : '400'">
                <div class="flex-1 text-left">
                  <span>{{ opt.label }}</span>
                  @if (opt.sublabel) { <span class="block text-[10px]" [style.color]="'var(--text-tertiary)'">{{ opt.sublabel }}</span> }
                </div>
                @if (value() === opt.value) { <lucide-icon [img]="CheckIcon" [size]="13" class="shrink-0" /> }
              </button>
            }
            @if (!filteredOptions().length) {
              <p class="px-3 py-2 text-xs" [style.color]="'var(--text-tertiary)'">No results found</p>
            }
          </div>
        </div>
      }
    </div>
  `,
})
export class SearchableSelectComponent implements ControlValueAccessor {
  private elRef = inject(ElementRef);
  readonly options = input<SelectOption[]>([]);
  readonly placeholder = input('Select...');
  readonly allowEmpty = input(false);
  readonly emptyLabel = input('None');
  readonly changed = output<string | null>();

  readonly ChevronIcon = ChevronDown; readonly SearchIcon = Search; readonly XIcon = X; readonly CheckIcon = Check;
  readonly isOpen = signal(false);
  readonly value = signal<string | null>(null);
  readonly filteredOptions = signal<SelectOption[]>([]);
  searchQuery = '';
  private onChange: any = () => {};
  private onTouched: any = () => {};

  selectedOption(): SelectOption | undefined { return this.options().find(o => o.value === this.value()); }

  toggle(): void {
    this.isOpen.update(v => !v);
    if (this.isOpen()) { this.searchQuery = ''; this.filteredOptions.set(this.options()); }
  }

  onSearch(): void {
    const q = this.searchQuery.toLowerCase();
    this.filteredOptions.set(!q ? this.options() : this.options().filter(o => o.label.toLowerCase().includes(q) || (o.sublabel || '').toLowerCase().includes(q)));
  }

  selectOption(opt: SelectOption | null): void {
    const v = opt?.value || null;
    this.value.set(v);
    this.onChange(v);
    this.changed.emit(v);
    this.isOpen.set(false);
  }

  @HostListener('document:click', ['$event'])
  onClickOutside(e: Event): void { if (!this.elRef.nativeElement.contains(e.target)) this.isOpen.set(false); }

  writeValue(val: any): void { this.value.set(val); this.filteredOptions.set(this.options()); }
  registerOnChange(fn: any): void { this.onChange = fn; }
  registerOnTouched(fn: any): void { this.onTouched = fn; }
}
