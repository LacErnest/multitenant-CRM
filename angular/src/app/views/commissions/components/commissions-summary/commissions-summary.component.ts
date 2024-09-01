import {
  Component,
  EventEmitter,
  Input,
  OnDestroy,
  OnInit,
  Output,
  SimpleChanges,
  ViewChild,
} from '@angular/core';
import { FilterOption } from '../../../dashboard/containers/analytics/analytics.component';
import { CommissionsSummary } from '../../interfaces/commissions-summary';
import {
  animate,
  style,
  transition,
  trigger,
  useAnimation,
} from '@angular/animations';
import { FormBuilder, FormControl } from '@angular/forms';
import { concat, Observable, of, Subject } from 'rxjs';
import { GlobalService } from '../../../../core/services/global.service';
import { ActivatedRoute } from '@angular/router';
import {
  catchError,
  debounceTime,
  distinctUntilChanged,
  filter,
  switchMap,
  tap,
} from 'rxjs/operators';
import { BalanceSheetComponent } from '../../../../shared/components/balance-sheet/balance-sheet.component';
import { SearchEntity } from '../../../projects/modules/project/interfaces/search-entity';
import { HttpParams } from '@angular/common/http';
import { SuggestService } from '../../../../shared/services/suggest.service';
import { UserRole } from '../../../../shared/enums/user-role.enum';
import { PayCommissionModalComponent } from '../pay-commission-modal/pay-commission-modal.component';
import {
  menuEnterAnimation,
  menuLeaveAnimation,
} from '../../../../shared/animations/browser-animations';
import {
  CommissionsPaymentLogs,
  CommissionLog,
  TotalOpenAmount,
} from '../../interfaces/commissions-payment-log';
import { DateFormat } from '../../../../shared/enums/date.format';
import { CommissionLogStatus } from '../../../../shared/enums/commission-log-status.enum';
import { ToastrService } from 'ngx-toastr';
import { Helpers } from '../../../../core/classes/helpers';
import { ConfirmModalComponent } from 'src/app/shared/components/confirm-modal/confirm-modal.component';
import { EditSalesCommissionModalComponent } from '../edit-sales-commission-modal/edit-sales-commission.component';
import { CommissionsService } from '../../commissions.service';
import { AddSalesCommissionModalComponent } from '../add-sales-commission-modal/add-sales-commission.component';
import { PayIndividualCommissionModalComponent } from '../pay-individual-commission-modal/pay-individual-commission-modal.component';
import { CompanySetting } from 'src/app/views/settings/interfaces/company-setting';

