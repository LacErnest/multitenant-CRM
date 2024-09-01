import { DOCUMENT } from '@angular/common';
import { HttpResponse } from '@angular/common/http';
import { Inject, Injectable } from '@angular/core';
import { ValidationErrors } from '@angular/forms';
import { ToastrService } from 'ngx-toastr';
import { Subject } from 'rxjs';
import { createDownloadLinkAndClick } from 'src/app/core/classes/helpers';
import { TemplateUploaded } from 'src/app/shared/interfaces/template-upload';
import { TemplateType } from 'src/app/shared/types/template-type';

@Injectable({
  providedIn: 'root',
})
export class TemplatesService {
  public docxTemplateDownload$: Subject<TemplateType> =
    new Subject<TemplateType>();
  public pdfTemplateDownload$: Subject<TemplateType> =
    new Subject<TemplateType>();
  public pdfFileUrl$: Subject<string> = new Subject<string>();
  public templateUpload$: Subject<TemplateUploaded> =
    new Subject<TemplateUploaded>();

  public constructor(
    @Inject(DOCUMENT) private _document: Document,
    private toastService: ToastrService
  ) {}

  public emitDocxTemplateDownload(type: TemplateType): void {
    this.docxTemplateDownload$.next(type);
  }

  public emitPDFTemplateDownload(type: TemplateType): void {
    this.pdfTemplateDownload$.next(type);
  }

  public emitTemplateUpload(uploadData: TemplateUploaded): void {
    this.templateUpload$.next(uploadData);
  }

  public createPdfUrl(response: HttpResponse<Blob>): void {
    const file = new Blob([response.body], { type: 'application/pdf' });
    this.pdfFileUrl$.next(URL.createObjectURL(file));
  }

  public downloadDocxTemplate(
    response: HttpResponse<Blob>,
    templateType: TemplateType
  ): void {
    const file = new Blob([response.body], {
      type: 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    });
    const filename = `${templateType}_template.docx`;
    createDownloadLinkAndClick(file, filename, this._document);

    this.toastService.success(
      'Template has been successfully downloaded.',
      'Success'
    );
  }

  public handleUploadSuccess(templateType: TemplateType): void {
    this.clearInput(templateType);

    this.toastService.success(
      'Template has been successfully uploaded.',
      'Success'
    );
  }

  public catchUploadError(
    error: ValidationErrors,
    templateType: TemplateType
  ): void {
    this.clearInput(templateType);

    if ('file' in error?.errors) {
      const {
        errors: {
          file: [err],
        },
      } = error;
      this.toastService.error(err, 'Error');
    } else {
      this.toastService.error(
        'Sorry, there has been an error on template import.',
        'Error'
      );
    }
  }

  public showDownloadError(): void {
    this.toastService.error(
      'Sorry, there has been an error on template download.',
      'Error'
    );
  }

  public clearInput(type: TemplateType): void {
    (<HTMLInputElement>document.getElementById(type)).value = '';
  }
}
