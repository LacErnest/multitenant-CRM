import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ContactsListComponent } from './containers/contacts-list/contacts-list.component';
import { RouterModule, Routes } from '@angular/router';
import { SharedModule } from '../../shared/shared.module';
import { ContactsResolver } from './resolvers/contacts.resolver';
import { PreferenceType } from '../../shared/enums/preference-type.enum';
import { DatatablePreferencesResolver } from '../../shared/resolvers/datatable-preferences.resolver';

const routes: Routes = [
  {
    path: '',
    component: ContactsListComponent,
    data: {
      preferences: PreferenceType.USERS,
      entity: 15,
    },
    resolve: {
      contacts: ContactsResolver,
      table_preferences: DatatablePreferencesResolver,
    },
  },
];

@NgModule({
  declarations: [ContactsListComponent],
  imports: [CommonModule, RouterModule.forChild(routes), SharedModule],
})
export class ContactsModule {}
