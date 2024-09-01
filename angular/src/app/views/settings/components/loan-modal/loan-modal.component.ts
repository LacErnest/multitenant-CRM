import {
  animateChild,
  group,
  query,
  transition,
  trigger,
  useAnimation,
} from '@angular/animations';
import { DOCUMENT, getCurrencySymbol } from '@angular/common';
import { Component, Inject, OnInit, Renderer2 } from '@angular/core';
import {
  FormBuilder,
  FormControl,
  FormGroup,
  Validators,
} from '@angular/forms';
import moment from 'moment';
import { Subject } from 'rxjs';
import { EnumService } from 'src/app/core/services/enum.service';
import { GlobalService } from 'src/app/core/services/global.service';
import {
  errorEnterMessageAnimation,
  errorLeaveMessageAnimation,
  modalBackdropEnterAnimation,
  modalBackdropLeaveAnimation,
  modalEnterAnimation,
  modalLeaveAnimation,
} from 'src/app/shared/animations/browser-animations';
import { currencyRegEx } from 'src/app/shared/constants/regex';
import { UserRole } from 'src/app/shared/enums/user-role.enum';
import { Loan } from 'src/app/views/settings/interfaces/loan';
import { environment } from 'src/environments/environment';

@Component({
  selector: 'oz-finance-loan-modal',
  templateUrl: './loan-modal.component.html',
  styleUrls: ['./loan-modal.component.scss'],
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
      transition(':enter', useAnimation(modalBackdropEnterAnimation)),
      transition(':leave', useAnimation(modalBackdropLeaveAnimation)),
    ]),
    trigger('modalAnimation', [
      transition(':enter', useAnimation(modalEnterAnimation)),
      transition(':leave', useAnimation(modalLeaveAnimation)),
    ]),
    trigger('errorMessageAnimation', [
      transition(':enter', useAnimation(errorEnterMessageAnimation)),
      transition(':leave', useAnimation(errorLeaveMessageAnimation)),
    ]),
  ],
})
export class LoanModalComponent implements OnInit {
  public currencyPrefix: string;
  public loan: Loan;
  public loanForm: FormGroup;
  public showLoanModal = false;

  private modalSubject: Subject<Loan>;

  constructor(
    @Inject(DOCUMENT) private _document: Document,
    private fb: FormBuilder,
    private globalService: GlobalService,
    private enumService: EnumService,
    private renderer: Renderer2
  ) {}

  public ngOnInit(): void {}

  public openModal(loan: Loan): Subject<Loan> {
    this.loan = loan;
    this.showLoanModal = true;
    this.initLoanForm();
    this.setCurrencyPrefix();

    if (this.loan) {
      this.loanForm.patchValue(this.loan);
    } else {
      this.loanForm.get('issued_at').patchValue(moment());
    }

    this.modalSubject = new Subject<Loan>();
    this.renderer.addClass(this._document.body, 'modal-opened');
    return this.modalSubject;
  }

  public dismissModal(): void {
    this.modalSubject.complete();
    this.showLoanModal = false;
    this.renderer.removeClass(this._document.body, 'modal-opened');
  }

  public submitLoanForm(): void {
    if (this.loanForm.valid) {
      const formData: Loan = this.loanForm.getRawValue();
      formData.issued_at = moment(formData.issued_at).format('YYYY-MM-DD');

      if (this.loan) {
        formData.id = this.loan.id;
      }

      this.closeModal(formData);
    }
  }

  private closeModal(value?: Loan): void {
    this.modalSubject.next(value);
    this.modalSubject.complete();
    this.showLoanModal = false;
    this.renderer.removeClass(this._document.body, 'modal-opened');
  }

  private initLoanForm(): void {
    this.loanForm = this.fb.group({
      description: new FormControl(undefined),
      amount: new FormControl(undefined, [
        Validators.required,
        Validators.min(1),
        Validators.max(1000000000),
        Validators.pattern(currencyRegEx),
      ]),
      issued_at: new FormControl(undefined, Validators.required),
    });
  }

  private setCurrencyPrefix(): void {
    const currencyCode = this.globalService.userCurrency;

    this.currencyPrefix =
      getCurrencySymbol(
        this.enumService.getEnumMap('currencycode').get(currencyCode),
        'wide'
      ) + ' ';
  }
}
