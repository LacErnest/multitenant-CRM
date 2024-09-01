import { Component, Inject, OnDestroy, OnInit, ViewChild } from '@angular/core';
import { ActivatedRoute, NavigationEnd, Router } from '@angular/router';
import { Subject } from 'rxjs';
import { filter, finalize, skip, takeUntil } from 'rxjs/operators';
import { EnumService } from 'src/app/core/services/enum.service';
import { GlobalService } from 'src/app/core/services/global.service';
import { RoutingService } from 'src/app/core/services/routing.service';
import { DatatableButtonConfig } from 'src/app/shared/classes/datatable/datatable-button-config';
import { DatatableContainerBase } from 'src/app/shared/classes/datatable/datatable-container-base';
import { DatatableMenuConfig } from 'src/app/shared/classes/datatable/datatable-menu-config';
import { DownloadModalComponent } from 'src/app/shared/components/download-modal/download-modal.component';
import { ExportFormat } from 'src/app/shared/enums/export.format';
import { TablePreferencesService } from 'src/app/shared/services/table-preferences.service';
import { ProjectService } from 'src/app/views/projects/modules/project/project.service';
import { ResourceInvoiceList } from 'src/app/views/resource-invoices/interfaces/resource-invoice-list';
import { ResourceInvoicesService } from 'src/app/views/resource-invoices/resource-invoices.service';
import { Helpers } from '../../../../core/classes/helpers';
import { DOCUMENT } from '@angular/common';
import { ToastrService } from 'ngx-toastr';
import { UserRole } from '../../../../shared/enums/user-role.enum';
import { AppStateService } from 'src/app/shared/services/app-state.service';

@Component({
  selector: 'oz-finance-resource-invoice-list',
  templateUrl: './resource-invoice-list.component.html',
  styleUrls: ['./resource-invoice-list.component.scss'],
})
export class ResourceInvoiceListComponent
  extends DatatableContainerBase
  implements OnInit, OnDestroy
{
  @ViewChild('downloadModal', { static: false })
  public downloadModal: DownloadModalComponent;

  public buttonConfig: DatatableButtonConfig = new DatatableButtonConfig({
    add: false,
    delete: false,
    export: this.globalService.canExport(),
  });
  public rowMenuConfig: DatatableMenuConfig = new DatatableMenuConfig({
    clone: false,
    delete: false,
    edit: false,
    view: true,
    showMenu: !this.isSalesPerson(),
  });

  public isLoading = false;
  public resourceInvoices: ResourceInvoiceList;

  private onDestroy$: Subject<void> = new Subject<void>();

  public constructor(
    protected route: ActivatedRoute,
    protected tablePreferencesService: TablePreferencesService,
    private enumService: EnumService,
    private globalService: GlobalService,
    private resourceInvoicesService: ResourceInvoicesService,
    private router: Router,
    private routingService: RoutingService,
    private projectService: ProjectService,
    private toastService: ToastrService,
    protected appStateService: AppStateService,
    @Inject(DOCUMENT) private doc: Document
  ) {
    super(tablePreferencesService, route, appStateService, doc);
  }

  public ngOnInit(): void {
    this.getResolvedData();
    this.setSubscriptions();
  }

  public ngOnDestroy(): void {
    this.onDestroy$?.next();
    this.onDestroy$?.complete();
  }

  public download({ resource_id, purchase_order_id, id, number }): void {
    this.downloadModal
      .openModal(
        this.projectService.exportResourceInvoiceCallback,
        [resource_id, purchase_order_id, id],
        `Resource Invoice: ${number}}`,
        [ExportFormat.PDF]
      )
      .subscribe();
  }

  public exportResourceInvoices(): void {
    this.isLoading = true;
    this.resourceInvoicesService
      .exportResourceInvoices()
      .pipe(finalize(() => (this.isLoading = false)))
      .subscribe(
        response => {
          const type = Helpers.getExportMIMEType(ExportFormat.XLSX);
          const file = new Blob([response], { type });
          this.createLinkForDownloading(
            ExportFormat.XLSX,
            file,
            'Resource Invoices'
          );
        },
        error => {
          this.toastService.error(error.error?.message, 'Download failed');
        }
      );
  }

  protected getData(): void {
    this.isLoading = true;

    this.resourceInvoicesService
      .getResourceInvoices(this.params)
      .pipe(finalize(() => (this.isLoading = false)))
      .subscribe(response => {
        this.resourceInvoices = response;
      });
  }

  public viewResourceInvoice({ id, project_id: projectId }): void {
    this.routingService.setNext();
    this.router
      .navigate([
        `/${this.globalService.currentCompany.id}/projects/${projectId}/resource_invoices/${id}`,
      ])
      .then();
  }

  private getResolvedData(): void {
    const { resourceInvoices, table_preferences } = this.route.snapshot.data;
    this.resourceInvoices = resourceInvoices;
    this.preferences = table_preferences;
  }

  private setSubscriptions(): void {
    this.globalService
      .getCurrentCompanyObservable()
      .pipe(skip(1), takeUntil(this.onDestroy$))
      .subscribe(value => {
        this.resetPaging();

        if (value?.id === 'all') {
          this.router.navigate(['/']).then();
        } else {
          this.router.navigate([`/${value.id}/resource_invoices`]).then();
        }
      });

    this.router.events
      .pipe(
        filter(e => e instanceof NavigationEnd),
        takeUntil(this.onDestroy$)
      )
      .subscribe(() => this.getResolvedData());
  }

  private isSalesPerson(): boolean {
    if (this.globalService.getUserRole() === UserRole.SALES_PERSON) {
      return true;
    }
  }
}
