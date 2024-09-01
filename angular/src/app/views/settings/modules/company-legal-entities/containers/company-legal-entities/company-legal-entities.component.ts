import { Component, OnDestroy, OnInit, ViewChild } from '@angular/core';
import { ActivatedRoute, NavigationEnd, Router } from '@angular/router';
import moment from 'moment';
import { ToastrService } from 'ngx-toastr';
import { Subject, Subscription } from 'rxjs';
import { filter, finalize, skip, takeUntil } from 'rxjs/operators';
import { DatatableButtonConfig } from 'src/app/shared/classes/datatable/datatable-button-config';
import { DatatableContainerBase } from 'src/app/shared/classes/datatable/datatable-container-base';
import { DatatableMenuConfig } from 'src/app/shared/classes/datatable/datatable-menu-config';
import {
  CompanyLegalEntitiesList,
  CompanyLegalEntity,
} from 'src/app/shared/interfaces/legal-entity';
import { Column } from 'src/app/shared/interfaces/table-preferences';
import { TablePreferencesService } from 'src/app/shared/services/table-preferences.service';
import { CompanyLegalEntitiesService } from 'src/app/views/settings/modules/company-legal-entities/company-legal-entities.service';
import { AddLegalEntityModalComponent } from 'src/app/views/settings/modules/company-legal-entities/components/add-legal-entity-modal/add-legal-entity-modal.component';
import { GlobalService } from 'src/app/core/services/global.service';
import { settingsRoutesRoles } from 'src/app/views/settings/settings-roles';
import { AppStateService } from 'src/app/shared/services/app-state.service';

