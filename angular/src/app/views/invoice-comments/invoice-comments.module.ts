import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule, Routes } from '@angular/router';
import { InvoicePaymentListComponent } from './containers/invoice-comment/invoice-comment-list.component';
import { SharedModule } from '../../shared/shared.module';
import { CurrencyCodeEnumResolver } from '../../shared/resolvers/currency-code-enum.resolver';
import { DatatablePreferencesResolver } from '../../shared/resolvers/datatable-preferences.resolver';
import { InvoiceStatusEnumResolver } from '../../shared/resolvers/invoice-status-enum.resolver';
import { InvoiceTypeEnumResolver } from '../../shared/resolvers/invoice-type-enum.resolver';
import { InvoicePaymentsResolver } from './resolvers/invoice-payments.resolver';
import { PreferenceType } from '../../shared/enums/preference-type.enum';
import { CreditNoteStatusEnumResolver } from '../../shared/resolvers/credit-note-status-enum.resolver';
import { CreditNoteTypeEnumResolver } from '../../shared/resolvers/credit-note-type-enum.resolver';

const routes: Routes = [
  {
    path: ':invoice_id',
    component: InvoicePaymentListComponent,
    resolve: {
      currencyCodes: CurrencyCodeEnumResolver,
      invoice_payments: InvoicePaymentsResolver,
      table_preferences: DatatablePreferencesResolver,
      creditNoteStatusEnum: CreditNoteStatusEnumResolver,
      creditNoteTypeEnum: CreditNoteTypeEnumResolver,
    },
    data: {
      preferences: PreferenceType.USERS,
      entity: 6,
    },
  },
  {
    path: '',
    component: InvoicePaymentListComponent,
    resolve: {
      currencyCodes: CurrencyCodeEnumResolver,
      invoice_payments: InvoicePaymentsResolver,
      table_preferences: DatatablePreferencesResolver,
      creditNoteStatusEnum: CreditNoteStatusEnumResolver,
      creditNoteTypeEnum: CreditNoteTypeEnumResolver,
    },
    data: {
      preferences: PreferenceType.USERS,
      entity: 6,
    },
  },
];

@NgModule({
  declarations: [InvoicePaymentListComponent],
  imports: [CommonModule, RouterModule.forChild(routes), SharedModule],
})
export class InvoicePaymentsModule {}
