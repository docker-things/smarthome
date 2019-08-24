import { NgModule } from '@angular/core';
import { Routes, RouterModule } from '@angular/router';
import {LogsComponent} from './logs/logs.component';
import {DashboardComponent} from './dashboard/dashboard.component';
import {LoginComponent} from './login/login.component';
import {ResetPasswordComponent} from './reset-password/reset-password.component';
import {AuthGuard} from './auth.guard';
import {DevicesComponent} from './devices/devices.component';
import {ModulesComponent} from './modules/modules.component';
import {TriggersComponent} from './triggers/triggers.component';
import {CronComponent} from './cron/cron.component';

const routes: Routes = [
  { path: '', component: DashboardComponent, canActivate: [AuthGuard] },
  { path: 'logs', component: LogsComponent, canActivate: [AuthGuard] },
  { path: 'login', component: LoginComponent },
  { path: 'reset', component: ResetPasswordComponent, canActivate: [AuthGuard] },
  { path: 'devices', component: DevicesComponent, canActivate: [AuthGuard] },
  { path: 'modules', component: ModulesComponent, canActivate: [AuthGuard] },
  { path: 'triggers', component: TriggersComponent, canActivate: [AuthGuard] },
  { path: 'cron', component: CronComponent, canActivate: [AuthGuard] }
  ];

@NgModule({
  imports: [RouterModule.forRoot(routes)],
  exports: [RouterModule]
})
export class AppRoutingModule { }
