import { Component, Input, OnDestroy, OnInit, ViewChild } from '@angular/core';
import { Subject } from 'rxjs';
import { finalize, takeUntil } from 'rxjs/operators';
import { TemplateVariablesModalComponent } from 'src/app/shared/components/template-variables-modal/template-variables-modal.component';
import { getTemplateLabel } from 'src/app/shared/enums/template-label.enum';
import {
  CompanyTemplates,
  ContractTemplates,
  Template,
} from 'src/app/shared/interfaces/template';
import { PdfPreviewModalComponent } from 'src/app/shared/components/pdf-preview-modal/pdf-preview-modal.component';
import { TemplatesService } from 'src/app/shared/services/templates.service';
import { TemplateType } from 'src/app/shared/types/template-type';
import { TemplateModel } from '../../interfaces/template-model';
import { GlobalService } from 'src/app/core/services/global.service';
import { SettingsService } from 'src/app/views/settings/settings.service';
import { ToastrService } from 'ngx-toastr';
import { ActivatedRoute, Router } from '@angular/router';
import { ConfirmModalComponent } from '../confirm-modal/confirm-modal.component';
import { FormBuilder, FormGroup, Validators } from '@angular/forms';

@Component({
  selector: 'oz-finance-templates',
  templateUrl: './templates.component.html',
  styleUrls: ['./templates.component.scss'],
})
export class TemplatesComponent implements OnInit, OnDestroy {
  @ViewChild('confirmModal') private confirmModal: ConfirmModalComponent;

  @Input() public isLoading = false;
  @Input() public pdfFile;
  @Input() public templates: CompanyTemplates | ContractTemplates;
  @Input() public templatesHeading: string;
  @Input() public templateModel: TemplateModel;

  @ViewChild('pdfPreviewModal', { static: false })
  public pdfPreviewModal: PdfPreviewModalComponent;
  @ViewChild('templateVariablesModal', { static: false })
  public templateVariablesModal: TemplateVariablesModalComponent;
  private directDowload = false;
  public editionMode = false;

  public templatesKeys: TemplateType[];
  public templatesValues: Template[];

  public chosenEntity: TemplateType;
  public file: string;

  templateForm: FormGroup;

  private onDestroy$: Subject<void> = new Subject<void>();

  public constructor(
    private templatesService: TemplatesService,
    private globalService: GlobalService,
    private settingsService: SettingsService,
    private toastrService: ToastrService,
    protected route: ActivatedRoute,
    private router: Router,
    private fb: FormBuilder
  ) {}

  public ngOnInit(): void {
    this.initTemplateForm();
    this.initSubscriptions();
    this.setTemplateValues();
    this.patchValueToTemplateForm();
  }

  public ngOnDestroy(): void {
    this.onDestroy$?.next();
    this.onDestroy$?.complete();
  }

  /**
   * Initialize the design template from rules
   */
  private initTemplateForm(): void {
    this.templateForm = this.fb.group({
      name: [
        null,
        [
          Validators.required,
          Validators.minLength(3),
          Validators.maxLength(255),
        ],
      ],
    });
  }

  /**
   * Initialize the template category form with values
   */
  private patchValueToTemplateForm(): void {
    if (this.templateModel) {
      this.templateForm.patchValue(this.templateModel);
    }
  }

  public get templateIndex(): number {
    return this.templatesKeys.findIndex(k => k === this.chosenEntity);
  }

  public getTemplateLabel(templateType: TemplateType): string {
    return getTemplateLabel(templateType);
  }

  public templateDownloadClicked(chosenEntity: TemplateType): void {
    this.directDowload = true;
    this.chosenEntity = chosenEntity;

    if (!this.templatesValues[this.templateIndex]?.link) {
      return;
    }

    this.templatesService.emitPDFTemplateDownload(this.chosenEntity);
  }

  public templateViewClicked(chosenEntity: TemplateType): void {
    this.directDowload = false;
    this.chosenEntity = chosenEntity;

    if (!this.templatesValues[this.templateIndex]?.link) {
      return;
    }

    this.templatesService.emitPDFTemplateDownload(this.chosenEntity);
  }

  public openTemplateVariablesModal(entity: TemplateType): void {
    this.chosenEntity = entity;
    const title = getTemplateLabel(this.chosenEntity);
    this.templateVariablesModal
      .openModal(`${title} template variables`)
      .subscribe();
  }

  private setTemplateValues(): void {
    this.templatesKeys = Object.keys(this.templates) as TemplateType[];
    this.templatesValues = Object.values(this.templates);
  }

  private initSubscriptions(): void {
    this.templatesService.pdfFileUrl$
      .pipe(takeUntil(this.onDestroy$))
      .subscribe(res => {
        this.file = res;

        const title = getTemplateLabel(this.chosenEntity);
        if (this.directDowload) {
          this.templatesService.emitDocxTemplateDownload(this.chosenEntity);
        } else {
          this.pdfPreviewModal.openPdfPreviewModal(`${title} preview`);
        }
      });
  }

  get goBackUrl(): string {
    const companyId = this.globalService.currentCompany.id;
    return `/${companyId}/settings/templates`;
  }

  private deleteTemplate(template: TemplateModel): void {
    this.isLoading = true;
    this.settingsService
      .deleteTemplate(template.id)
      .pipe(finalize(() => (this.isLoading = false)))
      .subscribe(
        () => {
          this.toastrService.success(
            'Template category was successfully deleted',
            'Success'
          );
          const route = [this.goBackUrl];
          this.router.navigate(route, { relativeTo: this.route }).then();
          return;
        },
        () => {
          this.toastrService.error(
            'Template category was not deleted',
            'Error'
          );
        }
      );
  }

  /**
   * Delete current template category and redirect to template categories view
   */
  public handleDeleteTemplate(): void {
    this.confirmModal
      .openModal(
        'Confirm',
        'Are you sure you want to delete this template category?'
      )
      .subscribe(result => {
        if (result) {
          this.deleteTemplate(this.templateModel);
        }
      });
  }

  private updateTemplate(template: TemplateModel): void {
    this.isLoading = true;

    this.settingsService
      .editTemplate(template.id, template)
      .pipe(
        finalize(() => {
          this.isLoading = false;
        })
      )
      .subscribe(
        response => {
          this.templateModel = response;
          this.toastrService.success(
            'Template category was successfully updated',
            'Success'
          );
        },
        () => {
          this.toastrService.error(
            'Template category was not updated',
            'Error'
          );
        }
      );
  }

  submitForm(): void {
    if (this.templateForm.valid && !this.isLoading) {
      this.templateModel.name = this.templateForm.value.name;
      this.updateTemplate(this.templateModel);
    }
  }
}
