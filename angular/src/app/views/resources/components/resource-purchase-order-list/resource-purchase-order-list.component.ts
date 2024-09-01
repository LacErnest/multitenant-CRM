import { Component, Input, OnInit, ViewChild } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { ToastrService } from 'ngx-toastr';
import { GlobalService } from 'src/app/core/services/global.service';
import { RoutingService } from 'src/app/core/services/routing.service';
import { DatatableButtonConfig } from 'src/app/shared/classes/datatable/datatable-button-config';
import { DatatableContainerBase } from 'src/app/shared/classes/datatable/datatable-container-base';
import { DatatableDetailConfig } from 'src/app/shared/classes/datatable/datatable-detail-config';
import { DatatableMenuConfig } from 'src/app/shared/classes/datatable/datatable-menu-config';
import { DownloadModalComponent } from 'src/app/shared/components/download-modal/download-modal.component';
import { ExportFormat } from 'src/app/shared/enums/export.format';
import { PurchaseOrder } from 'src/app/shared/interfaces/entities';
import { Resource } from 'src/app/shared/interfaces/resource';
import { TablePreferences } from 'src/app/shared/interfaces/table-preferences';
import { AppStateService } from 'src/app/shared/services/app-state.service';
import { TablePreferencesService } from 'src/app/shared/services/table-preferences.service';
import { ProjectService } from 'src/app/views/projects/modules/project/project.service';
import { ProjectPurchaseOrderService } from 'src/app/views/projects/modules/project/services/project-purchase-order.service';

@Component({
  selector: 'oz-finance-resource-purchase-order-list',
  templateUrl: './resource-purchase-order-list.component.html',
  styleUrls: ['./resource-purchase-order-list.component.scss'],
})
export class ResourcePurchaseOrderListComponent
  extends DatatableContainerBase
  implements OnInit
{
  @Input() public resource: Resource;
  @Input() public preferences: TablePreferences;

  @ViewChild('downloadModal', { static: false })
  private downloadModal: DownloadModalComponent;

  public buttonConfig: DatatableButtonConfig = new DatatableButtonConfig({
    add: false,
    columns: false,
    filters: false,
    delete: false,
  });
  public rowMenuConfig: DatatableMenuConfig = new DatatableMenuConfig({
    delete: false,
    clone: false,
  });
  public detailConfig: DatatableDetailConfig = new DatatableDetailConfig();

  public constructor(
    protected route: ActivatedRoute,
    protected tablePreferencesService: TablePreferencesService,
    private projectPurchaseOrderService: ProjectPurchaseOrderService,
    private projectService: ProjectService,
    private router: Router,
    private toastrService: ToastrService,
    private globalService: GlobalService,
    private routingService: RoutingService,
    protected appStateService: AppStateService
  ) {
    super(tablePreferencesService, route, appStateService);
  }

  public ngOnInit(): void {
    this.rows.data = this.resource?.purchase_orders;
  }

  public editPurchaseOrder(row: PurchaseOrder): void {
    this.routingService.setNext();

    this.router
      .navigate([
        `${this.globalService.currentCompany.id}/projects/${row.project_id}/purchase_orders/${row.id}`,
      ])
      .then();
  }

  public downloadPurchaseOrder({ id, number, project_id }): void {
    this.downloadModal
      .openModal(
        this.projectPurchaseOrderService.exportProjectPurchaseOrderCallback,
        [project_id, id],
        `Purchase Order: ${number}`
      )
      .subscribe();
  }

  public getData(): void {}

  public downloadResourceInvoice({
    detailRow: { resource_id, purchase_order_id, id, number },
  }): void {
    this.downloadModal
      .openModal(
        this.projectService.exportResourceInvoiceCallback,
        [resource_id, purchase_order_id, id],
        `Resource Invoice: ${number}}`,
        [ExportFormat.PDF]
      )
      .subscribe();
  }

  public viewResourceInvoicePage({ id, project_id }): void {
    this.router.navigate([
      `${this.globalService.currentCompany.id}/projects/${project_id}/resource_invoices/${id}`,
    ]);
  }
}
