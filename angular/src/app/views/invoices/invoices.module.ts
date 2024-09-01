import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule, Routes } from '@angular/router';
import { InvoiceListComponent } from './containers/invoice-list/invoice-list.component';
import { SharedModule } from '../../shared/shared.module';
import { CurrencyCodeEnumResolver } from '../../shared/resolvers/currency-code-enum.resolver';
import { DatatablePreferencesResolver } from '../../shared/resolvers/datatable-preferences.resolver';
import { InvoiceStatusEnumResolver } from '../../shared/resolvers/invoice-status-enum.resolver';
import { InvoiceTypeEnumResolver } from '../../shared/resolvers/invoice-type-enum.resolver';
import { InvoicesResolver } from './resolvers/invoices.resolver';
import { PreferenceType } from '../../shared/enums/preference-type.enum';
import { CreditNoteStatusEnumResolver } from '../../shared/resolvers/credit-note-status-enum.resolver';
import { CreditNoteTypeEnumResolver } from '../../shared/resolvers/credit-note-type-enum.resolver';
import { ProjectTypeEnumResolver } from '../../shared/resolvers/project-type-enum.resolver';

const routes: Routes = [
  {
    path: '',
    component: InvoiceListComponent,
    resolve: {
      currencyCodes: CurrencyCodeEnumResolver,
      invoices: InvoicesResolver,
      invoiceStatusEnum: InvoiceStatusEnumResolver,
      invoiceTypeEnum: InvoiceTypeEnumResolver,
      table_preferences: DatatablePreferencesResolver,
      creditNoteStatusEnum: CreditNoteStatusEnumResolver,
      creditNoteTypeEnum: CreditNoteTypeEnumResolver,
      projectTypeEnum: ProjectTypeEnumResolver,
    },
    data: {
      preferences: PreferenceType.USERS,
      entity: 6,
    },
  },
  {
    path: 'analytics',
    component: InvoiceListComponent,
    resolve: {
      currencyCodes: CurrencyCodeEnumResolver,
      invoices: InvoicesResolver,
      invoiceStatusEnum: InvoiceStatusEnumResolver,
      invoiceTypeEnum: InvoiceTypeEnumResolver,
      table_preferences: DatatablePreferencesResolver,
      creditNoteStatusEnum: CreditNoteStatusEnumResolver,
      creditNoteTypeEnum: CreditNoteTypeEnumResolver,
    },
    data: {
      preferences: PreferenceType.ANALYTICS,
      entity: 6,
    },
  },
];

@NgModule({
  declarations: [InvoiceListComponent],
  imports: [CommonModule, RouterModule.forChild(routes), SharedModule],
})
export class InvoicesModule {}
