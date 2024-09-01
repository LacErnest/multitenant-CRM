import { GlobalService } from '../../../core/services/global.service';
import { Router } from '@angular/router';
import { CommissionsSummary } from '../interfaces/commissions-summary';
import { HttpParams } from '@angular/common/http';
import { finalize } from 'rxjs/operators';
import { Helpers } from '../../../core/classes/helpers';
import { CommissionsService } from '../commissions.service';
import { UserRole } from '../../../shared/enums/user-role.enum';
import {
  CommissionsPaymentLogs,
  CreateLog,
  IndividualCommissionPayment,
  IndividualCommissionPaymentId,
  TotalOpenAmount,
} from '../interfaces/commissions-payment-log';
import { environment } from '../../../../environments/environment';

export abstract class CommissionsBase {
  public isLoading = false;
  public summary: CommissionsSummary;
  public paymentLogs: CommissionsPaymentLogs;
  public totalOpenAmount: TotalOpenAmount;
  protected params = new HttpParams();
  protected entity: string;
  public responseMessage = '';
  public errorsMessage = '';
  public currency: number;
  public userRole: number;

  protected constructor(
    protected commissionsService: CommissionsService,
    protected globalService: GlobalService,
    protected router: Router
  ) {
    this.userRole = this.globalService.getUserRole();
    this.currency = this.currency = environment.currency;
  }

  public filtersChanged({
    formValue,
    filterOption,
    expandedQuotes = [],
  }): void {
    this.isLoading = true;
    this.setQueryParams(formValue);

    const queryParams = { ...{ type: filterOption }, ...formValue };

    this.router.navigate(['/commissions'], { queryParams }).then(() => {
      this.commissionsService
        .getCommissionSummary(this.params)
        .pipe(
          finalize(() => {
            this.isLoading = false;
          })
        )
        .subscribe(
          response => {
            for (const company of response.companies) {
              for (const customer of company.customers) {
                const quote = customer.quotes.find(q =>
                  expandedQuotes.includes(q.order_id)
                );
                if (quote) {
                  customer.expanded = true;
                }
              }
            }

            this.summary = response;
          },
          error => {
            this.handleErrorMessage(error);
          }
        );

      this.getPaymentLogs();
      this.getSalesPersonTotalOpenAmount();
    });
  }

  public createPaymentLog({ amount, sales_person_id, responseMessage }): void {
    this.isLoading = true;
    this.responseMessage = responseMessage;

    this.params = Helpers.setParam(
      this.params,
      'sales_person_id',
      sales_person_id
    );

    const data: CreateLog = {
      amount: amount,
      sales_person_id: sales_person_id,
    };

    this.commissionsService
      .createCommissionPaymentLog(data)
      .pipe(
        finalize(() => {
          this.isLoading = false;
        })
      )
      .subscribe(
        response => {
          this.responseMessage = response.message;
          this.getSalesPersonTotalOpenAmount();
          this.getPaymentLogs();
        },
        error => {
          this.errorsMessage = error.message;
        }
      );
  }

  public createIndividualCommissionPayment({
    amount,
    sales_person_id,
    order_id,
    invoice_id,
    total,
  }): void {
    this.isLoading = true;

    const data: IndividualCommissionPayment = {
      amount: amount,
      sales_person_id: sales_person_id,
      order_id: order_id,
      invoice_id: invoice_id,
      total: total,
    };
    this.commissionsService
      .createIndividualCommissionPayment(data)
      .pipe(finalize(() => {}))
      .subscribe(
        response => {
          this.responseMessage = response.message;
          this.getSalesPersonTotalOpenAmount();
          this.getPaymentLogs();
        },
        error => {
          this.errorsMessage = error.message;
        }
      );
    this.isLoading = false;
  }

  public removeIndividualCommissionPayment({
    sales_person_id,
    order_id,
    invoice_id,
  }): void {
    this.isLoading = true;

    const data: IndividualCommissionPaymentId = {
      sales_person_id: sales_person_id,
      order_id: order_id,
      invoice_id: invoice_id,
    };
    this.commissionsService
      .removeIndividualCommissionPayment(data)
      .pipe(finalize(() => {}))
      .subscribe(
        response => {
          this.responseMessage = response.message;
          this.getSalesPersonTotalOpenAmount();
          this.getPaymentLogs();
        },
        error => {
          this.errorsMessage = error.message;
        }
      );
    this.isLoading = false;
  }

  public confirmPayment({ id, responseMessage }): void {
    this.isLoading = true;
    this.responseMessage = responseMessage;

    this.params = Helpers.setParam(
      this.params,
      'sales_person_id',
      this.globalService.userDetails.id
    );

    this.commissionsService
      .updateCommissionPaymentLog(id)
      .pipe(
        finalize(() => {
          this.isLoading = false;
        })
      )
      .subscribe(
        response => {
          this.responseMessage = response.message;
          this.getPaymentLogs();
        },
        error => {
          this.errorsMessage = error.message;
        }
      );
  }

  protected getSalesPersonTotalOpenAmount(): void {
    if (this.params.has('sales_person_id')) {
      this.commissionsService
        .getTotalOpenAmount(this.params)
        .pipe(
          finalize(() => {
            this.isLoading = false;
          })
        )
        .subscribe(
          response => {
            this.totalOpenAmount = response;
          },
          error => {
            this.handleErrorMessage(error);
          }
        );
    } else {
      this.totalOpenAmount = null;
    }
  }

  protected getPaymentLogs(): void {
    if (this.params.has('sales_person_id')) {
      const params = this.setSalesPersonIdParam();
      this.commissionsService
        .getCommissionPaymentLogs(params)
        .pipe(
          finalize(() => {
            this.isLoading = false;
          })
        )
        .subscribe(
          response => {
            this.paymentLogs = response;
          },
          error => {
            this.handleErrorMessage(error);
          }
        );
    } else {
      this.paymentLogs = { data: [] };
    }
  }

  protected setQueryParams(filter): void {
    Object.entries(filter).forEach(value => {
      this.params = Helpers.setParam(
        this.params,
        value[0],
        value[1]?.toString()
      );
    });

    if (this.globalService.getUserRole() === UserRole.SALES_PERSON) {
      this.params = Helpers.setParam(
        this.params,
        'sales_person_id',
        this.globalService.userDetails.id
      );
    }
  }

  // to set in HttpParams only sales_person_id
  protected setSalesPersonIdParam(): HttpParams {
    const httpParams = new HttpParams();
    return Helpers.setParam(
      httpParams,
      'sales_person_id',
      this.params.get('sales_person_id')
    );
  }

  protected handleErrorMessage(error): void {
    if (error && error.status === '422') {
      this.errorsMessage = 'Invalid data';
    }
  }
}
