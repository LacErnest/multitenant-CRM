import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ReactiveFormsModule } from '@angular/forms';
import { RouterModule, Routes } from '@angular/router';
import { SharedModule } from 'src/app/shared/shared.module';
import { NotificationSettingsFormComponent } from './containers/notification-settings-form/notification-settings-form.component';

import { NotificationSettingsResolver } from './resolvers/notification-settings.resolver';
import { NgSelectModule } from '@ng-select/ng-select';

const routes: Routes = [
  {
    path: '',
    component: NotificationSettingsFormComponent,
    resolve: {
      settings: NotificationSettingsResolver,
    },
  },
];

@NgModule({
  declarations: [NotificationSettingsFormComponent],
  imports: [
    CommonModule,
    RouterModule.forChild(routes),
    ReactiveFormsModule,
    SharedModule,
    NgSelectModule,
  ],
})
export class NotificationSettingsModule {}
