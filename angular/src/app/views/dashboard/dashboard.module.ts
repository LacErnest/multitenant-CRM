import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { AnalyticsComponent } from './containers/analytics/analytics.component';
import { GraphCardComponent } from './components/graph-card/graph-card.component';
import { RouterModule, Routes } from '@angular/router';
import { SharedModule } from '../../shared/shared.module';
import { DashboardResolver } from './resolvers/dashboard.resolver';
import { NgxChartsModule } from '@swimlane/ngx-charts';
import { FormsModule, ReactiveFormsModule } from '@angular/forms';
import { QuotesBalanceComponent } from './containers/quotes-balance/quotes-balance.component';
import { OrdersBalanceComponent } from './containers/orders-balance/orders-balance.component';
import { InvoicesBalanceComponent } from './containers/invoices-balance/invoices-balance.component';
import { PurchaseOrdersBalanceComponent } from './containers/purchase-orders-balance/purchase-orders-balance.component';
import { EarnoutsBalanceComponent } from './containers/earnouts-balance/earnouts-balance.component';
import { EarnoutSummaryResolver } from './resolvers/earnout-summary.resolver';
import { EarnoutStatusResolver } from './resolvers/earnout-status.resolver';
import { EarnoutGuard } from '../../core/guards/earnout.guard';
import { SummaryResolver } from './resolvers/summary.resolver';
import { NgSelectModule } from '@ng-select/ng-select';
import { FilterModalComponent } from './components/filter-modal/filter-modal.component';
import { ClickOutsideModule } from 'ng-click-outside';
import { OwlDateTimeModule } from '@danielmoncada/angular-datetime-picker';

const routes: Routes = [
  {
    path: '',
    component: AnalyticsComponent,
    resolve: {
      analytics: DashboardResolver,
    },
  },
  {
    path: 'summary',
    children: [
      {
        path: 'quotes',
        component: QuotesBalanceComponent,
        resolve: {
          summary: SummaryResolver,
        },
        data: {
          entity: 'quotes',
        },
      },
      {
        path: 'orders',
        component: OrdersBalanceComponent,
        resolve: {
          summary: SummaryResolver,
        },
        data: {
          entity: 'orders',
        },
      },
      {
        path: 'invoices',
        component: InvoicesBalanceComponent,
        resolve: {
          summary: SummaryResolver,
        },
        data: {
          entity: 'invoices',
        },
      },
      {
        path: 'purchase_orders',
        component: PurchaseOrdersBalanceComponent,
        resolve: {
          summary: SummaryResolver,
        },
        data: {
          entity: 'purchase_orders',
        },
      },
      {
        path: 'earnouts',
        component: EarnoutsBalanceComponent,
        canActivate: [EarnoutGuard],
        resolve: {
          summary: EarnoutSummaryResolver,
          status: EarnoutStatusResolver,
        },
        data: {
          entity: 'earnouts',
        },
      },
      {
        path: '**',
        redirectTo: 'dashboard',
      },
    ],
  },
];

@NgModule({
  declarations: [
    FilterModalComponent,
    AnalyticsComponent,
    GraphCardComponent,
    QuotesBalanceComponent,
    OrdersBalanceComponent,
    InvoicesBalanceComponent,
    PurchaseOrdersBalanceComponent,
    EarnoutsBalanceComponent,
  ],
  imports: [
    CommonModule,
    FormsModule,
    NgxChartsModule,
    RouterModule.forChild(routes),
    SharedModule,
    NgSelectModule,
    ClickOutsideModule,
    OwlDateTimeModule,
    ReactiveFormsModule,
  ],
})
export class DashboardModule {}
