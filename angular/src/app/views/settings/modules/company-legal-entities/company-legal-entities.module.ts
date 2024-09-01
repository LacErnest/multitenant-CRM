import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ReactiveFormsModule } from '@angular/forms';
import { RouterModule, Routes } from '@angular/router';
import { SharedModule } from 'src/app/shared/shared.module';
import { CompanyLegalEntitiesComponent } from './containers/company-legal-entities/company-legal-entities.component';
import { AddLegalEntityModalComponent } from './components/add-legal-entity-modal/add-legal-entity-modal.component';
import { NgSelectModule } from '@ng-select/ng-select';

const routes: Routes = [
  {
    path: '',
    component: CompanyLegalEntitiesComponent,
  },
];

@NgModule({
  declarations: [CompanyLegalEntitiesComponent, AddLegalEntityModalComponent],
  imports: [
    CommonModule,
    NgSelectModule,
    RouterModule.forChild(routes),
    SharedModule,
    ReactiveFormsModule,
  ],
})
export class CompanyLegalEntitiesModule {}
