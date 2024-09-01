import { NgModule } from '@angular/core';
import { RouterModule, Routes } from '@angular/router';
import { AuthGuard } from 'src/app/core/guards/auth.guard';
import { CompanyGuard } from 'src/app/core/guards/company.guard';
import { ReverseAuthGuard } from 'src/app/core/guards/reverse-auth.guard';
import { RoleGuard } from 'src/app/core/guards/role.guard';
import { AppContainerComponent } from 'src/app/core/layout/app-container/app-container.component';
import { ErrorNotFoundComponent } from 'src/app/shared/components/error-not-found/error-not-found.component';
import { UserRole } from 'src/app/shared/enums/user-role.enum';
import { CountryEnumResolver } from 'src/app/shared/resolvers/country-enum.resolver';
import { CurrencyCodeEnumResolver } from 'src/app/shared/resolvers/currency-code-enum.resolver';
import { CustomerStatusEnumResolver } from 'src/app/shared/resolvers/customer-status-enum.resolver';
import { EmployeeStatusEnumResolver } from 'src/app/shared/resolvers/employee-status-enum.resolver';
import { EmployeeTypeEnumResolver } from 'src/app/shared/resolvers/employee-type-enum.resolver';
import { IndustryTypeEnumResolver } from 'src/app/shared/resolvers/industry-type-enum.resolver';
import { OrderStatusEnumResolver } from 'src/app/shared/resolvers/order-status-enum.resolver';
import { PurchaseOrderStatusEnumResolver } from 'src/app/shared/resolvers/purchase-order-status-enum.resolver';
import { ResourceStatusEnumResolver } from 'src/app/shared/resolvers/resource-status-enum.resolver';
import { ResourceTypeEnumResolver } from 'src/app/shared/resolvers/resource-type-enum.resolver';
import { UserRoleEnumResolver } from 'src/app/shared/resolvers/user-role-enum.resolver';
import { LegalEntityGuard } from 'src/app/views/legal-entities/guards/legal-entity.guard';
import { CommissionGuard } from 'src/app/views/commissions/guards/commission.guard';
import { ProjectTypeEnumResolver } from './shared/resolvers/project-type-enum.resolver';

