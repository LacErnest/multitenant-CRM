import { TextFieldModule } from '@angular/cdk/text-field';
import { CommonModule } from '@angular/common';
import { NgModule } from '@angular/core';

import { FormsModule, ReactiveFormsModule } from '@angular/forms';
import { RouterModule, Routes } from '@angular/router';
import { OwlDateTimeModule } from '@danielmoncada/angular-datetime-picker';

import { ClickOutsideModule } from 'ng-click-outside';
import { PdfViewerModule } from 'ng2-pdf-viewer';
import { NgxCurrencyModule } from 'ngx-currency';

import { RoleGuard } from 'src/app/core/guards/role.guard';
import { ServicesListComponent } from 'src/app/shared/components/services-list/services-list.component';

import { PreferenceType } from 'src/app/shared/enums/preference-type.enum';
import { DatatablePreferencesResolver } from 'src/app/shared/resolvers/datatable-preferences.resolver';
import { UserRoleEnumResolver } from 'src/app/shared/resolvers/user-role-enum.resolver';
import { SharedModule } from 'src/app/shared/shared.module';

import { SettingsNavComponent } from 'src/app/views/settings/components/settings-nav/settings-nav.component';
import { CompanyTemplatesComponent } from './modules/templates-types/containers/company-templates/company-templates.component';

import { SettingsComponent } from 'src/app/views/settings/containers/settings/settings.component';
import { CompanyLegalEntitiesResolver } from 'src/app/views/settings/modules/company-legal-entities/resolvers/company-legal-entities.resolver';
import { LoansResolver } from 'src/app/views/settings/resolvers/loans.resolver';
import { CompanySettingsResolver } from 'src/app/views/settings/resolvers/company-settings.resolver';
import { ServicesResolver } from 'src/app/views/settings/resolvers/services.resolver';
import { CompanyTemplatesResolver } from './modules/templates-types/resolvers/company-templates.resolver';
import { CompanyTemplateResolver } from './modules/templates-types/resolvers/company-template.resolver';
import { settingsRoutesRoles } from 'src/app/views/settings/settings-roles';
import { LoansComponent } from './components/loans/loans.component';
import { SalesCommissionsComponent } from './components/sales-commissions/sales-commissions.component';
import { PriceModifiersComponent } from './components/price-modifiers/price-modifiers.component';
import { LoanModalComponent } from './components/loan-modal/loan-modal.component';
import { TemplatesTypesComponent } from './modules/templates-types/containers/templates-types/templates-types.component';
import { TemplatesTypesResolver } from './modules/templates-types/resolvers/templates-types.resolver';
import { TemplateTypeModalComponent } from './modules/templates-types/template-type-modal/template-type-modal.component';
import { NotificationSettingComponent } from './components/notification-settings/notification-settings.component';
import { CompanyNotificationSettingsResolver } from 'src/app/views/settings/resolvers/company-notification-settings.resolver';
import { NgSelectModule } from '@ng-select/ng-select';

