import { Component, Inject, OnDestroy, OnInit, ViewChild } from '@angular/core';
import { ChooseLegalEntityModalComponent } from 'src/app/views/customers/components/choose-legal-entity-modal/choose-legal-entity-modal.component';
import { EnumService } from 'src/app/core/services/enum.service';
import { ActivatedRoute, NavigationEnd, Router } from '@angular/router';
import { TablePreferencesService } from 'src/app/shared/services/table-preferences.service';
import { DatatableContainerBase } from 'src/app/shared/classes/datatable/datatable-container-base';
import { filter, finalize, skip } from 'rxjs/operators';
import { CustomersService, ImportMatches } from '../../customers.service';
import { DatatableButtonConfig } from 'src/app/shared/classes/datatable/datatable-button-config';
import { GlobalService } from 'src/app/core/services/global.service';
import { ToastrService } from 'ngx-toastr';
import { Subscription } from 'rxjs';
import { EntityImportModalComponent } from 'src/app/shared/components/entity-import-modal/entity-import-modal.component';
import { DOCUMENT } from '@angular/common';
import { DatatableMenuConfig } from 'src/app/shared/classes/datatable/datatable-menu-config';
import { DownloadModalComponent } from 'src/app/shared/components/download-modal/download-modal.component';
import { ExportFormat } from 'src/app/shared/enums/export.format';
import { RoutingService } from 'src/app/core/services/routing.service';
import { Helpers } from '../../../../core/classes/helpers';
import { UserRole } from '../../../../shared/enums/user-role.enum';
import { AppStateService } from 'src/app/shared/services/app-state.service';

