import {
  Component,
  EventEmitter,
  Input,
  OnDestroy,
  OnInit,
  Output,
  ViewChild,
} from '@angular/core';
import * as moment from 'moment';
import { ToastrService } from 'ngx-toastr';
import { EnumService } from 'src/app/core/services/enum.service';
import { ConfirmModalComponent } from 'src/app/shared/components/confirm-modal/confirm-modal.component';
import { DownloadModalComponent } from 'src/app/shared/components/download-modal/download-modal.component';
import { ExportFormat } from 'src/app/shared/enums/export.format';
import { UserRole } from 'src/app/shared/enums/user-role.enum';
import { Invoice } from 'src/app/shared/interfaces/entities';
import { PaidDateModalComponent } from 'src/app/views/projects/modules/project/components/paid-date-modal/paid-date-modal.component';
import { InvoiceStatus } from 'src/app/views/projects/modules/project/enums/invoice-status.enum';
import { InvoiceStatusUpdate } from 'src/app/views/projects/modules/project/interfaces/invoice-status-update';
import { ProjectService } from 'src/app/views/projects/modules/project/project.service';
import { ProjectInvoiceService } from 'src/app/views/projects/modules/project/services/project-invoice.service';
import { GlobalService } from '../../../../../../core/services/global.service';
import { TemplateModel } from '../../../../../../shared/interfaces/template-model';
import { SharedService } from '../../../../../../shared/services/shared.service';
import { Subject } from 'rxjs';
import { PartialPaidModalComponent } from '../partial-paid-modal/partial-paid-modal.component';
import { FormBuilder, FormControl, FormGroup } from '@angular/forms';

@Component({
  selector: 'oz-finance-invoice-form-action-buttons',
  templateUrl: './invoice-form-action-buttons.component.html',
  styleUrls: ['./invoice-form-action-buttons.component.scss'],
})
export class InvoiceFormActionButtonsComponent implements OnInit, OnDestroy {
  @Input() public invoice: Invoice;
  @Input() public isResourceInvoice = false;
  @Input() public downloadEnabled = true;
  @Input() public statusChangeEnabled = false;
  @Input() public userRole: number;
  @Input() public currency: number;
  @Input() public onPartialPay: Subject<void>;

  @Output() public statusUpdated: EventEmitter<InvoiceStatusUpdate> =
    new EventEmitter<InvoiceStatusUpdate>();
  @Output() public requireEmailTemplate: EventEmitter<boolean> =
    new EventEmitter<boolean>();

  @ViewChild('confirmModal', { static: false })
  public confirmModal: ConfirmModalComponent;
  @ViewChild('backDraftConfirmModal', { static: false })
  public backDraftConfirmModal: ConfirmModalComponent;
  @ViewChild('downloadModal', { static: false })
  public downloadModal: DownloadModalComponent;
  @ViewChild('paidDateModal', { static: false })
  public paidDateModal: PaidDateModalComponent;
  @ViewChild('partialPaidModal', { static: false })
  public partialPaidModal: PartialPaidModalComponent;

  public invoiceStatusEnum = InvoiceStatus;
  public userRoleEnum = UserRole;
  public templates: TemplateModel[] = [];
  private template_id: string;
  private onDestroy = new Subject<void>();
  public invoiceStatusForm: FormGroup;

  public constructor(
    private enumService: EnumService,
    private projectService: ProjectService,
    private projectInvoiceService: ProjectInvoiceService,
    private toastrService: ToastrService,
    private globalService: GlobalService,
    private sharedService: SharedService,
    private fb: FormBuilder
  ) {}

  public ngOnInit(): void {
    this.getCompanyTemplates();
    this.initInvoiceForm();
  }

  public ngOnDestroy(): void {
    this.onDestroy.next();
    this.onDestroy.complete();
  }

  private initInvoiceForm(invoiceStatus?: InvoiceStatus): void {
    if (!this.showInvoiceStatusForm(invoiceStatus)) {
      return;
    }
    if (this.invoiceStatusForm) {
      this.invoiceStatusForm.clearValidators();
    }
    const currentInvoiceStatus = invoiceStatus ?? this.invoice.status;
    switch (currentInvoiceStatus) {
      case InvoiceStatus.AUTHORISED:
        this.invoiceStatusForm = this.fb.group({
          notify_client: new FormControl(true),
        });
        break;
      default:
        this.invoiceStatusForm = this.fb.group({});
        break;
    }
  }