@Component({
  selector: 'oz-finance-company-legal-entities',
  templateUrl: './company-legal-entities.component.html',
  styleUrls: ['./company-legal-entities.component.scss'],
})
export class CompanyLegalEntitiesComponent
  extends DatatableContainerBase
  implements OnInit, OnDestroy
{
  @ViewChild('addLegalEntityModal')
  public addLegalEntityModal: AddLegalEntityModalComponent;

  public companyLegalEntities: CompanyLegalEntitiesList;
  public legalEntityColumns: Column[] = [
    { prop: 'name', name: 'name', type: 'string' },
    { prop: 'default', name: 'default', type: 'boolean' },
    { prop: 'local', name: 'local', type: 'boolean' },
    { prop: 'created_at', name: 'created at', type: 'date' },
    { prop: 'updated_at', name: 'updated at', type: 'date' },
  ];

  public buttonConfig: DatatableButtonConfig = new DatatableButtonConfig({
    columns: false,
    filters: false,
    export: false,
    delete: false,
  });
  public rowMenuConfig: DatatableMenuConfig = new DatatableMenuConfig({
    clone: false,
    edit: false,
    export: false,
    markAsDefault: true,
    markAsLocal: true,
  });

  private onDestroy$: Subject<void> = new Subject<void>();

  private companySub: Subscription;

  public constructor(
    protected route: ActivatedRoute,
    protected tablePreferencesService: TablePreferencesService,
    private globalService: GlobalService,
    private companyLegalEntitiesService: CompanyLegalEntitiesService,
    private router: Router,
    private toastrService: ToastrService,
    protected appStateService: AppStateService
  ) {
    super(tablePreferencesService, route, appStateService);
  }

  public ngOnInit(): void {
    this.getResolvedData();
    this.initSubscriptions();
  }

  public ngOnDestroy(): void {
    this.companySub?.unsubscribe();
  }

  public addLegalEntity(): void {
    this.addLegalEntityModal.openLegalEntityModal();
  }

  public markLegalEntityAsDefault(legalEntity: CompanyLegalEntity): void {
    this.isLoading = true;

    this.companyLegalEntitiesService
      .markLegalEntityAsDefault(legalEntity.legal_entity_id)
      .pipe(finalize(() => (this.isLoading = false)))
      .subscribe(
        () => {
          this.updateDefaultEntityInList(legalEntity.legal_entity_id);
          this.showSuccessMessage(
            'Legal entity was successfully marked as default'
          );
        },
        err =>
          this.showErrorMessage(err, 'Legal entity was not marked as default')
      );
  }

  public markLegalEntityAsLocal(legalEntity: CompanyLegalEntity): void {
    this.isLoading = true;

    this.companyLegalEntitiesService
      .markLegalEntityAsLocal(legalEntity.legal_entity_id)
      .pipe(finalize(() => (this.isLoading = false)))
      .subscribe(
        () => {
          this.updateLocalEntityInList(legalEntity.legal_entity_id);
          this.showSuccessMessage(
            'Legal entity was successfully marked as local'
          );
        },
        err =>
          this.showErrorMessage(err, 'Legal entity was not marked as local')
      );
  }

  public onDeleteLegalEntityClicked(legalEntities: CompanyLegalEntity[]): void {
    this.isLoading = true;

    this.companyLegalEntitiesService
      .removeLegalEntityFromCompany(legalEntities[0].legal_entity_id)
      .pipe(finalize(() => (this.isLoading = false)))
      .subscribe(
        () => {
          this.updateLegalEntityList(legalEntities[0].legal_entity_id);
          this.showSuccessMessage(
            'Legal entity was successfully unlinked from the company'
          );
          this.globalService.refreshCompany();
        },
        err =>
          this.showErrorMessage(
            err,
            'Legal entity was not unlinked from the company'
          )
      );
  }

  public onLegalEntityChosen(legalEntityId: string): void {
    this.isLoading = true;

    this.companyLegalEntitiesService
      .addLegalEntityToCompany(legalEntityId)
      .pipe(finalize(() => (this.isLoading = false)))
      .subscribe(
        res => {
          this.companyLegalEntities.count += 1;
          this.companyLegalEntities.data.push(res);
          this.companyLegalEntities.data = this.sortLegalEntities();
          this.showSuccessMessage(
            'Legal entity was successfully linked to the company'
          );
          this.globalService.refreshCompany();
        },
        err =>
          this.showErrorMessage(
            err,
            'Legal entity was not linked to the company'
          )
      );
  }

  protected getData(): void {}

  private getResolvedData(): void {
    this.companyLegalEntities = this.route.snapshot.data.companyLegalEntities;
    this.companyLegalEntities.data.forEach(
      l => (l.is_deletion_allowed = !(l.default && l.local))
    );
  }

  private showSuccessMessage(msg: string): void {
    this.toastrService.success(msg, 'Success');
  }

  private showErrorMessage(err, defaultErrMsg: string): void {
    const msg = err?.message ?? defaultErrMsg;
    this.toastrService.error(msg, 'Error');
  }

  private sortLegalEntities(): CompanyLegalEntity[] {
    return this.companyLegalEntities.data.sort((entity1, entity2) => {
      return entity1.name > entity2.name ? 1 : -1;
    });
  }

  private updateDefaultEntityInList(legalEntityId: string): void {
    const prevDefaultIndex = this.companyLegalEntities.data.findIndex(
      e => e.default
    );
    const curDefaultIndex = this.companyLegalEntities.data.findIndex(
      e => e.legal_entity_id === legalEntityId
    );

    this.companyLegalEntities.data[prevDefaultIndex].default = false;
    this.companyLegalEntities.data[curDefaultIndex].default = true;

    this.companyLegalEntities.data[prevDefaultIndex].is_deletion_allowed = true;
    this.companyLegalEntities.data[curDefaultIndex].is_deletion_allowed = false;

    this.companyLegalEntities.data[prevDefaultIndex].updated_at =
      moment().format();
    this.companyLegalEntities.data[curDefaultIndex].updated_at =
      moment().format();

    this.updateGlobalLegalEntityList();
  }

  private updateLocalEntityInList(legalEntityId: string): void {
    const prevLocalIndex = this.companyLegalEntities.data.findIndex(
      e => e.local
    );
    const curLocalIndex = this.companyLegalEntities.data.findIndex(
      e => e.legal_entity_id === legalEntityId
    );
    if (prevLocalIndex >= 0) {
      this.companyLegalEntities.data[prevLocalIndex].local = false;
      this.companyLegalEntities.data[prevLocalIndex].updated_at =
        moment().format();
    }
    this.companyLegalEntities.data[curLocalIndex].local = true;
    this.companyLegalEntities.data[curLocalIndex].updated_at =
      moment().format();

    this.updateGlobalLegalEntityList();
  }

  private updateGlobalLegalEntityList(): void {
    this.globalService.currentLegalEntities = this.companyLegalEntities.data;
  }

  private updateLegalEntityList(deletedEntityId: string): void {
    this.companyLegalEntities.count -= 1;

    const i = this.companyLegalEntities.data.findIndex(
      e => e.legal_entity_id === deletedEntityId
    );
    this.companyLegalEntities.data.splice(i, 1);

    this.updateGlobalLegalEntityList();
  }

  private initSubscriptions(): void {
    this.companySub = this.globalService
      .getCurrentCompanyObservable()
      .pipe(skip(1), takeUntil(this.onDestroy$))
      .subscribe(value => {
        const allowedRoles = settingsRoutesRoles.companyLegalEntities;
        const shouldGoToDashboard =
          value?.id === 'all' || !allowedRoles.includes(value.role);
        this.router
          .navigate([
            shouldGoToDashboard ? '/' : `/${value.id}/settings/legal_entities`,
          ])
          .then();
      });

    this.router.events
      .pipe(
        filter(e => e instanceof NavigationEnd),
        takeUntil(this.onDestroy$)
      )
      .subscribe(() => {
        // TODO: use getData()?
        this.companyLegalEntities.data =
          this.globalService.currentLegalEntities;
        this.companyLegalEntities.count =
          this.globalService.currentLegalEntities.length;
        // this.getResolvedData();
      });
  }
}
