import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule, Routes } from '@angular/router';
import { SharedModule } from '../../../../shared/shared.module';
import { FormsModule, ReactiveFormsModule } from '@angular/forms';
import { NgSelectModule } from '@ng-select/ng-select';
import { NgxCurrencyModule } from 'ngx-currency';
import { EmailTemplateFormComponent } from './containers/email-template-form/email-template-form.component';

import { RoleGuard } from 'src/app/core/guards/role.guard';
import { EmailTemplateResolver } from './resolvers/email-template.resolver';
import { SmtpSettingsResolver } from './resolvers/smtp-settings.resolver';
import { EmailManagementSharedModule } from './email-management-shared.module';
import { settingsRoutesRoles } from '../../settings-roles';

const routes: Routes = [
  {
    path: 'create',
    component: EmailTemplateFormComponent,
    canActivate: [RoleGuard],
    resolve: {
      smtpSettings: SmtpSettingsResolver,
    },
    data: {
      roles: settingsRoutesRoles.emailManagement.valueOf(),
    },
  },
  {
    path: ':email_template_id/edit',
    component: EmailTemplateFormComponent,
    canActivate: [RoleGuard],
    resolve: {
      emailTemplate: EmailTemplateResolver,
      smtpSettings: SmtpSettingsResolver,
    },
    data: {
      roles: settingsRoutesRoles.emailManagement.valueOf(),
    },
  },
];

@NgModule({
  declarations: [EmailTemplateFormComponent],
  exports: [],
  imports: [
    CommonModule,
    RouterModule.forChild(routes),
    SharedModule,
    ReactiveFormsModule,
    NgSelectModule,
    NgxCurrencyModule,
    FormsModule,
    EmailManagementSharedModule,
  ],
})
export class EmailTemplateModule {}
