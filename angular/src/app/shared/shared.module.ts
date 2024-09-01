import { DragDropModule } from '@angular/cdk/drag-drop';
import { TextFieldModule } from '@angular/cdk/text-field';
import { CommonModule } from '@angular/common';
import { HttpClientModule } from '@angular/common/http';
import { NgModule } from '@angular/core';
import { FormsModule, ReactiveFormsModule } from '@angular/forms';
import { RouterModule } from '@angular/router';

import { OwlDateTimeModule } from '@danielmoncada/angular-datetime-picker';
import { NgSelectModule } from '@ng-select/ng-select';
import { NgxDatatableModule } from '@swimlane/ngx-datatable';
import { ClickOutsideModule } from 'ng-click-outside';
import { PdfViewerModule } from 'ng2-pdf-viewer';
import { NgxCurrencyModule } from 'ngx-currency';

import { AlertComponent } from 'src/app/shared/components/alert/alert.component';
import { BalanceSheetComponent } from 'src/app/shared/components/balance-sheet/balance-sheet.component';
import { ConfirmModalComponent } from 'src/app/shared/components/confirm-modal/confirm-modal.component';
import { DatatableComponent } from 'src/app/shared/components/datatable/datatable.component';
import { DeadlineModalComponent } from 'src/app/shared/components/deadline-modal/deadline-modal.component';
import { DestinationModalComponent } from 'src/app/shared/components/destination-modal/destination-modal.component';
import { DownloadModalComponent } from 'src/app/shared/components/download-modal/download-modal.component';
import { EntityImportModalComponent } from 'src/app/shared/components/entity-import-modal/entity-import-modal.component';
import { EntityItemEditorComponent } from 'src/app/shared/components/entity-item-editor/entity-item-editor.component';
import { EntityItemModalComponent } from 'src/app/shared/components/entity-item-modal/entity-item-modal.component';
import { EntityModifierModalComponent } from 'src/app/shared/components/entity-modifier-modal/entity-modifier-modal.component';
import { EntityPenaltyModalComponent } from 'src/app/shared/components/entity-penalty-modal/entity-penalty-modal.component';
import { ErrorNotFoundComponent } from 'src/app/shared/components/error-not-found/error-not-found.component';
import { PageHeaderComponent } from 'src/app/shared/components/page-header/page-header.component';
import { PdfPreviewModalComponent } from 'src/app/shared/components/pdf-preview-modal/pdf-preview-modal.component';
import { QrCodeComponent } from 'src/app/shared/components/qr-code/qr-code.component';
import { RatingModalComponent } from 'src/app/shared/components/rating-modal/rating-modal.component';
import { RefusalModalComponent } from 'src/app/shared/components/refusal-modal/refusal-modal.component';
import { ReturnButtonComponent } from 'src/app/shared/components/return-button/return-button.component';
import { ServiceModalComponent } from 'src/app/shared/components/service-modal/service-modal.component';
import { ServicesListComponent } from 'src/app/shared/components/services-list/services-list.component';
import { TemplateVariablesModalComponent } from 'src/app/shared/components/template-variables-modal/template-variables-modal.component';
import { TwoFactorActivationModalComponent } from 'src/app/shared/components/two-factor-activation-modal/two-factor-activation-modal.component';

import { AutoFocusDirective } from 'src/app/shared/directives/auto-focus.directive';

