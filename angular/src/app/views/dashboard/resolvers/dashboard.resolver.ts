import { Injectable } from '@angular/core';
import {
  ActivatedRouteSnapshot,
  Resolve,
  RouterStateSnapshot,
} from '@angular/router';
import { Observable } from 'rxjs';
import { DashboardService } from 'src/app/views/dashboard/dashboard.service';
import { Analytics } from 'src/app/views/dashboard/interfaces/analytics';

@Injectable({
  providedIn: 'root',
})
export class DashboardResolver implements Resolve<any> {
  constructor(private dashboardService: DashboardService) {}

  resolve(
    route: ActivatedRouteSnapshot,
    state: RouterStateSnapshot
  ): Observable<Analytics> | Promise<Analytics> | Analytics {
    const params = this.dashboardService.getAnalyticsParams();

    if (this.dashboardService.shouldFetchAllAnalytics) {
      return this.dashboardService.getAllAnalytics(params);
    } else {
      return this.dashboardService.getCompanyAnalytics(params);
    }
  }
}
