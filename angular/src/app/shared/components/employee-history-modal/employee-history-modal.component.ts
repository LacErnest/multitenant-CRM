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
import { DOCUMENT, getCurrencySymbol } from '@angular/common';
import { Component, Inject, OnInit, Renderer2 } from '@angular/core';
import {
  FormBuilder,
  FormControl,
  FormGroup,
  Validators,
} from '@angular/forms';

import { Subject } from 'rxjs';

import { EnumService } from 'src/app/core/services/enum.service';
import { GlobalService } from 'src/app/core/services/global.service';
import {
  displayAnimation,
  errorEnterMessageAnimation,
  errorLeaveMessageAnimation,
} from 'src/app/shared/animations/browser-animations';
import {
  currencyRegEx,
  positiveFloatRegEx,
} from 'src/app/shared/constants/regex';
import { CurrencyPrefix } from 'src/app/shared/enums/currency-prefix.enum';
import { UserRole } from 'src/app/shared/enums/user-role.enum';
import { environment } from 'src/environments/environment';
import { EmployeeHistory } from '../../../views/employees/interfaces/employee-history';
import { dateBeforeValidator } from '../../validators/date-before.validator';

@Component({
  selector: 'oz-finance-employee-history-modal',
  templateUrl: './employee-history-modal.component.html',
  styleUrls: ['./employee-history-modal.component.scss'],
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
    trigger('displayAnimation', [
      transition(':enter', useAnimation(displayAnimation)),
    ]),
    trigger('errorMessageAnimation', [
      transition(':enter', useAnimation(errorEnterMessageAnimation)),
      transition(':leave', useAnimation(errorLeaveMessageAnimation)),
    ]),
  ],
})
export class EmployeeHistoryModalComponent implements OnInit {
  public currencyPrefix: string = CurrencyPrefix.USD;
  public history: EmployeeHistory;
  public showHistoryModal = false;
  public historyForm: FormGroup;

  private modalSubject: Subject<EmployeeHistory>;
  private employeeCurrency: number;

  public constructor(
    @Inject(DOCUMENT) private _document,
    private fb: FormBuilder,
    private globalService: GlobalService,
    private enumService: EnumService,
    private renderer: Renderer2
  ) {}

  public ngOnInit(): void {}

  public submitHistoryForm(): void {
    if (this.historyForm.valid) {
      const val = { ...this.history, ...this.historyForm.getRawValue() };
      this.closeModal(val);
    }
  }

  public openModal(
    history: EmployeeHistory,
    currency: number
  ): Subject<EmployeeHistory> {
    this.history = history;
    this.employeeCurrency = currency;

    this.initHistoryForm();
    this.patchValueHistoryForm();
    this.setCurrencyPrefix();

    this.showHistoryModal = true;

    this.modalSubject = new Subject<EmployeeHistory>();
    this.renderer.addClass(this._document.body, 'modal-opened');
    return this.modalSubject;
  }

  public dismissModal(): void {
    this.modalSubject.complete();
    this.showHistoryModal = false;
    this.renderer.removeClass(this._document.body, 'modal-opened');
  }

  private closeModal(value?: EmployeeHistory): void {
    this.modalSubject.next(value);
    this.modalSubject.complete();
    this.showHistoryModal = false;
    this.renderer.removeClass(this._document.body, 'modal-opened');
  }

  private initHistoryForm(): void {
    this.historyForm = this.fb.group(
      {
        start_date: new FormControl(undefined, [Validators.required]),
        end_date: new FormControl(undefined),
        employee_salary: new FormControl(undefined, [
          Validators.required,
          Validators.min(0),
          Validators.pattern(currencyRegEx),
        ]),
        working_hours: new FormControl(undefined, [
          Validators.required,
          Validators.pattern(positiveFloatRegEx),
          Validators.min(0),
        ]),
      },
      { validators: [dateBeforeValidator('start_date', 'end_date')] }
    );
  }

  private patchValueHistoryForm(): void {
    if (this.history) {
      this.historyForm.patchValue(this.history);
    }
  }

  private setCurrencyPrefix(): void {
    const currencyCode = this.employeeCurrency;

    this.currencyPrefix =
      getCurrencySymbol(
        this.enumService.getEnumMap('currencycode').get(currencyCode),
        'wide'
      ) + ' ';
  }
}
