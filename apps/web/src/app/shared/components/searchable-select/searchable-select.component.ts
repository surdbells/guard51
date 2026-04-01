import { Component, Input, Output, EventEmitter, signal, HostListener, ElementRef, inject, forwardRef } from '@angular/core';
import { FormsModule, NG_VALUE_ACCESSOR, ControlValueAccessor } from '@angular/forms';
import { NgClass } from '@angular/common';
import { LucideAngularModule, ChevronDown, Search, Check, X } from 'lucide-angular';

@Component({
  selector: 'g51-searchable-select',
  standalone: true,
  imports: [FormsModule, NgClass, LucideAngularModule],
  providers: [{ provide: NG_VALUE_ACCESSOR, useExisting: forwardRef(() => SearchableSelectComponent), multi: true }],
  template: `
    <div class="searchable-select relative">
      <div class="input-base w-full flex items-center gap-2 cursor-pointer" (click)="toggle()" [ngClass]="open() ? 'ring-2 ring-[rgba(27,58,92,0.08)] border-[var(--border-focus)]' : ''">
        <lucide-icon [img]="SearchIcon" [size]="14" [style.color]="'var(--text-tertiary)'" />
        @if (!open() && selectedLabel) {
          <span class="flex-1 text-sm truncate" [style.color]="'var(--text-primary)'">{{ selectedLabel }}</span>
          <button (click)="clear($event)" class="text-gray-400 hover:text-gray-600"><lucide-icon [img]="XIcon" [size]="12" /></button>
        } @else {
          <input type="text" [(ngModel)]="searchTerm" (input)="filterOptions()" (click)="$event.stopPropagation(); open.set(true)"
            [placeholder]="placeholder" class="flex-1 bg-transparent border-none outline-none text-sm" [style.color]="'var(--text-primary)'" />
        }
        <lucide-icon [img]="ChevronIcon" [size]="14" [style.color]="'var(--text-tertiary)'" class="shrink-0 transition-transform" [ngClass]="open() ? 'rotate-180' : ''" />
      </div>
      @if (open()) {
        <div class="dropdown">
          @if (!filteredOptions.length) {
            <div class="px-3 py-2 text-xs text-center" [style.color]="'var(--text-tertiary)'">No results</div>
          }
          @for (opt of filteredOptions; track opt.value) {
            <div class="dropdown-item" [ngClass]="opt.value === value ? 'selected' : ''" (click)="select(opt)">
              <div class="flex items-center gap-2">
                <span class="flex-1">{{ opt.label }}</span>
                @if (opt.value === value) { <lucide-icon [img]="CheckIcon" [size]="14" [style.color]="'var(--brand-500)'" /> }
              </div>
            </div>
          }
        </div>
      }
    </div>
  `,
})
export class SearchableSelectComponent implements ControlValueAccessor {
  @Input() options: { value: string; label: string }[] = [];
  @Input() placeholder = 'Search...';
  @Output() valueChange = new EventEmitter<string>();

  readonly SearchIcon = Search; readonly ChevronIcon = ChevronDown; readonly CheckIcon = Check; readonly XIcon = X;
  readonly open = signal(false);
  searchTerm = '';
  value = '';
  selectedLabel = '';
  filteredOptions: { value: string; label: string }[] = [];
  private el = inject(ElementRef);
  private onChange: any = () => {};
  private onTouched: any = () => {};

  ngOnInit(): void { this.filteredOptions = this.options; }
  ngOnChanges(): void { this.filteredOptions = this.options; this.updateLabel(); }

  toggle(): void { this.open.set(!this.open()); if (this.open()) { this.searchTerm = ''; this.filteredOptions = this.options; } }

  filterOptions(): void {
    const q = this.searchTerm.toLowerCase();
    this.filteredOptions = this.options.filter(o => o.label.toLowerCase().includes(q));
  }

  select(opt: { value: string; label: string }): void {
    this.value = opt.value; this.selectedLabel = opt.label;
    this.open.set(false); this.searchTerm = '';
    this.onChange(this.value); this.valueChange.emit(this.value);
  }

  clear(e: Event): void {
    e.stopPropagation();
    this.value = ''; this.selectedLabel = '';
    this.onChange(''); this.valueChange.emit('');
  }

  private updateLabel(): void {
    const opt = this.options.find(o => o.value === this.value);
    this.selectedLabel = opt?.label || '';
  }

  @HostListener('document:click', ['$event'])
  onDocClick(e: Event): void { if (!this.el.nativeElement.contains(e.target)) this.open.set(false); }

  writeValue(val: string): void { this.value = val || ''; this.updateLabel(); }
  registerOnChange(fn: any): void { this.onChange = fn; }
  registerOnTouched(fn: any): void { this.onTouched = fn; }
}
