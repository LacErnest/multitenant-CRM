import { Component, OnDestroy, OnInit } from '@angular/core';
import { ActivatedRoute, NavigationEnd, Router } from '@angular/router';
import { ToastrService } from 'ngx-toastr';
import { Subject } from 'rxjs';
import { filter, finalize, skip, takeUntil } from 'rxjs/operators';
import { GlobalService } from 'src/app/core/services/global.service';
import { DatatableButtonConfig } from 'src/app/shared/classes/datatable/datatable-button-config';
import { DatatableContainerBase } from 'src/app/shared/classes/datatable/datatable-container-base';
import { DatatableMenuConfig } from 'src/app/shared/classes/datatable/datatable-menu-config';
import { TablePreferencesService } from 'src/app/shared/services/table-preferences.service';
import { RentCost } from 'src/app/views/settings/modules/rent-costs/interfaces/rent-cost';
import { RentCostList } from 'src/app/views/settings/modules/rent-costs/interfaces/rent-cost-list';
import { RentCostsService } from 'src/app/views/settings/modules/rent-costs/rent-costs.service';
import { UserRole } from '../../../../../../shared/enums/user-role.enum';
import { AppStateService } from 'src/app/shared/services/app-state.service';

@Component({
  selector: 'oz-finance-rent-costs',
  templateUrl: './rent-costs.component.html',
  styleUrls: ['./rent-costs.component.scss'],
})
export class RentCostsComponent
  extends DatatableContainerBase
  implements OnInit, OnDestroy
{
  public rentCosts: RentCostList;
  public buttonConfig: DatatableButtonConfig = new DatatableButtonConfig({
    columns: true,
    filters: true,
    export: false,
    delete: false,
    refresh: true,
    add: !this.isOwnerReadOnly(),
  });
  public rowMenuConfig: DatatableMenuConfig = new DatatableMenuConfig({
    export: false,
    clone: false,
  });

  private onDestroyed$: Subject<void> = new Subject<void>();

  public constructor(
    protected route: ActivatedRoute,
    protected tablePreferencesService: TablePreferencesService,
    private globalService: GlobalService,
    private rentCostsService: RentCostsService,
    private router: Router,
    private toastService: ToastrService,
    protected appStateService: AppStateService
  ) {
    super(tablePreferencesService, route, appStateService);
  }

  public ngOnInit(): void {
    super.ngOnInit();
    this.getResolvedData();
    this.initSubscriptions();
  }

  public ngOnDestroy(): void {
    this.onDestroyed$.next();
    this.onDestroyed$.complete();
  }

  private getResolvedData(): void {
    const { table_preferences, rentCosts } = this.route.snapshot.data;
    this.rentCosts = rentCosts;
    this.preferences = table_preferences;
    this.setPermissions();
  }

  public addRentCost(): void {
    this.router.navigate(['create'], { relativeTo: this.route }).then();
  }

  public editRentCost(id: string): void {
    this.router.navigate([id], { relativeTo: this.route }).then();
  }

  public deleteRentCost([rentCost]: RentCost[]): void {
    this.isLoading = true;

    this.rentCostsService
      .deleteRentCost(rentCost.id)
      .pipe(finalize(() => (this.isLoading = false)))
      .subscribe(() => {
        this.getData();
        this.toastService.success(
          'Rent cost has been successfully deleted',
          'Success'
        );
      });
  }

  public isOwnerReadOnly(): boolean {
    return this.globalService.getUserRole() === UserRole.OWNER_READ_ONLY;
  }

  protected getData(): void {
    this.isLoading = true;

    this.rentCostsService
      .getRentCosts(this.params)
      .pipe(finalize(() => (this.isLoading = false)))
      .subscribe(response => {
        this.rentCosts = response;
        this.setPermissions();
      });
  }

  private initSubscriptions(): void {
    this.globalService
      .getCurrentCompanyObservable()
      .pipe(skip(1), takeUntil(this.onDestroyed$))
      .subscribe(value => {
        if (value?.id === 'all' || value.role > 1) {
          this.router.navigate(['/']).then();
        } else {
          this.router.navigate([`/${value.id}/settings/rent_costs`]).then();
        }
      });

    this.router.events
      .pipe(
        filter(e => e instanceof NavigationEnd),
        takeUntil(this.onDestroyed$)
      )
      .subscribe(() => this.getResolvedData());
  }

  private setPermissions(): void {
    this.rentCosts.data.forEach(c => {
      c.is_edit_allowed = !c.deleted_at;
      c.is_deletion_allowed = !c.deleted_at && !this.isOwnerReadOnly();
      return c;
    });
  }

  public refreshClicked(): void {
    this.getData();
  }
}
