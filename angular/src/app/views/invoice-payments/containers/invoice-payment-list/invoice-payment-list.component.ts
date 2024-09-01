import { Component, Inject, OnDestroy, OnInit, ViewChild } from '@angular/core';
import { InvoicePayment } from 'src/app/shared/interfaces/entities';
import { EnumService } from '../../../../core/services/enum.service';
import { DatatableButtonConfig } from '../../../../shared/classes/datatable/datatable-button-config';
import { GlobalService } from '../../../../core/services/global.service';
import { ActivatedRoute, NavigationEnd, Router } from '@angular/router';
import { TablePreferencesService } from '../../../../shared/services/table-preferences.service';
import { ToastrService } from 'ngx-toastr';
import { DatatableContainerBase } from '../../../../shared/classes/datatable/datatable-container-base';
import { InvoicePaymentsService } from '../../invoice-payments.service';
import { filter, finalize, skip } from 'rxjs/operators';
import { Subscription } from 'rxjs';
import { DOCUMENT } from '@angular/common';
import { DatatableMenuConfig } from '../../../../shared/classes/datatable/datatable-menu-config';
import { DownloadModalComponent } from '../../../../shared/components/download-modal/download-modal.component';
import { RoutingService } from '../../../../core/services/routing.service';
import { Helpers } from '../../../../core/classes/helpers';
import { ExportFormat } from '../../../../shared/enums/export.format';
import { UserRole } from '../../../../shared/enums/user-role.enum';
import { SharedService } from '../../../../shared/services/shared.service';
import { TemplateModel } from '../../../../shared/interfaces/template-model';
import { AppStateService } from '../../../../shared/services/app-state.service';

@Component({
  selector: 'oz-finance-invoice-payment-list',
  templateUrl: './invoice-payment-list.component.html',
  styleUrls: ['./invoice-payment-list.component.scss'],
})
export class InvoicePaymentListComponent
  extends DatatableContainerBase
  implements OnInit, OnDestroy
{
  @ViewChild('downloadModal', { static: false })
  private downloadModal: DownloadModalComponent;

  public buttonConfig: DatatableButtonConfig = new DatatableButtonConfig({
    add: false,
    delete: false,
  });
  public rowMenuConfig: DatatableMenuConfig = new DatatableMenuConfig({
    delete: false,
  });
  public invoice_payments: { data: any; count: number }; // TODO: add interface
  public invoice: any;
  public isLoading = false;
  public templates: TemplateModel[] = [];

  private navigationSub: Subscription;
  private companySub: Subscription;
  private template_id: string;

  constructor(
    private enumService: EnumService,
    private globalService: GlobalService,
    public invoicesPaymentService: InvoicePaymentsService,
    protected route: ActivatedRoute,
    private router: Router,
    private routingService: RoutingService,
    protected tablePreferencesService: TablePreferencesService,
    private toastService: ToastrService,
    private sharedService: SharedService,
    protected appStateService: AppStateService,
    @Inject(DOCUMENT) private doc: Document
  ) {
    super(tablePreferencesService, route, appStateService, doc);
  }

  public ngOnInit(): void {
    this.getResolvedData();
    this.setPermissions();
    this.getCompanyTemplates();
    if (this.globalService.getUserRole() === 0) {
      this.companySub = this.globalService
        .getCurrentCompanyObservable()
        .pipe(skip(1))
        .subscribe(value => {
          this.resetPaging();
          if (value?.id === 'all') {
            this.router.navigate(['/']).then();
          } else {
            this.router.navigate(['/' + value.id + '/invoice-payments']).then();
          }
        });

      this.navigationSub = this.router.events
        .pipe(filter(e => e instanceof NavigationEnd))
        .subscribe(() => this.getResolvedData());
    }
  }

  public ngOnDestroy(): void {
    this.navigationSub?.unsubscribe();
    this.companySub?.unsubscribe();
  }

  protected getData(): void {
    this.isLoading = true;
    this.checkIfAnalyticsDataFetch();

    this.invoicesPaymentService
      .getInvoicePayments(this.invoice.id, this.params)
      .pipe(finalize(() => (this.isLoading = false)))
      .subscribe(response => {
        this.invoice_payments = response;
      });
  }

  public editInvoicePayment({
    id: invoicePaymentId,
    invoice_id: invoiceId,
    project_id: projectId,
  }): void {
    //
  }

  public deleteInvoicePayment(invoicePayments: InvoicePayment[]): void {
    this.isLoading = true;

    this.invoicesPaymentService
      .deleteInvoicePayments(
        this.invoice.id,
        invoicePayments.map(i => i.id.toString())
      )
      .pipe(finalize(() => (this.isLoading = false)))
      .subscribe(() => {
        this.getData();
        const msgBeginning =
          invoicePayments.length > 1 ? 'Payments have' : 'Payment has';
        this.toastService.success(
          `${msgBeginning} been successfully deleted`,
          'Success'
        );
      });
  }

  public downloadInvoice({ project_id, invoice_id, id, number }): void {
    this.template_id = this.templates[0]['id'];
    this.downloadModal
      .openModal(
        this.invoicesPaymentService.exportInvoicePaymentCallback,
        [project_id, invoice_id, id, this.template_id],
        'Invoice: ' + number,
        null,
        null,
        this.templates
      )
      .subscribe(
        () => {
          //
        },
        () => {
          //
        }
      );
  }

  public cloneInvoicePayment({ id, projectID, invoiceId, destination }): void {
    this.isLoading = true;

    this.invoicesPaymentService
      .cloneInvoicePayment(projectID, invoiceId, id, destination)
      .pipe(
        finalize(() => {
          this.isLoading = false;
        })
      )
      .subscribe(() => {
        this.toastService.success('Payment cloned successfully', 'Success');
        this.getData();
      });
  }

  public exportInvoicePayments(): void {
    this.isLoading = true;
    this.invoicesPaymentService
      .exportInvoicePayments(this.invoice.id)
      .pipe(finalize(() => (this.isLoading = false)))
      .subscribe(
        response => {
          const type = Helpers.getExportMIMEType(ExportFormat.XLSX);
          const file = new Blob([response], { type });
          this.createLinkForDownloading(
            ExportFormat.XLSX,
            file,
            'Invoices Payments'
          );
        },
        error => {
          this.toastService.error(error.error?.message, 'Download failed');
        }
      );
  }

  private getResolvedData(): void {
    const { invoice, invoice_payments, table_preferences } =
      this.route.snapshot.data;
    this.invoice_payments = invoice_payments;
    this.invoice = invoice;
    this.preferences = table_preferences;
  }

  private setPermissions(): void {
    if ([3, 4, 7].includes(this.globalService.getUserRole())) {
      this.buttonConfig.add = false;
      this.rowMenuConfig.edit = false;
      this.rowMenuConfig.view = true;
      this.rowMenuConfig.clone = false;
      this.rowMenuConfig.export = false;
    }

    if (this.globalService.getUserRole() === UserRole.OWNER_READ_ONLY) {
      this.buttonConfig.add = false;
      this.rowMenuConfig.clone = false;
    }

    if (this.globalService.getUserRole() === UserRole.SALES_PERSON) {
      this.rowMenuConfig.showMenu = false;
    }
  }

  private getCompanyTemplates(): void {
    this.sharedService.getTemplates().subscribe(response => {
      this.templates = response;
    });
  }
}
