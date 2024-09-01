import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { LoginComponent } from './containers/login/login.component';
import { RecoverComponent } from './containers/recover/recover.component';
import { RouterModule, Routes } from '@angular/router';
import { SetComponent } from './containers/set/set.component';
import { ResetComponent } from './containers/reset/reset.component';
import { FormsModule, ReactiveFormsModule } from '@angular/forms';
import { SharedModule } from '../../shared/shared.module';

const routes: Routes = [
  {
    path: 'login',
    component: LoginComponent,
  },
  {
    path: 'recover',
    component: RecoverComponent,
  },
  {
    path: 'reset/:reset_token/:email',
    component: ResetComponent,
  },
  {
    path: 'set/:set_token/:email',
    component: SetComponent,
  },
  {
    path: '**',
    pathMatch: 'full',
    redirectTo: 'login',
  },
  {
    path: '',
    pathMatch: 'full',
    redirectTo: 'login',
  },
];

@NgModule({
  declarations: [
    LoginComponent,
    RecoverComponent,
    SetComponent,
    ResetComponent,
  ],
  imports: [
    CommonModule,
    RouterModule.forChild(routes),
    ReactiveFormsModule,
    SharedModule,
    FormsModule,
  ],
})
export class AuthenticationModule {}
