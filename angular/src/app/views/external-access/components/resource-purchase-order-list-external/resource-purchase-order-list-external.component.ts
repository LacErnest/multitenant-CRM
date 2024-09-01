import { Component, Inject, Input, OnInit, ViewChild } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { ToastrService } from 'ngx-toastr';
import { finalize } from 'rxjs/operators';
import { DatatableButtonConfig } from 'src/app/shared/classes/datatable/datatable-button-config';
import { DatatableContainerBase } from 'src/app/shared/classes/datatable/datatable-container-base';
import { DatatableDetailConfig } from 'src/app/shared/classes/datatable/datatable-detail-config';
import { DatatableMenuConfig } from 'src/app/shared/classes/datatable/datatable-menu-config';
import { DownloadModalComponent } from 'src/app/shared/components/download-modal/download-modal.component';
import { ExportFormat } from 'src/app/shared/enums/export.format';
import { Resource } from 'src/app/shared/interfaces/resource';
import { TablePreferencesService } from 'src/app/shared/services/table-preferences.service';
import { ExternalAccessService } from 'src/app/views/external-access/external-access.service';
import { TablePreferences } from '../../../../shared/interfaces/table-preferences';
import { DOCUMENT } from '@angular/common';
import { AppStateService } from 'src/app/shared/services/app-state.service';

@Component({
  selector: 'oz-finance-resource-purchase-order-list-external',
  templateUrl: './resource-purchase-order-list-external.component.html',
  styleUrls: ['./resource-purchase-order-list-external.component.scss'],
})
export class ResourcePurchaseOrderListExternalComponent
  extends DatatableContainerBase
  implements OnInit
{
  @Input() public companyID: string;
  @Input() public resource: Resource;
  @Input() public preferences: TablePreferences;
  @ViewChild('downloadModal', { static: false })
  public downloadModal: DownloadModalComponent;

  public buttonConfig: DatatableButtonConfig = new DatatableButtonConfig({
    add: false,
    export: false,
    columns: false,
    filters: false,
    delete: false,
  });
  public rowMenuConfig: DatatableMenuConfig = new DatatableMenuConfig({
    delete: false,
    clone: false,
    edit: false,
    view: false,
    uploadInvoice: true,
  });
  public detailConfig: DatatableDetailConfig = new DatatableDetailConfig();

  public constructor(
    protected tablePreferencesService: TablePreferencesService,
    private externalAccessService: ExternalAccessService,
    protected route: ActivatedRoute,
    private router: Router,
    private toastrService: ToastrService,
    protected appStateService: AppStateService
  ) {
    super(tablePreferencesService, route, appStateService);
  }

  public ngOnInit(): void {
    this.getResolvedData();
  }

  public invoiceUploaded({ uploaded, row }): void {
    this.isLoading = true;

    this.externalAccessService
      .uploadInvoice(row.company_id, this.resource.id, row.id, uploaded)
      .pipe(finalize(() => (this.isLoading = false)))
      .subscribe(
        () => {
          this.toastrService.success('File successfully uploaded', 'Success');
          this.getData();
        },
        () => {
          this.toastrService.error('File was not uploaded', 'Error');
        }
      );
  }

  public downloadInvoice({
    row: { id: purchaseOrderId, company_id },
    detailRow: { id: resourceInvoiceId, number },
  }): void {
    this.downloadModal
      .openModal(
        this.externalAccessService.downloadInvoiceCallback,
        [company_id, this.resource.id, purchaseOrderId, resourceInvoiceId],
        `Resource Invoice: ${number}}`,
        [ExportFormat.PDF]
      )
      .subscribe();
  }

  public downloadPurchaseOrder({ company_id, id, number, resource_id }): void {
    this.downloadModal
      .openModal(
        this.externalAccessService.downloadPurchaseOrderCallback,
        [company_id, resource_id, id],
        `Purchase Order: ${number}`
      )
      .subscribe();
  }

  public viewResourceInvoicePage({ id, project_id }): void {
    this.router.navigate([
      `${this.companyID}/projects/${project_id}/resource_invoices/${id}`,
    ]);
  }

  protected getData(): void {
    this.isLoading = true;

    this.externalAccessService
      .getResource(this.companyID, this.resource.id)
      .pipe(
        finalize(() => {
          this.isLoading = false;
        })
      )
      .subscribe(response => {
        this.resource = response;
        this.setPurchaseOrders();
      });
  }

  private getResolvedData(): void {
    this.setPurchaseOrders();
    this.preferences = this.route.snapshot.data.tablePreferences;
  }

  private setPurchaseOrders(): void {
    this.rows.data = this.resource?.purchase_orders;
  }
}
