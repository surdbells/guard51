import { Component, inject, signal, OnInit } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { NgClass, DatePipe } from '@angular/common';
import { LucideAngularModule, ListTodo, Plus, AlertTriangle, CheckCircle, Clock, User } from 'lucide-angular';
import { PageHeaderComponent } from '@shared/components/page-header/page-header.component';
import { StatsCardComponent } from '@shared/components/stats-card/stats-card.component';
import { ModalComponent } from '@shared/components/modal/modal.component';
import { EmptyStateComponent } from '@shared/components/empty-state/empty-state.component';
import { ApiService } from '@core/services/api.service';
import { AuthStore } from '@core/services/auth.store';
import { ToastService } from '@core/services/toast.service';

@Component({
  selector: 'g51-tasks',
  standalone: true,
  imports: [FormsModule, NgClass, DatePipe, LucideAngularModule, PageHeaderComponent, StatsCardComponent, ModalComponent, EmptyStateComponent],
  template: `
    <g51-page-header title="Task Management" subtitle="Assign tasks to guards and track completion">
      <button (click)="showCreate.set(true)" class="btn-primary flex items-center gap-2">
        <lucide-icon [img]="PlusIcon" [size]="16" /> New Task
      </button>
    </g51-page-header>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6 stagger-children">
      <g51-stats-card label="Total Tasks" [value]="stats().total" [icon]="ListTodoIcon" />
      <g51-stats-card label="In Progress" [value]="stats().inProgress" [icon]="ClockIcon" />
      <g51-stats-card label="Overdue" [value]="stats().overdue" [icon]="AlertTriangleIcon" />
      <g51-stats-card label="Completed (7d)" [value]="stats().completedWeek" [icon]="CheckCircleIcon" />
    </div>

    <!-- Status filter tabs -->
    <div class="tab-pills">
      @for (tab of ['All', 'Pending', 'In Progress', 'Overdue', 'Completed']; track tab) {
        <button (click)="statusFilter.set(tab)" class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors"
          [ngClass]="statusFilter() === tab ? 'bg-[var(--color-brand-500)] text-white' : 'bg-[var(--surface-muted)]'"
          [style.color]="statusFilter() !== tab ? 'var(--text-secondary)' : ''">{{ tab }}</button>
      }
    </div>

    <!-- Task list -->
    <div class="space-y-2">
      @for (task of filteredTasks(); track task.id) {
        <div class="card p-4 card-hover border-l-4"
          [style.borderLeftColor]="task.priority === 'critical' ? 'var(--color-danger)' : task.priority === 'high' ? 'var(--color-warning)' : task.priority === 'medium' ? 'var(--color-brand-500)' : 'var(--text-tertiary)'">
          <div class="flex items-start justify-between">
            <div class="flex-1">
              <div class="flex items-center gap-2 mb-1">
                <h4 class="text-sm font-semibold" [style.color]="'var(--text-primary)'">{{ task.title }}</h4>
                <span class="badge text-[10px]"
                  [ngClass]="task.is_overdue ? 'bg-red-50 text-red-600 dark:bg-red-950 dark:text-red-400'
                    : task.status === 'completed' ? 'bg-emerald-50 text-emerald-600 dark:bg-emerald-950 dark:text-emerald-400'
                    : task.status === 'in_progress' ? 'bg-blue-50 text-blue-600' : 'bg-[var(--surface-muted)]'">
                  {{ task.is_overdue ? 'Overdue' : task.status_label }}
                </span>
              </div>
              <p class="text-xs line-clamp-1" [style.color]="'var(--text-secondary)'">{{ task.description }}</p>
              <div class="flex items-center gap-3 mt-1.5 text-[10px]" [style.color]="'var(--text-tertiary)'">
                <span class="flex items-center gap-1"><lucide-icon [img]="UserIcon" [size]="10" /> {{ task.assigned_to?.substring(0,8) }}</span>
                @if (task.due_date) { <span class="flex items-center gap-1"><lucide-icon [img]="ClockIcon" [size]="10" /> Due {{ task.due_date | date:'shortDate' }}</span> }
                <span class="badge text-[9px]"
                  [ngClass]="task.priority === 'critical' ? 'bg-red-50 text-red-600' : task.priority === 'high' ? 'bg-amber-50 text-amber-600' : 'bg-[var(--surface-muted)]'">{{ task.priority_label }}</span>
              </div>
            </div>
            <div class="flex gap-1.5 shrink-0 ml-3">
              @if (task.status === 'pending') {
                <button (click)="updateTask(task.id, 'start')" class="btn-secondary text-xs py-1 px-2">Start</button>
              }
              @if (task.status === 'in_progress' || task.status === 'overdue') {
                <button (click)="updateTask(task.id, 'complete')" class="btn-primary text-xs py-1 px-2">Complete</button>
              }
            </div>
          </div>
        </div>
      } @empty {
        <g51-empty-state title="No Tasks" message="Create a task to assign work to guards." [icon]="ListTodoIcon" />
      }
    </div>

    <!-- Create task modal -->
    <g51-modal [open]="showCreate()" title="New Task" maxWidth="520px" (closed)="showCreate.set(false)">
      <div class="space-y-3">
        <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Title *</label>
          <input type="text" [(ngModel)]="form.title" class="input-base w-full" placeholder="Task title" /></div>
        <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Description *</label>
          <textarea [(ngModel)]="form.description" rows="3" class="input-base w-full resize-none" placeholder="Task details..."></textarea></div>
        <div class="grid grid-cols-2 gap-3">
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Priority</label>
            <select [(ngModel)]="form.priority" class="input-base w-full">
              <option value="low">Low</option><option value="medium">Medium</option>
              <option value="high">High</option><option value="critical">Critical</option></select></div>
          <div><label class="block text-xs font-medium mb-1" [style.color]="'var(--text-secondary)'">Due Date</label>
            <input type="datetime-local" [(ngModel)]="form.due_date" class="input-base w-full" /></div>
        </div>
      </div>
      <div modal-footer>
        <button (click)="showCreate.set(false)" class="btn-secondary">Cancel</button>
        <button (click)="onCreate()" class="btn-primary">Create Task</button>
      </div>
    </g51-modal>
  `,
})
export class TasksComponent implements OnInit {
  private api = inject(ApiService);
  readonly auth = inject(AuthStore); private toast = inject(ToastService);
  readonly ListTodoIcon = ListTodo; readonly PlusIcon = Plus; readonly AlertTriangleIcon = AlertTriangle;
  readonly CheckCircleIcon = CheckCircle; readonly ClockIcon = Clock; readonly UserIcon = User;
  readonly showCreate = signal(false);
  readonly statusFilter = signal('All');
  readonly tasks = signal<any[]>([]);
  readonly stats = signal({ total: 0, inProgress: 0, overdue: 0, completedWeek: 0 });
  form = { title: '', description: '', priority: 'medium', due_date: '' };

  filteredTasks = () => {
    const f = this.statusFilter();
    const all = this.tasks();
    if (f === 'All') return all;
    if (f === 'Overdue') return all.filter(t => t.is_overdue);
    return all.filter(t => t.status_label === f);
  };

  ngOnInit(): void {
    this.api.get<any>('/tasks').subscribe({
      next: res => {
        if (res.data) {
          const tasks = res.data.tasks || [];
          this.tasks.set(tasks);
          this.stats.set({
            total: tasks.length,
            inProgress: tasks.filter((t: any) => t.status === 'in_progress').length,
            overdue: tasks.filter((t: any) => t.is_overdue).length,
            completedWeek: tasks.filter((t: any) => t.status === 'completed').length,
          });
        }
      },
    });
  }

  updateTask(id: string, action: string): void {
    this.api.post(`/tasks/${id}/status`, { action }).subscribe({
      next: () => { this.toast.success(`Task ${action === 'complete' ? 'completed' : 'started'}`); this.ngOnInit(); },
    });
  }

  onCreate(): void {
    this.api.post('/tasks', this.form).subscribe({
      next: () => { this.showCreate.set(false); this.toast.success('Task created'); this.ngOnInit(); },
    });
  }
}
