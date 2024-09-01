import { Component, Inject, OnInit, ViewChild } from '@angular/core';
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
import { Invoice } from 'src/app/shared/interfaces/entities';
import { TablePreferencesService } from 'src/app/shared/services/table-preferences.service';
import { InvoicesService } from 'src/app/views/invoices/invoices.service';
import { OrderStatus } from 'src/app/views/projects/modules/project/enums/order-status.enum';
import { Project } from 'src/app/views/projects/modules/project/interfaces/project';
import { ProjectService } from 'src/app/views/projects/modules/project/project.service';
import { ProjectInvoiceService } from 'src/app/views/projects/modules/project/services/project-invoice.service';
import { InvoiceStatusUpdate } from '../../interfaces/invoice-status-update';
import { InvoiceStatus } from '../../enums/invoice-status.enum';
import { TemplateModel } from '../../../../../../shared/interfaces/template-model';
import { SharedService } from '../../../../../../shared/services/shared.service';
import { AppStateService } from 'src/app/shared/services/app-state.service';
import { ExportFormat } from 'src/app/shared/enums/export.format';
import { Helpers } from 'src/app/core/classes/helpers';
import { DOCUMENT } from '@angular/common';
import { HttpParams } from '@angular/common/http';

@Component({
  selector: 'oz-finance-project-invoice-list',
  templateUrl: './project-invoice-list.component.html',
  styleUrls: ['./project-invoice-list.component.scss'],
})
export class ProjectInvoiceListComponent
  extends DatatableContainerBase
  implements OnInit
{
  @ViewChild('downloadModal') public downloadModal: DownloadModalComponent;

  public project: Project;
  public buttonConfig: DatatableButtonConfig = new DatatableButtonConfig({
    columns: true,
    filters: true,
    delete: false,
    export: this.globalService.canExport(),
  });
  public rowMenuConfig: DatatableMenuConfig = new DatatableMenuConfig({
    delete: false,
  });
  public projectEntity = ProjectEntityEnum;
  public invoices: { data: any; count: number };
  public templates: TemplateModel[] = [];

  private template_id: string;

  public constructor(
    protected route: ActivatedRoute,
    protected tablePreferencesService: TablePreferencesService,
    private projectInvoiceService: ProjectInvoiceService,
    private projectService: ProjectService,
    private router: Router,
    private toastrService: ToastrService,
    private globalService: GlobalService,
    private invoicesService: InvoicesService,
    private sharedService: SharedService,
    protected appStateService: AppStateService,
    private toastService: ToastrService,
    @Inject(DOCUMENT) private doc: Document
  ) {
    super(tablePreferencesService, route, appStateService, doc);
  }

  public ngOnInit(): void {
    this.getResolvedData();
    this.setPermissions();
    this.getCompanyTemplates();
  }

  public addInvoice(): void {
    this.router.navigate(['create'], { relativeTo: this.route }).then();
  }

  public editInvoice(id: string): void {
    this.router.navigate([id, 'edit'], { relativeTo: this.route }).then();
  }

  public cloneInvoice({ id, destination }): void {
    this.isLoading = true;

    this.projectInvoiceService
      .cloneProjectInvoice(this.project.id, id, destination)
      .pipe(finalize(() => (this.isLoading = false)))
      .subscribe(() => {
        this.toastrService.success('Invoice cloned successfully', 'Success');
        this.getData();
      });
  }

  public downloadInvoice({ id, number }): void {
    this.template_id = this.templates[0]['id'];
    this.downloadModal
      .openModal(
        this.invoicesService.exportInvoiceCallback,
        [this.project.id, id, this.template_id],
        `Invoice: ${number}`,
        null,
        null,
        this.templates
      )
      .subscribe();
  }

  public deleteInvoices(invoices: Invoice[]): void {
    this.isLoading = true;

    this.projectInvoiceService
      .deleteProjectInvoices(
        this.project.id,
        invoices.map(q => q.id.toString())
      )
      .pipe(finalize(() => (this.isLoading = false)))
      .subscribe(() => {
        this.getData();
        const msgBeginning =
          invoices.length > 1 ? 'Invoices have' : 'Invoice has';
        this.toastrService.success(
          `${msgBeginning} been successfully deleted`,
          'Success'
        );
      });
  }

  public cancelInvoice({ id: invoiceId }): void {
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
          this.toastrService.success(
            'Invoice canceled successfully',
            'Success'
          );
        },
        error => {
          this.toastrService.error(error.error?.message, 'Update failed');
        }
      );
  }

  protected getData(): void {
    this.isLoading = true;

    this.invoicesService
      .getProjectInvoices(this.project.id, this.params)
      .pipe(finalize(() => (this.isLoading = false)))
      .subscribe(response => {
        this.invoices = response;
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

    if (this.project.order?.status > 1) {
      this.rowMenuConfig.order = false;
      this.rowMenuConfig.edit = false;
      this.rowMenuConfig.view =
        isProjectManager ||
        (isProjectManagerRestricted && isCurrentProjectManager) ||
        true;
      this.buttonConfig.add = false;
    }

    if (readOnlyMode) {
      this.buttonConfig.add =
        isProjectManager ||
        (isProjectManagerRestricted && isCurrentProjectManager);
      this.rowMenuConfig.edit =
        isProjectManager ||
        (isProjectManagerRestricted && isCurrentProjectManager);
      this.rowMenuConfig.view =
        isProjectManager ||
        (isProjectManagerRestricted && isCurrentProjectManager);
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

  private getCompanyTemplates(): void {
    this.sharedService.getTemplates().subscribe(response => {
      this.templates = response;
    });
  }

  public exportInvoices(): void {
    this.isLoading = true;
    const params = Helpers.setParam(
      new HttpParams(),
      'project',
      this.project.id
    );
    this.invoicesService
      .exportInvoices(params)
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
}
