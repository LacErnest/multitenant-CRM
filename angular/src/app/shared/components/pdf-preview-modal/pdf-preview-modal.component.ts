import {
  Component,
  Input,
  OnDestroy,
  OnInit,
  TemplateRef,
  ViewChild,
} from '@angular/core';
import { ModalComponent } from 'src/app/shared/components/modal/modal.component';
import { TemplatesService } from 'src/app/shared/services/templates.service';
import { TemplateType } from 'src/app/shared/types/template-type';

@Component({
  selector: 'oz-finance-pdf-preview-modal',
  templateUrl: './pdf-preview-modal.component.html',
  styleUrls: ['./pdf-preview-modal.component.scss'],
})

/**
 * TODO: check `ng2-pdf-viewer` updates with fix for `pdfjs-dist` version incompatibility
 * link to issue - https://github.com/VadimDez/ng2-pdf-viewer/issues/715
 */
export class PdfPreviewModalComponent implements OnInit, OnDestroy {
  @Input() public file: string;
  @Input() public templateType: TemplateType;

  @ViewChild('modal') public modal: ModalComponent;
  @ViewChild('pdfPreviewModal') public pdfPreviewModal: TemplateRef<any>;

  public previewModalHeading: string;

  public constructor(private templatesService: TemplatesService) {}

  public ngOnInit(): void {}

  public ngOnDestroy(): void {
    this.modal?.closeModal();
  }

  public openPdfPreviewModal(title: string): void {
    this.previewModalHeading = title;

    this.modal.openModal().subscribe(
      () => this.templatesService.emitDocxTemplateDownload(this.templateType),
      () => {},
      () => {
        this.file = null;
      }
    );
  }
}
