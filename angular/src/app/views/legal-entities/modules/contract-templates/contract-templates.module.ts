import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule, Routes } from '@angular/router';
import { SharedModule } from 'src/app/shared/shared.module';
import { ContractTemplatesResolver } from 'src/app/views/legal-entities/modules/contract-templates/resolvers/contract-templates.resolver';
import { ContractTemplatesComponent } from './containers/contract-templates/contract-templates.component';

const routes: Routes = [
  {
    path: '',
    component: ContractTemplatesComponent,
    resolve: {
      contractTemplates: ContractTemplatesResolver,
    },
  },
];

@NgModule({
  declarations: [ContractTemplatesComponent],
  imports: [CommonModule, RouterModule.forChild(routes), SharedModule],
})
export class ContractTemplatesModule {}
