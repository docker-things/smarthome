import {BrowserModule} from '@angular/platform-browser';
import {NgModule} from '@angular/core';

import {AppRoutingModule} from './app-routing.module';
import {AppComponent} from './app.component';
import {BrowserAnimationsModule} from '@angular/platform-browser/animations';

import {
  NbThemeModule,
  NbLayoutModule,
  NbSidebarModule,
  NbMenuModule,
  NbIconModule,
  NbListModule,
  NbButtonModule,
  NbInputModule,
  NbTreeGridModule,
  NbToastrModule, NbAccordionModule, NbTabsetModule, NbSelectModule, NbCardModule
} from '@nebular/theme';
import {NbEvaIconsModule} from '@nebular/eva-icons';
import {LogsComponent} from './logs/logs.component';
import {DashboardComponent} from './dashboard/dashboard.component';
import {UiSwitchModule} from 'ngx-ui-switch';
import {NgxBootstrapSliderModule} from 'ngx-bootstrap-slider';
import {LoginComponent} from './login/login.component';
import {ResetPasswordComponent} from './reset-password/reset-password.component';
import {HttpClientModule} from '@angular/common/http';
import {DashboardService} from './dashboard/dashboard.service';
import {ModulesComponent} from './modules/modules.component';
import {TriggersComponent} from './triggers/triggers.component';
import {CronComponent} from './cron/cron.component';
import {DevicesComponent} from './devices/devices.component';
import { EditableInputComponent } from './components/editable-input/editable-input.component';


@NgModule({
  declarations: [
    AppComponent,
    LogsComponent,
    DashboardComponent,
    LoginComponent,
    ResetPasswordComponent,
    ModulesComponent,
    TriggersComponent,
    CronComponent,
    DevicesComponent,
    EditableInputComponent
  ],
  imports: [
    BrowserModule,
    AppRoutingModule,
    BrowserAnimationsModule,
    NbThemeModule.forRoot({name: 'default'}),
    NbLayoutModule,
    NbEvaIconsModule,
    NbSidebarModule.forRoot(),
    NbMenuModule.forRoot(),
    NbIconModule,
    NbListModule,
    UiSwitchModule,
    NgxBootstrapSliderModule,
    NbButtonModule,
    NbInputModule,
    HttpClientModule,
    NbTreeGridModule,
    NbToastrModule.forRoot(),
    NbAccordionModule,
    NbTabsetModule,
    NbSelectModule,
    NbCardModule
  ],
  providers: [DashboardService],
  bootstrap: [AppComponent]
})
export class AppModule {
}