const routes: Routes = [
  {
    path: '404',
    component: ErrorNotFoundComponent,
  },
  {
    path: 'auth',
    loadChildren: () =>
      import('./views/authentication/authentication.module').then(
        mod => mod.AuthenticationModule
      ),
    canActivate: [ReverseAuthGuard],
  },
  {
    path: 'freelancers',
    loadChildren: () =>
      import('./views/external-access/external-access.module').then(
        mod => mod.ExternalAccessModule
      ),
  },
  {
    path: '',
    component: AppContainerComponent,
    canActivate: [AuthGuard],
    resolve: {
      currencyCodes: CurrencyCodeEnumResolver,
      userRoles: UserRoleEnumResolver,
      projectTypeEnum: ProjectTypeEnumResolver,
    },
    children: [
      {
        path: '',
        pathMatch: 'full',
        redirectTo: 'dashboard',
      },
      {
        path: 'xero/redirect',
        loadChildren: () =>
          import('./views/xero-redirect/xero-redirect.module').then(
            m => m.XeroRedirectModule
          ),
      },
      {
        path: 'dashboard',
        loadChildren: () =>
          import('./views/dashboard/dashboard.module').then(
            mod => mod.DashboardModule
          ),
      },
      {
        path: 'commissions',
        loadChildren: () =>
          import('./views/commissions/commissions.module').then(
            mod => mod.CommissionsModule
          ),
        canActivate: [CommissionGuard],
      },
      {
        path: 'legal_entities',
        loadChildren: () =>
          import('./views/legal-entities/legal-entities.module').then(
            m => m.LegalEntitiesModule
          ),
        canActivate: [LegalEntityGuard],
      },
      {
        path: ':company_id',
        canActivate: [CompanyGuard],
        children: [
          {
            path: 'settings',
            loadChildren: () =>
              import('./views/settings/settings.module').then(
                mod => mod.SettingsModule
              ),
          },
          {
            path: 'customers',
            loadChildren: () =>
              import('./views/customers/customers.module').then(
                mod => mod.CustomersModule
              ),
            resolve: {
              countryEnum: CountryEnumResolver,
              customerStatusEnum: CustomerStatusEnumResolver,
              industryTypeEnum: IndustryTypeEnumResolver,
            },
            canActivate: [RoleGuard],
            data: {
              roles: [0, 1, 2, 3, 6],
            },
          },
          {
            path: 'employees',
            loadChildren: () =>
              import('./views/employees/employees.module').then(
                mod => mod.EmployeesModule
              ),
            resolve: {
              countryEnum: CountryEnumResolver,
              employeeStatus: EmployeeStatusEnumResolver,
              employeeType: EmployeeTypeEnumResolver,
            },
            canActivate: [RoleGuard],
            data: {
              roles: [
                UserRole.ADMINISTRATOR.valueOf(),
                UserRole.OWNER.valueOf(),
                UserRole.ACCOUNTANT.valueOf(),
                UserRole.PROJECT_MANAGER.valueOf(),
                UserRole.HUMAN_RESOURCES.valueOf(),
                UserRole.OWNER_READ_ONLY.valueOf(),
                UserRole.PROJECT_MANAGER_RESTRICTED.valueOf(),
              ],
            },
          },
          {
            path: 'contacts',
            loadChildren: () =>
              import('./views/contacts/contacts.module').then(
                mod => mod.ContactsModule
              ),
            canActivate: [RoleGuard],
            data: {
              roles: [0, 1, 2, 3, 6],
            },
          },
          {
            path: 'projects',
            loadChildren: () =>
              import('./views/projects/projects.module').then(
                mod => mod.ProjectsModule
              ),
            canActivate: [RoleGuard],
            data: {
              roles: [0, 1, 2, 3, 4, 6, 7],
            },
          },
          {
            path: 'invoices',
            loadChildren: () =>
              import('./views/invoices/invoices.module').then(
                mod => mod.InvoicesModule
              ),
            canActivate: [RoleGuard],
            data: {
              roles: [0, 1, 2, 3, 4, 6, 7],
            },
          },
          {
            path: 'orders',
            loadChildren: () =>
              import('./views/orders/orders.module').then(
                mod => mod.OrdersModule
              ),
            canActivate: [RoleGuard],
            data: {
              roles: [0, 1, 2, 3, 4, 6, 7],
            },
            resolve: {
              orderStatusEnum: OrderStatusEnumResolver,
            },
          },
          {
            path: 'quotes',
            loadChildren: () =>
              import('./views/quotes/quotes.module').then(
                mod => mod.QuotesModule
              ),
            canActivate: [RoleGuard],
            data: {
              roles: [
                UserRole.ADMINISTRATOR.valueOf(),
                UserRole.OWNER.valueOf(),
                UserRole.ACCOUNTANT.valueOf(),
                UserRole.SALES_PERSON.valueOf(),
                UserRole.PROJECT_MANAGER.valueOf(),
                UserRole.HUMAN_RESOURCES.valueOf(),
                UserRole.OWNER_READ_ONLY.valueOf(),
                UserRole.PROJECT_MANAGER_RESTRICTED.valueOf(),
              ],
            },
          },
          {
            path: 'purchase_orders',
            loadChildren: () =>
              import('./views/purchase-orders/purchase-orders.module').then(
                mod => mod.PurchaseOrdersModule
              ),
            resolve: {
              purchaseOrderStatusEnum: PurchaseOrderStatusEnumResolver,
            },
            canActivate: [RoleGuard],
            data: {
              roles: [0, 1, 2, 4, 6, 7],
            },
          },
          {
            path: 'resources',
            loadChildren: () =>
              import('./views/resources/resources.module').then(
                mod => mod.ResourcesModule
              ),
            canActivate: [RoleGuard],
            data: {
              roles: [0, 1, 2, 4, 5, 6, 7],
            },
            resolve: {
              countryEnum: CountryEnumResolver,
              resourceTypeEnum: ResourceTypeEnumResolver,
              resourceStatusEnum: ResourceStatusEnumResolver,
            },
          },
          {
            path: 'resource_invoices',
            loadChildren: () =>
              import('./views/resource-invoices/resource-invoices.module').then(
                m => m.ResourceInvoicesModule
              ),
            data: {
              roles: [0, 1, 2, 3, 4, 6, 7],
            },
          },
          {
            path: '**',
            redirectTo: '/404',
          },
        ],
      },
    ],
  },
  {
    path: '**',
    redirectTo: '404',
    pathMatch: 'full',
  },
];

@NgModule({
  imports: [RouterModule.forRoot(routes)],
  exports: [RouterModule],
})
export class AppRoutingModule {}
