import { Component, OnDestroy, OnInit } from '@angular/core';
import { GlobalService } from '../../../../../../core/services/global.service';
import { ActivatedRoute, NavigationEnd, Router } from '@angular/router';
import { filter, finalize, skip } from 'rxjs/operators';
import { ToastrService } from 'ngx-toastr';
import { DatatableButtonConfig } from '../../../../../../shared/classes/datatable/datatable-button-config';
import { Subscription } from 'rxjs';
import {
  DatatableMenuConfig,
  DatatableMenuStyle,
} from '../../../../../../shared/classes/datatable/datatable-menu-config';
import { UserRole } from '../../../../../../shared/enums/user-role.enum';
import { AppStateService } from 'src/app/shared/services/app-state.service';
import { MenuOption } from 'src/app/shared/interfaces/table-menu-option';
import { DatatableContainerBase } from 'src/app/shared/classes/datatable/datatable-container-base';
import { TablePreferencesService } from 'src/app/shared/services/table-preferences.service';

@Component({
  selector: 'oz-finance-email-configuration-list',
  templateUrl: './email-configuration-list.component.html',
  styleUrls: ['./email-configuration-list.component.scss'],
})
export class EmailConfigurationListComponent
  extends DatatableContainerBase
  implements OnInit, OnDestroy
{
  smtpSettings: { data: any[]; count: number };
  buttonConfig: DatatableButtonConfig = new DatatableButtonConfig({
    export: false,
    add: false,
    import: false,
    refresh: false,
  });
  rowMenuConfig: DatatableMenuConfig = new DatatableMenuConfig({
    clone: false,
    export: false,
    delete: false,
    style: DatatableMenuStyle.INLINE,
  });
  protected table = 'smtp_settings';
  private navigationSub: Subscription;
  private companySub: Subscription;

  constructor(
    private globalService: GlobalService,
    protected route: ActivatedRoute,
    protected tablePreferencesService: TablePreferencesService,
    private router: Router,
    private toastService: ToastrService,
    protected appStateService: AppStateService
  ) {
    super(tablePreferencesService, route, appStateService);
  }

  ngOnInit(): void {
    this.getResolvedData();
    this.initSubscriptions();

    const userRole = this.globalService.getUserRole();
    this.buttonConfig.add =
      this.buttonConfig.delete =
      this.buttonConfig.edit =
        userRole === 0 || userRole === 1;
  }

  ngOnDestroy() {
    this.navigationSub?.unsubscribe();
    this.companySub?.unsubscribe();
  }

  protected getData(): void {
    //
  }

  /**
   * Edit smtp settings
   * @param id
   */
  editSettings(id: string): void {
    this.router.navigate(['smtp_settings/' + id + '/edit'], {
      relativeTo: this.route,
    });
  }

  /**
   * Fetch data from current request
   */
  private getResolvedData(): void {
    const { tablePreferences, smtp_settings } = this.route.snapshot.data;
    this.smtpSettings = { data: smtp_settings, count: smtp_settings.length };
    this.preferences = tablePreferences;
  }

  /**
   * Initializes subscriptions
   */
  private initSubscriptions(): void {
    this.companySub = this.globalService
      .getCurrentCompanyObservable()
      .pipe(skip(1))
      .subscribe(value => {
        if (value?.id === 'all' || value.role > 1) {
          this.router.navigate(['/']).then();
        } else {
          this.router
            .navigate([
              '/' + value.id + '/settings/email_management/configurations',
            ])
            .then();
        }
      });

    this.navigationSub = this.router.events
      .pipe(filter(e => e instanceof NavigationEnd))
      .subscribe(() => this.getResolvedData());
  }
}
