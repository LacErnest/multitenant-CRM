import { Directive, Inject, OnInit, Renderer2 } from '@angular/core';
import { HttpParams } from '@angular/common/http';
import { Helpers } from 'src/app/core/classes/helpers';
import {
  Column,
  Filter,
  Sort,
  TablePreferences,
} from 'src/app/shared/interfaces/table-preferences';
import { TablePreferencesService } from '../../services/table-preferences.service';
import { ActivatedRoute } from '@angular/router';
import { PreferenceType } from '../../enums/preference-type.enum';
import { ProjectEntityEnum } from '../../enums/project-entity.enum';
import { ExportFormat } from '../../enums/export.format';
import { DOCUMENT } from '@angular/common';
import { AppStateService } from '../../services/app-state.service';

@Directive()
export abstract class DatatableContainerBase implements OnInit {
  public currency: number;
  public isLoading = false;
  public rows: { data: any[]; count: number } = { data: [], count: 0 };
  public preferences: TablePreferences = {
    all_columns: [],
    columns: [],
    filters: [],
    sorts: [],
    default_columns: [],
    default_filters: [],
  };
  public page = 0;

  protected params: HttpParams = new HttpParams();
  protected entity: number;

  public projectEntity = ProjectEntityEnum;

  protected constructor(
    protected tablePreferencesService: TablePreferencesService,
    protected route: ActivatedRoute,
    protected appStateService: AppStateService,
    private document: Document = null
  ) {
    this.entity = this.route.snapshot.data.entity;
    this.page = route.snapshot.queryParams.return
      ? tablePreferencesService.getTablePage(this.entity) ?? 0
      : this.appStateService.getLastDataTablePage(this.entity) ?? 0;
  }

  public ngOnInit(): void {
    //
  }

  private getLastUpdatedPage(): void {
    this.appStateService.getLastDataTablePageObserver().subscribe(page => {
      if (this.page !== page) {
        this.page = page;
        this.params = Helpers.setParam(this.params, 'page', page.toString());
      }
    });
  }

  protected abstract getData(): void;

  public filtersUpdated(filters: Filter[]): void {
    this.appStateService.setLastDataTablePage(0, this.entity);
    this.resetPaging();
    this.preferences.filters = filters;
    this.updatePreferences(this.preferences);
  }

  public sortsUpdated(sorts: Sort[]): void {
    this.resetPaging();
    this.preferences.sorts = sorts;
    this.updatePreferences(this.preferences);
  }

  public pageUpdated(page: number): void {
    this.page = page;
    this.tablePreferencesService.setTablePage(this.entity, page);
    this.params = Helpers.setParam(this.params, 'page', page.toString());
    this.appStateService.setLastDataTablePage(page, this.entity);
    this.checkIfAnalyticsDataFetch();
    this.getData();
  }

  public refreshClicked(): void {
    this.getData();
  }

  public checkIfAnalyticsDataFetch(): void {
    if (this.hasAnalyticsQueryParam()) {
      this.params = Helpers.setParam(
        this.params,
        'key',
        this.route.snapshot?.queryParams?.key
      );
    }
  }

  public columnsUpdated(columns: Column[]): void {
    this.preferences.columns = columns;
    this.updatePreferences(this.preferences);
  }

  public columnsAndSortsUpdated({ columns, sorts }): void {
    this.preferences.columns = columns;
    this.preferences.sorts = sorts;
    this.updatePreferences(this.preferences);
  }

  protected resetPaging(): void {
    this.page = 0;
    this.tablePreferencesService.removeTablePage(this.entity);
    this.appStateService.setLastDataTablePage(this.page, this.entity);
    this.params = Helpers.setParam(this.params, 'page', this.page.toString());
  }

  protected createLinkForDownloading(format: ExportFormat, file, filename) {
    const link = this.document.createElement('a');
    this.document.body.appendChild(link);
    link.setAttribute(
      'href',
      format === ExportFormat.PDF ? file : URL.createObjectURL(file)
    );
    link.setAttribute('download', filename);
    link.click();
    this.document.body.removeChild(link);
  }

  private updatePreferences(preferences): void {
    // copy prefs and remove empty props
    const prefs = JSON.parse(JSON.stringify(preferences));
    Helpers.removeEmpty(prefs);
    delete prefs.all_columns;
    if (typeof prefs.filters !== 'undefined') {
      prefs.filters = prefs.filters.map(filter => {
        if (filter.prop === 'intra_company' && !filter.type) {
          return { ...filter, type: 'enum', value: [0, 1], cast: 'boolean' };
        }
        return filter;
      });
    }

    this.tablePreferencesService
      .setTablePreferences(
        this.hasAnalyticsQueryParam()
          ? PreferenceType.ANALYTICS
          : PreferenceType.USERS,
        this.entity,
        prefs
      )
      .subscribe(response => {
        this.preferences = response;
        this.getData();
      });
  }

  private hasAnalyticsQueryParam(): boolean {
    const { queryParams } = this.route.snapshot;
    return queryParams?.key === 'analytics';
  }
}
