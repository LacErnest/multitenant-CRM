import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule, Routes } from '@angular/router';
import { TaxRatesComponent } from 'src/app/views/legal-entities/modules/tax-rates/containers/tax-rates/tax-rates.component';
import { TaxRateFormComponent } from 'src/app/views/legal-entities/modules/tax-rates/containers/tax-rate-form/tax-rate-form.component';
import { TaxRatesResolver } from 'src/app/views/legal-entities/modules/tax-rates/resolvers/tax-rates.resolver';
import { TaxRateResolver } from 'src/app/views/legal-entities/modules/tax-rates/resolvers/tax-rate.resolver';
import { SharedModule } from 'src/app/shared/shared.module';
import { ReactiveFormsModule } from '@angular/forms';
import { XeroTaxRatesResolver } from 'src/app/views/legal-entities/modules/tax-rates/resolvers/xero-tax-rates.resolver';
import { NgSelectModule } from '@ng-select/ng-select';
import { OwlDateTimeModule } from '@danielmoncada/angular-datetime-picker';
import { NgxCurrencyModule } from 'ngx-currency';
import { XeroLinkedResolver } from 'src/app/views/legal-entities/modules/xero-link/resolvers/xero-linked.resolver';

const routes: Routes = [
  {
    path: '',
    component: TaxRatesComponent,
    resolve: {
      taxRates: TaxRatesResolver,
    },
  },
  {
    path: 'create',
    component: TaxRateFormComponent,
    resolve: {
      xeroTaxRates: XeroTaxRatesResolver,
    },
  },
  {
    path: ':id',
    component: TaxRateFormComponent,
    resolve: {
      taxRate: TaxRateResolver,
      xeroTaxRates: XeroTaxRatesResolver,
    },
  },
  {
    path: '**',
    redirectTo: '',
    pathMatch: 'full',
  },
];

@NgModule({
  declarations: [TaxRatesComponent, TaxRateFormComponent],
  imports: [
    CommonModule,
    RouterModule.forChild(routes),
    SharedModule,
    ReactiveFormsModule,
    NgSelectModule,
    OwlDateTimeModule,
    NgxCurrencyModule,
  ],
})
export class TaxRatesModule {}
