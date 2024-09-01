import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { CountryEnumResolver } from 'src/app/shared/resolvers/country-enum.resolver';
import { ResourceFormExternalComponent } from './containers/resource-form-external/resource-form-external.component';
import { NgSelectModule } from '@ng-select/ng-select';
import { SharedModule } from '../../shared/shared.module';
import { ReactiveFormsModule } from '@angular/forms';
import { RouterModule, Routes } from '@angular/router';
import { ExternalContainerComponent } from './containers/external-container/external-container.component';
import { InvoiceStatusEnumResolver } from '../../shared/resolvers/invoice-status-enum.resolver';
import { CurrencyCodeEnumResolver } from '../../shared/resolvers/currency-code-enum.resolver';
import { ExternalAccessResourceResolver } from './resolvers/external-access-resource.resolver';
import { PurchaseOrderStatusEnumResolver } from '../../shared/resolvers/purchase-order-status-enum.resolver';
import { ResourceStatusEnumResolver } from '../../shared/resolvers/resource-status-enum.resolver';
import { ExternalAccessTablePreferencesResolver } from './resolvers/external-access-table-preferences.resolver';
import { ResourcePurchaseOrderListExternalComponent } from './components/resource-purchase-order-list-external/resource-purchase-order-list-external.component';
import { NgxCurrencyModule } from 'ngx-currency';
import { ExternalAccessGuard } from '../../core/guards/external-access.guard';
import { TablePreferenceType } from '../../shared/enums/table-preference-type.enum';

const routes: Routes = [
  {
    path: '',
    component: ExternalContainerComponent,
    children: [
      {
        path: ':company_id/:resource_id/:token',
        component: ResourceFormExternalComponent,
        canActivate: [ExternalAccessGuard],
        resolve: {
          countryEnum: CountryEnumResolver,
          currencyCodes: CurrencyCodeEnumResolver,
          invoiceStatus: InvoiceStatusEnumResolver,
          resourceStatus: ResourceStatusEnumResolver,
          purchaseOrderStatusEnum: PurchaseOrderStatusEnumResolver,
          tablePreferences: ExternalAccessTablePreferencesResolver,
          resource: ExternalAccessResourceResolver,
        },
        data: {
          entity: TablePreferenceType.EXTERNAL_ACCESS_PURCHASE_ORDERS.valueOf(),
        },
      },
    ],
  },
];

@NgModule({
  declarations: [
    ResourceFormExternalComponent,
    ExternalContainerComponent,
    ResourcePurchaseOrderListExternalComponent,
  ],
  imports: [
    CommonModule,
    NgSelectModule,
    SharedModule,
    RouterModule.forChild(routes),
    ReactiveFormsModule,
    NgxCurrencyModule,
  ],
})
export class ExternalAccessModule {}