import { EntityItemPricePipe } from 'src/app/shared/pipes/entity-item-price.pipe';
import { EnumValuePipe } from 'src/app/shared/pipes/enum-value.pipe';
import { EnumPipe } from 'src/app/shared/pipes/enum.pipe';
import { MomentDatePipe } from 'src/app/shared/pipes/moment-date.pipe';
import { ByteDisplayPipe } from './pipes/byte-display.pipe';
import { FileUploadDownloadComponent } from './components/file-upload-download/file-upload-download.component';
import { ControlErrorIconComponent } from './components/control-error-icon/control-error-icon.component';
import { ModalComponent } from './components/modal/modal.component';
import { ControlRequiredErrorComponent } from './components/control-required-error/control-required-error.component';
import { TemplateComponent } from './components/template/template.component';
import { TemplatesComponent } from './components/templates/templates.component';
import { HeadingComponent } from './components/heading/heading.component';
import { LoadingOverlayComponent } from './components/loading-overlay/loading-overlay.component';
import { LegalEntitySelectComponent } from './components/legal-entity-select/legal-entity-select.component';
import { EarnoutSheetComponent } from './components/earnout-sheet/earnout-sheet.component';
import { VatEditModalComponent } from './components/vat-edit-modal/vat-edit-modal.component';
import { DownPaymentEditModalComponent } from './components/down-payment-edit-modal/down-payment-edit-modal.component';
import { EmployeeHistoryModalComponent } from './components/employee-history-modal/employee-history-modal.component';
import { UploadModalComponent } from './components/upload-modal/upload-modal.component';
import { EntityCommentEditorComponent } from 'src/app/shared/components/entity-comment-editor/entity-comment-editor.component';
import { EntityCommentModalComponent } from 'src/app/shared/components/entity-comment-modal/entity-comment-modal.component';
import { SvgIconComponent } from './components/svg-icon/svg-icon.component';
import { HtmlEditorComponent } from 'src/app/shared/components/html-editor/html-editor.component';
import { AngularEditorModule } from '@kolkov/angular-editor';
import { GridtableComponent } from 'src/app/shared/components/gridtable/gridtable.component';
import { TemplateVariableComponent } from './components/template-variable/template-variable.component';

@NgModule({
  declarations: [
    PageHeaderComponent,
    AlertComponent,
    TwoFactorActivationModalComponent,
    QrCodeComponent,
    DatatableComponent,
    HtmlEditorComponent,
    ConfirmModalComponent,
    EntityItemEditorComponent,
    EntityItemPricePipe,
    EntityItemModalComponent,
    MomentDatePipe,
    EnumValuePipe,
    EnumPipe,
    EntityModifierModalComponent,
    EntityImportModalComponent,
    ReturnButtonComponent,
    BalanceSheetComponent,
    EntityPenaltyModalComponent,
    RatingModalComponent,
    RefusalModalComponent,
    DownloadModalComponent,
    DeadlineModalComponent,
    DestinationModalComponent,
    ErrorNotFoundComponent,
    AutoFocusDirective,
    ServicesListComponent,
    ServiceModalComponent,
    ByteDisplayPipe,
    FileUploadDownloadComponent,
    ControlErrorIconComponent,
    ModalComponent,
    ControlRequiredErrorComponent,
    TemplateComponent,
    TemplatesComponent,
    HeadingComponent,
    LoadingOverlayComponent,
    PdfPreviewModalComponent,
    TemplateVariablesModalComponent,
    LegalEntitySelectComponent,
    EarnoutSheetComponent,
    VatEditModalComponent,
    DownPaymentEditModalComponent,
    EmployeeHistoryModalComponent,
    UploadModalComponent,
    EntityCommentEditorComponent,
    EntityCommentModalComponent,
    SvgIconComponent,
    GridtableComponent,
    TemplateVariableComponent,
  ],
  exports: [
    PageHeaderComponent,
    AlertComponent,
    TwoFactorActivationModalComponent,
    DatatableComponent,
    EntityItemEditorComponent,
    EntityImportModalComponent,
    EnumValuePipe,
    EnumPipe,
    ConfirmModalComponent,
    BalanceSheetComponent,
    MomentDatePipe,
    RatingModalComponent,
    DownloadModalComponent,
    RefusalModalComponent,
    ErrorNotFoundComponent,
    AutoFocusDirective,
    DeadlineModalComponent,
    ServicesListComponent,
    ByteDisplayPipe,
    FileUploadDownloadComponent,
    ControlErrorIconComponent,
    ModalComponent,
    ControlRequiredErrorComponent,
    TemplateComponent,
    TemplatesComponent,
    LoadingOverlayComponent,
    LegalEntitySelectComponent,
    EarnoutSheetComponent,
    EmployeeHistoryModalComponent,
    UploadModalComponent,
    EntityCommentEditorComponent,
    EntityCommentModalComponent,
    HtmlEditorComponent,
    SvgIconComponent,
    GridtableComponent,
    TemplateVariableComponent,
  ],
  imports: [
    CommonModule,
    HttpClientModule,
    NgxDatatableModule,
    FormsModule,
    ReactiveFormsModule,
    ClickOutsideModule,
    NgSelectModule,
    TextFieldModule,
    NgSelectModule,
    OwlDateTimeModule,
    ClickOutsideModule,
    PdfViewerModule,
    DragDropModule,
    NgxCurrencyModule,
    AngularEditorModule,
    RouterModule,
  ],
})
export class SharedModule {}
