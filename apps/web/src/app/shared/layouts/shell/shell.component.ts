import { Component } from '@angular/core';
import { RouterOutlet } from '@angular/router';

@Component({
  selector: 'g51-shell',
  standalone: true,
  imports: [RouterOutlet],
  template: `
    <div class="min-h-screen" style="background: var(--surface-bg)">
      <!-- Shell layout: sidebar + header + content (Batch 2) -->
      <main class="p-6">
        <router-outlet />
      </main>
    </div>
  `,
})
export class ShellComponent {}
