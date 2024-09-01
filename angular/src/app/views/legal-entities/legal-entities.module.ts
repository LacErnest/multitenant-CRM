import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ReactiveFormsModule } from '@angular/forms';
import { RouterModule, Routes } from '@angular/router';
import { NgSelectModule } from '@ng-select/ng-select';
import { TablePreferenceType } from 'src/app/shared/enums/table-preference-type.enum';
import { CountryEnumResolver } from 'src/app/shared/resolvers/country-enum.resolver';
import { DatatablePreferencesResolver } from 'src/app/shared/resolvers/datatable-preferences.resolver';
import { SharedModule } from 'src/app/shared/shared.module';
import { LegalEntitiesResolver } from 'src/app/views/legal-entities/resolvers/legal-entities.resolver';
import { LegalEntityResolver } from 'src/app/views/legal-entities/resolvers/legal-entity.resolver';
import { LegalEntitiesListComponent } from './containers/legal-entities-list/legal-entities-list.component';
import { LegalEntityFormComponent } from './components/legal-entity-form/legal-entity-form.component';
import { LegalEntityWrapperComponent } from './containers/legal-entity-wrapper/legal-entity-wrapper.component';
import { LegalEntityNavComponent } from './components/legal-entity-nav/legal-entity-nav.component';

const routes: Routes = [
  {
    path: '',
    component: LegalEntitiesListComponent,
    resolve: {
      countryEnum: CountryEnumResolver,
      legalEntities: LegalEntitiesResolver,
      tablePreferences: DatatablePreferencesResolver,
    },
    data: {
      entity: TablePreferenceType.LEGAL_ENTITIES,
    },
  },
  {
    path: 'create',
    component: LegalEntityWrapperComponent,
    children: [
      {
        path: '',
        component: LegalEntityFormComponent,
        resolve: {
          countryEnum: CountryEnumResolver,
        },
      },
    ],
  },
  {
    path: ':legal_entity_id',
    component: LegalEntityWrapperComponent,
    children: [
      {
        path: '',
        component: LegalEntityFormComponent,
        resolve: {
          countryEnum: CountryEnumResolver,
          legalEntity: LegalEntityResolver,
        },
      },
      {
        path: 'tax_rates',
        loadChildren: () =>
          import('./modules/tax-rates/tax-rates.module').then(
            m => m.TaxRatesModule
          ),
      },
      {
        path: 'xero',
        loadChildren: () =>
          import('./modules/xero-link/xero-link.module').then(
            m => m.XeroLinkModule
          ),
      },
      {
        path: 'settings',
        loadChildren: () =>
          import('./modules/document-settings/document-settings.module').then(
            m => m.DocumentSettingsModule
          ),
      },
      {
        path: 'templates',
        loadChildren: () =>
          import('./modules/contract-templates/contract-templates.module').then(
            m => m.ContractTemplatesModule
          ),
      },
      {
        path: 'notifications',
        loadChildren: () =>
          import(
            './modules/notification-settings/notification-settings.module'
          ).then(m => m.NotificationSettingsModule),
      },
    ],
  },
];

@NgModule({
  declarations: [
    LegalEntitiesListComponent,
    LegalEntityFormComponent,
    LegalEntityWrapperComponent,
    LegalEntityNavComponent,
  ],
  imports: [
    CommonModule,
    RouterModule.forChild(routes),
    SharedModule,
    ReactiveFormsModule,
    NgSelectModule,
  ],
})
export class LegalEntitiesModule {}
