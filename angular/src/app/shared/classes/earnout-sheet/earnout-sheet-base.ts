import { HttpParams } from '@angular/common/http';
import { DashboardService } from '../../../views/dashboard/dashboard.service';
import { GlobalService } from '../../../core/services/global.service';
import {
  ActionType,
  PredictionType,
  StatusType,
  SummaryType,
} from '../../../views/dashboard/types/earnout-summary-type';
import { finalize } from 'rxjs/operators';
import { Helpers } from '../../../core/classes/helpers';
import { Router } from '@angular/router';
import { UserRole } from '../../enums/user-role.enum';

export abstract class EarnoutSheetBase {
  public isLoading = false;
  public summary: SummaryType;
  public status: StatusType;
  protected params = new HttpParams();
  protected entity: number;
  public userRole: number;
  public companyId: string;
  public currency: number;
  public summaryAction: string;
  public EuroCurrencyCode = 43;
  public errorsMessage = '';
  public responseMessage = '';
  public actions: ActionType = {
    approve: 'approve',
    confirm: 'confirm',
    received: 'received',
  };
  public prediction: PredictionType;

  protected constructor(
    protected dashboardService: DashboardService,
    protected globalService: GlobalService,
    protected router: Router
  ) {
    this.userRole = this.globalService.getUserRole();
    this.companyId = this.globalService.currentCompany?.id;
    this.currency =
      this.userRole === UserRole.ADMINISTRATOR
        ? this.EuroCurrencyCode
        : this.globalService.userCurrency;
  }

  public filtersChanged({ formValue }): void {
    this.isLoading = true;
    this.setQueryParams(formValue);

    const queryParams = { ...formValue };

    this.router
      .navigate(['/dashboard/summary/' + this.entity], { queryParams })
      .then(() => {
        this.dashboardService
          .getEarnoutAnalyticsSummary(this.params)
          .pipe(
            finalize(() => {
              this.isLoading = false;
            })
          )
          .subscribe(
            response => {
              this.summary = response;
            },
            error => {
              this.errorsMessage = error.message;
            }
          );

        this.getStatusData();
      });
  }

  public handleAction({ formValue, action }): void {
    this.isLoading = true;
    this.summaryAction = action;
    this.setQueryParams(formValue);

    const queryParams = { ...formValue };

    this.router
      .navigate(['/dashboard/summary/' + this.entity], { queryParams })
      .then(() => {
        if (this.summaryAction === this.actions.approve) {
          this.dashboardService
            .earnoutStatusApprove(this.params, this.companyId)
            .pipe(
              finalize(() => {
                this.isLoading = false;
              })
            )
            .subscribe(
              response => {
                this.getStatusData();
                this.responseMessage = response.message;
              },
              error => {
                this.errorsMessage = error.message;
              }
            );
        } else {
          this.dashboardService
            .earnoutStatusChange(
              this.params,
              this.companyId,
              this.summaryAction
            )
            .pipe(
              finalize(() => {
                this.isLoading = false;
              })
            )
            .subscribe(
              response => {
                this.getStatusData();
                this.responseMessage = response.message;
              },
              error => {
                this.errorsMessage = error.message;
              }
            );
        }
      });
  }

  public predictionAsked(): void {
    this.isLoading = true;

    this.dashboardService
      .getEarnoutPredictionSummary()
      .pipe(
        finalize(() => {
          this.isLoading = false;
        })
      )
      .subscribe(
        response => {
          this.prediction = response;
        },
        error => {
          this.errorsMessage = error.message;
        }
      );
  }

  protected getStatusData(): void {
    this.dashboardService
      .getEarnoutStatus(this.params)
      .pipe(
        finalize(() => {
          // this.isLoading = false;
        })
      )
      .subscribe(
        response => {
          this.status = response;
        },
        error => {
          this.errorsMessage = error.message;
        }
      );
  }

  protected setQueryParams(filter): void {
    Object.entries(filter).forEach(value => {
      this.params = Helpers.setParam(
        this.params,
        value[0],
        value[1]?.toString()
      );
    });
  }
}
