import { Component, OnInit, inject } from '@angular/core';
import { RouterOutlet } from '@angular/router';
import { TranslateService } from '@ngx-translate/core';
import { ThemeService } from './core/services/theme.service';
import { SwUpdateService } from './core/services/sw-update.service';
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
  private swUpdate = inject(SwUpdateService);

  ngOnInit(): void {
    this.theme.init();
    this.swUpdate.init();
    this.translate.setDefaultLang('en');
    const savedLang = localStorage.getItem('g51_lang') || 'en';
    this.translate.use(savedLang);
  }
}
