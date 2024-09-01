import { Component, Inject, OnDestroy, OnInit, ViewChild } from '@angular/core';
import { DatatableContainerBase } from '../../../../shared/classes/datatable/datatable-container-base';
import { TablePreferencesService } from '../../../../shared/services/table-preferences.service';
import { GlobalService } from '../../../../core/services/global.service';
import { ActivatedRoute, NavigationEnd, Router } from '@angular/router';
import { ResourcesService } from '../../resources.service';
import { filter, finalize, skip } from 'rxjs/operators';
import { Subscription } from 'rxjs';
import { ToastrService } from 'ngx-toastr';
import { DatatableMenuConfig } from '../../../../shared/classes/datatable/datatable-menu-config';
import { EntityImportModalComponent } from '../../../../shared/components/entity-import-modal/entity-import-modal.component';
import { DatatableButtonConfig } from '../../../../shared/classes/datatable/datatable-button-config';
import { ImportMatches } from '../../../customers/customers.service';
import { ResourceExportModalComponent } from '../../components/resource-export-modal/resource-export-modal.component';
import { DownloadModalComponent } from '../../../../shared/components/download-modal/download-modal.component';
import { ExportFormat } from '../../../../shared/enums/export.format';
import { RoutingService } from '../../../../core/services/routing.service';
import { Helpers } from '../../../../core/classes/helpers';
import { DOCUMENT } from '@angular/common';
import { UserRole } from '../../../../shared/enums/user-role.enum';
import { AppStateService } from 'src/app/shared/services/app-state.service';
import { TablePreferenceType } from 'src/app/shared/enums/table-preference-type.enum';

@Component({
  selector: 'oz-finance-resource-list',
  templateUrl: './resource-list.component.html',
  styleUrls: ['./resource-list.component.scss'],
})
export class ResourceListComponent
  extends DatatableContainerBase
  implements OnInit, OnDestroy
{
  @ViewChild('downloadModal', { static: false })
  downloadModal: DownloadModalComponent;
  @ViewChild('exportResourceModal', { static: false })
  exportResourceModal: ResourceExportModalComponent;
  @ViewChild('importEntityModal', { static: false })
  importEntityModal: EntityImportModalComponent;

  importedColumns: any = [];
  properties = [];
  uploadedFileId: string;
  buttonConfig: DatatableButtonConfig = new DatatableButtonConfig({
    import: true,
    delete: false,
    add: !this.isOwnerReadOnly(),
    export: this.globalService.canExport(TablePreferenceType.RESOURCES),
  });
  rowMenuConfig: DatatableMenuConfig = new DatatableMenuConfig({
    clone: false,
    delete: false,
  });

  private navigationSub: Subscription;
  private companySub: Subscription;

  constructor(
    public resourcesService: ResourcesService,
    private globalService: GlobalService,
    private router: Router,
    private routingService: RoutingService,
    protected route: ActivatedRoute,
    protected tablePreferenceService: TablePreferencesService,
    private toastrService: ToastrService,
    protected appStateService: AppStateService,
    @Inject(DOCUMENT) private doc: Document
  ) {
    super(tablePreferenceService, route, appStateService, doc);
  }

  ngOnInit(): void {
    this.getResolvedData();
    this.companySub = this.globalService
      .getCurrentCompanyObservable()
      .pipe(skip(1))
      .subscribe(value => {
        this.resetPaging();
        if (value?.id === 'all') {
          this.router.navigate(['/']).then();
        } else {
          this.router.navigate(['/' + value.id + '/resources']).then();
        }
      });

    this.navigationSub = this.router.events
      .pipe(filter(e => e instanceof NavigationEnd))
      .subscribe(() => this.getResolvedData());

    this.buttonConfig.import =
      this.globalService.getUserRole() !== 5 && !this.isOwnerReadOnly();
  }

  ngOnDestroy() {
    this.navigationSub?.unsubscribe();
    this.companySub?.unsubscribe();
  }

  getData() {
    this.isLoading = true;
    this.resourcesService
      .getResources(this.params)
      .pipe(
        finalize(() => {
          this.isLoading = false;
        })
      )
      .subscribe(response => {
        this.rows = response;
      });
  }

  addResource() {
    this.routingService.setNext();
    this.router.navigate(['create'], { relativeTo: this.route }).then();
  }

  editResource(id: string) {
    this.routingService.setNext();
    this.router.navigate([id, 'edit'], { relativeTo: this.route }).then();
  }

  deleteResources(resources: any[]) {
    this.isLoading = true;
    this.resourcesService
      .deleteResources(resources.map(p => p.id.toString()))
      .pipe(
        finalize(() => {
          this.isLoading = false;
        })
      )
      .subscribe(() => {
        this.toastrService.success(undefined, 'Resource deleted');
        this.getData();
      });
  }

  importResource(resource: any): void {
    this.resourcesService.importResource(resource).subscribe(
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

  exportResource({ id, name }) {
    this.exportResourceModal.openModal().subscribe(
      result => {
        this.downloadModal
          .openModal(
            this.resourcesService.exportResourceCallback,
            [id, result.type],
            'Resource ' + result.type.toUpperCase() + ': ' + name,
            [ExportFormat.PDF]
          )
          .subscribe(
            () => {},
            () => {}
          );
      },
      () => {}
    );
  }

  public exportResources(): void {
    this.isLoading = true;
    this.resourcesService
      .exportResources()
      .pipe(finalize(() => (this.isLoading = false)))
      .subscribe(
        response => {
          const type = Helpers.getExportMIMEType(ExportFormat.XLSX);
          const file = new Blob([response], { type });
          this.createLinkForDownloading(ExportFormat.XLSX, file, 'Resources');
        },
        error => {
          this.toastrService.error(error.error?.message, 'Download failed');
        }
      );
  }

  openImportModal(): void {
    this.importEntityModal.openModal('Import data').subscribe(
      () => {},
      () => {}
    );
  }

  finalizeImport(matches: ImportMatches): void {
    if (this.isLoading) {
      return;
    }

    this.isLoading = true;
    this.importEntityModal.closeModal();
    this.resourcesService
      .finalizeResourceImport({ id: this.uploadedFileId, matches })
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

  private getResolvedData() {
    this.preferences = this.route.snapshot.data.tablePreferences;
    this.rows = this.route.snapshot.data.resources;
  }

  private isOwnerReadOnly(): boolean {
    return this.globalService.getUserRole() === UserRole.OWNER_READ_ONLY;
  }
}
