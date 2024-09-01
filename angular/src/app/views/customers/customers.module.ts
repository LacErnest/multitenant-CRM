import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule, Routes } from '@angular/router';
import { FormsModule, ReactiveFormsModule } from '@angular/forms';
import { NgSelectModule } from '@ng-select/ng-select';
import { ClickOutsideModule } from 'ng-click-outside';
import { TextFieldModule } from '@angular/cdk/text-field';
import { CustomerListComponent } from 'src/app/views/customers/containers/customer-list/customer-list.component';
import { PreferenceType } from 'src/app/shared/enums/preference-type.enum';
import { CustomersResolver } from 'src/app/views/customers/resolvers/customers.resolver';
import { CustomerResolver } from 'src/app/views/customers/resolvers/customer.resolver';
import { SharedModule } from 'src/app/shared/shared.module';
import { ContactListComponent } from 'src/app/views/customers/components/contact-list/contact-list.component';
import { CurrencyCodeEnumResolver } from 'src/app/shared/resolvers/currency-code-enum.resolver';
import { CustomerFormComponent } from 'src/app/views/customers/containers/customer-form/customer-form.component';
import { DatatablePreferencesResolver } from 'src/app/shared/resolvers/datatable-preferences.resolver';
import { ContactGenderEnumResolver } from 'src/app/shared/resolvers/contact-gender-enum-resolver.service';
import { ChooseLegalEntityModalComponent } from './components/choose-legal-entity-modal/choose-legal-entity-modal.component';

const routes: Routes = [
  {
    path: '',
    component: CustomerListComponent,
    data: {
      preferences: PreferenceType.USERS.valueOf(),
      entity: 1,
    },
    resolve: {
      customers: CustomersResolver,
      currencyCodes: CurrencyCodeEnumResolver,
      table_preferences: DatatablePreferencesResolver,
    },
  },
  {
    path: 'analytics',
    component: CustomerListComponent,
    data: {
      preferences: PreferenceType.ANALYTICS.valueOf(),
      entity: 1,
    },
    resolve: {
      customers: CustomersResolver,
      currencyCodes: CurrencyCodeEnumResolver,
      table_preferences: DatatablePreferencesResolver,
    },
  },
  {
    path: 'create',
    component: CustomerFormComponent,
  },
  {
    path: ':customer_id',
    children: [
      {
        path: '',
        pathMatch: 'full',
        redirectTo: 'edit',
      },
      {
        path: 'edit',
        component: CustomerFormComponent,
        resolve: {
          customer: CustomerResolver,
        },
      },
      {
        path: 'contacts',
        loadChildren: () =>
          import('./modules/contacts/contacts.module').then(
            mod => mod.ContactsModule
          ),
        resolve: {
          contactgenders: ContactGenderEnumResolver,
        },
      },
    ],
  },
];

@NgModule({
  declarations: [
    CustomerListComponent,
    CustomerFormComponent,
    ContactListComponent,
    ChooseLegalEntityModalComponent,
  ],
  imports: [
    CommonModule,
    NgSelectModule,
    ReactiveFormsModule,
    RouterModule.forChild(routes),
    SharedModule,
    ClickOutsideModule,
    TextFieldModule,
    FormsModule,
  ],
})
export class CustomersModule {}
