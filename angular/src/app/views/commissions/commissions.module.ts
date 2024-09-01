import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule, Routes } from '@angular/router';
import { SharedModule } from '../../shared/shared.module';
import { CommissionsSummaryResolver } from './resolvers/commissions-summary.resolver';
import { CommissionsSettingsResolver } from './resolvers/commissions-settings.resolver';
import { CommissionsComponent } from './containers/commissions/commissions.component';
import { CommissionsSummaryComponent } from './components/commissions-summary/commissions-summary.component';
import { NgSelectModule } from '@ng-select/ng-select';
import { FormsModule, ReactiveFormsModule } from '@angular/forms';
import { CommissionsPaymentLogResolver } from './resolvers/commissions-payment-log.resolver';
import { PayCommissionModalComponent } from './components/pay-commission-modal/pay-commission-modal.component';
import { SalesTotalOpenAmountResolver } from './resolvers/sales-total-open-amount.resolver';
import { CommissionPaymentLogEnumResolver } from './resolvers/commission-payment-log-status-enum.resolver';
import { NgxCurrencyModule } from 'ngx-currency';
import { DragDropModule } from '@angular/cdk/drag-drop';
import { ClickOutsideModule } from 'ng-click-outside';
import { EditSalesCommissionModalComponent } from './components/edit-sales-commission-modal/edit-sales-commission.component';
import { AddSalesCommissionModalComponent } from './components/add-sales-commission-modal/add-sales-commission.component';
import { PayIndividualCommissionModalComponent } from './components/pay-individual-commission-modal/pay-individual-commission-modal.component';

const routes: Routes = [
  {
    path: '',
    component: CommissionsComponent,
    data: {
      entity: 'commissions',
    },
    resolve: {
      summary: CommissionsSummaryResolver,
      settings: CommissionsSettingsResolver,
      payment_log: CommissionsPaymentLogResolver,
      total_open_amount: SalesTotalOpenAmountResolver,
      commissionLogStatusEnum: CommissionPaymentLogEnumResolver,
    },
  },
];

@NgModule({
  declarations: [
    CommissionsComponent,
    CommissionsSummaryComponent,
    PayCommissionModalComponent,
    EditSalesCommissionModalComponent,
    AddSalesCommissionModalComponent,
    PayIndividualCommissionModalComponent,
  ],
  exports: [
    PayCommissionModalComponent,
    EditSalesCommissionModalComponent,
    AddSalesCommissionModalComponent,
    PayIndividualCommissionModalComponent,
  ],
  imports: [
    CommonModule,
    SharedModule,
    RouterModule.forChild(routes),
    ReactiveFormsModule,
    FormsModule,
    NgSelectModule,
    NgxCurrencyModule,
    DragDropModule,
    ClickOutsideModule,
  ],
})
export class CommissionsModule {}
