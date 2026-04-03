import { Component, OnInit, inject } from '@angular/core';
import { RouterOutlet } from '@angular/router';
import { TranslateService } from '@ngx-translate/core';
import { ThemeService } from './core/services/theme.service';
import { ToastComponent } from './shared/components/toast/toast.component';
import { ConfirmDialogComponent } from './shared/components/confirm-dialog/confirm-dialog.component';

@Component({
  selector: 'g51-root',
  standalone: true,
  imports: [RouterOutlet, ToastComponent, ConfirmDialogComponent],
  template: `
    <router-outlet />
    <g51-toast />
    <g51-confirm-dialog />
  `,
})
export class AppComponent implements OnInit {
  private translate = inject(TranslateService);
  private theme = inject(ThemeService);

  ngOnInit(): void {
    // Initialize theme (reads from localStorage or system preference)
    this.theme.init();

    // Initialize i18n
    this.translate.setDefaultLang('en');
    const savedLang = localStorage.getItem('g51_lang') || 'en';
    this.translate.use(savedLang);
  }
}
