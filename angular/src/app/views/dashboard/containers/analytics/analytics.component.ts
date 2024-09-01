import { HttpParams } from '@angular/common/http';
import { Component, OnDestroy, OnInit, ViewChild } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { Observable, Subject } from 'rxjs';
import { finalize, skip, takeUntil } from 'rxjs/operators';
import { Helpers } from 'src/app/core/classes/helpers';
import { GlobalService } from 'src/app/core/services/global.service';
import { PreferenceType } from 'src/app/shared/enums/preference-type.enum';
import { TablePreferenceType } from 'src/app/shared/enums/table-preference-type.enum';
import { UserRole } from 'src/app/shared/enums/user-role.enum';
import { TablePreferences } from 'src/app/shared/interfaces/table-preferences';
import { ResizeService } from 'src/app/shared/services/resize.service';
import { TablePreferencesService } from 'src/app/shared/services/table-preferences.service';
import { ChartType } from 'src/app/views/dashboard/chart-type.enum';
import { FilterModalComponent } from 'src/app/views/dashboard/components/filter-modal/filter-modal.component';
import { DashboardService } from 'src/app/views/dashboard/dashboard.service';
import moment from 'moment';
import { Analytics } from 'src/app/views/dashboard/interfaces/analytics';
import { AnalyticsFilters } from 'src/app/views/dashboard/interfaces/analytics-filters';
import { EntityType } from 'src/app/views/dashboard/types/analytics-entity';
import { AnalyticsViewType } from 'src/app/views/dashboard/types/analytics-view-type';

// TODO: remove and use FilterType instead
export type FilterOption = 'date' | 'week' | 'month' | 'quarter' | 'year';

@Component({
  selector: 'oz-finance--analytics',
  templateUrl: './analytics.component.html',
  styleUrls: ['./analytics.component.scss'],
})
export class AnalyticsComponent implements OnInit, OnDestroy {
  @ViewChild('filterModal') public filterModal: FilterModalComponent;

  public analyticsFilters: AnalyticsFilters = {
    type: 'year',
    year: null,
    quarter: null,
    month: null,
    week: null,
    day: null,
  };
  public analytics: Analytics;
  public chosenMonth: string;
  public innerWidth: number;
  public isCompanyChosen: boolean;
  public isLoading = false;
  public months = [
    { key: 1, value: 'January' },
    { key: 2, value: 'February' },
    { key: 3, value: 'March' },
    { key: 4, value: 'April' },
    { key: 5, value: 'May' },
    { key: 6, value: 'June' },
    { key: 7, value: 'July' },
    { key: 8, value: 'August' },
    { key: 9, value: 'September' },
    { key: 10, value: 'October' },
    { key: 11, value: 'November' },
    { key: 12, value: 'December' },
  ]; // TODO: refactor
  public userRole: number;
  public userRoleEnum = UserRole;
  public chartType = ChartType;

  private onDestroy$: Subject<void> = new Subject<void>();

  constructor(
    private dashboardService: DashboardService,
    private globalService: GlobalService,
    private route: ActivatedRoute,
    private router: Router,
    private tablePreferencesService: TablePreferencesService,
    private resizeService: ResizeService
  ) {
    this.setDefaultValues();
  }

  public ngOnInit(): void {
    this.getResolvedData();
    this.initSubscriptions();
  }

  public ngOnDestroy(): void {
    this.onDestroy$.next();
    this.onDestroy$.complete();
  }

  public applyFilters(filters): void {
    this.analyticsFilters = filters;
    let params = new HttpParams();

    for (const [key, value] of Object.entries(filters)) {
      if (value && key in filters && key !== 'type') {
        params = Helpers.setParam(params, key, value.toString());
      }
    }

    if (this.analyticsFilters.type === 'month') {
      this.chosenMonth = this.months.find(
        m => m.key === this.analyticsFilters.month
      ).value;
    }

    this.dashboardService.clearAnalyticsCache();
    this.fetchAnalytics(params);
  }

