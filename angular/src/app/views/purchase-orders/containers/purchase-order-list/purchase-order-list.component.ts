import { Component, Inject, OnDestroy, OnInit, ViewChild } from '@angular/core';
import { PurchaseOrder } from 'src/app/shared/interfaces/entities';
import { EnumService } from '../../../../core/services/enum.service';
import { ActivatedRoute, NavigationEnd, Router } from '@angular/router';
import { TablePreferencesService } from '../../../../shared/services/table-preferences.service';
import { ToastrService } from 'ngx-toastr';
import { DatatableContainerBase } from '../../../../shared/classes/datatable/datatable-container-base';
import { DatatableButtonConfig } from '../../../../shared/classes/datatable/datatable-button-config';
import { GlobalService } from '../../../../core/services/global.service';
import { filter, finalize, skip } from 'rxjs/operators';
import { PurchaseOrdersService } from '../../purchase-orders.service';
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
  selector: 'oz-finance-purchase-order-list',
  templateUrl: './purchase-order-list.component.html',
  styleUrls: ['./purchase-order-list.component.scss'],
})
export class PurchaseOrderListComponent
  extends DatatableContainerBase
  implements OnInit, OnDestroy
{
  @ViewChild('downloadModal', { static: false })
  private downloadModal: DownloadModalComponent;

  public buttonConfig: DatatableButtonConfig = new DatatableButtonConfig({
    add: !this.isOwnerReadOnly() && !this.isPmRestricted(),
    delete: false,
    refresh: true,
    export: this.globalService.canExport(),
  });
  public rowMenuConfig: DatatableMenuConfig = new DatatableMenuConfig({
    delete: false,
    clone: !this.isOwnerReadOnly(),
  });
  public isLoading = false;
  public purchaseOrders: { data: any; count: number }; // TODO: add interface
  public templates: TemplateModel[] = [];

  private navigationSub: Subscription;
  private companySub: Subscription;
  private template_id: string;

  constructor(
    private enumService: EnumService,
    private globalService: GlobalService,
    public purchaseOrdersService: PurchaseOrdersService,
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
    super.ngOnInit();
    this.getResolvedData();
    this.getCompanyTemplates();
    this.companySub = this.globalService
      .getCurrentCompanyObservable()
      .pipe(skip(1))
      .subscribe(value => {
        this.resetPaging();
        if (value?.id === 'all') {
          this.router.navigate(['/']).then();
        } else {
          this.router.navigate(['/' + value.id + '/purchase_orders']).then();
        }
      });

    this.navigationSub = this.router.events
      .pipe(filter(e => e instanceof NavigationEnd))
      .subscribe(() => this.getResolvedData());
  }

  public ngOnDestroy(): void {
    this.navigationSub?.unsubscribe();
    this.companySub?.unsubscribe();
  }

  protected getData(): void {
    this.isLoading = true;
    this.checkIfAnalyticsDataFetch();

    this.purchaseOrdersService
      .getPurchaseOrders(this.params)
      .pipe(finalize(() => (this.isLoading = false)))
      .subscribe(response => {
        this.purchaseOrders = response;
      });
  }

  public addPurchaseOrder(): void {
    this.routingService.setNext();
    this.router
      .navigate([
        `/${this.globalService.currentCompany.id}/projects/create_purchase_order`,
      ])
      .then();
  }

  public editPurchaseOrder({ id, project_id: projectId }): void {
    this.routingService.setNext();
    this.router
      .navigate([
        `/${this.globalService.currentCompany.id}/projects/${projectId}/purchase_orders/${id}/edit`,
      ])
      .then();
  }

  public deletePurchaseOrders(purchaseOrders: PurchaseOrder[]): void {
    this.isLoading = true;

    this.purchaseOrdersService
      .deletePurchaseOrders(purchaseOrders.map(o => o.id.toString()))
      .pipe(finalize(() => (this.isLoading = false)))
      .subscribe(() => {
        /*        const [deletedOrder] = purchaseOrders;
                const index = this.purchaseOrders.data.findIndex(o => o.id === deletedOrder.id);
                this.purchaseOrders.data[index].status = 2;*/
        this.getData();
        const msgBeginning =
          purchaseOrders.length > 1
            ? 'Purchase orders have'
            : 'Purchase order has';
        this.toastService.success(
          `${msgBeginning} been successfully deleted`,
          'Success'
        );
      });
  }

  public downloadPurchaseOrder({ project_id, id, number }): void {
    this.template_id = this.templates[0]['id'];
    this.downloadModal
      .openModal(
        this.purchaseOrdersService.exportPurchaseOrderCallback,
        [project_id, id, this.template_id],
        'Purchase Order: ' + number,
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

  public clonePurchaseOrder({ id, projectID, destination }): void {
    this.isLoading = true;
    this.purchaseOrdersService
      .clonePurchaseOrder(projectID, id, destination)
      .pipe(
        finalize(() => {
          this.isLoading = false;
        })
      )
      .subscribe(() => {
        this.toastService.success(
          'Purchase order cloned successfully',
          'Success'
        );
        this.getData();
      });
  }

  public exportPurchaseOrders(): void {
    this.isLoading = true;
    this.purchaseOrdersService
      .exportPurchaseOrders()
      .pipe(finalize(() => (this.isLoading = false)))
      .subscribe(
        response => {
          const type = Helpers.getExportMIMEType(ExportFormat.XLSX);
          const file = new Blob([response], { type });
          this.createLinkForDownloading(
            ExportFormat.XLSX,
            file,
            'Purchase Orders'
          );
        },
        error => {
          this.toastService.error(error.error?.message, 'Download failed');
        }
      );
  }

  private getResolvedData(): void {
    const { purchaseOrders, table_preferences } = this.route.snapshot.data;
    this.purchaseOrders = purchaseOrders;
    this.preferences = table_preferences;
  }

  private isOwnerReadOnly(): boolean {
    return this.globalService.getUserRole() === UserRole.OWNER_READ_ONLY;
  }

  private isPmRestricted(): boolean {
    return (
      this.globalService.getUserRole() === UserRole.PROJECT_MANAGER_RESTRICTED
    );
  }

  private getCompanyTemplates(): void {
    this.sharedService.getTemplates().subscribe(response => {
      this.templates = response;
    });
  }
}
