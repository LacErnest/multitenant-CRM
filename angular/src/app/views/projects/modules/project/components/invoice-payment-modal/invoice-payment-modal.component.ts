import {
  Component,
  Inject,
  Input,
  OnChanges,
  OnInit,
  Renderer2,
  SimpleChanges,
} from '@angular/core';
import { concat, Observable, of, Subject } from 'rxjs';
import {
  catchError,
  debounceTime,
  distinctUntilChanged,
  filter,
  switchMap,
  tap,
} from 'rxjs/operators';
import { DOCUMENT, getCurrencySymbol } from '@angular/common';
import {
  animate,
  animateChild,
  group,
  query,
  style,
  transition,
  trigger,
  useAnimation,
} from '@angular/animations';
import {
  FormBuilder,
  FormControl,
  FormGroup,
  Validators,
} from '@angular/forms';
import { GlobalService } from 'src/app/core/services/global.service';
import { EnumService } from 'src/app/core/services/enum.service';
import {
  errorEnterMessageAnimation,
  errorLeaveMessageAnimation,
} from 'src/app/shared/animations/browser-animations';
import { CommissionLog } from 'src/app/views/commissions/interfaces/commissions-payment-log';
import { currencyRegEx } from 'src/app/shared/constants/regex';
import { Invoice, InvoicePayment } from 'src/app/shared/interfaces/entities';
import { InvoicePaymentsService } from 'src/app/views/projects/modules/project/services/invoice-payments.service';
import { ToastrService } from 'ngx-toastr';
import { InvoiceStatus } from '../../enums/invoice-status.enum';
@Component({
  selector: 'oz-finance-invoice-payment-modal',
  templateUrl: './invoice-payment-modal.component.html',
  styleUrls: ['./invoice-payment-modal.component.scss'],
  animations: [
    trigger('modalContainerAnimation', [
      transition(':enter', [
        group([
          query('@modalBackdropAnimation', animateChild()),
          query('@modalAnimation', animateChild()),
        ]),
      ]),
      transition(':leave', [
        group([
          query('@modalBackdropAnimation', animateChild()),
          query('@modalAnimation', animateChild()),
        ]),
      ]),
    ]),
    trigger('modalBackdropAnimation', [
      transition(':enter', [
        style({ opacity: 0 }),
        animate('300ms ease-in', style({ opacity: 1 })),
      ]),
      transition(':leave', [
        style({ opacity: 1 }),
        animate('200ms ease-out', style({ opacity: 0 })),
      ]),
    ]),
    trigger('modalAnimation', [
      transition(':enter', [
        style({ opacity: 0, transform: 'translateY(1rem)' }),
        animate(
          '300ms ease-in',
          style({ opacity: 1, transform: 'translateY(0)' })
        ),
      ]),
      transition(':leave', [
        style({ opacity: 1, transform: 'translateY(0)' }),
        animate(
          '200ms ease-out',
          style({ opacity: 0, transform: 'translateY(1rem)' })
        ),
      ]),
    ]),
    trigger('errorMessageAnimation', [
      transition(':enter', useAnimation(errorEnterMessageAnimation)),
      transition(':leave', useAnimation(errorLeaveMessageAnimation)),
    ]),
  ],
})
export class InvoicePaymentModalComponent implements OnInit, OnChanges {
  //@Input() currency: number;
  @Input() invoices: Invoice[];
  @Input() invoice: Invoice;

  public invoicePaymentForm: FormGroup;
  public currencyPrefix: string;

  public showPayCommissionModal = false;
  public title = 'Invoice payment';
  public totalAmount: number;
  private modalSubject: Subject<any>;
  public invoicePayment: InvoicePayment;
  invoiceSelect: Observable<any[]> = new Observable<any[]>();
  invoiceInput: Subject<string> = new Subject<string>();
  isLoading: boolean;

  constructor(
    @Inject(DOCUMENT) private _document,
    private fb: FormBuilder,
    private globalService: GlobalService,
    private enumService: EnumService,
    private renderer: Renderer2,
    private invoicePaymentService: InvoicePaymentsService,
    private toastrService: ToastrService
  ) {}

  ngOnInit(): void {
    //
  }

  ngOnChanges(changes: SimpleChanges): void {
    this.disableIfReadonly();
  }

  public openModal(
    title?: string,
    amount?: number,
    invoicePayment?: InvoicePayment
  ): Subject<any> {
    this.title = title;
    this.invoicePayment = invoicePayment;
    this.calculateTotalPrice(this.invoice, amount);
    this.initForm();
    this.disableIfReadonly();
    this.initFormData();
    this.onCreateGroupFormValueChange();
    this.setCurrencyPrefix();

    this.renderer.addClass(this._document.body, 'modal-opened');
    this.showPayCommissionModal = true;
    this.modalSubject = new Subject<any>();

    return this.modalSubject;
  }

