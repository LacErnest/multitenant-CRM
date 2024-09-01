import {
  Component,
  EventEmitter,
  Inject,
  Input,
  OnDestroy,
  OnInit,
  Output,
  ViewChild,
} from '@angular/core';
import { ActivatedRoute, NavigationEnd, Router } from '@angular/router';
import { ToastrService } from 'ngx-toastr';

import { Subject } from 'rxjs';
import { filter, finalize, skip, takeUntil } from 'rxjs/operators';

import { GlobalService } from 'src/app/core/services/global.service';
import { DatatableButtonConfig } from 'src/app/shared/classes/datatable/datatable-button-config';
import { DatatableContainerBase } from 'src/app/shared/classes/datatable/datatable-container-base';
import { DatatableMenuConfig } from 'src/app/shared/classes/datatable/datatable-menu-config';
import { ConfirmModalComponent } from 'src/app/shared/components/confirm-modal/confirm-modal.component';
import { UserRole } from 'src/app/shared/enums/user-role.enum';
import { Service, ServiceList } from 'src/app/shared/interfaces/service';
import { ServicesService } from 'src/app/shared/services/services.service';
import { TablePreferencesService } from 'src/app/shared/services/table-preferences.service';
import { ServiceModalComponent } from 'src/app/shared/components/service-modal/service-modal.component';
import { DOCUMENT } from '@angular/common';
import { Helpers } from '../../../core/classes/helpers';
import { ExportFormat } from '../../enums/export.format';
import { TablePreferences } from '../../interfaces/table-preferences';
import { AppStateService } from '../../services/app-state.service';

