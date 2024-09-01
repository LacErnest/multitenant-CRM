import { TextFieldModule } from '@angular/cdk/text-field';
import { CommonModule, CurrencyPipe } from '@angular/common';
import { NgModule } from '@angular/core';
import { FormsModule, ReactiveFormsModule } from '@angular/forms';
import { RouterModule, Routes } from '@angular/router';
import { OwlDateTimeModule } from '@danielmoncada/angular-datetime-picker';
import { NgSelectModule } from '@ng-select/ng-select';
import { ClickOutsideModule } from 'ng-click-outside';
import { RoleGuard } from 'src/app/core/guards/role.guard';
import { TablePreferenceType } from 'src/app/shared/enums/table-preference-type.enum';
import { UserRole } from 'src/app/shared/enums/user-role.enum';
import { CreditNoteStatusEnumResolver } from 'src/app/shared/resolvers/credit-note-status-enum.resolver';
import { CreditNoteTypeEnumResolver } from 'src/app/shared/resolvers/credit-note-type-enum.resolver';
import { DatatablePreferencesResolver } from 'src/app/shared/resolvers/datatable-preferences.resolver';
import { EmployeeStatusEnumResolver } from 'src/app/shared/resolvers/employee-status-enum.resolver';
import { EmployeeTypeEnumResolver } from 'src/app/shared/resolvers/employee-type-enum.resolver';
import { InvoiceStatusEnumResolver } from 'src/app/shared/resolvers/invoice-status-enum.resolver';
import { InvoiceTypeEnumResolver } from 'src/app/shared/resolvers/invoice-type-enum.resolver';
import { OrderStatusEnumResolver } from 'src/app/shared/resolvers/order-status-enum.resolver';
import { PriceModifierQuantityTypeEnumResolver } from 'src/app/shared/resolvers/price-modifier-quantity-type-enum.resolver';
import { EntityPenaltyTypeEnumResolver } from 'src/app/shared/resolvers/entity-penalty-type-enum.resolver';
import { PriceModifierTypeEnumResolver } from 'src/app/shared/resolvers/price-modifier-type-enum.resolver';
import { PurchaseOrderStatusEnumResolver } from 'src/app/shared/resolvers/purchase-order-status-enum.resolver';
import { QuoteStatusEnumResolver } from 'src/app/shared/resolvers/quote-status-enum.resolver';
import { SharedModule } from 'src/app/shared/shared.module';
import { CreditNotesListComponent } from 'src/app/views/projects/modules/project/components/credit-notes-list/credit-notes-list.component';
import { InvoiceFormActionButtonsComponent } from 'src/app/views/projects/modules/project/components/invoice-form-action-buttons/invoice-form-action-buttons.component';
import { InvoiceFormComponent } from 'src/app/views/projects/modules/project/components/invoice-form/invoice-form.component';
import { OrderFormComponent } from 'src/app/views/projects/modules/project/components/order-form/order-form.component';
import { PaidDateModalComponent } from 'src/app/views/projects/modules/project/components/paid-date-modal/paid-date-modal.component';
import { ProjectEmployeeListComponent } from 'src/app/views/projects/modules/project/components/project-employee-list/project-employee-list.component';
import { ProjectEmployeeModalComponent } from 'src/app/views/projects/modules/project/components/project-employee-modal/project-employee-modal.component';
import { ProjectInvoiceListComponent } from 'src/app/views/projects/modules/project/components/project-invoice-list/project-invoice-list.component';
import { ProjectNavComponent } from 'src/app/views/projects/modules/project/components/project-nav/project-nav.component';
import { ProjectPurchaseOrderListComponent } from 'src/app/views/projects/modules/project/components/project-purchase-order-list/project-purchase-order-list.component';
import { ProjectQuoteListComponent } from 'src/app/views/projects/modules/project/components/project-quote-list/project-quote-list.component';
import { ProjectResourceInvoiceListComponent } from 'src/app/views/projects/modules/project/components/project-resource-invoice-list/project-resource-invoice-list.component';
import { PurchaseOrderFormComponent } from 'src/app/views/projects/modules/project/components/purchase-order-form/purchase-order-form.component';
import { QuoteCloneModalComponent } from 'src/app/views/projects/modules/project/components/quote-clone-modal/quote-clone-modal.component';
import { QuoteFormComponent } from 'src/app/views/projects/modules/project/components/quote-form/quote-form.component';
import { InvoicePaymentListComponent } from 'src/app/views/projects/modules/project/components/invoice-payment-list/invoice-payment-list.component';
import { ProjectComponent } from 'src/app/views/projects/modules/project/containers/project/project.component';
import { CustomerCurrencyResolver } from 'src/app/views/projects/modules/project/resolvers/customer-currency.resolver';
import { InvoiceResolver } from 'src/app/views/projects/modules/project/resolvers/invoice.resolver';
import { EmailTemplateResolver } from 'src/app/views/projects/modules/project/resolvers/email-template.resolver';

