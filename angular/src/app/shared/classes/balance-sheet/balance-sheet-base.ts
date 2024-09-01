import { HttpParams } from '@angular/common/http';
import { DashboardService } from '../../../views/dashboard/dashboard.service';
import { GlobalService } from '../../../core/services/global.service';
import { finalize } from 'rxjs/operators';
import { Helpers } from '../../../core/classes/helpers';
import { Router } from '@angular/router';
import { FilterOption } from '../../../views/dashboard/containers/analytics/analytics.component';

export type SummaryType = {
  data: {
    companies: { customers: any[]; id: string; name: string }[];
  };
  periods: any;
};
export abstract class BalanceSheetBase {
  isLoading = false;
  summary: SummaryType;
  protected params = new HttpParams();
  protected entity: number;

  protected constructor(
    protected dashboardService: DashboardService,
    protected globalService: GlobalService,
    protected router: Router
  ) {}

  filtersChanged({ formValue, filterOption }) {
    this.isLoading = true;
    this.setQueryParams(formValue);

    const queryParams = { ...{ type: filterOption }, ...formValue };

    this.router
      .navigate(['/dashboard/summary/' + this.entity], { queryParams })
      .then(() => {
        if (this.globalService.currentCompany.id !== 'all') {
          this.dashboardService
            .getAnalyticsSummary(this.entity, this.params)
            .pipe(
              finalize(() => {
                this.isLoading = false;
              })
            )
            .subscribe(response => {
              this.summary = response;
            });
        } else {
          this.dashboardService
            .getAllAnalyticsSummary(this.entity, this.params)
            .pipe(
              finalize(() => {
                this.isLoading = false;
              })
            )
            .subscribe(response => {
              this.summary = response;
            });
        }
      });
  }

  protected setQueryParams(filter) {
    Object.entries(filter).forEach(value => {
      this.params = Helpers.setParam(
        this.params,
        value[0],
        value[1]?.toString()
      );
    });
  }
}
