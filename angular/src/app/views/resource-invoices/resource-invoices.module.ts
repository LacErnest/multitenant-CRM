import { CommonModule } from '@angular/common';
import { NgModule } from '@angular/core';
import { RouterModule, Routes } from '@angular/router';
import { PreferenceType } from 'src/app/shared/enums/preference-type.enum';
import { TablePreferenceType } from 'src/app/shared/enums/table-preference-type.enum';
import { CurrencyCodeEnumResolver } from 'src/app/shared/resolvers/currency-code-enum.resolver';
import { DatatablePreferencesResolver } from 'src/app/shared/resolvers/datatable-preferences.resolver';
import { InvoiceStatusEnumResolver } from 'src/app/shared/resolvers/invoice-status-enum.resolver';
import { SharedModule } from 'src/app/shared/shared.module';
import { ResourceInvoiceListComponent } from 'src/app/views/resource-invoices/containers/resource-invoice-list/resource-invoice-list.component';
import { ResourceInvoicesResolver } from 'src/app/views/resource-invoices/resolvers/resource-invoices.resolver';

const routes: Routes = [
  {
    path: '',
    component: ResourceInvoiceListComponent,
    resolve: {
      currencyCodes: CurrencyCodeEnumResolver,
      invoiceStatusEnum: InvoiceStatusEnumResolver,
      resourceInvoices: ResourceInvoicesResolver,
      table_preferences: DatatablePreferencesResolver,
    },
    data: {
      preferences: PreferenceType.USERS.valueOf(),
      entity: TablePreferenceType.RESOURCE_INVOICES.valueOf(),
    },
  },
];

@NgModule({
  declarations: [ResourceInvoiceListComponent],
  imports: [CommonModule, RouterModule.forChild(routes), SharedModule],
})
export class ResourceInvoicesModule {}
