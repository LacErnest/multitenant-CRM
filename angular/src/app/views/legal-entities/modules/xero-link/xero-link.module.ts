import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule, Routes } from '@angular/router';
import { XeroLinkComponent } from 'src/app/views/legal-entities/modules/xero-link/containers/xero-link/xero-link.component';
import { XeroLinkedResolver } from 'src/app/views/legal-entities/modules/xero-link/resolvers/xero-linked.resolver';

const routes: Routes = [
  {
    path: '',
    component: XeroLinkComponent,
    resolve: {
      isXeroLinked: XeroLinkedResolver,
    },
  },
];

@NgModule({
  declarations: [XeroLinkComponent],
  imports: [CommonModule, RouterModule.forChild(routes)],
})
export class XeroLinkModule {}