import { OrderResolver } from 'src/app/views/projects/modules/project/resolvers/order.resolver';
import { ProjectResolver } from 'src/app/views/projects/modules/project/resolvers/project.resolver';
import { PurchaseOrderResolver } from 'src/app/views/projects/modules/project/resolvers/purchase-order.resolver';
import { QuoteResolver } from 'src/app/views/projects/modules/project/resolvers/quote.resolver';
import { ResourceInvoiceFormComponent } from 'src/app/views/projects/modules/project/components/resource-invoice-form/resource-invoice-form.component';
import { NgxCurrencyModule } from 'ngx-currency';
import { PartialPaidModalComponent } from './components/partial-paid-modal/partial-paid-modal.component';
import { InvoicePaymentModalComponent } from 'src/app/views/projects/modules/project/components/invoice-payment-modal/invoice-payment-modal.component';
import { CommentResolver } from '../../../../shared/resolvers/comment.resolver';
import { CompanySettingsResolver } from 'src/app/views/settings/resolvers/company-settings.resolver';
import { DragDropModule } from '@angular/cdk/drag-drop';
import { CommissionsSummaryResolver } from 'src/app/views/commissions/resolvers/commissions-summary.resolver';
import { CommissionsSettingsResolver } from 'src/app/views/commissions/resolvers/commissions-settings.resolver';
import { CommissionsPaymentLogResolver } from 'src/app/views/commissions/resolvers/commissions-payment-log.resolver';
import { SalesTotalOpenAmountResolver } from 'src/app/views/commissions/resolvers/sales-total-open-amount.resolver';
import { CommissionPaymentLogEnumResolver } from 'src/app/views/commissions/resolvers/commission-payment-log-status-enum.resolver';
import { ProjectCommissionsSummaryComponent } from 'src/app/views/projects/modules/project/components/project-commissions-summary/project-commissions-summary.component';
import { CommissionsModule } from 'src/app/views/commissions/commissions.module';
import { PayCommissionModalComponent } from 'src/app/views/commissions/components/pay-commission-modal/pay-commission-modal.component';
import { EditSalesCommissionModalComponent } from 'src/app/views/commissions/components/edit-sales-commission-modal/edit-sales-commission.component';
import { AddSalesCommissionModalComponent } from 'src/app/views/commissions/components/add-sales-commission-modal/add-sales-commission.component';
import { PayIndividualCommissionModalComponent } from 'src/app/views/commissions/components/pay-individual-commission-modal/pay-individual-commission-modal.component';
import { EmailManagementSharedModule } from 'src/app/views/settings/modules/email-management/email-management-shared.module';