  // TODO: refactor
  public handleChartClicked({
    data: selection,
    entity,
  }: {
    data: any;
    entity: number;
  }): void {
    const key = PreferenceType.ANALYTICS;
    const preferences: any = {
      filters: [
        {
          prop: 'status',
          type: 'enum',
        },
        {
          prop: 'date',
          type: 'date',
        },
        {
          prop: 'intra_company',
          type: 'enum',
          value: [0],
          cast: 'boolean',
        },
      ],
    };

    const [status_filter, date_filter] = preferences.filters;
    let { month, day } = this.analyticsFilters;
    const { type, week } = this.analyticsFilters;

    const isPreviousPeriod = selection.series.includes('prev');
    const year = isPreviousPeriod
      ? moment.utc({ year: this.analyticsFilters.year }).add(-1, 'year').year()
      : moment.utc({ year: this.analyticsFilters.year }).year();

    if (isPreviousPeriod) {
      const [series] = selection.series.split(' ');
      selection.series = series;
    }

    const isTimelineChart = entity === TablePreferenceType.PURCHASE_ORDERS;
    const hour = isTimelineChart ? selection.name : selection.series;

    switch (type) {
      case 'date':
        date_filter.value = Helpers.getDateRange(
          moment.utc({ day, month, year, hour }),
          'hour'
        );
        break;
      case 'week':
        day = isTimelineChart ? selection.name : selection.series;

        date_filter.value = Helpers.getDateRange(
          moment.utc({ year }).week(week).day(day),
          'day'
        );
        break;
      case 'month':
        day = isTimelineChart ? selection.name : selection.series;

        date_filter.value = Helpers.getDateRange(
          moment.utc({
            day,
            month: month - 1,
            year,
          }),
          'day'
        );
        break;
      case 'quarter':
      case 'year':
        if (isTimelineChart) {
          month = +this.months.find(m => m.value === selection.name).key - 1;
        } else {
          month = +this.months.find(m => m.value === selection.series).key - 1;
        }

        date_filter.value = Helpers.getDateRange(
          moment.utc({ day: 1, month, year }),
          'month'
        );
        break;
    }

    /**
     * NOTE: not all statuses should be used for table preferences,
     * only those, which show realistic results
     */
    switch (entity) {
      case TablePreferenceType.QUOTES:
        status_filter.value = [1, 3, 4];
        break;
      case TablePreferenceType.ORDERS:
        status_filter.value = [1, 2, 3];
        break;
      case TablePreferenceType.INVOICES:
        status_filter.value = [2, 3, 4];
        break;
      case TablePreferenceType.PURCHASE_ORDERS:
        status_filter.value = [1, 3, 4];
        break;
    }
    this.setTablePreferences(key, entity, preferences);
  }

  // TODO: refactor
  public handleViewClicked({
    view,
    entity,
  }: {
    view: AnalyticsViewType;
    entity: number;
  }): void {
    const key = PreferenceType.ANALYTICS;

    const preferences: any = {
      filters: [
        {
          prop: 'status',
          type: 'enum',
        },
        {
          prop: 'date',
          type: 'date',
        },
        {
          prop: 'intra_company',
          type: 'enum',
          value: [0],
          cast: 'boolean',
        },
      ],
    };

    const [status_filter, date_filter] = preferences.filters;

    date_filter.value = this.getDateFilterInterval();
    /**
     * NOTE: invoices and purchase_orders have different enum values for `awaiting_payment` and `paid` statuses
     */
    switch (view) {
      case 'awaiting_payment':
        if (entity === 7) {
          status_filter.value = [4];
          date_filter.prop = 'date';
          date_filter['should'] = [
            {
              prop: 'date',
              cond: { status: [4] },
              gte: false,
            },
          ];
        } else {
          status_filter.value = [3, 8];
          date_filter.prop = 'date';
          date_filter['should'] = [
            {
              prop: 'date',
              cond: { status: [3] },
            },
            {
              prop: 'created_at',
              cond: { status: [8] },
            },
          ];
        }
        break;
      case 'declined':
        date_filter.prop = 'date';
        status_filter.value = [2];
        break;
      case 'active':
        status_filter.value = [1];
        date_filter.prop = 'created_at';
        date_filter['should'] = [
          {
            prop: 'created_at',
            cond: { status: status_filter.value },
          },
        ];
        break;
      case 'delivered':
        status_filter.value = [2, 3];
        date_filter.prop = 'delivered_at';
        break;
      case 'draft':
        status_filter.value = [0];
        date_filter.prop = 'created_at';
        date_filter['should'] = [
          {
            prop: 'created_at',
            cond: { status: status_filter.value },
          },
        ];
        break;
      case 'paid':
        status_filter.value = entity === 6 ? [4] : [5];
        date_filter.prop = 'pay_date';
        date_filter['should'] = [
          {
            prop: 'pay_date',
            cond: { status: status_filter.value },
          },
        ];
        break;
      case 'awaiting_approval':
        status_filter.value = [1];
        date_filter.prop = 'created_at';
        date_filter['should'] = [
          {
            prop: 'created_at',
            cond: { status: status_filter.value },
          },
        ];
        break;
      case 'total':
        switch (entity) {
          case 4:
            status_filter.value = [1, 2, 3, 4, 5];
            break;
          case 5:
            status_filter.value = [1, 2, 3, 4];
            break;
          case 6:
            status_filter.value = [1, 2, 3, 4, 5, 6, 7, 8];
            break;
          case 7:
            status_filter.value = [1, 2, 3, 4, 5, 6, 7];
            break;
        }
        break;
      case 'overdue':
        status_filter.value = [3, 8];
        date_filter.prop = 'due_date';
        preferences.custom_key = 'analytics_invoices_overdue';
        date_filter.value[1] = moment().toISOString();
        break;
      case 'approved_authorised':
        status_filter.value = [1, 2];
        preferences.filters[1].prop = 'created_at';
        date_filter['should'] = [
          {
            prop: 'created_at',
            cond: { status: status_filter.value },
          },
        ];
        break;
      case 'intra_company':
        // const x = preferences.filters.findIndex(f => f.prop === 'status');
        // preferences.filters.splice(x, 1);
        status_filter.value = [0, 1, 2, 3, 4, 5, 6, 7, 8];
        preferences.filters.push({
          prop: 'intra_company',
          type: 'enum',
          value: [1],
          cast: 'boolean',
        });
        break;
    }

    let quarter_start_month;
    this.setTablePreferences(key, entity, preferences);
  }

