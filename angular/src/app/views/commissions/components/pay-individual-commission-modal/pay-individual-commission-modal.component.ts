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
import { currencyRegEx } from '../../../../shared/constants/regex';
import {
  errorEnterMessageAnimation,
  errorLeaveMessageAnimation,
} from '../../../../shared/animations/browser-animations';
import { GlobalService } from '../../../../core/services/global.service';
import { EnumService } from '../../../../core/services/enum.service';
import { CommissionLog } from '../../interfaces/commissions-payment-log';

@Component({
  selector: 'oz-finance-pay-individual-commission-modal',
  templateUrl: './pay-individual-commission-modal.component.html',
  styleUrls: ['./pay-individual-commission-modal.component.scss'],
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
export class PayIndividualCommissionModalComponent implements OnInit {
  @Input() currency: number;

  public payCommissionForm: FormGroup;
  public currencyPrefix: string;

  public showPayCommissionModal = false;
  public title = 'Pay individual commission';
  public totalAmount: number;
  private modalSubject: Subject<any>;
  public amount: any;
  public commission: any;
  public quote: any;
  public paidValue: any;

  constructor(
    @Inject(DOCUMENT) private _document,
    private fb: FormBuilder,
    private globalService: GlobalService,
    private enumService: EnumService,
    private renderer: Renderer2
  ) {}

  ngOnInit(): void {}

  public openModal(quote, commission): Subject<any> {
    this.quote = quote;
    this.commission = commission;
    this.amount = commission?.commission;
    this.paidValue = commission?.paid_value;

    if (commission?.paid_value > 0) {
      this.amount -= commission?.paid_value;
    }

    this.initForm();
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
      amount: new FormControl(this.totalAmount, [
        Validators.required,
        Validators.min(0.01),
        Validators.max(this.totalAmount),
        Validators.pattern(currencyRegEx),
      ]),
    });
  }

  private setCurrencyPrefix(): void {
    this.currencyPrefix =
      getCurrencySymbol(
        this.enumService.getEnumMap('currencycode').get(this.currency),
        'wide'
      ) + ' ';
  }
}
