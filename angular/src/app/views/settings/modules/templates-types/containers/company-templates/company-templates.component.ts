import { Component, OnDestroy, OnInit } from '@angular/core';
import { ActivatedRoute, NavigationEnd, Router } from '@angular/router';
import { Subject } from 'rxjs';
import { filter, finalize, skip, takeUntil } from 'rxjs/operators';
import { GlobalService } from 'src/app/core/services/global.service';
import { ExportFormat } from 'src/app/shared/enums/export.format';
import { CompanyTemplates } from 'src/app/shared/interfaces/template';
import { TemplateModel } from 'src/app/shared/interfaces/template-model';
import { TemplatesService } from 'src/app/shared/services/templates.service';
import { TemplateType } from 'src/app/shared/types/template-type';
import { SettingsService } from 'src/app/views/settings/settings.service';

@Component({
  selector: 'oz-finance-company-templates',
  templateUrl: './company-templates.component.html',
  styleUrls: ['./company-templates.component.scss'],
})
export class CompanyTemplatesComponent implements OnInit, OnDestroy {
  public templateId: string;
  public companyTemplates: CompanyTemplates;
  public isLoading = false;
  public templateModel: TemplateModel;

  private onDestroy$: Subject<void> = new Subject<void>();

  public constructor(
    private route: ActivatedRoute,
    private router: Router,
    private globalService: GlobalService,
    private settingsService: SettingsService,
    private templatesService: TemplatesService
  ) {}

  public ngOnInit(): void {
    this.route.params.subscribe(params => {
      this.templateId = params['template_id'];
    });
    this.getResolvedData();
    this.initSubscriptions();
  }

  public ngOnDestroy(): void {
    this.onDestroy$?.next();
    this.onDestroy$?.complete();
  }

  public getPDFTemplate(templateType: TemplateType): void {
    this.isLoading = true;

    this.settingsService
      .getTemplate(templateType, ExportFormat.PDF, this.templateId)
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

    this.settingsService
      .uploadTemplate(type, file, this.templateId)
      .pipe(finalize(() => (this.isLoading = false)))
      .subscribe(
        () => this.templatesService.handleUploadSuccess(type),
        error => this.templatesService.catchUploadError(error, type)
      );
  }

  private getResolvedData(): void {
    this.companyTemplates = this.route.snapshot.data.companyTemplates;
    this.templateModel = this.route.snapshot.data.companyTemplateModel;
  }

  private getDocxTemplate(type: TemplateType): void {
    this.isLoading = true;

    this.settingsService
      .getTemplate(type, ExportFormat.DOCX, this.templateId)
      .pipe(finalize(() => (this.isLoading = false)))
      .subscribe(
        response => {
          this.templatesService.downloadDocxTemplate(response, type);
        },
        () => this.templatesService.showDownloadError()
      );
  }

  private initSubscriptions(): void {
    // current company subscription
    // TODO: check similar subscriptions in other settings components
    this.globalService
      .getCurrentCompanyObservable()
      .pipe(skip(1), takeUntil(this.onDestroy$))
      .subscribe(value => {
        const allowedRoles = [0, 1, 2, 5, 6];
        const shouldNavigateToDashboard =
          value?.id === 'all' || !allowedRoles.includes(value.role);
        this.router
          .navigate([
            shouldNavigateToDashboard
              ? '/'
              : `/${value.id}/settings/templates/${this.templateId}/view`,
          ])
          .then();
      });

    // router subscription
    this.router.events
      .pipe(
        filter(e => e instanceof NavigationEnd),
        takeUntil(this.onDestroy$)
      )
      .subscribe(() => this.getResolvedData());

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
