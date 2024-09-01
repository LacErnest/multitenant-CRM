import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule, Routes } from '@angular/router';
import { SharedModule } from '../../../../shared/shared.module';
import { FormsModule, ReactiveFormsModule } from '@angular/forms';
import { NgSelectModule } from '@ng-select/ng-select';
import { NgxCurrencyModule } from 'ngx-currency';
import { EmailConfigurationListComponent } from './containers/email-configuration-list/email-configuration-list.component';
import { EmailTemplateListComponent } from './containers/email-template-list/email-template-list.component';
import { DesignTemplateListComponent } from './containers/design-template-list/design-template-list.component';

import { EmailManagementComponent } from './containers/email-management/email-management.component';
import { SmtpSettingsComponent } from './containers/smtp-settings/smtp-settings.component';
import { SmtpSettingResolver } from './resolvers/smtp-setting.resolver';
import { SmtpSettingsResolver } from './resolvers/smtp-settings.resolver';
import { EmailTemplatesResolver } from './resolvers/email-templates.resolver';
import { DesignTemplatesResolver } from './resolvers/design-templates.resolver';

import { RoleGuard } from 'src/app/core/guards/role.guard';
import { DatatablePreferencesResolver } from 'src/app/shared/resolvers/datatable-preferences.resolver';
import { TablePreferenceType } from 'src/app/shared/enums/table-preference-type.enum';
import { settingsRoutesRoles } from 'src/app/views/settings/settings-roles';

const routes: Routes = [
  {
    path: '',
    component: EmailManagementComponent,
    canActivate: [RoleGuard],
    data: {
      roles: settingsRoutesRoles.emailManagement.valueOf(),
    },
    resolve: {},
    children: [
      {
        path: 'configurations',
        component: EmailConfigurationListComponent,
        data: {
          entity: TablePreferenceType.SMTP_SETTINGS.valueOf(),
        },
        resolve: {
          tablePreferences: DatatablePreferencesResolver,
          smtp_settings: SmtpSettingsResolver,
        },
      },
      {
        path: 'templates',
        component: EmailTemplateListComponent,
        data: {
          entity: TablePreferenceType.EMAIL_TEMPLATES.valueOf(),
        },
        resolve: {
          tablePreferences: DatatablePreferencesResolver,
          email_templates: EmailTemplatesResolver,
        },
      },
      {
        path: 'design_templates',
        component: DesignTemplateListComponent,
        data: {
          entity: TablePreferenceType.DESIGN_TEMPLATES.valueOf(),
        },
        resolve: {
          tablePreferences: DatatablePreferencesResolver,
          design_templates: DesignTemplatesResolver,
        },
      },
      { path: '', redirectTo: 'templates', pathMatch: 'full' },
    ],
  },
  {
    path: 'configurations/smtp_settings/create',
    component: SmtpSettingsComponent,
    canActivate: [RoleGuard],
    resolve: {},
    data: {
      roles: settingsRoutesRoles.emailManagement.valueOf(),
    },
  },
  {
    path: 'configurations/smtp_settings/:smtp_setting_id/edit',
    component: SmtpSettingsComponent,
    canActivate: [RoleGuard],
    resolve: {
      settings: SmtpSettingResolver,
    },
    data: {
      roles: settingsRoutesRoles.emailManagement.valueOf(),
    },
  },
];

@NgModule({
  declarations: [
    EmailConfigurationListComponent,
    EmailTemplateListComponent,
    EmailManagementComponent,
    SmtpSettingsComponent,
    DesignTemplateListComponent,
  ],
  imports: [
    CommonModule,
    RouterModule.forChild(routes),
    SharedModule,
    NgSelectModule,
    NgxCurrencyModule,
    ReactiveFormsModule,
    FormsModule,
  ],
})
export class EmailManagementModule {}
