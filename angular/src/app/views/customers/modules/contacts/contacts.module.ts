import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ContactFormComponent } from './containers/contact-form/contact-form.component';
import { RouterModule, Routes } from '@angular/router';
import { SharedModule } from '../../../../shared/shared.module';
import { ReactiveFormsModule } from '@angular/forms';
import { ContactResolver } from './resolvers/contact.resolver';
import { NgSelectModule } from '@ng-select/ng-select';

const routes: Routes = [
  {
    path: 'create',
    component: ContactFormComponent,
  },
  {
    path: ':contact_id',
    children: [
      {
        path: 'edit',
        component: ContactFormComponent,
      },
      {
        path: '',
        pathMatch: 'full',
        redirectTo: 'edit',
      },
    ],
    resolve: {
      contact: ContactResolver,
    },
  },
  {
    path: '',
    pathMatch: 'full',
    redirectTo: '/dashboard',
  },
];

@NgModule({
  declarations: [ContactFormComponent],
  imports: [
    CommonModule,
    ReactiveFormsModule,
    RouterModule.forChild(routes),
    SharedModule,
    NgSelectModule,
  ],
})
export class ContactsModule {}
