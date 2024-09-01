import {
  Component,
  Input,
  OnInit,
  ViewChild,
  OnDestroy,
  Output,
  EventEmitter,
  OnChanges,
  SimpleChanges,
} from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { ToastrService } from 'ngx-toastr';
import { finalize } from 'rxjs/operators';
import { GlobalService } from 'src/app/core/services/global.service';
import { DatatableButtonConfig } from 'src/app/shared/classes/datatable/datatable-button-config';
import { DatatableContainerBase } from 'src/app/shared/classes/datatable/datatable-container-base';
import { DatatableMenuConfig } from 'src/app/shared/classes/datatable/datatable-menu-config';
import { DownloadModalComponent } from 'src/app/shared/components/download-modal/download-modal.component';
import { ProjectEntityEnum } from 'src/app/shared/enums/project-entity.enum';
import { UserRole } from 'src/app/shared/enums/user-role.enum';
import { Invoice, InvoicePayment } from 'src/app/shared/interfaces/entities';
import { TablePreferencesService } from 'src/app/shared/services/table-preferences.service';
import { InvoicesService } from 'src/app/views/invoices/invoices.service';
import { InvoicePaymentsService } from 'src/app/views/projects/modules/project/services/invoice-payments.service';
import { InvoiceStatusUpdate } from '../../interfaces/invoice-status-update';
import { InvoiceStatus } from '../../enums/invoice-status.enum';
import { TemplateModel } from '../../../../../../shared/interfaces/template-model';
import { SharedService } from '../../../../../../shared/services/shared.service';
import { InvoicePaymentModalComponent } from '../invoice-payment-modal/invoice-payment-modal.component';
import { environment } from 'src/environments/environment';
import { Observable } from 'rxjs';
import { Subject } from 'rxjs';
import { AppStateService } from 'src/app/shared/services/app-state.service';
@Component({
  selector: 'oz-finance-invoice-payment-list',
  templateUrl: './invoice-payment-list.component.html',
  styleUrls: ['./invoice-payment-list.component.scss'],
})
export class InvoicePaymentListComponent
  extends DatatableContainerBase
  implements OnInit, OnDestroy, OnChanges
{
  @ViewChild('invoicePaymentModal', { static: false })
  public invoicePaymentModal: InvoicePaymentModalComponent;
  @Input() invoice: Invoice;
  @Input() events: Observable<void>;
  @Output() public invoiceUpdated: EventEmitter<Invoice> =
    new EventEmitter<Invoice>();
  @Input() modalToogleEvent: Subject<boolean>;
  @Input() onRefresh: Subject<boolean> = new Subject<boolean>();

  public project_id: string;
  public buttonConfig: DatatableButtonConfig = new DatatableButtonConfig({
    columns: true,
    filters: true,
    delete: false,
    export: false,
  });
  public rowMenuConfig: DatatableMenuConfig = new DatatableMenuConfig({
    delete: false,
  });
  public projectEntity = ProjectEntityEnum;
  public invoice_payments: { data: any; count: number };
  public invoices: Invoice[];
  public templates: TemplateModel[] = [];
  private openModalEventSubscription: any;

  private template_id: string;

  public constructor(
    protected route: ActivatedRoute,
    protected tablePreferencesService: TablePreferencesService,
    private toastrService: ToastrService,
    private globalService: GlobalService,
    private invoicesService: InvoicesService,
    private sharedService: SharedService,
    private invoicePaymentsService: InvoicePaymentsService,
    protected appStateService: AppStateService
  ) {
    super(tablePreferencesService, route, appStateService);
  }

  public ngOnInit(): void {
    this.setDefaultCurrency();
    this.getResolvedData();
    this.setPermissions();
    this.getCompanyTemplates();
    this.eventsSubscribe();
  }

  ngOnDestroy(): void {
    this.openModalEventSubscription.unsubscribe();
  }

  ngOnChanges(changes: SimpleChanges): void {
    this.setPermissions();
  }

  private eventsSubscribe(): void {
    this.openModalEventSubscription = this.events.subscribe(() =>
      this.addInvoicePayment()
    );

    this.onRefresh.subscribe(response => {
      if (response) {
        this.getData();
      }
    });
  }
  /**
   * Set default currency
   */
  protected setDefaultCurrency(): void {
    this.currency = this.invoice?.currency_code;
  }

  public addInvoicePayment(): void {
    this.modalToogleEvent.next(true);
    this.isLoading = true;
    this.invoicesService
      .getSingleFromProject(this.project_id, this.invoice.id)
      .subscribe(response => {
        this.invoice = response;
        setTimeout(() => {
          this.isLoading = false;
          this.invoicePaymentModal
            .openModal('Add partial payment', 0)
            .subscribe(result => {
              this.modalToogleEvent.next(false);
              if (result) {
                this.isLoading = true;
                this.invoicePaymentsService
                  .createInvoicePayment(
                    result.project_id,
                    result.invoice_id,
                    result
                  )
                  .subscribe(
                    response => {
                      this.toastrService.success(
                        'Invoice payment created successfully',
                        'Create successful'
                      );
                      this.invoice = response.invoice;
                      this.invoiceUpdated.emit(this.invoice);
                      this.getData();
                    },
                    error => {
                      let msg = error.error?.message || error?.message;
                      if (typeof msg === 'object' && 'pay_amount' in msg) {
                        msg = msg.pay_amount[0];
                      }
                      this.toastrService.error(msg, 'Update failed');
                      this.isLoading = false;
                    }
                  );
              }
            });
        }, 100);
      });
  }

  public editInvoicePayment(invoicePaymentId: string): void {
    this.isLoading = true;
    this.invoicePaymentsService
      .getInvoicePayment(
        this.invoice.project_id,
        this.invoice.id,
        invoicePaymentId
      )
      .subscribe(
        response => {
          this.modalToogleEvent.next(true);
          this.isLoading = false;
          this.invoicePaymentModal
            .openModal('Edit partial payment', 0, response)
            .subscribe(result => {
              this.modalToogleEvent.next(false);
              if (result) {
                this.isLoading = true;
                this.invoicePaymentsService
                  .editInvoicePayment(
                    result.project_id,
                    result.invoice_id,
                    invoicePaymentId,
                    { ...result, invoice_payment_id: invoicePaymentId }
                  )
                  .subscribe(
                    response => {
                      this.toastrService.success(
                        'Invoice payment updated successfully',
                        'Update successful'
                      );
                      this.invoice = response.invoice;
                      this.invoiceUpdated.emit(this.invoice);
                      this.getData();
                    },
                    error => {
                      let msg = error.error?.message || error?.message;
                      if (typeof msg === 'object' && 'pay_amount' in msg) {
                        msg = msg.pay_amount[0];
                      }
                      this.toastrService.error(msg, 'Update failed');
                      this.isLoading = false;
                    }
                  );
              }
            });
        },
        error => {
          const msg = error.error?.message ?? error?.message;
          this.toastrService.error(msg, 'Update failed');
          this.isLoading = false;
        }
      );
  }

  public deleteInvoicesPayments(invoice_payments: InvoicePayment[]): void {
    this.isLoading = true;

    this.invoicePaymentsService
      .deleteInvoicePayments(
        this.invoice.project_id,
        this.invoice.id,
        invoice_payments.map(q => q.id.toString())
      )
      .pipe(finalize(() => (this.isLoading = false)))
      .subscribe(response => {
        this.getData();
        this.invoiceUpdated.emit(response.invoice);
        const msgBeginning =
          invoice_payments.length > 1
            ? 'Invoice Payments have'
            : 'Invoice Payment has';
        this.toastrService.success(
          `${msgBeginning} been successfully deleted`,
          'Success'
        );
      });
  }

  public cancelInvoicePayment({ id: invoicePaymentId }): void {
    this.isLoading = true;

    const status: InvoiceStatusUpdate = {
      status: InvoiceStatus.CANCELED,
    };

    this.invoicePaymentsService
      .changeInvoicePaymentStatus(
        this.project_id,
        this.invoice.id,
        invoicePaymentId,
        status
      )
      .pipe(
        finalize(() => {
          this.isLoading = false;
        })
      )
      .subscribe(
        response => {
          this.getData();
          this.toastrService.success(
            'Invoice Payment canceled successfully',
            'Success'
          );
        },
        error => {
          this.toastrService.error(error.error?.message, 'Update failed');
        }
      );
  }

  protected getData(): void {
    if (this.invoice) {
      this.getPaymentsDataFromInvoice();
    } else {
      this.getInvoices();
      this.getPaymentsDataFromProject();
    }
  }

  /**
   * Get current project invoices data
   */
  protected getInvoices(): void {
    this.invoicesService
      .getProjectInvoices(this.project_id, this.params)
      .subscribe(response => {
        this.invoices = response?.data;
      });
  }

  protected getPaymentsDataFromInvoice(): void {
    this.isLoading = true;

    this.invoicePaymentsService
      .getInvoicePayments(this.project_id, this.invoice.id)
      .pipe(finalize(() => (this.isLoading = false)))
      .subscribe(response => {
        this.invoice_payments = response;
      });
  }

  protected getPaymentsDataFromProject(): void {
    this.isLoading = true;

    this.invoicePaymentsService
      .getProjectInvoicePayments(this.project_id, this.params)
      .pipe(finalize(() => (this.isLoading = false)))
      .subscribe(response => {
        this.invoice_payments = response;
      });
  }

  private getResolvedData(): void {
    if (this.invoice) {
      this.project_id = this.invoice.project_id;
    } else {
      this.project_id = this.route.snapshot.parent.parent.data.project?.id;
      this.invoice = this.route.snapshot.parent.parent.data.invoice;
    }
    this.preferences = this.route.snapshot.data.tablePreferences;
    this.getData();
  }

  private setPermissions(): void {
    const role = this.globalService.getUserRole();

    const readOnlyMode =
      [
        UserRole.SALES_PERSON,
        UserRole.PROJECT_MANAGER,
        UserRole.OWNER_READ_ONLY,
        UserRole.PROJECT_MANAGER_RESTRICTED,
      ].includes(role) ||
      ![
        InvoiceStatus.SUBMITTED,
        InvoiceStatus.UNPAID,
        InvoiceStatus.PARTIAL_PAID,
      ].includes(this.invoice?.status) ||
      this.invoice?.status === InvoiceStatus.PAID;
    this.rowMenuConfig.clone = false;
    this.rowMenuConfig.cancel = false;
    this.rowMenuConfig.delete = true;
    this.buttonConfig.add = true;
    if (readOnlyMode) {
      this.buttonConfig.add = false;
      this.rowMenuConfig.edit = false;
      this.rowMenuConfig.view = true;
      this.rowMenuConfig.delete = false;
      this.rowMenuConfig.export = false;
    }

    if (role === UserRole.ADMINISTRATOR) {
      this.rowMenuConfig.cancel = true;
    }

    if (role === UserRole.SALES_PERSON) {
      this.rowMenuConfig.showMenu = false;
    }
  }

  private getCompanyTemplates(): void {
    this.sharedService.getTemplates().subscribe(response => {
      this.templates = response;
    });
  }
}
