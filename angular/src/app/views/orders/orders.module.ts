import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { OrderListComponent } from './containers/order-list/order-list.component';
import { RouterModule, Routes } from '@angular/router';
import { SharedModule } from '../../shared/shared.module';
import { CurrencyCodeEnumResolver } from '../../shared/resolvers/currency-code-enum.resolver';
import { DatatablePreferencesResolver } from '../../shared/resolvers/datatable-preferences.resolver';
import { OrdersResolver } from './resolvers/orders.resolver';
import { OrderStatusEnumResolver } from '../../shared/resolvers/order-status-enum.resolver';
import { PreferenceType } from '../../shared/enums/preference-type.enum';
import { ProjectTypeEnumResolver } from 'src/app/shared/resolvers/project-type-enum.resolver';

const routes: Routes = [
  {
    path: '',
    component: OrderListComponent,
    data: {
      preferences: PreferenceType.USERS,
      entity: 5,
    },
    resolve: {
      currencyCodes: CurrencyCodeEnumResolver,
      orders: OrdersResolver,
      orderStatuses: OrderStatusEnumResolver,
      table_preferences: DatatablePreferencesResolver,
      projectTypeEnum: ProjectTypeEnumResolver,
    },
  },
  {
    path: 'analytics',
    component: OrderListComponent,
    data: {
      preferences: PreferenceType.ANALYTICS,
      entity: 5,
    },
    resolve: {
      currencyCodes: CurrencyCodeEnumResolver,
      orders: OrdersResolver,
      orderStatuses: OrderStatusEnumResolver,
      table_preferences: DatatablePreferencesResolver,
    },
  },
];

@NgModule({
  declarations: [OrderListComponent],
  imports: [CommonModule, RouterModule.forChild(routes), SharedModule],
})
export class OrdersModule {}
