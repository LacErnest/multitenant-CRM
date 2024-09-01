import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule, Routes } from '@angular/router';
import { EmployeesListComponent } from './containers/employees-list/employees-list.component';
import { SharedModule } from '../../shared/shared.module';
import { EmployeeFormComponent } from './containers/employee-form/employee-form.component';
import { DatatablePreferencesResolver } from '../../shared/resolvers/datatable-preferences.resolver';
import { PreferenceType } from '../../shared/enums/preference-type.enum';
import { EmployeeResolver } from './resolvers/employee.resolver';
import { EmployeesResolver } from './resolvers/employees.resolver';
import { FormsModule, ReactiveFormsModule } from '@angular/forms';
import { NgSelectModule } from '@ng-select/ng-select';
import { ClickOutsideModule } from 'ng-click-outside';
import { NgxCurrencyModule } from 'ngx-currency';
import { OwlDateTimeModule } from '@danielmoncada/angular-datetime-picker';
import { EmployeesAssignListComponent } from './containers/employee-assign-list/employees-assign-list.component';
import { EmployeeAssignModalComponent } from './containers/employee-assign-modal/employee-assign-modal.component';
import { EmployeePurchaseOrderListComponent } from './components/employee-purchase-order-list/employee-purchase-order-list.component';
import { PurchaseOrderStatusEnumResolver } from '../../shared/resolvers/purchase-order-status-enum.resolver';
import { InvoiceStatusEnumResolver } from '../../shared/resolvers/invoice-status-enum.resolver';
import { InvoiceTypeEnumResolver } from '../../shared/resolvers/invoice-type-enum.resolver';
import { TablePreferenceType } from '../../shared/enums/table-preference-type.enum';
import { EmployeeServicesResolver } from './resolvers/employee-services.resolver';
import { EmployeeHistoriesListComponent } from './components/employee-histories-list/employee-histories-list.component';

const routes: Routes = [
  {
    path: '',
    component: EmployeesListComponent,
    resolve: {
      employees: EmployeesResolver,
      tablePreferences: DatatablePreferencesResolver,
    },
    data: {
      preferences: PreferenceType.USERS,
      entity: 13,
    },
  },
  {
    path: 'active',
    component: EmployeesAssignListComponent,
  },
  {
    path: 'create',
    component: EmployeeFormComponent,
  },
  {
    path: ':employee_id',
    data: {
      entity: TablePreferenceType.EMPLOYEE_HISTORIES.valueOf(),
    },
    children: [
      {
        path: 'edit',
        component: EmployeeFormComponent,
        resolve: {
          employee: EmployeeResolver,
          tablePreferences: DatatablePreferencesResolver,
          purchaseOrderStatuses: PurchaseOrderStatusEnumResolver,
          invoiceStatusEnum: InvoiceStatusEnumResolver,
          invoiceTypeEnum: InvoiceTypeEnumResolver,
        },
      },
    ],
  },
];

@NgModule({
  declarations: [
    EmployeesListComponent,
    EmployeeFormComponent,
    EmployeesAssignListComponent,
    EmployeeAssignModalComponent,
    EmployeePurchaseOrderListComponent,
    EmployeeHistoriesListComponent,
  ],
  imports: [
    CommonModule,
    RouterModule.forChild(routes),
    SharedModule,
    ReactiveFormsModule,
    NgSelectModule,
    ClickOutsideModule,
    NgxCurrencyModule,
    FormsModule,
    OwlDateTimeModule,
  ],
})
export class EmployeesModule {}