  private getDateFilterInterval(): string[] {
    let quarter_start_month;
    let date_filter;
    const { type, year, quarter, month, week, day } = this.analyticsFilters;
    /**
     * NOTE: setting date filter to table preferences
     */
    switch (type) {
      case 'year':
        date_filter = Helpers.getDateRange(
          moment.utc({ day: 1, month: 0, year }),
          'year'
        );
        break;
      case 'quarter':
        /**
         * NOTE: first month of each quarter is needed for proper date range
         */
        switch (quarter) {
          case 1:
            quarter_start_month = 0;
            break;
          case 2:
            quarter_start_month = 3;
            break;
          case 3:
            quarter_start_month = 6;
            break;
          case 4:
            quarter_start_month = 9;
            break;
        }
        date_filter = Helpers.getDateRange(
          moment.utc({ day: 1, month: quarter_start_month, year }),
          'quarter'
        );
        break;
      case 'month':
        date_filter = Helpers.getDateRange(
          moment.utc({ day: 1, month: month - 1, year }),
          'month'
        );
        break;
      case 'week':
        date_filter = Helpers.getDateRange(
          moment.utc({ year }).day('Monday').week(week),
          'week'
        );
        break;
      case 'date':
        date_filter = Helpers.getDateRange(
          moment.utc({ day, month: month - 1, year }),
          'day'
        );
        break;
    }

    return date_filter;
  }

  public handleSummaryClicked(entity: EntityType): void {
    this.router
      .navigate([`/dashboard/summary/${entity}`], {
        queryParams: this.analyticsFilters,
      })
      .then();
  }

  public showEarnOutsSummary(entity: EntityType): void {
    const earnOutsQueryParams = {
      year: moment.utc().year(),
      quarter: moment.utc().quarter(),
    };

    this.router
      .navigate([`/dashboard/summary/${entity}`], {
        queryParams: earnOutsQueryParams,
      })
      .then();
  }

  public refresh(): void {
    this.dashboardService.clearAnalyticsCache();
    let request: Observable<Analytics>;

    const params = this.dashboardService.getAnalyticsParams();
    const shouldFetchAllAnalytics =
      this.globalService.getUserRole() === UserRole.ADMINISTRATOR &&
      this.globalService.currentCompany?.id === 'all';

    if (shouldFetchAllAnalytics) {
      request = this.dashboardService.getAllAnalytics(params);
    } else {
      request = this.dashboardService.getCompanyAnalytics(params);
    }

    this.isLoading = true;
    request
      .pipe(
        finalize(() => {
          this.isLoading = false;
        })
      )
      .subscribe(response => {
        this.analytics = response;
      });
  }

