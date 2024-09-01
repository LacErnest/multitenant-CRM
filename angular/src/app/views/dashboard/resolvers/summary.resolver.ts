import {
  ActivatedRouteSnapshot,
  Resolve,
  RouterStateSnapshot,
} from '@angular/router';
import { DashboardService } from '../dashboard.service';
import { Observable } from 'rxjs';
import { HttpParams } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { GlobalService } from '../../../core/services/global.service';
import { Helpers } from '../../../core/classes/helpers';

@Injectable({
  providedIn: 'root',
})
export class SummaryResolver implements Resolve<any> {
  constructor(
    private dashboardService: DashboardService,
    private globalService: GlobalService
  ) {}

  resolve(
    route: ActivatedRouteSnapshot,
    state: RouterStateSnapshot
  ): Observable<any> | Promise<any> | any {
    let params = new HttpParams();
    for (const [key, value] of Object.entries(route.queryParams)) {
      if (key !== 'type') {
        params = Helpers.setParam(params, key, value);
      }
    }

    if (
      this.globalService.getUserRole() === 0 &&
      this.globalService.currentCompany?.id === 'all'
    ) {
      return this.dashboardService.getAllAnalyticsSummary(
        route.data.entity,
        params
      );
    } else {
      return this.dashboardService.getAnalyticsSummary(
        route.data.entity,
        params
      );
    }
  }
}
