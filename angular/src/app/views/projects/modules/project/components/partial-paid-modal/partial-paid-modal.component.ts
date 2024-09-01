import { Component, Inject, Input, OnInit, Renderer2 } from '@angular/core';
import { Subject } from 'rxjs';
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

@Component({
  selector: 'oz-finance-partial-paid-modal',
  templateUrl: './partial-paid-modal.component.html',
  styleUrls: ['./partial-paid-modal.component.scss'],
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
export class PartialPaidModalComponent implements OnInit {
  @Input() currency: number;

  public payCommissionForm: FormGroup;
  public currencyPrefix: string;

  public showPayCommissionModal = false;
  public title = 'Pay commission';
  public totalAmount: number;
  private modalSubject: Subject<any>;

  constructor(
    @Inject(DOCUMENT) private _document,
    private fb: FormBuilder,
    private globalService: GlobalService,
    private enumService: EnumService,
    private renderer: Renderer2
  ) {}

  ngOnInit(): void {
    //
  }

  public openModal(title?: string, amount?: number): Subject<any> {
    this.title = title;
    this.totalAmount = amount;

    this.initForm();
    this.onCreateGroupFormValueChange();
    this.setCurrencyPrefix();

    this.renderer.addClass(this._document.body, 'modal-opened');
    this.showPayCommissionModal = true;
    this.modalSubject = new Subject<any>();

    return this.modalSubject;
  }

  public submitLoanForm(): void {
    if (this.payCommissionForm.valid) {
      const formData: CommissionLog = this.payCommissionForm.getRawValue();

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
    this.payCommissionForm = this.fb.group({
      pay_full_price: new FormControl(true, []),
      amount: new FormControl(this.totalAmount, [
        Validators.required,
        Validators.min(0.01),
        Validators.max(this.totalAmount),
        Validators.pattern(currencyRegEx),
      ]),
      pay_date: new FormControl(null, [Validators.required]),
    });
  }

  private setCurrencyPrefix(): void {
    this.currencyPrefix =
      getCurrencySymbol(
        this.enumService.getEnumMap('currencycode').get(this.currency),
        'wide'
      ) + ' ';
  }

  /**
   * Pay commission form changes logic
   * @return void
   */
  private onCreateGroupFormValueChange(): void {
    this.payCommissionForm.get('amount').valueChanges.subscribe(value => {
      this.amountChanged(value);
    });
  }

  /**
   * Call when amount changed
   * It is used to change the  Pay full price status
   * @param value
   */
  public amountChanged(value: number): void {
    this.payCommissionForm
      .get('pay_full_price')
      .setValue(value === this.totalAmount);
  }
}