const routes: Routes = [
  {
    path: '',
    component: ProjectComponent,
    runGuardsAndResolvers: 'always',
    resolve: {
      project: ProjectResolver,
      priceModifierQuantityTypes: PriceModifierQuantityTypeEnumResolver,
      entityPenaltyTypes: EntityPenaltyTypeEnumResolver,
      priceModifierTypes: PriceModifierTypeEnumResolver,
    },
    children: [
      {
        path: 'quotes',
        resolve: {
          currency: CustomerCurrencyResolver,
          quoteStatusEnum: QuoteStatusEnumResolver,
        },
        canActivate: [RoleGuard],
        data: {
          roles: [
            UserRole.ADMINISTRATOR.valueOf(),
            UserRole.OWNER.valueOf(),
            UserRole.ACCOUNTANT.valueOf(),
            UserRole.SALES_PERSON.valueOf(),
            UserRole.PROJECT_MANAGER.valueOf(),
            UserRole.OWNER_READ_ONLY.valueOf(),
            UserRole.PROJECT_MANAGER_RESTRICTED.valueOf(),
          ],
        },
        children: [
          {
            path: '',
            component: ProjectQuoteListComponent,
            resolve: {
              tablePreferences: DatatablePreferencesResolver,
            },
            data: {
              entity: TablePreferenceType.PROJECT_QUOTES.valueOf(),
            },
          },
          {
            path: 'create',
            component: QuoteFormComponent,
            data: {},
          },
          {
            path: ':quote_id',
            resolve: {
              quote: QuoteResolver,
            },
            children: [
              {
                path: 'edit',
                component: QuoteFormComponent,
              },
              {
                path: '',
                pathMatch: 'full',
                redirectTo: 'edit',
              },
            ],
          },
        ],
      },
      {
        path: 'orders',
        resolve: {
          orderStatusEnum: OrderStatusEnumResolver,
          settings: CompanySettingsResolver,
        },
        children: [
          {
            path: ':order_id',
            resolve: {
              order: OrderResolver,
            },
            children: [
              {
                path: 'edit',
                component: OrderFormComponent,
              },
              {
                path: '',
                pathMatch: 'full',
                redirectTo: 'edit',
              },
            ],
          },
        ],
      },
      {
        path: 'invoices',
        resolve: {
          invoiceStatusEnum: InvoiceStatusEnumResolver,
          invoiceTypeEnum: InvoiceTypeEnumResolver,
          currency: CustomerCurrencyResolver,
          settings: CompanySettingsResolver,
        },
        children: [
          {
            path: '',
            component: ProjectInvoiceListComponent,
            resolve: {
              tablePreferences: DatatablePreferencesResolver,
            },
            data: {
              entity: TablePreferenceType.PROJECT_INVOICES.valueOf(),
            },
          },
          {
            path: 'create',
            component: InvoiceFormComponent,
            resolve: {
              // taxRate: CurrentTaxRateResolver,
            },
          },
          {
            path: ':invoice_id',
            resolve: {
              invoice: InvoiceResolver,
              emailTemplate: EmailTemplateResolver,
              creditNoteTypeEnum: CreditNoteTypeEnumResolver,
              creditNoteStatusEnum: CreditNoteStatusEnumResolver,
              comments: CommentResolver,
              //settings: CompanySettingsResolver
            },
            children: [
              {
                path: 'edit',
                component: InvoiceFormComponent,
                resolve: {
                  tablePreferences: DatatablePreferencesResolver,
                },
                data: {
                  entity: TablePreferenceType.INVOICES_PAYMENTS.valueOf(),
                },
              },
              {
                path: '',
                pathMatch: 'full',
                redirectTo: 'edit',
              },
            ],
            data: {
              entity: TablePreferenceType.INVOICES.valueOf(),
            },
          },
        ],
      },
      {
        path: 'invoice_payments',
        resolve: {
          invoiceStatusEnum: InvoiceStatusEnumResolver,
          invoiceTypeEnum: InvoiceTypeEnumResolver,
          currency: CustomerCurrencyResolver,
          settings: CompanySettingsResolver,
        },
        children: [
          {
            path: '',
            component: InvoicePaymentListComponent,
            resolve: {
              tablePreferences: DatatablePreferencesResolver,
            },
            data: {
              entity: TablePreferenceType.INVOICES_PAYMENTS.valueOf(),
            },
          },
          {
            path: ':invoice_id',
            resolve: {
              invoice: InvoiceResolver,
              creditNoteTypeEnum: CreditNoteTypeEnumResolver,
              creditNoteStatusEnum: CreditNoteStatusEnumResolver,
              tablePreferences: DatatablePreferencesResolver,
            },
            data: {
              entity: TablePreferenceType.INVOICES_PAYMENTS.valueOf(),
            },
            children: [
              {
                path: '',
                pathMatch: 'full',
                redirectTo: 'edit',
              },
            ],
          },
        ],
      },
      {
        path: 'resource_invoices',
        children: [
          {
            path: '',
            component: ProjectResourceInvoiceListComponent,
            resolve: {
              invoiceStatusEnum: InvoiceStatusEnumResolver,
              tablePreferences: DatatablePreferencesResolver,
            },
            data: {
              entity: TablePreferenceType.PROJECT_RESOURCE_INVOICES.valueOf(),
            },
          },
          {
            path: ':resource_invoice_id',
            component: ResourceInvoiceFormComponent,
            resolve: {
              invoiceStatusEnum: InvoiceStatusEnumResolver,
              resourceInvoice: InvoiceResolver,
            },
          },
        ],
      },
      {
        path: 'purchase_orders',
        resolve: {
          purchaseOrderStatusEnum: PurchaseOrderStatusEnumResolver,
          settings: CompanySettingsResolver,
        },
        canActivate: [RoleGuard],
        data: {
          roles: [
            UserRole.ADMINISTRATOR.valueOf(),
            UserRole.OWNER.valueOf(),
            UserRole.ACCOUNTANT.valueOf(),
            UserRole.SALES_PERSON.valueOf(),
            UserRole.PROJECT_MANAGER.valueOf(),
            UserRole.OWNER_READ_ONLY.valueOf(),
            UserRole.PROJECT_MANAGER_RESTRICTED.valueOf(),
          ],
        },
        children: [
          {
            path: '',
            component: ProjectPurchaseOrderListComponent,
            resolve: {
              tablePreferences: DatatablePreferencesResolver,
            },
            data: {
              entity: TablePreferenceType.PROJECT_PURCHASE_ORDERS.valueOf(),
            },
          },
          {
            path: 'create',
            component: PurchaseOrderFormComponent,
            resolve: {
              // taxRate: CurrentTaxRateResolver,
            },
          },
          {
            path: ':purchase_order_id',
            resolve: {
              purchaseOrder: PurchaseOrderResolver,
            },
            children: [
              {
                path: 'edit',
                component: PurchaseOrderFormComponent,
              },
              {
                path: '',
                pathMatch: 'full',
                redirectTo: 'edit',
              },
            ],
          },
        ],
      },
      {
        path: 'employees',
        resolve: {
          employeeTypeEnum: EmployeeTypeEnumResolver,
          employeeStatusEnum: EmployeeStatusEnumResolver,
        },
        children: [
          {
            path: '',
            component: ProjectEmployeeListComponent,
            resolve: {
              tablePreferences: DatatablePreferencesResolver,
            },
            data: {
              entity: TablePreferenceType.PROJECT_EMPLOYEES.valueOf(),
            },
          },
        ],
      },
      {
        path: 'sales_peoples',
        component: ProjectCommissionsSummaryComponent,
        data: {
          entity: TablePreferenceType.PROJECT_COMMISSIONS.valueOf(),
        },
        resolve: {
          summary: CommissionsSummaryResolver,
          settings: CommissionsSettingsResolver,
          payment_log: CommissionsPaymentLogResolver,
          total_open_amount: SalesTotalOpenAmountResolver,
          commissionLogStatusEnum: CommissionPaymentLogEnumResolver,
          invoiceStatusEnum: InvoiceStatusEnumResolver,
          orderStatusEnum: OrderStatusEnumResolver,
          tablePreferences: DatatablePreferencesResolver,
        },
      },
      {
        path: '',
        pathMatch: 'full',
        redirectTo: '/dashboard',
      },
    ],
  },
];