  public showInvoiceStatusForm(invoiceStatus?: InvoiceStatus): boolean {
    const currentInvoiceStatus = invoiceStatus ?? this.invoice.status;
    return currentInvoiceStatus === InvoiceStatus.AUTHORISED;
  }

  public toggleClientNotification(): void {
    this.invoiceStatusForm.controls?.notify_client?.setValue(
      !this.invoiceStatusForm.controls?.notify_client?.value
    );
  }

  public changeStatus(status: InvoiceStatus, pay_date?: string): void {
    const statusLabel = this.enumService
      .getEnumMap('invoicestatus')
      .get(status);

    this.confirmModal
      .openModal(
        'Confirm',
        `Are you sure want to change status to ${statusLabel}? This cannot be undone.`
      )
      .subscribe(result => {
        if (
          result &&
          (this.invoice?.status != InvoiceStatus.AUTHORISED ||
            this.invoice?.email_template_globally_disabled ||
            this.invoice?.email_template_id ||
            !this.invoiceStatusForm.get('notify_client')?.value)
        ) {
          this.changeInvoiceStatus(status, pay_date);
        } else if (result) {
          this.requireEmailTemplate.emit(true);
        }
      });
  }

  public download(): void {
    if (this.templates) {
      this.template_id = this.templates[0]['id'];
    }

    const exportCallback = this.isResourceInvoice
      ? this.projectService.exportResourceInvoiceCallback
      : this.projectInvoiceService.exportProjectInvoiceCallback;

    const args = this.isResourceInvoice
      ? [
          this.invoice.resource_id,
          this.invoice.purchase_order_id,
          this.invoice.id,
        ]
      : [this.invoice.project_id, this.invoice.id, this.template_id];

    this.downloadModal
      .openModal(
        exportCallback,
        args,
        `${this.isResourceInvoice ? 'Resource Invoice' : 'Invoice'} ${this.invoice.number}`,
        this.isResourceInvoice
          ? [ExportFormat.PDF]
          : [ExportFormat.PDF, ExportFormat.DOCX],
        null,
        this.templates,
        this.isResourceInvoice
      )
      .subscribe();
  }

  public setPaidStatus(): void {
    if (this.isResourceInvoice) {
      this.changeStatus(InvoiceStatus.PAID, moment().format('YYYY-MM-DD'));
    } else {
      this.onPartialPay.next();
    }
    //this.onPartialPay.complete();
  }

  public setPartialPaidStatus(): void {
    this.setPaidStatus();
  }

  public showDraftButton(): boolean {
    const superUser = this.globalService.userDetails?.super_user;

    return superUser && this.invoice?.status !== this.invoiceStatusEnum.DRAFT;
  }

  private changeInvoiceStatus(status: number, pay_date?: string): void {
    this.projectInvoiceService
      .changeProjectInvoiceStatus(
        this.invoice.project_id,
        this.invoice.id,
        {
          status,
          pay_date,
          email_template_id: this.invoice.email_template_id,
        },
        this.invoiceStatusForm?.value
      )
      .subscribe(
        response => {
          this.toastrService.success(
            'Invoice updated successfully',
            'Update successful'
          );
          if (response.status === 'error') {
            this.toastrService.error(response.message, 'Something went wrong');
          }
          this.initInvoiceForm(response.invoice.status);
          this.statusUpdated.emit({
            status: response.invoice.status,
            pay_date,
          });
        },
        error => {
          const msg = error.error?.message ?? error?.message;
          this.toastrService.error(msg, 'Update failed');
        }
      );
  }

  private getCompanyTemplates(): void {
    this.sharedService.getTemplates().subscribe(response => {
      this.templates = response;
    });
  }

  public setInvoiceAsDraft(status: InvoiceStatus): void {
    const statusLabel = this.enumService
      .getEnumMap('invoicestatus')
      .get(status);

    this.backDraftConfirmModal
      .openModal(
        'Confirm',
        `Are you sure want to change status to ${statusLabel}? This cannot be undone.`
      )
      .subscribe(result => {
        if (result) {
          this.changeInvoiceStatus(status);
        }
      });
  }

  get invoiceStatusConfirmationLabel(): string {
    if (
      !this.invoice?.email_template_globally_disabled &&
      this.invoice?.status == InvoiceStatus.AUTHORISED &&
      !this.invoice?.email_template_id &&
      this.invoiceStatusForm.get('notify_client')?.value
    ) {
      return 'Select email template';
    }
    return 'Yes';
  }
}
