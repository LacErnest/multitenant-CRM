import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule, Routes } from '@angular/router';
import { SharedModule } from '../../../../shared/shared.module';
import { UserFormComponent } from './containers/user-form/user-form.component';
import { UserListComponent } from './containers/user-list/user-list.component';
import { ReactiveFormsModule } from '@angular/forms';
import { UsersResolver } from './resolvers/users.resolver';
import { UserResolver } from './resolvers/user-resolver';
import { NgSelectModule } from '@ng-select/ng-select';
import { DatatablePreferencesResolver } from '../../../../shared/resolvers/datatable-preferences.resolver';
import { RoleGuard } from '../../../../core/guards/role.guard';
import { PreferenceType } from '../../../../shared/enums/preference-type.enum';
import { MailSettingsResolver } from './resolvers/mail-settings.resolver';
import { NgxCurrencyModule } from 'ngx-currency';
import { CommissionModelEnumResolver } from '../../../../shared/resolvers/commission-model-enum.resolver';

const routes: Routes = [
  {
    path: '',
    component: UserListComponent,
    canActivate: [RoleGuard],
    data: {
      preferences: PreferenceType.USERS,
      entity: 0,
      roles: [0, 1, 2, 6],
    },
    resolve: {
      table_preferences: DatatablePreferencesResolver,
      users: UsersResolver,
    },
  },
  {
    path: 'analytics',
    component: UserListComponent,
    canActivate: [RoleGuard],
    data: {
      preferences: PreferenceType.ANALYTICS,
      entity: 0,
      roles: [0, 1, 2, 6],
    },
    resolve: {
      table_preferences: DatatablePreferencesResolver,
      users: UsersResolver,
    },
  },
  {
    path: 'create',
    component: UserFormComponent,
    data: {
      roles: [0, 1],
    },
    resolve: {
      commissionModels: CommissionModelEnumResolver,
    },
  },
  {
    path: ':user_id',
    children: [
      {
        path: 'edit',
        component: UserFormComponent,
        data: {
          roles: [0, 1, 6],
        },
        resolve: {
          user: UserResolver,
          mailSettings: MailSettingsResolver,
          commissionModels: CommissionModelEnumResolver,
        },
      },
      {
        path: '',
        pathMatch: 'full',
        redirectTo: 'edit',
      },
    ],
  },
];

@NgModule({
  declarations: [UserFormComponent, UserListComponent],
  imports: [
    CommonModule,
    RouterModule.forChild(routes),
    SharedModule,
    ReactiveFormsModule,
    NgSelectModule,
    NgxCurrencyModule,
  ],
})
export class UsersModule {}
