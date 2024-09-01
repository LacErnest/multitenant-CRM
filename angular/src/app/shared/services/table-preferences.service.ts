import { HttpClient, HttpParams } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { Helpers } from 'src/app/core/classes/helpers';
import { GlobalService } from 'src/app/core/services/global.service';
import { PreferenceType } from 'src/app/shared/enums/preference-type.enum';
import { TablePreferenceType } from 'src/app/shared/enums/table-preference-type.enum';
import { TablePreferences } from 'src/app/shared/interfaces/table-preferences';
import { LegalEntitiesService } from 'src/app/views/legal-entities/legal-entities.service';
import { AppStateService } from './app-state.service';
import { ActivatedRouteSnapshot } from '@angular/router';

@Injectable({
  providedIn: 'root',
})
export class TablePreferencesService {
  private tablePages = new Map<number, number>();
  private page: number;

  public constructor(
    private http: HttpClient,
    private globalService: GlobalService,
    private legalEntitiesService: LegalEntitiesService,
    protected appStateService: AppStateService
  ) {}

  public getTableHttpParams(
    entity: number,
    preferences: PreferenceType
  ): HttpParams {
    let params = new HttpParams();
    this.page = this.appStateService.getLastDataTablePage(entity);

    if (preferences !== PreferenceType.USERS) {
      return params;
    } else {
      const page = this.page || this.getTablePage(entity);

      if (page !== undefined) {
        params = Helpers.setParam(params, 'page', page.toString());
      }
      return params;
    }
  }

  public getTablePreferences(
    key: PreferenceType,
    entity: number
  ): Observable<TablePreferences> {
    const companyId = this.getCurrentCompanyId(entity);
    return this.http.get<TablePreferences>(
      `api/${companyId}/table_preferences/${key}/${entity.toString()}`
    );
  }

  public setTablePreferences(
    key: PreferenceType,
    entity: number,
    preferences: TablePreferences
  ): Observable<TablePreferences> {
    const companyId = this.getCurrentCompanyId(entity);
    return this.http.put<TablePreferences>(
      `api/${companyId}/table_preferences/${key}`,
      { entity, ...preferences }
    );
  }

  public getTablePage(entity: number): number {
    return this.tablePages.get(entity);
  }

  public setTablePage(entity: number, page: number): void {
    this.tablePages.set(entity, page);
  }

  public removeTablePage(entity: number): void {
    this.tablePages.delete(entity);
  }

  private getCurrentCompanyId(entity: number): string {
    return entity === TablePreferenceType.LEGAL_ENTITIES
      ? this.legalEntitiesService.legalEntityCompanyId
      : this.globalService.currentCompany?.id;
  }

  public getTableParams(
    route: ActivatedRouteSnapshot,
    params: HttpParams
  ): HttpParams {
    if (route.queryParams.return) {
      params = this.getTableHttpParams(
        route.data.entity,
        route.data.preferences
      );
    }
    const page = this.appStateService.getLastDataTablePage(route.data.entity);
    if (page) {
      params = Helpers.setParam(params, 'page', page.toString());
    }

    return params;
  }
}