  public submitLoanForm(): void {
    if (this.invoicePaymentForm.valid) {
      const formData: CommissionLog = this.invoicePaymentForm.getRawValue();
      this.closeModal(formData);
    }
  }

  private closeModal(value?: CommissionLog): void {
    this.modalSubject.next(value);
    this.modalSubject.complete();
    this.showPayCommissionModal = false;
    this.renderer.removeClass(this._document.body, 'modal-opened');
  }

  public dismissModal(): void {
    this.modalSubject.complete();
    this.showPayCommissionModal = false;
    this.renderer.removeClass(this._document.body, 'modal-opened');
  }

  public initForm(): void {
    this.invoicePaymentForm = this.fb.group({
      pay_full_price: new FormControl(this.getInitialFullPriceStatus(), []),
      pay_amount: new FormControl(this.totalAmount, [
        Validators.required,
        Validators.min(0.01),
        Validators.max(this.totalAmount),
        Validators.pattern(currencyRegEx),
      ]),
      pay_date: new FormControl(null, [Validators.required]),
      pay_amount_usd: new FormControl(null, []),
    });
  }

  /**
   * Caculating current full payment status to sync with current payment amount
   * @returns boolean
   */
  private getInitialFullPriceStatus(): boolean {
    if (!this.invoicePayment) {
      return true;
    }
    return this.invoicePayment.pay_amount === this.totalAmount;
  }

  private initFormData(): void {
    if (this.invoicePayment) {
      this.invoicePaymentForm
        .get('pay_amount')
        .setValue(this.invoicePayment.pay_amount);
      this.invoicePaymentForm
        .get('pay_date')
        .setValue(this.invoicePayment.pay_date);
      this.invoicePaymentForm
        .get('pay_full_price')
        .setValue(this.getInitialFullPriceStatus());
    }
  }

  /**
   * Calculate remaining amount to paid
   * @param invoice
   * @param defaultAmount
   * @returns void
   */
  private calculateTotalPrice(invoice: Invoice, defaultAmount?: number): void {
    if (invoice) {
      this.totalAmount =
        invoice.customer_total_price - invoice.total_paid_amount;
    } else {
      this.totalAmount = defaultAmount ?? 0;
    }
    if (this.invoicePayment) {
      this.totalAmount += this.invoicePayment.pay_amount;
    }
    this.totalAmount = parseFloat(this.totalAmount.toFixed(2));
  }

  /**
   * Define private currency prefix for payment amount
   */
  private setCurrencyPrefix(): void {
    this.currencyPrefix =
      getCurrencySymbol(
        this.enumService
          .getEnumMap('currencycode')
          .get(this.invoice?.currency_code),
        'wide'
      ) + ' ';
  }

  /**
   * Pay commission form changes logic
   * @return void
   */
  private onCreateGroupFormValueChange(): void {
    this.invoicePaymentForm.get('pay_amount').valueChanges.subscribe(value => {
      this.payAmountChanged(value);
    });
  }

  /**
   * Call when amount changed
   * It is used to change the  Pay full price status
   * @param value
   */
  public payAmountChanged(value: number): void {
    this.invoicePaymentForm
      .get('pay_full_price')
      .setValue(value === this.totalAmount);
  }

  public onPayFullPrice($event): void {
    if ($event.currentTarget.checked) {
      this.invoicePaymentForm.get('pay_amount').setValue(this.totalAmount);
      this.invoicePaymentForm.get('pay_amount_usd').setValue(this.totalAmount);
    }
  }

  public submit(): void {
    if (this.invoicePaymentForm.valid && !this.isLoading) {
      const value = this.invoicePaymentForm.getRawValue();
      value.pay_amount = parseFloat(value.pay_amount);
      this.closeModal({
        ...value,
        project_id: this.invoice.project_id,
        invoice_id: this.invoice.id,
        currency_code: this.invoice.currency_code,
      });
    }
  }

  private disableIfReadonly(): void {
    if (this.isInvoicePaid() && this.invoicePaymentForm) {
      for (const controlName in this.invoicePaymentForm.controls) {
        const control = this.invoicePaymentForm.controls[controlName];
        control.disable();
      }
    }
  }

  /**
   * Check if current invoice is paid
   */
  public isInvoicePaid(): boolean {
    return this.invoice && this.invoice.status === InvoiceStatus.PAID;
  }
}