const routes: Routes = [
  {
    path: '',
    component: SettingsComponent,
    children: [
      {
        path: '',
        redirectTo: 'templates',
      },
      {
        path: 'templates',
        // component: TemplatesTypesComponent,
        resolve: {
          templateTypes: TemplatesTypesResolver,
        },
        canActivate: [RoleGuard],
        data: {
          roles: settingsRoutesRoles.templates.valueOf(),
        },
        children: [
          {
            path: '',
            component: TemplatesTypesComponent,
          },
          {
            path: ':template_id/view',
            component: CompanyTemplatesComponent,
            resolve: {
              companyTemplates: CompanyTemplatesResolver,
              companyTemplateModel: CompanyTemplateResolver,
            },
            // canActivate: [
            //   RoleGuard
            // ],
            // data: {
            //   roles: settingsRoutesRoles.templates.valueOf()
            // },
          },
        ],
      },
      {
        path: 'services',
        component: ServicesListComponent,
        canActivate: [RoleGuard],
        data: {
          preferences: PreferenceType.USERS.valueOf(),
          entity: 14,
          roles: settingsRoutesRoles.services.valueOf(),
        },
        resolve: {
          services: ServicesResolver,
          tablePreferences: DatatablePreferencesResolver,
        },
      },
      {
        path: 'users',
        loadChildren: () =>
          import('./modules/users/users.module').then(mod => mod.UsersModule),
        resolve: {
          userRole: UserRoleEnumResolver,
        },
        canActivate: [RoleGuard],
        data: {
          roles: settingsRoutesRoles.users.valueOf(),
        },
      },
      {
        path: 'loans',
        component: LoansComponent, // TODO: create a module
        canActivate: [RoleGuard],
        resolve: {
          loans: LoansResolver,
        },
        data: {
          roles: settingsRoutesRoles.loans.valueOf(),
        },
      },
      {
        path: 'rent_costs',
        loadChildren: () =>
          import('./modules/rent-costs/rent-costs.module').then(
            mod => mod.RentCostsModule
          ),
        canActivate: [RoleGuard],
        data: {
          roles: settingsRoutesRoles.rentCosts.valueOf(),
        },
      },
      {
        path: 'sales_commissions',
        component: SalesCommissionsComponent, // TODO: create a module
        canActivate: [RoleGuard],
        resolve: {
          settings: CompanySettingsResolver,
        },
        data: {
          roles: settingsRoutesRoles.salesCommissions.valueOf(),
        },
      },
      {
        path: 'price_modifiers',
        component: PriceModifiersComponent, // TODO: create a module
        canActivate: [RoleGuard],
        resolve: {
          settings: CompanySettingsResolver,
        },
        data: {
          roles: settingsRoutesRoles.salesCommissions.valueOf(),
        },
      },
      {
        path: 'legal_entities',
        loadChildren: () =>
          import(
            './modules/company-legal-entities/company-legal-entities.module'
          ).then(mod => mod.CompanyLegalEntitiesModule),
        canActivate: [RoleGuard],
        data: {
          roles: settingsRoutesRoles.companyLegalEntities.valueOf(),
        },
        resolve: {
          companyLegalEntities: CompanyLegalEntitiesResolver,
        },
      },
      {
        path: 'notifications',
        component: NotificationSettingComponent, // TODO: create a module
        canActivate: [RoleGuard],
        resolve: {
          settings: CompanyNotificationSettingsResolver,
        },
        data: {
          roles: settingsRoutesRoles.notificationSettings.valueOf(),
        },
      },
      {
        path: 'email_management',
        loadChildren: () =>
          import('./modules/email-management/email-management.module').then(
            mod => mod.EmailManagementModule
          ),
        resolve: {
          userRole: UserRoleEnumResolver,
        },
        canActivate: [RoleGuard],
        data: {
          roles: settingsRoutesRoles.emailManagement.valueOf(),
        },
      },
    ],
  },
  {
    path: 'email_templates',
    loadChildren: () =>
      import('./modules/email-management/email-template.module').then(
        mod => mod.EmailTemplateModule
      ),
    resolve: {
      userRole: UserRoleEnumResolver,
    },
    canActivate: [RoleGuard],
    data: {
      roles: settingsRoutesRoles.emailManagement.valueOf(),
    },
  },
  {
    path: 'design_templates',
    loadChildren: () =>
      import('./modules/email-management/design-template.module').then(
        mod => mod.DesignTemplateModule
      ),
    resolve: {
      userRole: UserRoleEnumResolver,
    },
    canActivate: [RoleGuard],
    data: {
      roles: settingsRoutesRoles.emailManagement.valueOf(),
    },
  },
];

@NgModule({
  declarations: [
    SettingsComponent,
    SettingsNavComponent,
    CompanyTemplatesComponent,
    LoansComponent,
    LoanModalComponent,
    TemplatesTypesComponent,
    TemplateTypeModalComponent,
    SalesCommissionsComponent,
    NotificationSettingComponent,
    PriceModifiersComponent,
  ],
  imports: [
    CommonModule,
    RouterModule.forChild(routes),
    PdfViewerModule,
    SharedModule,
    ReactiveFormsModule,
    NgxCurrencyModule,
    TextFieldModule,
    ClickOutsideModule,
    OwlDateTimeModule,
    NgSelectModule,
    FormsModule,
  ],
})
export class SettingsModule {}
