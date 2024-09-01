import { CommonModule } from '@angular/common';
import { NgModule } from '@angular/core';
import { RouterModule, Routes } from '@angular/router';
import { NgxDatatableModule } from '@swimlane/ngx-datatable';
import { SharedModule } from 'src/app/shared/shared.module';
import { ProjectFormWrapperComponent } from 'src/app/views/projects/containers/project-form-wrapper/project-form-wrapper.component';
import { ProjectModule } from 'src/app/views/projects/modules/project/project.module';
import { ProjectPurchaseOrderFormWrapperComponent } from 'src/app/views/projects/containers/project-purchase-order-form-wrapper/project-purchase-order-form-wrapper.component';
import { CompanySettingsResolver } from 'src/app/views/settings/resolvers/company-settings.resolver';
import { ProjectResolver } from './modules/project/resolvers/project.resolver';

const routes: Routes = [
  {
    path: 'create',
    component: ProjectFormWrapperComponent,
    resolve: {
      settings: CompanySettingsResolver,
    },
  },
  {
    path: 'create_purchase_order',
    component: ProjectPurchaseOrderFormWrapperComponent,
  },
  {
    path: ':project_id',
    loadChildren: () =>
      import('./modules/project/project.module').then(mod => mod.ProjectModule),
    resolve: {
      project: ProjectResolver,
    },
  },
  {
    path: '**',
    pathMatch: 'full',
    redirectTo: '/dashboard',
  },
];

@NgModule({
  declarations: [
    ProjectFormWrapperComponent,
    ProjectPurchaseOrderFormWrapperComponent,
  ],
  imports: [
    CommonModule,
    RouterModule.forChild(routes),
    SharedModule,
    NgxDatatableModule,
    ProjectModule,
  ],
})
export class ProjectsModule {}