@NgModule({
  declarations: [
    ProjectComponent,
    ProjectNavComponent,
    InvoiceFormComponent,
    OrderFormComponent,
    PurchaseOrderFormComponent,
    ProjectQuoteListComponent,
    ProjectPurchaseOrderListComponent,
    ProjectInvoiceListComponent,
    InvoicePaymentListComponent,
    PaidDateModalComponent,
    ProjectResourceInvoiceListComponent,
    ProjectEmployeeListComponent,
    ProjectEmployeeModalComponent,
    PaidDateModalComponent,
    PaidDateModalComponent,
    QuoteCloneModalComponent,
    QuoteFormComponent,
    CreditNotesListComponent,
    ResourceInvoiceFormComponent,
    InvoiceFormActionButtonsComponent,
    PartialPaidModalComponent,
    InvoicePaymentModalComponent,
    ProjectCommissionsSummaryComponent,
  ],
  exports: [QuoteFormComponent, PurchaseOrderFormComponent],
  providers: [CurrencyPipe],
  imports: [
    CommonModule,
    RouterModule.forChild(routes),
    SharedModule,
    ReactiveFormsModule,
    NgSelectModule,
    TextFieldModule,
    OwlDateTimeModule,
    FormsModule,
    ClickOutsideModule,
    NgxCurrencyModule,
    DragDropModule,
    CommissionsModule,
    EmailManagementSharedModule,
  ],
})
export class ProjectModule {}
