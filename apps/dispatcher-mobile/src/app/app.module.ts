import { NgModule } from '@angular/core';
import { NativeScriptModule } from '@nativescript/angular';
import { AppRoutingModule } from './app-routing.module';
import { AppComponent } from './app.component';
@NgModule({ declarations: [AppComponent], imports: [NativeScriptModule, AppRoutingModule], bootstrap: [AppComponent] })
export class AppModule {}
