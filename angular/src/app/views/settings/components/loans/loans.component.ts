import { Component, OnDestroy, OnInit, ViewChild } from '@angular/core';
import { ActivatedRoute, NavigationEnd, Router } from '@angular/router';
import moment from 'moment';
import { ToastrService } from 'ngx-toastr';
import { Subject } from 'rxjs';
import { filter, finalize, skip, takeUntil } from 'rxjs/operators';
import { GlobalService } from 'src/app/core/services/global.service';
import { DatatableButtonConfig } from 'src/app/shared/classes/datatable/datatable-button-config';
import { DatatableContainerBase } from 'src/app/shared/classes/datatable/datatable-container-base';
import { DatatableMenuConfig } from 'src/app/shared/classes/datatable/datatable-menu-config';
import { AppStateService } from 'src/app/shared/services/app-state.service';
import { TablePreferencesService } from 'src/app/shared/services/table-preferences.service';
import { LoanModalComponent } from 'src/app/views/settings/components/loan-modal/loan-modal.component';
import { LoanHelpers } from 'src/app/views/settings/helpers/loan-helpers';
import { Loan, LoanList } from 'src/app/views/settings/interfaces/loan';
import { settingsRoutesRoles } from 'src/app/views/settings/settings-roles';
import { SettingsService } from 'src/app/views/settings/settings.service';

const LOANS_PAGINATION_COUNT = 10;

