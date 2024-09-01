import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ReactiveFormsModule } from '@angular/forms';
import { RouterModule, Routes } from '@angular/router';
import { OwlDateTimeModule } from '@danielmoncada/angular-datetime-picker';
import { NgxCurrencyModule } from 'ngx-currency';
import { SharedModule } from 'src/app/shared/shared.module';
import { DatatablePreferencesResolver } from '../../../../shared/resolvers/datatable-preferences.resolver';
import { RentCostResolver } from 'src/app/views/settings/modules/rent-costs/resolvers/rent-cost.resolver';
import { RentCostsResolver } from 'src/app/views/settings/modules/rent-costs/resolvers/rent-costs.resolver';
import { RentCostsComponent } from './containers/rent-costs/rent-costs.component';
import { RentCostFormComponent } from './containers/rent-cost-form/rent-cost-form.component';

const routes: Routes = [
  {
    path: '',
    component: RentCostsComponent,
    data: {
      entity: 24,
    },
    resolve: {
      table_preferences: DatatablePreferencesResolver,
      rentCosts: RentCostsResolver,
    },
  },
  {
    path: 'create',
    component: RentCostFormComponent,
  },
  {
    path: ':id',
    component: RentCostFormComponent,
    resolve: {
      rentCost: RentCostResolver,
    },
  },
];

@NgModule({
  declarations: [RentCostsComponent, RentCostFormComponent],
  imports: [
    CommonModule,
    RouterModule.forChild(routes),
    SharedModule,
    OwlDateTimeModule,
    ReactiveFormsModule,
    NgxCurrencyModule,
  ],
})
export class RentCostsModule {}
