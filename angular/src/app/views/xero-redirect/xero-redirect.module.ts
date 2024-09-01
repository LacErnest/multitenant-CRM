import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule, Routes } from '@angular/router';
import { SharedModule } from 'src/app/shared/shared.module';
import { XeroRedirectComponent } from 'src/app/views/xero-redirect/containers/xero-redirect/xero-redirect.component';

const routes: Routes = [
  {
    path: '',
    component: XeroRedirectComponent,
  },
];

@NgModule({
  declarations: [XeroRedirectComponent],
  imports: [CommonModule, RouterModule.forChild(routes), SharedModule],
})
export class XeroRedirectModule {}