@Component({
  selector: 'oz-finance-customer-list',
  templateUrl: './customer-list.component.html',
  styleUrls: ['./customer-list.component.scss'],
})
export class CustomerListComponent
  extends DatatableContainerBase
  implements OnInit, OnDestroy
{
  @ViewChild('downloadModal', { static: false })
  public downloadModal: DownloadModalComponent;
  @ViewChild('importEntityModal', { static: false })
  public importEntityModal: EntityImportModalComponent;
  @ViewChild('chooseLegalEntityModal', { static: false })
  public chooseLegalEntityModal: ChooseLegalEntityModalComponent;

  public buttonConfig: DatatableButtonConfig = new DatatableButtonConfig({
    delete: false,
    add: !this.isOwnerReadOnly(),
    import: !this.isOwnerReadOnly(),
    export: this.globalService.canExport(),
  });
  public rowMenuConfig: DatatableMenuConfig = new DatatableMenuConfig({
    clone: false,
    delete: false,
  });

  public customers: { data: any; count: number };
  public importedColumns: any = [];
  public properties = [];

  private navigationSub: Subscription;
  private companySub: Subscription;

  private exportedCustomer: {
    id: string;
    name: string;
  } = null;
  private uploadedFileId: string;

  public constructor(
    private customersService: CustomersService,
    private enumService: EnumService,
    private globalService: GlobalService,
    protected route: ActivatedRoute,
    private router: Router,
    private routingService: RoutingService,
    protected tablePreferencesService: TablePreferencesService,
    private toastService: ToastrService,
    protected appStateService: AppStateService,
    @Inject(DOCUMENT) private doc: Document
  ) {
    super(tablePreferencesService, route, appStateService, doc);
  }

  public ngOnInit(): void {
    this.getResolvedData();
    this.initSubscriptions();
  }

  public ngOnDestroy(): void {
    this.navigationSub?.unsubscribe();
    this.companySub?.unsubscribe();
  }

  protected getData(): void {
    this.isLoading = true;

    this.customersService
      .getCustomers(this.params)
      .pipe(finalize(() => (this.isLoading = false)))
      .subscribe(response => {
        this.customers = response;
      });
  }

  public addCustomer(): void {
    this.routingService.setNext();
    this.router.navigate(['create'], { relativeTo: this.route }).then();
  }

  public editCustomer(id: string): void {
    this.routingService.setNext();
    this.router.navigate([id + '/edit'], { relativeTo: this.route }).then();
  }

  public deleteCustomers(customers: any): void {
    this.isLoading = true;

    this.customersService
      .deleteCustomers(customers.map(c => c.id.toString()))
      .pipe(finalize(() => (this.isLoading = false)))
      .subscribe(() => {
        this.customers.data = this.customers.data.filter(
          c => !customers.includes(c)
        );
        const msgBeginning =
          customers.length > 1 ? 'Customers have' : 'Customer has';
        this.toastService.success(
          `${msgBeginning} been successfully deleted`,
          'Success'
        );
      });
  }

  public importCustomer(customer: any): void {
    this.customersService.importCustomer(customer).subscribe(
      response => {
        const { columns, id, properties } = response;
        this.importedColumns = columns;
        this.uploadedFileId = id;
        this.properties = properties;
        this.openImportModal();
      },
      error => {
        this.toastService.error(error?.message.file[0], 'Import failed');
      }
    );
  }

  public finalizeImport(matches: ImportMatches): void {
    if (this.isLoading) {
      return;
    }

    this.isLoading = true;
    this.importEntityModal.closeModal();

    this.customersService
      .finalizeImportCustomer({ id: this.uploadedFileId, matches })
      .pipe(finalize(() => (this.isLoading = false)))
      .subscribe(
        response => {
          this.customers.data = response.data;
          this.customers.count = response.count;
          this.importEntityModal.closeModal();
          if ('notValidFileRows' in response) {
            const notValidFields = response.notValidFileRows.join(', ');
            this.toastService.warning(
              `Import is finished, but next fields were not imported: ${notValidFields}.`,
              'Data import',
              { timeOut: 10000 }
            );
          } else {
            this.toastService.success(
              'Data was successfully imported',
              'Data import'
            );
          }
        },
        () => {
          this.toastService.error(
            'Sorry, data was not imported',
            'Data import'
          );
        }
      );
  }

  public exportCustomer({ id, name }): void {
    this.exportedCustomer = { id, name };
    this.chooseLegalEntityModal.openCompanyLegalEntityModal();
  }

  public exportCustomers(): void {
    this.isLoading = true;
    this.customersService
      .exportCustomers()
      .pipe(finalize(() => (this.isLoading = false)))
      .subscribe(
        response => {
          const type = Helpers.getExportMIMEType(ExportFormat.XLSX);
          const file = new Blob([response], { type });
          this.createLinkForDownloading(ExportFormat.XLSX, file, 'Customers');
        },
        error => {
          this.toastService.error(error.error?.message, 'Download failed');
        }
      );
  }

  public download(legalEntityId: string): void {
    this.downloadModal
      .openModal(
        this.customersService.exportCustomerCallback,
        [this.exportedCustomer.id],
        'Customer NDA: ' + this.exportedCustomer.name,
        [ExportFormat.PDF],
        legalEntityId
      )
      .subscribe();
  }

  private openImportModal(): void {
    this.importEntityModal.openModal('Import data').subscribe(
      () => {},
      () => {}
    );
  }

  private getResolvedData(): void {
    const { table_preferences, customers } = this.route.snapshot.data;
    this.customers = customers;
    this.preferences = table_preferences;
  }

  private initSubscriptions(): void {
    this.companySub = this.globalService
      .getCurrentCompanyObservable()
      .pipe(skip(1))
      .subscribe(value => {
        this.resetPaging();

        if (value?.id === 'all') {
          this.router.navigate(['/']).then();
        } else {
          this.router.navigate(['/' + value.id + '/customers']).then();
        }
      });

    this.navigationSub = this.router.events
      .pipe(filter(e => e instanceof NavigationEnd))
      .subscribe(() => this.getResolvedData());
  }

  private isOwnerReadOnly(): boolean {
    return this.globalService.getUserRole() === UserRole.OWNER_READ_ONLY;
  }
}
