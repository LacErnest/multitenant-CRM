import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule, Routes } from '@angular/router';
import { SharedModule } from '../../../../shared/shared.module';
import { FormsModule, ReactiveFormsModule } from '@angular/forms';
import { NgSelectModule } from '@ng-select/ng-select';
import { NgxCurrencyModule } from 'ngx-currency';
import { DesignTemplateFormComponent } from './containers/design-template-form/design-template-form.component';
import { DesignTemplateResolver } from './resolvers/design-template.resolver';

import { RoleGuard } from 'src/app/core/guards/role.guard';
import { EmailEditorModule } from 'angular-email-editor';
import { EmailManagementSharedModule } from './email-management-shared.module';
import { settingsRoutesRoles } from '../../settings-roles';

const routes: Routes = [
  {
    path: 'create',
    component: DesignTemplateFormComponent,
    canActivate: [RoleGuard],
    data: {
      roles: settingsRoutesRoles.emailManagement.valueOf(),
    },
  },
  {
    path: ':design_template_id/edit',
    component: DesignTemplateFormComponent,
    canActivate: [RoleGuard],
    resolve: {
      designTemplate: DesignTemplateResolver,
    },
    data: {
      roles: settingsRoutesRoles.emailManagement.valueOf(),
    },
  },
];

@NgModule({
  declarations: [DesignTemplateFormComponent],
  exports: [],
  imports: [
    CommonModule,
    RouterModule.forChild(routes),
    SharedModule,
    ReactiveFormsModule,
    NgSelectModule,
    NgxCurrencyModule,
    FormsModule,
    EmailEditorModule,
    EmailManagementSharedModule,
  ],
})
export class DesignTemplateModule {}
