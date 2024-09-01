import { Component, Inject, OnDestroy, OnInit, ViewChild } from '@angular/core';
import { Invoice } from 'src/app/shared/interfaces/entities';
import { EnumService } from '../../../../core/services/enum.service';
import { DatatableButtonConfig } from '../../../../shared/classes/datatable/datatable-button-config';
import { GlobalService } from '../../../../core/services/global.service';
import { ActivatedRoute, NavigationEnd, Router } from '@angular/router';
import { TablePreferencesService } from '../../../../shared/services/table-preferences.service';
import { ToastrService } from 'ngx-toastr';
import { DatatableContainerBase } from '../../../../shared/classes/datatable/datatable-container-base';
import { InvoicesService } from '../../invoices.service';
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
import { AppStateService } from 'src/app/shared/services/app-state.service';

@Component({
  selector: 'oz-finance-invoice-list',
  templateUrl: './invoice-list.component.html',
  styleUrls: ['./invoice-list.component.scss'],
})
export class InvoiceListComponent
  extends DatatableContainerBase
  implements OnInit, OnDestroy
{
  @ViewChild('downloadModal', { static: false })
  private downloadModal: DownloadModalComponent;

  public buttonConfig: DatatableButtonConfig = new DatatableButtonConfig({
    add: false,
    delete: false,
    refresh: true,
    export: this.globalService.canExport(),
  });
  public rowMenuConfig: DatatableMenuConfig = new DatatableMenuConfig({
    delete: false,
  });
  public invoices: { data: any; count: number }; // TODO: add interface
  public isLoading = false;
  public templates: TemplateModel[] = [];

  private navigationSub: Subscription;
  private companySub: Subscription;
  private template_id: string;

  constructor(
    private enumService: EnumService,
    private globalService: GlobalService,
    public invoicesService: InvoicesService,
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
            this.router.navigate(['/' + value.id + '/invoices']).then();
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

    this.invoicesService
      .getInvoices(this.params)
      .pipe(finalize(() => (this.isLoading = false)))
      .subscribe(response => {
        this.invoices = response;
      });
  }

  public editInvoice({ id: invoiceId, project_id: projectId }): void {
    this.routingService.setNext();
    this.router
      .navigate([
        `/${this.globalService.currentCompany.id}/projects/${projectId}/invoices/${invoiceId}/edit`,
      ])
      .then();
  }

  public deleteInvoice(invoices: Invoice[]): void {
    this.isLoading = true;

    this.invoicesService
      .deleteInvoices(invoices.map(i => i.id.toString()))
      .pipe(finalize(() => (this.isLoading = false)))
      .subscribe(() => {
        /*        const [deletedInvoice] = invoices;
                const index = this.invoices.data.findIndex(i => i.id === deletedInvoice.id);
                this.invoices.data[index].status = 2;*/
        this.getData();
        const msgBeginning =
          invoices.length > 1 ? 'Invoices have' : 'Invoice has';
        this.toastService.success(
          `${msgBeginning} been successfully deleted`,
          'Success'
        );
      });
  }

  public downloadInvoice({ project_id, id, number }): void {
    this.template_id = this.templates[0]['id'];
    this.downloadModal
      .openModal(
        this.invoicesService.exportInvoiceCallback,
        [project_id, id, this.template_id],
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

  public cloneInvoice({ id, projectID, destination }): void {
    this.isLoading = true;

    this.invoicesService
      .cloneInvoice(projectID, id, destination)
      .pipe(
        finalize(() => {
          this.isLoading = false;
        })
      )
      .subscribe(() => {
        this.toastService.success('Invoice cloned successfully', 'Success');
        this.getData();
      });
  }

  public exportInvoices(): void {
    this.isLoading = true;
    this.invoicesService
      .exportInvoices()
      .pipe(finalize(() => (this.isLoading = false)))
      .subscribe(
        response => {
          const type = Helpers.getExportMIMEType(ExportFormat.XLSX);
          const file = new Blob([response], { type });
          this.createLinkForDownloading(ExportFormat.XLSX, file, 'Invoices');
        },
        error => {
          this.toastService.error(error.error?.message, 'Download failed');
        }
      );
  }

  private getResolvedData(): void {
    const { invoices, table_preferences } = this.route.snapshot.data;
    this.invoices = invoices;
    this.preferences = table_preferences;
    this.invoices.data.forEach(
      i => (i.is_deletion_allowed = !!i.legal_entity_id)
    );
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
