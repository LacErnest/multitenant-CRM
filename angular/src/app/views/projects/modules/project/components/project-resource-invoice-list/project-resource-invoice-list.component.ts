import { Component, Inject, OnInit, ViewChild } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { finalize } from 'rxjs/operators';
import { DatatableButtonConfig } from 'src/app/shared/classes/datatable/datatable-button-config';
import { DatatableContainerBase } from 'src/app/shared/classes/datatable/datatable-container-base';
import { DatatableMenuConfig } from 'src/app/shared/classes/datatable/datatable-menu-config';
import { DownloadModalComponent } from 'src/app/shared/components/download-modal/download-modal.component';
import { ExportFormat } from 'src/app/shared/enums/export.format';
import { TablePreferencesService } from 'src/app/shared/services/table-preferences.service';
import { Project } from 'src/app/views/projects/modules/project/interfaces/project';
import { ProjectService } from 'src/app/views/projects/modules/project/project.service';
import { InvoiceStatusUpdate } from '../../interfaces/invoice-status-update';
import { InvoiceStatus } from '../../enums/invoice-status.enum';
import { ToastrService } from 'ngx-toastr';
import { ProjectInvoiceService } from '../../services/project-invoice.service';
import { UserRole } from '../../../../../../shared/enums/user-role.enum';
import { GlobalService } from '../../../../../../core/services/global.service';
import { ResourceInvoicesService } from '../../../../../resource-invoices/resource-invoices.service';
import { AppStateService } from 'src/app/shared/services/app-state.service';
import { Helpers } from 'src/app/core/classes/helpers';
import { DOCUMENT } from '@angular/common';
import { HttpParams } from '@angular/common/http';

@Component({
  selector: 'oz-finance-project-resource-invoice-list',
  templateUrl: './project-resource-invoice-list.component.html',
  styleUrls: ['./project-resource-invoice-list.component.scss'],
})
export class ProjectResourceInvoiceListComponent
  extends DatatableContainerBase
  implements OnInit
{
  @ViewChild('downloadModal', { static: false })
  public downloadModal: DownloadModalComponent;

  public buttonConfig: DatatableButtonConfig = new DatatableButtonConfig({
    columns: true,
    filters: true,
    delete: false,
    export: this.globalService.canExport(),
  });
  public rowMenuConfig: DatatableMenuConfig = new DatatableMenuConfig({
    delete: false,
    clone: false,
    edit: false,
    view: true,
  });
  public resourceInvoices: { data: any; count: number };

  public project: Project;

  public constructor(
    protected route: ActivatedRoute,
    protected tablePreferencesService: TablePreferencesService,
    private projectInvoiceService: ProjectInvoiceService,
    private toastService: ToastrService,
    private globalService: GlobalService,
    private projectService: ProjectService,
    private router: Router,
    private resourceInvoicesService: ResourceInvoicesService,
    protected appStateService: AppStateService,
    @Inject(DOCUMENT) private doc: Document
  ) {
    super(tablePreferencesService, route, appStateService, doc);
  }

  public ngOnInit(): void {
    this.getResolvedData();
    this.setPermissions();
  }

  public viewResourceInvoice(id: string): void {
    this.router.navigate([id], { relativeTo: this.route }).then();
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

  public cancelResourceInvoice({ id: invoiceId }): void {
    this.isLoading = true;

    const status: InvoiceStatusUpdate = {
      status: InvoiceStatus.CANCELED,
    };

    this.projectInvoiceService
      .changeProjectInvoiceStatus(this.project.id, invoiceId, status)
      .pipe(
        finalize(() => {
          this.isLoading = false;
        })
      )
      .subscribe(
        response => {
          this.getData();
          this.toastService.success(
            'Resource invoice canceled successfully',
            'Success'
          );
        },
        error => {
          this.toastService.error(error.error?.message, 'Update failed');
        }
      );
  }

  protected getData(): void {
    this.isLoading = true;

    this.resourceInvoicesService
      .getResourceInvoicesProject(this.project.id, this.params)
      .pipe(
        finalize(() => {
          this.isLoading = false;
        })
      )
      .subscribe(response => {
        this.resourceInvoices = response;
      });
  }

  private getResolvedData(): void {
    this.project = this.route.snapshot.parent.parent.data.project;
    this.preferences = this.route.snapshot.data.tablePreferences;
    this.getData();
  }

  private setPermissions(): void {
    const role = this.globalService.getUserRole();

    const readOnlyMode = [
      UserRole.SALES_PERSON,
      UserRole.PROJECT_MANAGER,
      UserRole.OWNER_READ_ONLY,
      UserRole.PROJECT_MANAGER_RESTRICTED,
    ].includes(role);

    const isProjectManager = role === UserRole.PROJECT_MANAGER;
    const isProjectManagerRestricted =
      role === UserRole.PROJECT_MANAGER_RESTRICTED;
    const isCurrentProjectManager = this.projectService.isCurrentProjectManager(
      this.project
    );

    if (readOnlyMode) {
      this.rowMenuConfig.edit = false;
      this.rowMenuConfig.view =
        isProjectManager ||
        (isProjectManagerRestricted && isCurrentProjectManager) ||
        true;
      this.rowMenuConfig.cancel = false;
      this.rowMenuConfig.clone = false;
      this.rowMenuConfig.export = false;
    }

    if (role === UserRole.ADMINISTRATOR) {
      this.rowMenuConfig.cancel = true;
    }

    if (role === UserRole.SALES_PERSON) {
      this.rowMenuConfig.showMenu = false;
    }
  }

  public exportResourceInvoices(): void {
    this.isLoading = true;
    const params = Helpers.setParam(
      new HttpParams(),
      'project',
      this.project.id
    );
    this.resourceInvoicesService
      .exportResourceInvoices(params)
      .pipe(finalize(() => (this.isLoading = false)))
      .subscribe(
        response => {
          const type = Helpers.getExportMIMEType(ExportFormat.XLSX);
          const file = new Blob([response], { type });
          this.createLinkForDownloading(
            ExportFormat.XLSX,
            file,
            'ResourceInvoices'
          );
        },
        error => {
          this.toastService.error(error.error?.message, 'Download failed');
        }
      );
  }
}
