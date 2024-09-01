import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { QuoteListComponent } from './containers/quote-list/quote-list.component';
import { RouterModule, Routes } from '@angular/router';
import { SharedModule } from '../../shared/shared.module';
import { CurrencyCodeEnumResolver } from '../../shared/resolvers/currency-code-enum.resolver';
import { QuotesResolver } from './resolvers/quotes.resolver';
import { DatatablePreferencesResolver } from '../../shared/resolvers/datatable-preferences.resolver';
import { QuoteStatusEnumResolver } from '../../shared/resolvers/quote-status-enum.resolver';
import { PreferenceType } from '../../shared/enums/preference-type.enum';
import { ProjectTypeEnumResolver } from 'src/app/shared/resolvers/project-type-enum.resolver';

const routes: Routes = [
  {
    path: '',
    component: QuoteListComponent,
    resolve: {
      currencyCodes: CurrencyCodeEnumResolver,
      quotes: QuotesResolver,
      table_preferences: DatatablePreferencesResolver,
      quoteStatusEnum: QuoteStatusEnumResolver,
      projectTypeEnum: ProjectTypeEnumResolver,
    },
    data: {
      preferences: PreferenceType.USERS,
      entity: 4,
    },
  },
  {
    path: 'analytics',
    component: QuoteListComponent,
    resolve: {
      currencyCodes: CurrencyCodeEnumResolver,
      quotes: QuotesResolver,
      table_preferences: DatatablePreferencesResolver,
      quoteStatusEnum: QuoteStatusEnumResolver,
    },
    data: {
      preferences: PreferenceType.ANALYTICS,
      entity: 4,
    },
  },
];

@NgModule({
  declarations: [QuoteListComponent],
  imports: [CommonModule, RouterModule.forChild(routes), SharedModule],
})
export class QuotesModule {}