@Component({
  selector: 'oz-finance-commissions-summary',
  templateUrl: './commissions-summary.component.html',
  styleUrls: ['./commissions-summary.component.scss'],
  animations: [
    trigger('filterAnimation', [
      transition(':enter', [
        style({ opacity: 0 }),
        animate('250ms ease-in', style({ opacity: 1 })),
      ]),
    ]),
    trigger('collapseAnimation', [
      transition(':enter', [
        style({ opacity: 0 }),
        animate('250ms ease-in-out', style({ opacity: 1 })),
      ]),
      transition(':leave', [
        style({ opacity: 1 }),
        animate('250ms ease-in-out', style({ opacity: 0 })),
      ]),
    ]),
    trigger('menuAnimation', [
      transition(':enter', useAnimation(menuEnterAnimation)),
      transition(':leave', useAnimation(menuLeaveAnimation)),
    ]),
  ],
})
export class CommissionsSummaryComponent
  extends BalanceSheetComponent
  implements OnInit, OnDestroy
{
  @Input() isLoading = false;
  @Input() commissionSummary: CommissionsSummary;
  @Input() totalOpenAmount: TotalOpenAmount;
  @Input() paymentLogs: CommissionsPaymentLogs;
  @Input() responseMessage = '';
  @Input() errorsMessage: string;
  @Input() currency: number;
  @Input() userRole: number;

  @Output() filtersChanged = new EventEmitter<{
    formValue: any;
    filterOption: FilterOption;
    expandedQuotes: string[];
  }>();
  @Output() createPaymentLog = new EventEmitter<{
    amount: number;
    sales_person_id: string;
    responseMessage: string;
  }>();
  @Output() createIndividualCommissionPayment = new EventEmitter<{
    amount: number;
    sales_person_id: string;
    order_id: string;
    invoice_id: string;
    total: number;
  }>();
  @Output() removeIndividualCommissionPayment = new EventEmitter<{
    sales_person_id: string;
    order_id: string;
    invoice_id: string;
  }>();
  @Output() confirmPayment = new EventEmitter<{
    id: string;
    responseMessage: string;
  }>();

  @ViewChild('payCommissionModal', { static: false })
  public payCommissionModal: PayCommissionModalComponent;
  @ViewChild('confirmModal', { static: false })
  public confirmModal: ConfirmModalComponent;
  @ViewChild('editSalesCommissionModal', { static: false })
  public editSalesCommissionModal: EditSalesCommissionModalComponent;
  @ViewChild('addSalesCommissionModal', { static: false })
  public addSalesCommissionModal: AddSalesCommissionModalComponent;
  @ViewChild('payIndividualCommissionModal', { static: false })
  public payIndividualCommissionModal: PayIndividualCommissionModalComponent;

  public commissionSettings: CompanySetting;

  public salesPersonRole = UserRole.SALES_PERSON;
  public ownerRole = UserRole.OWNER;
  public ownerReadOnly = UserRole.OWNER_READ_ONLY;
  public statusPaid = CommissionLogStatus.PAID;
  public dateFormats = DateFormat;
  public readOnly = false;

  public salesSelect$: Observable<SearchEntity[]>;
  public salesInput$: Subject<string> = new Subject<string>();
  public selectedSales: SearchEntity;
  public salesDefault: SearchEntity[] = [];
  public isSalesLoading = false;

  public isAllowedForOperations = false;

  constructor(
    protected fb: FormBuilder,
    protected globalService: GlobalService,
    protected route: ActivatedRoute,
    private suggestService: SuggestService,
    protected toastrService: ToastrService,
    private commissionsService: CommissionsService
  ) {
    super(fb, globalService, route);
  }

  ngOnInit(): void {
    super.ngOnInit();
    this.getResolvedData();
    this.initSalesTypeAhead();

    this.isAllowedForOperations =
      this.userRole === UserRole.ACCOUNTANT ||
      this.userRole === UserRole.ADMINISTRATOR;
  }

  private getResolvedData(): void {
    const { settings } = this.route.snapshot.data;
    this.commissionSettings = settings;
  }

  protected initFilterForm(): void {
    super.initFilterForm();

    this.filterForm.addControl('sales_person_id', new FormControl(undefined));
    this.filterForm.addControl('sp_name', new FormControl(undefined));

    if (
      this.currentFilter.sales_person_id &&
      this.userRole !== this.salesPersonRole
    ) {
      this.filterForm.controls.sales_person_id.setValue(
        this.currentFilter.sales_person_id
      );
      this.filterForm.controls.sp_name.setValue(this.currentFilter.sp_name);
      this.selectedSales = {
        id: this.currentFilter.sales_person_id,
        name: this.currentFilter.sp_name,
      };
    }
  }

  public ngOnChanges(changes: SimpleChanges): void {
    if (changes && changes.errorsMessage && this.errorsMessage) {
      this.toastrService.error(this.errorsMessage, 'Error');
      this.errorsMessage = '';
    }

    if (changes && changes.responseMessage && this.responseMessage) {
      this.toastrService.success(this.responseMessage, 'Success');
      this.responseMessage = '';
    }
  }

  private initSalesTypeAhead(): void {
    const params = new HttpParams();
    this.salesSelect$ = concat(
      of(this.salesDefault), // default items
      this.salesInput$.pipe(
        filter(t => !!t),
        debounceTime(500),
        distinctUntilChanged(),
        tap(() => {
          this.isSalesLoading = true;
        }),
        switchMap(term =>
          this.suggestService.suggestSalesPersons(term, params).pipe(
            catchError(() => of([])), // empty list on error
            tap(() => {
              this.isSalesLoading = false;
            })
          )
        )
      )
    );
  }

  public resetPeriodFiltersForm(): void {
    super.resetForm();
  }

  public resetForm(): void {
    super.resetForm();

    this.filterForm.controls.sales_person_id.reset(undefined);
    this.filterForm.controls.sp_name.reset(undefined);
    this.selectedSales = null;
  }

  // Payment logs code
  public customerChanged(event: SearchEntity): void {
    if (typeof event !== 'undefined') {
      this.filterForm.patchValue({ sales_person_id: event.id });
      this.filterForm.patchValue({ sp_name: event.name });
    } else {
      this.filterForm.patchValue({ sales_person_id: undefined });
      this.filterForm.patchValue({ sp_name: undefined });
    }
  }

  public handlePayCommission(): void {
    this.payCommissionModal
      .openModal(
        'Pay commission',
        this.totalOpenAmount?.total_commission_amount
      )
      .subscribe(result => {
        if (result) {
          this.createPaymentLog.emit({
            amount: result.amount,
            sales_person_id: this.filterForm.controls.sales_person_id.value,
            responseMessage: this.responseMessage,
          });
        }
      });
  }

  public confirm(i: number, item: CommissionLog): void {
    this.confirmPayment.emit({
      id: item.id,
      responseMessage: this.responseMessage,
    });
  }

  public addSalesCommission(): void {
    this.addSalesCommissionModal.openModal().subscribe(result => {
      const { order_id, invoice_id, sales_person_id, ...data } = result;
      this.isLoading = true;
      this.commissionsService
        .createSalesCommissionPercentage(
          order_id,
          invoice_id,
          sales_person_id,
          data
        )
        .subscribe(
          () => {
            this.toastrService.success(null, 'Sales commission created');
            this.refreshFilters({ order_id });
          },
          err => {
            let msg =
              err?.message ??
              'Could not create sales commission. Try again or contact an administrator';
            if (err?.message?.sales_person_id) {
              msg = err?.message?.sales_person_id[0];
            }
            this.toastrService.error(msg, 'Error');
            this.isLoading = false;
          }
        );
    });
  }

  public editSalesCommission(invoice, commission): void {
    this.editSalesCommissionModal
      .openModal(invoice, commission)
      .subscribe(result => {
        const { order_id, invoice_id, sales_person_id, ...data } = result;
        this.isLoading = true;
        this.commissionsService
          .updateSalesCommissionPercentage(
            order_id,
            invoice_id,
            sales_person_id,
            data
          )
          .subscribe(
            () => {
              this.toastrService.success(null, 'Sales commission updated');
              this.refreshFilters(invoice);
            },
            err => {
              const msg =
                err?.message ??
                'Could not update sales commission. Try again or contact an administrator';
              this.toastrService.error(msg, 'Error');
              this.isLoading = false;
            }
          );
      });
  }

  public payIndividualSalesComission(invoice, commission): void {
    this.isLoading = true;
    this.payIndividualCommissionModal.openModal(invoice, commission).subscribe(
      result => {
        if (result) {
          if (result.amount + commission.paid_value > commission.commission) {
            this.toastrService.error(
              null,
              'The paid value cannot be bigger than commission total value'
            );
            return;
          }
          this.createIndividualCommissionPayment.emit({
            amount: result.amount,
            sales_person_id: commission.sales_person_id,
            order_id: invoice.order_id,
            invoice_id: invoice.id,
            total: commission.commission,
          });
        }
        this.refreshFilters(invoice);
      },
      err => {
        const msg =
          err?.message ??
          'Could not update sales commission. Try again or contact an administrator';
        this.toastrService.error(msg, 'Error');
        this.isLoading = false;
      }
    );
  }

  public unpayIndividualSalesComission(invoice, commission): void {
    this.confirmModal
      .openModal(
        'Confirm',
        'Are you sure you want to cancel the payment of this commissi?'
      )
      .subscribe(confirm => {
        if (confirm) {
          this.isLoading = true;
          // setting commission_percentage to 0 means deleting the sales commission
          this.removeIndividualCommissionPayment.emit({
            sales_person_id: commission.sales_person_id,
            order_id: invoice.order_id,
            invoice_id: invoice.id,
          });

          this.refreshFilters(invoice);
        }
      });
  }

  public deleteSalesCommission(invoice, commission): void {
    this.confirmModal
      .openModal('Confirm', 'Are you sure you want to delete sales commission?')
      .subscribe(confirm => {
        if (confirm) {
          this.isLoading = true;
          // setting commission_percentage to 0 means deleting the sales commission
          this.commissionsService
            .deleteSalesCommissionPercentageById(
              commission.commission_percentage_id
            )
            .subscribe(
              () => {
                this.toastrService.success(null, 'Sales commission deleted');
                this.refreshFilters(invoice);
              },
              err => {
                const msg =
                  err?.message ??
                  'Could not remove sales commission. Try again or contact an administrator';
                this.toastrService.error(msg, 'Error');
                this.isLoading = false;
              }
            );
        }
      });
  }

  private refreshFilters(invoice): void {
    setTimeout(() => {
      this.filtersChanged.emit({
        formValue: this.filterForm.getRawValue(),
        filterOption: this.filterOption,
        expandedQuotes: [invoice.order_id],
      });
      this.isLoading = false;
    }, 5000);
  }
}
