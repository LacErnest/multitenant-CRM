import { HttpClient, HttpParams, HttpResponse } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { interval, Observable, Subject, Subscription } from 'rxjs';
import { mergeMap, publishReplay, refCount } from 'rxjs/operators';
import { Helpers } from 'src/app/core/classes/helpers';
import { GlobalService } from 'src/app/core/services/global.service';
import { UserRole } from 'src/app/shared/enums/user-role.enum';
import { Analytics } from 'src/app/views/dashboard/interfaces/analytics';
import { AnalyticsFilters } from 'src/app/views/dashboard/interfaces/analytics-filters';
import { PredictionType } from './types/earnout-summary-type';

@Injectable({
  providedIn: 'root',
})
export class DashboardService {
  public analyticsSubject: Subject<Analytics> = new Subject<Analytics>();

  private cachedAnalytics: Observable<any>; // TODO: add type
  private pollingSub: Subscription;

  constructor(
    private http: HttpClient,
    private globalService: GlobalService
  ) {}

  public get shouldFetchAllAnalytics(): boolean {
    return (
      this.globalService.getUserRole() === UserRole.ADMINISTRATOR &&
      this.globalService.currentCompany?.id === 'all'
    );
  }

  public getAllAnalytics(params?: HttpParams): Observable<Analytics> {
    return this.http.get<Analytics>('api/dashboard', { params });

    /*
    TODO: check cached analytics
    const request = this.http.get(
      'api/dashboard',
      { params }
    );

    if (!this.cachedAnalytics) {
      this.cachedAnalytics = request.pipe(publishReplay(1), refCount());
    }

    this.startAnalyticsPolling(request);
    return this.cachedAnalytics;
     */
  }

  public getAllAnalyticsSummary(
    entity: any,
    params: HttpParams
  ): Observable<any> {
    return this.http.get(`api/dashboard/${entity}/summary`, { params });
  }

  public getCompanyAnalytics(params?: HttpParams): Observable<Analytics> {
    return this.http.get<Analytics>(
      `api/${this.globalService.currentCompany?.id}/dashboard`,
      { params }
    );

    /*
    TODO: check cached analytics
    const request = this.http.get(
      `api/${this.globalService.currentCompany?.id}/dashboard`,
      { params }
    );

    if (!this.cachedAnalytics) {
      this.cachedAnalytics = request.pipe(publishReplay(1), refCount());
    }

    this.startAnalyticsPolling(request);
    return this.cachedAnalytics;
     */
  }

  public getAnalyticsSummary(entity: any, params: HttpParams): Observable<any> {
    return this.http.get(
      `api/${this.globalService.currentCompany?.id}/dashboard/${entity}/summary`,
      { params }
    );
  }

  public getEarnoutAnalyticsSummary(params: HttpParams): Observable<any> {
    return this.http.get(
      `api/${this.globalService.currentCompany?.id}/dashboard/earn_out_summary`,
      { params: params }
    );
  }

  public getEarnoutStatus(params: HttpParams): Observable<any> {
    return this.http.get(
      `api/${this.globalService.currentCompany?.id}/dashboard/earn_out_status`,
      { params: params }
    );
  }

  public earnoutStatusApprove(
    params: HttpParams,
    companyId: string
  ): Observable<any> {
    return this.http.post(
      `api/${companyId}/dashboard/earn_out_status/approve`,
      companyId,
      { params: params }
    );
  }

  public earnoutStatusChange(
    params: HttpParams,
    companyId: string,
    endpoint: string
  ): Observable<any> {
    return this.http.patch(
      `api/${companyId}/dashboard/earn_out_status/${endpoint}`,
      companyId,
      { params: params }
    );
  }

  public clearAnalyticsCache(): void {
    this.cachedAnalytics = null;
  }

  public startAnalyticsPolling(request: Observable<any>): void {
    this.pollingSub?.unsubscribe();
    this.pollingSub = interval(3600000)
      .pipe(mergeMap(() => request))
      .subscribe(response => {
        this.analyticsSubject.next(response);
      });
  }

  public getAnalyticsParams(): HttpParams {
    let params = new HttpParams();
    const savedFilters: AnalyticsFilters = JSON.parse(
      localStorage.getItem('analytics_filters')
    );

    if (savedFilters) {
      for (const [key, value] of Object.entries(savedFilters)) {
        params = Helpers.setParam(params, key, value);
      }
    }

    return params;
  }

  public exportEarnOut(params: HttpParams): Observable<HttpResponse<Blob>> {
    return this.http.get(
      'api/' +
        this.globalService.currentCompany?.id +
        '/dashboard/earn_out_summary/export',
      { params: params, responseType: 'blob', observe: 'response' }
    );
  }

  public getEarnoutPredictionSummary(): Observable<any> {
    return this.http.get(
      `api/${this.globalService.currentCompany?.id}/dashboard/earn_out_prospection`
    );
  }
}
