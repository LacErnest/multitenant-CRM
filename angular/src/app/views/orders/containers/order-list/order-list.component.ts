import { Component, Inject, OnDestroy, OnInit, ViewChild } from '@angular/core';
import { Order } from 'src/app/shared/interfaces/entities';
import { EnumService } from '../../../../core/services/enum.service';
import { DatatableButtonConfig } from '../../../../shared/classes/datatable/datatable-button-config';
import { OrdersService } from '../../orders.service';
import { ToastrService } from 'ngx-toastr';
import { TablePreferencesService } from '../../../../shared/services/table-preferences.service';
import {
  ActivatedRoute,
  NavigationEnd,
  Router,
  RouterState,
} from '@angular/router';
import { GlobalService } from '../../../../core/services/global.service';
import { DatatableContainerBase } from '../../../../shared/classes/datatable/datatable-container-base';
import { filter, finalize, skip } from 'rxjs/operators';
import { Subscription } from 'rxjs';
import { DOCUMENT } from '@angular/common';
import { Helpers } from '../../../../core/classes/helpers';
import { DatatableMenuConfig } from '../../../../shared/classes/datatable/datatable-menu-config';
import { DownloadModalComponent } from '../../../../shared/components/download-modal/download-modal.component';
import { RoutingService } from '../../../../core/services/routing.service';
import { UserRole } from '../../../../shared/enums/user-role.enum';
import { ExportFormat } from '../../../../shared/enums/export.format';
import { SharedService } from '../../../../shared/services/shared.service';
import { TemplateModel } from '../../../../shared/interfaces/template-model';
import { AppStateService } from 'src/app/shared/services/app-state.service';

@Component({
  selector: 'oz-finance-order-list',
  templateUrl: './order-list.component.html',
  styleUrls: ['./order-list.component.scss'],
})
export class OrderListComponent
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
    clone: false,
  });
  public isLoading = false;
  public orders: { data: any; count: number }; // TODO: add interface
  public templates: TemplateModel[] = [];

  private navigationSub: Subscription;
  private customerSub: Subscription;
  private template_id: string;

  constructor(
    private enumService: EnumService,
    private globalService: GlobalService,
    public ordersService: OrdersService,
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
    this.customerSub = this.globalService
      .getCurrentCompanyObservable()
      .pipe(skip(1))
      .subscribe(value => {
        this.resetPaging();
        if (value?.id === 'all') {
          this.router.navigate(['/']).then();
        } else {
          this.router.navigate(['/' + value.id + '/orders']).then();
        }
      });

    this.navigationSub = this.router.events
      .pipe(filter(e => e instanceof NavigationEnd))
      .subscribe(() => this.getResolvedData());
  }

  public ngOnDestroy(): void {
    this.navigationSub?.unsubscribe();
    this.customerSub?.unsubscribe();
  }

  protected getData(): void {
    this.isLoading = true;
    this.checkIfAnalyticsDataFetch();

    this.ordersService
      .getOrders(this.params)
      .pipe(finalize(() => (this.isLoading = false)))
      .subscribe(response => {
        this.orders = response;
      });
  }

  public editOrder({ id: orderId, project_id: projectId }): void {
    this.routingService.setNext();
    this.router
      .navigate([
        `/${this.globalService.currentCompany.id}/projects/${projectId}/orders/${orderId}/edit`,
      ])
      .then();
  }

  public deleteOrder(orders: Order[]): void {
    this.isLoading = true;

    this.ordersService
      .deleteOrders(orders.map(o => o.id.toString()))
      .pipe(finalize(() => (this.isLoading = false)))
      .subscribe(() => {
        /*const [deletedOrder] = orders;
        const index = this.orders.data.findIndex(o => o.id === deletedOrder.id);
        this.orders.data[index].status = 1;*/
        this.getData();
        const msgBeginning = orders.length > 1 ? 'Orders have' : 'Order has';
        this.toastService.success(
          `${msgBeginning} been successfully deleted`,
          'Success'
        );
      });
  }

  public downloadOrder({ project_id, id, number }): void {
    this.template_id = this.templates[0]['id'];
    this.downloadModal
      .openModal(
        this.ordersService.exportOrderCallback,
        [project_id, id, this.template_id],
        'Order: ' + number,
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

  public exportOrders(): void {
    this.isLoading = true;
    this.ordersService
      .exportOrders()
      .pipe(finalize(() => (this.isLoading = false)))
      .subscribe(
        response => {
          const type = Helpers.getExportMIMEType(ExportFormat.XLSX);
          const file = new Blob([response], { type });
          this.createLinkForDownloading(ExportFormat.XLSX, file, 'Orders');
        },
        error => {
          this.toastService.error(error.error?.message, 'Download failed');
        }
      );
  }

  private getResolvedData(): void {
    const { orders, table_preferences } = this.route.snapshot.data;
    this.orders = orders;
    this.preferences = table_preferences;
  }

  private setPermissions(): void {
    const role = this.globalService.getUserRole();

    if (role === UserRole.SALES_PERSON) {
      this.buttonConfig.add = false;
      this.rowMenuConfig.edit = false;
      this.rowMenuConfig.view = true;
      this.rowMenuConfig.clone = false;
      this.rowMenuConfig.export = false;
    }
  }

  private getCompanyTemplates(): void {
    this.sharedService.getTemplates().subscribe(response => {
      this.templates = response;
    });
  }
}
