import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ReactiveFormsModule } from '@angular/forms';
import { RouterModule, Routes } from '@angular/router';
import { SharedModule } from 'src/app/shared/shared.module';
import { DocumentSettingsResolver } from 'src/app/views/legal-entities/modules/document-settings/resolvers/document-settings.resolver';
import { DocumentSettingsFormComponent } from './containers/document-settings-form/document-settings-form.component';

const routes: Routes = [
  {
    path: '',
    component: DocumentSettingsFormComponent,
    resolve: {
      settings: DocumentSettingsResolver,
    },
  },
];

@NgModule({
  declarations: [DocumentSettingsFormComponent],
  imports: [
    CommonModule,
    RouterModule.forChild(routes),
    ReactiveFormsModule,
    SharedModule,
  ],
})
export class DocumentSettingsModule {}
