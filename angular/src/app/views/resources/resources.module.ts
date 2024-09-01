import { CommonModule } from '@angular/common';
import { NgModule } from '@angular/core';
import { ReactiveFormsModule } from '@angular/forms';
import { RouterModule, Routes } from '@angular/router';
import { NgSelectModule } from '@ng-select/ng-select';
import { ClickOutsideModule } from 'ng-click-outside';
import { NgxCurrencyModule } from 'ngx-currency';
import { PreferenceType } from 'src/app/shared/enums/preference-type.enum';
import { TablePreferenceType } from 'src/app/shared/enums/table-preference-type.enum';
import { DatatablePreferencesResolver } from 'src/app/shared/resolvers/datatable-preferences.resolver';
import { InvoiceStatusEnumResolver } from 'src/app/shared/resolvers/invoice-status-enum.resolver';
import { InvoiceTypeEnumResolver } from 'src/app/shared/resolvers/invoice-type-enum.resolver';
import { PurchaseOrderStatusEnumResolver } from 'src/app/shared/resolvers/purchase-order-status-enum.resolver';
import { SharedModule } from 'src/app/shared/shared.module';
import { ResourceExportModalComponent } from 'src/app/views/resources/components/resource-export-modal/resource-export-modal.component';
import { ResourcePurchaseOrderListComponent } from 'src/app/views/resources/components/resource-purchase-order-list/resource-purchase-order-list.component';
import { ResourceFormComponent } from 'src/app/views/resources/containers/resource-form/resource-form.component';
import { ResourceListComponent } from 'src/app/views/resources/containers/resource-list/resource-list.component';
import { ResourceServicesResolver } from 'src/app/views/resources/resolvers/resource-services.resolver';
import { ResourceResolver } from 'src/app/views/resources/resolvers/resource.resolver';
import { ResourcesResolver } from 'src/app/views/resources/resolvers/resources.resolver';

const routes: Routes = [
  {
    path: '',
    component: ResourceListComponent,
    resolve: {
      resources: ResourcesResolver,
      tablePreferences: DatatablePreferencesResolver,
    },
    data: {
      preferences: PreferenceType.USERS.valueOf(),
      entity: TablePreferenceType.RESOURCES.valueOf(),
    },
  },
  {
    path: 'analytics',
    component: ResourceListComponent,
    resolve: {
      resources: ResourcesResolver,
      tablePreferences: DatatablePreferencesResolver,
    },
    data: {
      preferences: PreferenceType.ANALYTICS.valueOf(),
      entity: TablePreferenceType.RESOURCES.valueOf(),
    },
  },
  {
    path: 'create',
    component: ResourceFormComponent,
    resolve: {
      tablePreferences: DatatablePreferencesResolver,
      invoiceStatusEnum: InvoiceStatusEnumResolver,
      invoiceTypeEnum: InvoiceTypeEnumResolver,
    },
    data: {
      entity: TablePreferenceType.RESOURCE_SERVICES.valueOf(),
    },
  },
  {
    path: ':resource_id',
    resolve: {
      resource: ResourceResolver,
      services: ResourceServicesResolver,
      tablePreferences: DatatablePreferencesResolver,
      purchaseOrderStatuses: PurchaseOrderStatusEnumResolver,
      invoiceStatusEnum: InvoiceStatusEnumResolver,
      invoiceTypeEnum: InvoiceTypeEnumResolver,
    },
    data: {
      entity: TablePreferenceType.RESOURCE_SERVICES.valueOf(),
    },
    children: [
      {
        path: 'edit',
        component: ResourceFormComponent,
      },
      {
        path: '',
        pathMatch: 'full',
        redirectTo: 'edit',
      },
    ],
  },
];

@NgModule({
  declarations: [
    ResourceListComponent,
    ResourceFormComponent,
    ResourceExportModalComponent,
    ResourcePurchaseOrderListComponent,
  ],
  imports: [
    CommonModule,
    RouterModule.forChild(routes),
    SharedModule,
    ReactiveFormsModule,
    NgSelectModule,
    ClickOutsideModule,
    NgxCurrencyModule,
  ],
})
export class ResourcesModule {}
