import { Component } from '@angular/core';
@Component({ selector: 'g51-disp-history', template: `<ActionBar title="History" class="action-bar"><NavigationButton text="Back" android.systemIcon="ic_menu_back"></NavigationButton></ActionBar><ScrollView><StackLayout class="p-4"><Label text="Recent Dispatches" class="text-sm font-bold mb-2"></Label><StackLayout class="card p-3 mb-2"><Label text="Mrs. Adeyemi — Resolved" class="text-sm"></Label><Label text="15 min response" class="text-xs text-muted"></Label></StackLayout></StackLayout></ScrollView>` })
export class DispatcherHistoryComponent {}