@Component({
  selector: 'oz-finance-loans',
  templateUrl: './loans.component.html',
  styleUrls: ['./loans.component.scss'],
})
export class LoansComponent
  extends DatatableContainerBase
  implements OnInit, OnDestroy
{
  @ViewChild('loanModal') public loanModal: LoanModalComponent;

  public loans: LoanList;
  public buttonConfig: DatatableButtonConfig = new DatatableButtonConfig({
    columns: false,
    filters: false,
    export: false,
    delete: false,
    refresh: true,
  });
  public rowMenuConfig: DatatableMenuConfig = new DatatableMenuConfig({
    export: false,
    clone: false,
  });
  public loanColumns = [
    { prop: 'author', name: 'author', type: 'string' },
    { prop: 'description', name: 'description', type: 'string' },
    { prop: 'amount', name: 'amount', type: 'decimal' },
    { prop: 'amount_left', name: 'open amount', type: 'decimal' },
    { prop: 'issued_at', name: 'issued at', type: 'date' },
    { prop: 'paid_at', name: 'paid at', type: 'date' },
    { prop: 'updated_at', name: 'updated at', type: 'date' },
    { prop: 'deleted_at', name: 'deleted at', type: 'date' },
  ];
  public companyCurrency = this.globalService.userCurrency;

  private onDestroy$: Subject<void> = new Subject<void>();

  constructor(
    protected tablePreferencesService: TablePreferencesService,
    protected route: ActivatedRoute,
    private globalService: GlobalService,
    private router: Router,
    private settingsService: SettingsService,
    private toastrService: ToastrService,
    protected appStateService: AppStateService
  ) {
    super(tablePreferencesService, route, appStateService);
  }

  public ngOnInit(): void {
    this.getResolvedData();
    this.setPermissions();
    this.initSubscriptions();
  }

  public ngOnDestroy(): void {
    this.onDestroy$.next();
    this.onDestroy$.complete();
  }

  //#region loan CRUD invocation
  public addLoan(): void {
    this.loanModal.openModal(undefined).subscribe(value => {
      if (value) {
        this.createLoan(value);
      }
    });
  }

  public editLoan(loan: Loan): void {
    this.loanModal.openModal(loan).subscribe(value => {
      if (value) {
        this.updateLoan(value);
      }
    });
  }

  public onDeleteLoanClicked(loans: Loan[]): void {
    const [loan] = loans;

    /**
     * NOTE: recheck in case 3 days have passed since GET response was set to data-table
     */
    const isDeletionAllowedRecheck = LoanHelpers.isLoanDeletionAllowed(
      loan.paid_at,
      loan.created_at,
      loan.deleted_at
    );

    if (isDeletionAllowedRecheck) {
      this.deleteLoan(loan);
    } else {
      const index = this.loans.data.findIndex(l => l.id === loan.id);
      this.loans.data[index].is_deletion_allowed = false;
      this.toastrService.warning(
        'Sorry, deletion is not allowed anymore',
        'Warning'
      );
    }
  }
  //#endregion

  protected getData(): void {
    this.isLoading = true;

    this.settingsService
      .getLoans(this.params)
      .pipe(finalize(() => (this.isLoading = false)))
      .subscribe(response => {
        this.loans = response;
        this.setPermissions();
      });
  }

  //#region setting values

  private getResolvedData(): void {
    this.loans = this.route.snapshot.data.loans;
  }

  //#endregion

  //#region loan CRUD
  private createLoan(loan: Loan): void {
    this.isLoading = true;

    this.settingsService
      .addLoan(loan)
      .pipe(
        finalize(() => {
          this.isLoading = false;
        })
      )
      .subscribe(
        response => {
          this.updateLoansListAfterCreate(response);

          this.toastrService.success('Loan was successfully added', 'Success');
        },
        error => {
          this.toastrService.error('Loan was not created', 'Error');
        }
      );
  }

  private updateLoansListAfterCreate(loan: Loan): void {
    /**
     * NOTE: if there's only one loan page, there's no need to re-fetch list
     */
    if (this.loans.count < LOANS_PAGINATION_COUNT) {
      loan.is_edit_allowed = loan.is_deletion_allowed = true;
      this.loans.data.push(loan);
      this.loans.count += 1;
      this.sortLoans();
    } else {
      this.getData();
    }
  }

  private sortLoans(): void {
    this.loans.data = this.loans.data.sort((l1, l2) => {
      return +new Date(l2.issued_at) - +new Date(l1.issued_at);
    });
  }

  private updateLoan(loan: Loan): void {
    this.isLoading = true;

    this.settingsService
      .editLoan(loan.id, loan)
      .pipe(
        finalize(() => {
          this.isLoading = false;
        })
      )
      .subscribe(
        response => {
          this.updateLoansListAfterUpdate(response);

          this.toastrService.success(
            'Loan was successfully updated',
            'Success'
          );
        },
        error => {
          this.toastrService.error('Loan was not updated', 'Error');
        }
      );
  }

  private updateLoansListAfterUpdate(loan: Loan): void {
    /**
     * NOTE: if there's pagination => list should be re-fetched because of sorting
     */
    if (this.loans.count > LOANS_PAGINATION_COUNT) {
      this.getData();
    } else {
      loan.is_edit_allowed = LoanHelpers.isLoanEditAllowed(
        loan.paid_at,
        loan.deleted_at
      );
      loan.is_deletion_allowed = LoanHelpers.isLoanDeletionAllowed(
        loan.paid_at,
        loan.created_at,
        loan.deleted_at
      );

      const index = this.loans.data.findIndex(l => l.id === loan.id);
      this.loans.data[index] = loan;
      this.sortLoans();
    }
  }

  private deleteLoan(loan: Loan): void {
    this.isLoading = true;

    this.settingsService
      .deleteLoan(loan.id)
      .pipe(finalize(() => (this.isLoading = false)))
      .subscribe(
        () => {
          const index = this.loans.data.findIndex(l => l.id === loan.id);
          this.loans.data[index].deleted_at = moment().format();
          this.loans.data[index].is_edit_allowed = this.loans.data[
            index
          ].is_deletion_allowed = false;
          this.toastrService.success(
            'Loan was successfully deleted',
            'Success'
          );
          this.getData();
        },
        error => {
          this.toastrService.error('Loan was not deleted', 'Error');
        }
      );
  }
  //#endregion

  private setPermissions(): void {
    this.loans.data.forEach(loan => {
      loan.is_edit_allowed = LoanHelpers.isLoanEditAllowed(
        loan.paid_at,
        loan.deleted_at
      );
      loan.is_deletion_allowed = LoanHelpers.isLoanDeletionAllowed(
        loan.paid_at,
        loan.created_at,
        loan.deleted_at
      );

      return loan;
    });
  }

  private initSubscriptions(): void {
    this.globalService
      .getCurrentCompanyObservable()
      .pipe(skip(1), takeUntil(this.onDestroy$))
      .subscribe(value => {
        const allowedRoles = settingsRoutesRoles.loans;

        if (value?.id === 'all' || !allowedRoles.includes(value.role)) {
          this.router.navigate(['/']).then();
        } else {
          this.router.navigate([`/${value.id}/settings/loans`]).then();
        }
      });

    this.router.events
      .pipe(
        filter(e => e instanceof NavigationEnd),
        takeUntil(this.onDestroy$)
      )
      .subscribe(() => {
        this.getResolvedData();
        this.setPermissions();
      });
  }

  public refreshClicked(): void {
    this.getData();
  }
}
