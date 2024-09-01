import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { TemplateFormComponent } from './template-form.component';
import { ReactiveFormsModule } from '@angular/forms';
import { NgSelectModule } from '@ng-select/ng-select';
import { TextFieldModule } from '@angular/cdk/text-field';
import { OwlDateTimeModule } from '@danielmoncada/angular-datetime-picker';

@NgModule({
  declarations: [TemplateFormComponent],
  imports: [
    CommonModule,
    NgSelectModule,
    ReactiveFormsModule,
    TextFieldModule,
    OwlDateTimeModule,
  ],
  exports: [TemplateFormComponent],
})
export class TemplateFormModule {}
