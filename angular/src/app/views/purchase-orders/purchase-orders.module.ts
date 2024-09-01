import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule, Routes } from '@angular/router';
import { PurchaseOrderListComponent } from './containers/purchase-order-list/purchase-order-list.component';
import { SharedModule } from '../../shared/shared.module';
import { PurchaseOrdersResolver } from './resolvers/purchase-orders.resolver';
import { DatatablePreferencesResolver } from '../../shared/resolvers/datatable-preferences.resolver';
import { CurrencyCodeEnumResolver } from '../../shared/resolvers/currency-code-enum.resolver';
import { PreferenceType } from '../../shared/enums/preference-type.enum';
import { ProjectTypeEnumResolver } from 'src/app/shared/resolvers/project-type-enum.resolver';

const routes: Routes = [
  {
    path: '',
    component: PurchaseOrderListComponent,
    data: {
      preferences: PreferenceType.USERS,
      entity: 7,
    },
    resolve: {
      currencyCodes: CurrencyCodeEnumResolver,
      purchaseOrders: PurchaseOrdersResolver,
      table_preferences: DatatablePreferencesResolver,
      projectTypeEnum: ProjectTypeEnumResolver,
    },
  },
  {
    path: 'analytics',
    component: PurchaseOrderListComponent,
    data: {
      preferences: PreferenceType.ANALYTICS,
      entity: 7,
    },
    resolve: {
      currencyCodes: CurrencyCodeEnumResolver,
      purchaseOrders: PurchaseOrdersResolver,
      table_preferences: DatatablePreferencesResolver,
    },
  },
];

@NgModule({
  declarations: [PurchaseOrderListComponent],
  imports: [CommonModule, RouterModule.forChild(routes), SharedModule],
})
export class PurchaseOrdersModule {}
