import { Component, Inject, OnInit, Renderer2 } from '@angular/core';
import { Observable, Subject } from 'rxjs';
import { ExportFormat } from '../../enums/export.format';
import { Helpers } from '../../../core/classes/helpers';
import { DOCUMENT } from '@angular/common';
import { finalize } from 'rxjs/operators';
import {
  animateChild,
  group,
  query,
  transition,
  trigger,
  useAnimation,
} from '@angular/animations';
import {
  modalBackdropEnterAnimation,
  modalBackdropLeaveAnimation,
  modalEnterAnimation,
  modalLeaveAnimation,
} from '../../animations/browser-animations';
import { ToastrService } from 'ngx-toastr';
import { TemplateModel } from '../../interfaces/template-model';
import { SharedService } from '../../services/shared.service';
import {
  FormBuilder,
  FormControl,
  FormGroup,
  Validators,
} from '@angular/forms';

export type DownloadCallback = (
  format: ExportFormat,
  ...args
) => Observable<Blob>;

@Component({
  selector: 'oz-finance-download-modal',
  templateUrl: './download-modal.component.html',
  styleUrls: ['./download-modal.component.scss'],
  animations: [
    trigger('modalContainerAnimation', [
      transition(':enter', [
        group([
          query('@modalBackdropAnimation', animateChild()),
          query('@modalAnimation', animateChild()),
        ]),
      ]),
      transition(':leave', [
        group([
          query('@modalBackdropAnimation', animateChild()),
          query('@modalAnimation', animateChild()),
        ]),
      ]),
    ]),
    trigger('modalBackdropAnimation', [
      transition(':enter', useAnimation(modalBackdropEnterAnimation)),
      transition(':leave', useAnimation(modalBackdropLeaveAnimation)),
    ]),
    trigger('modalAnimation', [
      transition(':enter', useAnimation(modalEnterAnimation)),
      transition(':leave', useAnimation(modalLeaveAnimation)),
    ]),
  ],
})
export class DownloadModalComponent implements OnInit {
  ExportFormat = ExportFormat;
  showDownloadModal = false;
  previewFile: string;
  isLoading = false;
  filename: string;
  args: any;
  allowedFormats: ExportFormat[];

  public legalEntityId: string;
  public templates: TemplateModel[];
  public templateForm: FormGroup;

  private callback: DownloadCallback;
  private modalSubject: Subject<any>;
  private template_id: string;
  private isResourceInvoice = false;

  constructor(
    @Inject(DOCUMENT) private document,
    private renderer: Renderer2,
    private toastrService: ToastrService,
    private sharedService: SharedService,
    private fb: FormBuilder
  ) {}

  ngOnInit(): void {}

  public openModal(
    callback: DownloadCallback,
    args: any[],
    filename: string,
    allowedFormats?: ExportFormat[],
    legalEntityId?: string,
    allTemplates?: TemplateModel[],
    isResourceInvoice?: boolean
  ): Subject<any> {
    this.initTemplateForm();
    this.callback = callback;
    this.renderer.addClass(this.document.body, 'modal-opened');
    this.args = args;
    this.filename = filename;
    this.showDownloadModal = true;
    this.allowedFormats = allowedFormats ?? [
      ExportFormat.PDF,
      ExportFormat.DOCX,
    ];
    this.legalEntityId = legalEntityId;
    this.templates = allTemplates;
    this.isResourceInvoice = isResourceInvoice;
    this.patchValueTemplateForm();
    this.modalSubject = new Subject<any>();
    this.fetchPreview();
    return this.modalSubject;
  }

  closeModal(value?: any) {
    this.modalSubject.next(value);
    this.modalSubject.complete();
    this.showDownloadModal = false;
    this.previewFile = undefined;
    this.renderer.removeClass(this.document.body, 'modal-opened');
  }

  dismissModal() {
    this.modalSubject.complete();
    this.showDownloadModal = false;
    this.previewFile = undefined;
    this.renderer.removeClass(this.document.body, 'modal-opened');
  }

  downloadFile(format: ExportFormat) {
    this.isLoading = true;
    if (format === ExportFormat.PDF && this.previewFile) {
      this.createLinkForDownloading(format, this.previewFile);
      this.closeModal();
    } else {
      this.callback(format, ...this.args)
        .pipe(
          finalize(() => {
            this.isLoading = false;
            this.closeModal();
          })
        )
        .subscribe(
          response => {
            const type = Helpers.getExportMIMEType(format);
            const file = new Blob([response], { type });
            this.createLinkForDownloading(format, file);
            this.previewFile = undefined;
          },
          error => {
            this.toastrService.error(error.error?.message, 'Download failed');
          }
        );
    }
  }

  createLinkForDownloading(format: ExportFormat, file) {
    const link = this.document.createElement('a');
    this.document.body.appendChild(link);
    link.setAttribute(
      'href',
      format === ExportFormat.PDF ? file : URL.createObjectURL(file)
    );
    link.setAttribute('download', this.filename);
    link.click();
    this.document.body.removeChild(link);
  }

  private fetchPreview(): void {
    this.isLoading = true;
    this.callback(ExportFormat.PDF, ...this.args, this.legalEntityId)
      .pipe(
        finalize(() => {
          this.isLoading = false;
        })
      )
      .subscribe(response => {
        const type = Helpers.getExportMIMEType(ExportFormat.PDF);
        const file = new Blob([response], { type });
        this.previewFile = URL.createObjectURL(file);
      });
  }

  private initTemplateForm(): void {
    this.templateForm = this.fb.group({
      name: new FormControl(undefined, Validators.required),
    });
  }

  public templateChanged(event): void {
    this.template_id = event.id;
    this.templateForm.controls.name.patchValue(event.id);
    this.args[2] = this.template_id;
    this.fetchPreview();
  }

  public showTemplateForm(): boolean {
    if (!this.templates || this.isResourceInvoice) {
      return false;
    } else {
      return this.templates.length > 1;
    }
  }

  private patchValueTemplateForm(): void {
    if (this.templates) {
      this.templateForm.controls.name.patchValue(this.templates[0]['id']);
      this.template_id = this.templates[0]['id'];
    }
  }
}
