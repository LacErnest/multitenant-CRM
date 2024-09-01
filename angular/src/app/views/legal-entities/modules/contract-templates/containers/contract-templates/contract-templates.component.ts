import { Component, OnDestroy, OnInit } from '@angular/core';
import { ActivatedRoute } from '@angular/router';
import { Subject } from 'rxjs';
import { finalize, takeUntil } from 'rxjs/operators';
import { ExportFormat } from 'src/app/shared/enums/export.format';
import { ContractTemplates } from 'src/app/shared/interfaces/template';
import { TemplateUploaded } from 'src/app/shared/interfaces/template-upload';
import { TemplatesService } from 'src/app/shared/services/templates.service';
import { TemplateType } from 'src/app/shared/types/template-type';
import { ContractTemplatesService } from 'src/app/views/legal-entities/modules/contract-templates/contract-templates.service';

@Component({
  selector: 'oz-finance-contract-templates',
  templateUrl: './contract-templates.component.html',
  styleUrls: ['./contract-templates.component.scss'],
})
export class ContractTemplatesComponent implements OnInit, OnDestroy {
  public contractTemplates: ContractTemplates;
  public isLoading = false;

  private onDestroy$: Subject<void> = new Subject<void>();

  public constructor(
    private contractTemplatesService: ContractTemplatesService,
    private route: ActivatedRoute,
    private templatesService: TemplatesService
  ) {}

  public ngOnInit(): void {
    this.getResolvedData();
    this.initSubscriptions();
  }

  public ngOnDestroy(): void {
    this.onDestroy$?.next();
    this.onDestroy$?.complete();
  }

  private getResolvedData(): void {
    this.contractTemplates = this.route.snapshot.data.contractTemplates;
  }

  public getPDFTemplate(templateType: TemplateType): void {
    this.isLoading = true;

    this.contractTemplatesService
      .getContractTemplate(templateType, ExportFormat.PDF)
      .pipe(finalize(() => (this.isLoading = false)))
      .subscribe(
        response => {
          this.templatesService.createPdfUrl(response);
        },
        () => this.templatesService.showDownloadError()
      );
  }

  public saveUploadedTemplate({ file, type }): void {
    this.isLoading = true;

    this.contractTemplatesService
      .uploadContractTemplate(type, file)
      .pipe(finalize(() => (this.isLoading = false)))
      .subscribe(
        () => this.templatesService.handleUploadSuccess(type),
        error => this.templatesService.catchUploadError(error, type)
      );
  }

  private getDocxTemplate(type: TemplateType): void {
    this.isLoading = true;

    this.contractTemplatesService
      .getContractTemplate(type, ExportFormat.DOCX)
      .pipe(finalize(() => (this.isLoading = false)))
      .subscribe(
        response => this.templatesService.downloadDocxTemplate(response, type),
        () => this.templatesService.showDownloadError()
      );
  }

  private initSubscriptions(): void {
    // DOCX template download subscription
    this.templatesService.docxTemplateDownload$
      .pipe(takeUntil(this.onDestroy$))
      .subscribe(res => {
        this.getDocxTemplate(res);
      });

    // PDF template download subscription
    this.templatesService.pdfTemplateDownload$
      .pipe(takeUntil(this.onDestroy$))
      .subscribe(res => {
        this.getPDFTemplate(res);
      });

    // template upload subscription
    this.templatesService.templateUpload$
      .pipe(takeUntil(this.onDestroy$))
      .subscribe(res => {
        this.saveUploadedTemplate(res);
      });
  }
}
