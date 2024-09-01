import { Component, Inject, OnInit, ViewChild } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { ToastrService } from 'ngx-toastr';
import { finalize } from 'rxjs/operators';
import { DatatableButtonConfig } from 'src/app/shared/classes/datatable/datatable-button-config';
import { DatatableContainerBase } from 'src/app/shared/classes/datatable/datatable-container-base';
import { DatatableMenuConfig } from 'src/app/shared/classes/datatable/datatable-menu-config';
import { DownloadModalComponent } from 'src/app/shared/components/download-modal/download-modal.component';
import { ProjectEntityEnum } from 'src/app/shared/enums/project-entity.enum';
import { ExportFormat } from 'src/app/shared/enums/export.format';
import { PurchaseOrderStatus } from 'src/app/shared/enums/purchase-order-status.enum';
import { PurchaseOrder } from 'src/app/shared/interfaces/entities';
import { TablePreferencesService } from 'src/app/shared/services/table-preferences.service';
import { Project } from 'src/app/views/projects/modules/project/interfaces/project';
import { ProjectService } from 'src/app/views/projects/modules/project/project.service';
import { ProjectPurchaseOrderService } from 'src/app/views/projects/modules/project/services/project-purchase-order.service';
import { PurchaseOrdersService } from 'src/app/views/purchase-orders/purchase-orders.service';
import { UserRole } from 'src/app/shared/enums/user-role.enum';
import { GlobalService } from 'src/app/core/services/global.service';
import { TemplateModel } from '../../../../../../shared/interfaces/template-model';
import { SharedService } from '../../../../../../shared/services/shared.service';
import { AppStateService } from 'src/app/shared/services/app-state.service';
import { Helpers } from 'src/app/core/classes/helpers';
import { DOCUMENT } from '@angular/common';
import { HttpParams } from '@angular/common/http';

@Component({
  selector: 'oz-finance-project-purchase-order-list',
  templateUrl: './project-purchase-order-list.component.html',
  styleUrls: ['./project-purchase-order-list.component.scss'],
})
export class ProjectPurchaseOrderListComponent
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
  public purchaseOrders: { data: any; count: number };
  public templates: TemplateModel[] = [];

  private template_id: string;

  public constructor(
    protected tablePreferencesService: TablePreferencesService,
    private projectPurchaseOrderService: ProjectPurchaseOrderService,
    private projectService: ProjectService,
    private globalService: GlobalService,
    private router: Router,
    protected route: ActivatedRoute,
    private toastrService: ToastrService,
    private purchaseOrdersService: PurchaseOrdersService,
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
  }

  public addPurchaseOrder(): void {
    this.router.navigate(['create'], { relativeTo: this.route }).then();
  }

  public editPurchaseOrder(id: string): void {
    this.router.navigate([id, 'edit'], { relativeTo: this.route }).then();
  }

  public clonePurchaseOrder({ id, destination }): void {
    this.isLoading = true;

    this.projectPurchaseOrderService
      .cloneProjectPurchaseOrder(this.project.id, id, destination)
      .pipe(finalize(() => (this.isLoading = false)))
      .subscribe(() => {
        this.getData();
      });
  }

  public downloadPurchaseOrder({ id, number }): void {
    this.template_id = this.templates[0]['id'];
    this.downloadModal
      .openModal(
        this.purchaseOrdersService.exportPurchaseOrderCallback,
        [this.project.id, id, this.template_id],
        `Purchase Order: ${number}`,
        null,
        null,
        this.templates
      )
      .subscribe();
  }

  public deletePurchaseOrders(purchaseOrders: PurchaseOrder[]): void {
    this.isLoading = true;

    this.projectPurchaseOrderService
      .deleteProjectPurchaseOrders(
        this.project.id,
        purchaseOrders.map(po => po.id.toString())
      )
      .pipe(finalize(() => (this.isLoading = false)))
      .subscribe(() => {
        this.getData();
        const msgBeginning =
          purchaseOrders.length > 1
            ? 'Purchase orders have'
            : 'Purchase order has';
        this.toastrService.success(
          `${msgBeginning} been successfully deleted`,
          'Success'
        );
      });
  }

  public cancelPurchaseOrder({ id: purchaseOrderID }): void {
    this.isLoading = true;

    const status = {
      status: PurchaseOrderStatus.CANCELED,
    };

    this.projectPurchaseOrderService
      .editProjectPurchaseOrderStatus(this.project.id, purchaseOrderID, status)
      .pipe(
        finalize(() => {
          this.isLoading = false;
        })
      )
      .subscribe(
        response => {
          this.getData();
          this.toastrService.success(
            'Purchase order canceled successfully',
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

    this.purchaseOrdersService
      .getProjectPurchaseOrders(this.project.id, this.params)
      .pipe(finalize(() => (this.isLoading = false)))
      .subscribe(response => {
        this.purchaseOrders = response;
      });
  }

  private getResolvedData(): void {
    this.project = this.route.snapshot.parent.parent.data.project;
    // this.rows = this.project.purchase_orders.rows;
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

    this.rows.data.forEach(po => {
      po.is_edit_allowed = po.status === PurchaseOrderStatus.DRAFT;
    });

    this.rowMenuConfig.view =
      this.project.order?.status > PurchaseOrderStatus.DRAFT;

    if (role === UserRole.ADMINISTRATOR || role === UserRole.OWNER) {
      this.rowMenuConfig.cancel = true;
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
        (isProjectManagerRestricted && isCurrentProjectManager) ||
        true;
      this.rowMenuConfig.cancel = false;
      this.rowMenuConfig.clone = false;
      this.rowMenuConfig.export = false;
    }
  }
  private getCompanyTemplates(): void {
    this.sharedService.getTemplates().subscribe(response => {
      this.templates = response;
    });
  }

  public exportPurchaseOrders(): void {
    this.isLoading = true;
    const params = Helpers.setParam(
      new HttpParams(),
      'project',
      this.project.id
    );
    this.purchaseOrdersService
      .exportPurchaseOrders(params)
      .pipe(finalize(() => (this.isLoading = false)))
      .subscribe(
        response => {
          const type = Helpers.getExportMIMEType(ExportFormat.XLSX);
          const file = new Blob([response], { type });
          this.createLinkForDownloading(
            ExportFormat.XLSX,
            file,
            'PurchaseOrders'
          );
        },
        error => {
          this.toastrService.error(error.error?.message, 'Download failed');
        }
      );
  }
}
