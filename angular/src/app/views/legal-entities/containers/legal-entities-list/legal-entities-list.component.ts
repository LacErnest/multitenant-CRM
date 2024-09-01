import { Component, Inject, OnInit } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import moment from 'moment';
import { ToastrService } from 'ngx-toastr';
import { finalize } from 'rxjs/operators';
import { GlobalService } from 'src/app/core/services/global.service';
import { RoutingService } from 'src/app/core/services/routing.service';
import { DatatableButtonConfig } from 'src/app/shared/classes/datatable/datatable-button-config';
import { DatatableContainerBase } from 'src/app/shared/classes/datatable/datatable-container-base';
import { DatatableMenuConfig } from 'src/app/shared/classes/datatable/datatable-menu-config';
import { UserRole } from 'src/app/shared/enums/user-role.enum';
import { TablePreferencesService } from 'src/app/shared/services/table-preferences.service';
import {
  LegalEntitiesList,
  LegalEntity,
} from 'src/app/shared/interfaces/legal-entity';
import { LegalEntitiesService } from 'src/app/views/legal-entities/legal-entities.service';
import { AppStateService } from 'src/app/shared/services/app-state.service';
import { DOCUMENT } from '@angular/common';

@Component({
  selector: 'oz-finance-legal-entities-list',
  templateUrl: './legal-entities-list.component.html',
  styleUrls: ['./legal-entities-list.component.scss'],
})
export class LegalEntitiesListComponent
  extends DatatableContainerBase
  implements OnInit
{
  public buttonConfig: DatatableButtonConfig = new DatatableButtonConfig({
    delete: false,
    export: false,
  });
  public rowMenuConfig: DatatableMenuConfig = new DatatableMenuConfig({
    clone: false,
    delete: false,
    export: false,
  });

  public legalEntities: LegalEntitiesList;

  public constructor(
    protected route: ActivatedRoute,
    protected tablePreferencesService: TablePreferencesService,
    private globalService: GlobalService,
    private legalEntitiesService: LegalEntitiesService,
    private router: Router,
    private routingService: RoutingService,
    private toastrService: ToastrService,
    protected appStateService: AppStateService,
    @Inject(DOCUMENT) private doc: Document
  ) {
    super(tablePreferencesService, route, appStateService, doc);
  }

  public ngOnInit(): void {
    this.getResolvedData();
    this.setPermissions();
  }

  public addLegalEntity(): void {
    this.routingService.setNext();
    this.router.navigate(['./create'], { relativeTo: this.route });
  }

  public editLegalEntity(id: string): void {
    this.routingService.setNext();
    this.router.navigate([`./${id}`], { relativeTo: this.route });
  }

  public deleteLegalEntity(legalEntities: LegalEntity[]): void {
    this.isLoading = true;

    const [legalEntity] = legalEntities;

    this.legalEntitiesService
      .deleteLegalEntity(legalEntity.id)
      .pipe(finalize(() => (this.isLoading = false)))
      .subscribe(
        () => {
          const index = this.legalEntities.data.findIndex(
            e => e.id === legalEntity.id
          );
          this.legalEntities.data[index].deleted_at = moment().format();

          this.toastrService.success(
            'Legal entity was successfully deleted',
            'Success'
          );
        },
        () => {
          this.toastrService.error('Legal entity was not deleted', 'Error');
        }
      );
  }

  protected getData(): void {
    this.isLoading = true;

    this.legalEntitiesService
      .getLegalEntities(this.params)
      .pipe(finalize(() => (this.isLoading = false)))
      .subscribe(response => (this.legalEntities = response));
  }

  private getResolvedData(): void {
    const { legalEntities, tablePreferences } = this.route.snapshot.data;

    this.legalEntities = legalEntities;
    this.preferences = tablePreferences;
  }

  private setPermissions(): void {
    const companyId = this.legalEntitiesService.legalEntityCompanyId;
    const companyRole = this.globalService.companies.find(
      c => c.id === companyId
    )?.role;
    this.rowMenuConfig.delete = companyRole === UserRole.ADMINISTRATOR;
  }
}