  public openFilterModal(): void {
    this.filterModal.openModal('Analytics filters').subscribe(() => {});
  }

  public showEarnOutsChart(): boolean {
    return (
      this.userRole === UserRole.ADMINISTRATOR ||
      this.userRole === UserRole.OWNER ||
      this.userRole === UserRole.ACCOUNTANT ||
      this.userRole === UserRole.OWNER_READ_ONLY
    );
  }

  private fetchAnalytics(params: HttpParams): void {
    this.isLoading = true;

    const analyticsSub = this.dashboardService.shouldFetchAllAnalytics
      ? this.dashboardService.getAllAnalytics(params)
      : this.dashboardService.getCompanyAnalytics(params);

    analyticsSub
      .pipe(finalize(() => (this.isLoading = false)))
      .subscribe(res => {
        this.analytics = res;
        this.filterModal.closeModal();
      });
  }

  private updateAnalyticsOnCompanyChange(value): void {
    this.dashboardService.clearAnalyticsCache();

    if (value?.id === 'all') {
      this.isCompanyChosen = false;
      this.dashboardService.getAllAnalytics().subscribe(res => {
        this.analytics = res;
      });
    } else {
      this.isCompanyChosen = true;
      this.dashboardService.getCompanyAnalytics().subscribe(res => {
        this.analytics = res;
      });
    }

    if (localStorage.getItem('analytics_filters')) {
      this.resetFilters();
      localStorage.removeItem('analytics_filters');
    }
  }

  private setTablePreferences(
    key: PreferenceType,
    entity: number,
    preferences: TablePreferences
  ): void {
    this.tablePreferencesService
      .setTablePreferences(key, entity, preferences)
      .subscribe(() => {
        this.router
          .navigate([this.getEntityURL(entity)], {
            queryParams: { key: 'analytics' },
          })
          .then();
      });
  }

  private getEntityURL(entity: TablePreferenceType): string {
    const companyId = this.globalService.currentCompany.id;

    switch (entity) {
      case TablePreferenceType.PROJECTS:
        return `${companyId}/projects/analytics`;
      case TablePreferenceType.QUOTES:
        return `${companyId}/quotes/analytics`;
      case TablePreferenceType.ORDERS:
        return `${companyId}/orders/analytics`;
      case TablePreferenceType.INVOICES:
        return `${companyId}/invoices/analytics`;
      case TablePreferenceType.PURCHASE_ORDERS:
        return `${companyId}/purchase_orders/analytics`;
    }
  }

  private getResolvedData(): void {
    const { analytics } = this.route.snapshot.data;
    this.analytics = analytics;

    const savedFilters = JSON.parse(localStorage.getItem('analytics_filters'));

    if (savedFilters) {
      this.analyticsFilters = savedFilters;

      if (savedFilters.month) {
        this.chosenMonth = this.months.find(
          m => m.key === +savedFilters.month
        ).value;
      }
    }
  }

  private setDefaultValues(): void {
    this.analyticsFilters.year = new Date().getUTCFullYear();
    this.isCompanyChosen = this.globalService.currentCompany.id !== 'all';
    this.userRole = this.globalService.getUserRole();
    this.innerWidth = window.innerWidth;
  }

  private initSubscriptions(): void {
    this.globalService
      .getCurrentCompanyObservable()
      .pipe(takeUntil(this.onDestroy$), skip(1))
      .subscribe(value => {
        this.userRole = value.role;
        this.updateAnalyticsOnCompanyChange(value);
      });

    this.dashboardService.analyticsSubject
      .pipe(takeUntil(this.onDestroy$))
      .subscribe(value => {
        this.analytics = value;
      });

    this.resizeService.resizeObs
      .pipe(takeUntil(this.onDestroy$))
      .subscribe(e => {
        this.innerWidth = e?.target.innerWidth;
      });
  }

  private resetFilters(): void {
    for (const key in this.analyticsFilters) {
      switch (key) {
        case 'type':
          this.analyticsFilters[key] = 'year';
          break;
        case 'year':
          this.analyticsFilters[key] = new Date().getUTCFullYear();
          break;
        case 'quarter':
        case 'month':
        case 'day':
          this.analyticsFilters[key] = null;
          break;
      }
    }
  }
}