@Component({
  selector: 'oz-finance-services-list',
  templateUrl: './services-list.component.html',
  styleUrls: ['./services-list.component.scss'],
})
export class ServicesListComponent
  extends DatatableContainerBase
  implements OnInit, OnDestroy
{
  @Input() public isGlobalServiceList = true;
  @Input() public resourceId: string;
  @Input() public resourceCurrency: number;
  @Input() public readonlyMode = false;
  @Input() public employeePreferences: TablePreferences;
  @Input() public isEmployeeServiceList = false;

  @Output() public resourceServiceAdded: EventEmitter<Service> =
    new EventEmitter<Service>();
  @Output() public resourceServiceRemoved: EventEmitter<number> =
    new EventEmitter<number>();

  @ViewChild('serviceModal', { static: false })
  private serviceModal: ServiceModalComponent;
  @ViewChild('confirmModal', { static: false })
  private confirmModal: ConfirmModalComponent;

  public buttonConfig: DatatableButtonConfig = new DatatableButtonConfig({
    delete: !this.isOwnerReadOnly(),
    add: !this.isOwnerReadOnly(),
  });
  public rowMenuConfig: DatatableMenuConfig = new DatatableMenuConfig({
    export: false,
    clone: false,
    showMenu: !this.isOwnerReadOnly(),
  });
  public services: ServiceList;

  private onDestroy$: Subject<void> = new Subject<void>();
  private defaultServiceList: ServiceList = { count: 0, data: [] };

  constructor(
    protected tablePreferencesService: TablePreferencesService,
    protected route: ActivatedRoute,
    private globalService: GlobalService,
    private router: Router,
    private servicesService: ServicesService,
    private toastrService: ToastrService,
    protected appStateService: AppStateService,
    @Inject(DOCUMENT) private doc: Document
  ) {
    super(tablePreferencesService, route, appStateService, doc);
  }

  //#region lifecycle hooks

  public ngOnInit(): void {
    this.getResolvedData();

    if (this.isGlobalServiceList) {
      this.initSubscriptions();
    }

    if (this.readonlyMode === true) {
      this.disabledActionButtons();
    }
  }

  public ngOnDestroy(): void {
    this.onDestroy$.next();
    this.onDestroy$.complete();
  }

  public disabledActionButtons(): void {
    this.buttonConfig.add = false;
    this.buttonConfig.delete = false;
    this.rowMenuConfig.showMenu = false;
  }

  //#endregion

  protected getData(): void {
    this.isGlobalServiceList
      ? this.getGlobalServices()
      : this.getResourceServices();
  }

  private getGlobalServices(): void {
    this.isLoading = true;

    this.servicesService
      .getServices(this.params)
      .pipe(finalize(() => (this.isLoading = false)))
      .subscribe(response => {
        this.services = response;
      });
  }

  private getResourceServices(): void {
    this.servicesService
      .getResourceServices(this.resourceId)
      .pipe(finalize(() => (this.isLoading = false)))
      .subscribe(response => {
        this.services = response;
      });
  }

  //#region public & private methods for services CRUD

  public addService(): void {
    this.serviceModal.openModal(undefined, this.resourceCurrency).subscribe(
      result => {
        this.isGlobalServiceList
          ? this.createService(result)
          : this.addResourceService(result);
      },
      () => {
        this.toastrService.error('Service was not added', 'Error');
      }
    );
  }

  public editService(service: Service): void {
    this.serviceModal.openModal(service, this.resourceCurrency).subscribe(
      result => {
        this.resourceId
          ? this.updateResourceService(result)
          : this.updateService(result);
      },
      () => {
        this.toastrService.error('Service was not updated', 'Error');
      }
    );
  }

  public onDeleteServiceClicked(services: Service[]): void {
    this.resourceId
      ? this.deleteService(services)
      : this.removeServiceFromList(services);
  }

  public exportServices(): void {
    this.isLoading = true;
    this.servicesService
      .exportServices()
      .pipe(finalize(() => (this.isLoading = false)))
      .subscribe(
        response => {
          const type = Helpers.getExportMIMEType(ExportFormat.XLSX);
          const file = new Blob([response], { type });
          this.createLinkForDownloading(ExportFormat.XLSX, file, 'Services');
        },
        error => {
          this.toastrService.error(error.error?.message, 'Download failed');
        }
      );
  }

  private addResourceService(service: Service): void {
    this.resourceId
      ? this.createResourceService(service)
      : this.emitServiceAdd(service);
  }

  private createResourceService(service: Service): void {
    this.isLoading = true;

    this.servicesService
      .createResourceServices(this.resourceId, [service])
      .pipe(
        finalize(() => {
          this.isLoading = false;
        })
      )
      .subscribe(() => {
        this.getData();
        this.toastrService.success('Service was successfully added', 'Success');
      });
  }

  private emitServiceAdd(service: Service): void {
    this.services.data.push(service);
    this.services.count += 1;
    this.resourceServiceAdded.emit(service);
  }

  private createService(service: Service): void {
    this.isLoading = true;

    this.servicesService
      .addService(service)
      .pipe(
        finalize(() => {
          this.isLoading = false;
        })
      )
      .subscribe(() => {
        this.getData();
        this.toastrService.success('Service was successfully added', 'Success');
      });
  }

  private updateService(service: Service): void {
    this.isLoading = true;

    this.servicesService
      .editService(service.id, service)
      .pipe(
        finalize(() => {
          this.isLoading = false;
        })
      )
      .subscribe(() => {
        this.getData();
        this.toastrService.success(
          'Service was successfully updated',
          'Success'
        );
      });
  }

  private updateResourceService(service: Service): void {
    this.isLoading = true;

    this.servicesService
      .editResourceService(this.resourceId, service.id, service)
      .pipe(
        finalize(() => {
          this.isLoading = false;
        })
      )
      .subscribe(() => {
        this.getData();
        this.toastrService.success(
          'Service was successfully updated',
          'Success'
        );
      });
  }

  private removeServiceFromList(servicesToRemove: Service[]): void {
    /**
     * NOTE: only one service at a time can be deleted
     */
    const serviceIndex = this.services.data.findIndex(
      s => s.id === servicesToRemove[0].id
    );
    this.services.data.splice(serviceIndex, 1);
    this.services.count -= 1;

    if (!this.resourceId) {
      this.resourceServiceRemoved.emit(serviceIndex);
    }
  }

  private deleteService(services: Service[]): void {
    this.isLoading = true;

    this.servicesService
      .deleteService(services.map(s => s.id))
      .pipe(finalize(() => (this.isLoading = false)))
      .subscribe(
        () => {
          this.getData();

          this.toastrService.success(
            'Service was successfully deleted',
            'Success'
          );
        },
        error => {
          this.toastrService.error('Service was not deleted', 'Error');
        }
      );
  }

  //#endregion

  //#region setting values

  private getResolvedData(): void {
    if (!this.isEmployeeServiceList) {
      this.services =
        this.route.snapshot.data.services || this.defaultServiceList;
      this.preferences = this.route.snapshot.data.tablePreferences;
    } else {
      this.buttonConfig.filters = false;
      this.buttonConfig.columns = false;
      this.preferences = this.employeePreferences;
      this.getData();
    }
  }

  //#endregion

  //#region subscriptions

  private initSubscriptions(): void {
    this.globalService
      .getCurrentCompanyObservable()
      .pipe(takeUntil(this.onDestroy$), skip(1))
      .subscribe(value => {
        this.resetPaging();

        if (value?.id === 'all' || value.role > UserRole.ACCOUNTANT) {
          this.router.navigate(['/']).then();
        } else {
          this.router.navigate(['/' + value.id + '/settings/services']).then();
        }
      });

    this.router.events
      .pipe(
        takeUntil(this.onDestroy$),
        filter(e => e instanceof NavigationEnd)
      )
      .subscribe(() => this.getResolvedData());
  }
  //#endregion

  private isOwnerReadOnly(): boolean {
    return this.globalService.getUserRole() === UserRole.OWNER_READ_ONLY;
  }
}
