import { Component, Inject, OnDestroy, OnInit, ViewChild } from '@angular/core';
import { ActivatedRoute, NavigationEnd, Router } from '@angular/router';
import { ToastrService } from 'ngx-toastr';
import { Subscription } from 'rxjs';
import { filter, finalize, skip } from 'rxjs/operators';
import { GlobalService } from 'src/app/core/services/global.service';
import { RoutingService } from 'src/app/core/services/routing.service';
import { DatatableButtonConfig } from 'src/app/shared/classes/datatable/datatable-button-config';
import { DatatableContainerBase } from 'src/app/shared/classes/datatable/datatable-container-base';
import { DatatableMenuConfig } from 'src/app/shared/classes/datatable/datatable-menu-config';
import { DownloadModalComponent } from 'src/app/shared/components/download-modal/download-modal.component';
import { EntityImportModalComponent } from 'src/app/shared/components/entity-import-modal/entity-import-modal.component';
import { ExportFormat } from 'src/app/shared/enums/export.format';
import { UserRole } from 'src/app/shared/enums/user-role.enum';
import { TablePreferencesService } from 'src/app/shared/services/table-preferences.service';
import { ImportMatches } from 'src/app/views/customers/customers.service';
import { EmployeesService } from 'src/app/views/employees/employees.service';
import { Helpers } from '../../../../core/classes/helpers';
import { DOCUMENT } from '@angular/common';
import { AppStateService } from 'src/app/shared/services/app-state.service';
import { TablePreferenceType } from 'src/app/shared/enums/table-preference-type.enum';

@Component({
  selector: 'oz-finance-employees-list',
  templateUrl: './employees-list.component.html',
  styleUrls: ['./employees-list.component.scss'],
})
export class EmployeesListComponent
  extends DatatableContainerBase
  implements OnInit, OnDestroy
{
  @ViewChild('downloadModal', { static: false })
  downloadModal: DownloadModalComponent;
  @ViewChild('importEntityModal', { static: false })
  importEntityModal: EntityImportModalComponent;

  importedColumns: any = [];
  properties = [];
  uploadedFileId: string;
  rowMenuConfig = new DatatableMenuConfig({
    clone: false,
    delete: false,
  });
  buttonConfig = new DatatableButtonConfig({
    delete: false,
    import: true,
    add: !this.isOwnerReadOnly(),
    export: this.globalService.canExport(TablePreferenceType.EMPLOYEES),
  });

  private navigationSub: Subscription;
  private companySub: Subscription;

  constructor(
    private employeesService: EmployeesService,
    private toastrService: ToastrService,
    private globalService: GlobalService,
    private router: Router,
    private routingService: RoutingService,
    protected route: ActivatedRoute,
    protected tablePreferencesService: TablePreferencesService,
    protected appStateService: AppStateService,
    @Inject(DOCUMENT) private doc: Document
  ) {
    super(tablePreferencesService, route, appStateService, doc);
  }

  ngOnInit(): void {
    this.getResolvedData();
    this.setPermissions();
    this.companySub = this.globalService
      .getCurrentCompanyObservable()
      .pipe(skip(1))
      .subscribe(value => {
        this.resetPaging();
        if (value?.id === 'all') {
          this.router.navigate(['/']).then();
        } else {
          this.router.navigate(['/' + value.id + '/projects']).then();
        }
      });

    this.navigationSub = this.router.events
      .pipe(filter(e => e instanceof NavigationEnd))
      .subscribe(() => this.getResolvedData());
  }

  ngOnDestroy() {
    this.navigationSub?.unsubscribe();
    this.companySub?.unsubscribe();
  }

  getData() {
    this.isLoading = true;
    this.employeesService
      .getEmployees(this.params)
      .pipe(
        finalize(() => {
          this.isLoading = false;
        })
      )
      .subscribe(response => {
        this.rows = response;
      });
  }

  addEmployee() {
    this.routingService.setNext();
    this.router.navigate(['create'], { relativeTo: this.route }).then();
  }

  editEmployee(id: string) {
    this.routingService.setNext();
    this.router.navigate([id, 'edit'], { relativeTo: this.route }).then();
  }

  openImportModal(): void {
    this.importEntityModal.openModal('Import data').subscribe(
      () => {},
      () => {}
    );
  }

  downloadEmployee({ id, name }) {
    this.downloadModal
      .openModal(
        this.employeesService.exportEmployeeCallback,
        [id],
        'Employee NDA: ' + name,
        [ExportFormat.PDF]
      )
      .subscribe(
        () => {},
        () => {}
      );
  }

  importEmployee(employee: any): void {
    this.employeesService.importEmployee(employee).subscribe(
      response => {
        const { columns, id, properties } = response;
        this.importedColumns = columns;
        this.uploadedFileId = id;
        this.properties = properties;
        this.openImportModal();
      },
      error => {}
    );
  }

  finalizeImport(matches: ImportMatches): void {
    if (this.isLoading) {
      return;
    }

    this.isLoading = true;
    this.importEntityModal.closeModal();
    this.employeesService
      .finalizeImportEmployee({ id: this.uploadedFileId, matches })
      .pipe(finalize(() => (this.isLoading = false)))
      .subscribe(
        response => {
          this.rows.data = response.data;
          this.rows.count = response.count;
          this.importEntityModal.closeModal();
          if ('notValidFileRows' in response) {
            const notValidFields = response.notValidFileRows.join(', ');
            this.toastrService.warning(
              `Import is finished, but next fields were not imported: ${notValidFields}.`,
              'Data import',
              { timeOut: 10000 }
            );
          } else {
            this.toastrService.success(
              'Data was successfully imported',
              'Data import'
            );
          }
        },
        () => {
          this.toastrService.error(
            'Sorry, data was not imported',
            'Data import'
          );
        }
      );
  }

  public exportEmployees(): void {
    this.isLoading = true;
    this.employeesService
      .exportEmployees()
      .pipe(finalize(() => (this.isLoading = false)))
      .subscribe(
        response => {
          const type = Helpers.getExportMIMEType(ExportFormat.XLSX);
          const file = new Blob([response], { type });
          this.createLinkForDownloading(ExportFormat.XLSX, file, 'Employees');
        },
        error => {
          this.toastrService.error(error.error?.message, 'Download failed');
        }
      );
  }

  private getResolvedData() {
    this.preferences = this.route.snapshot.data.tablePreferences;
    this.rows = this.route.snapshot.data.employees;
  }

  private setPermissions(): void {
    const userRole = this.globalService.getUserRole();
    this.buttonConfig.import =
      userRole !== UserRole.PROJECT_MANAGER &&
      userRole !== UserRole.HUMAN_RESOURCES &&
      userRole !== UserRole.OWNER_READ_ONLY;
  }

  private isOwnerReadOnly(): boolean {
    return this.globalService.getUserRole() === UserRole.OWNER_READ_ONLY;
  }
}
