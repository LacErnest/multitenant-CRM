import { Component, Inject, OnInit, Renderer2 } from '@angular/core';
import { concat, Observable, of, Subject } from 'rxjs';
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
import { positiveFloatRegEx } from '../../../../../../shared/constants/regex';
import { HttpParams } from '@angular/common/http';
import {
  catchError,
  debounceTime,
  distinctUntilChanged,
  switchMap,
  tap,
} from 'rxjs/operators';
import { SuggestService } from '../../../../../../shared/services/suggest.service';
import { Helpers } from '../../../../../../core/classes/helpers';
import {
  errorEnterMessageAnimation,
  errorLeaveMessageAnimation,
} from '../../../../../../shared/animations/browser-animations';
import { DOCUMENT } from '@angular/common';
import moment from 'moment';

@Component({
  selector: 'oz-finance-project-employee-modal',
  templateUrl: './project-employee-modal.component.html',
  styleUrls: ['./project-employee-modal.component.scss'],
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
export class ProjectEmployeeModalComponent implements OnInit {
  isEmployeeLoading = false;

  showProjectEmployeeModal = false;
  projectEmployeeForm: FormGroup;
  employee: any;

  selectedEmployee: string;
  employeeSelect: Observable<any[]> = new Observable<any[]>();
  employeeInput: Subject<string> = new Subject<string>();

  public readOnly = false;
  public months = [
    'January',
    'February',
    'March',
    'April',
    'May',
    'June',
    'July',
    'August',
    'September',
    'October',
    'November',
    'December',
  ];
  public years = [];
  public currentYear = moment.utc().year();
  public today = new Date();
  public currentMonth = this.today.toLocaleString('default', { month: 'long' });

  private modalSubject: Subject<any>;

  constructor(
    @Inject(DOCUMENT) private _document,
    private fb: FormBuilder,
    private suggestService: SuggestService,
    private renderer: Renderer2
  ) {}

  public get cannotSubmit(): boolean {
    return (
      this.projectEmployeeForm.invalid ||
      !this.projectEmployeeForm.dirty ||
      !this.selectedEmployee
    );
  }

  ngOnInit(): void {
    this.fillYears();
    this.initEmployeeTypeAhead();
  }

  public openModal(employee?: any, month?: any): Subject<any> {
    this.employee = employee;
    this.selectedEmployee = employee?.id;

    this.initProjectEmployeeForm();
    this.patchValueProjectEmployeeForm(month);

    this.showProjectEmployeeModal = true;
    this.modalSubject = new Subject<any>();
    this.renderer.addClass(this._document.body, 'modal-opened');
    return this.modalSubject;
  }

  submit(): void {
    if (this.projectEmployeeForm.valid) {
      this.closeModal({
        hours: this.projectEmployeeForm.get('hours').value,
        employee_id: this.selectedEmployee,
        month: this.projectEmployeeForm.get('month').value,
        year: this.projectEmployeeForm.get('year').value,
      });
    }
  }

  closeModal(value?: any): void {
    this.modalSubject.next(value);
    this.modalSubject.complete();
    this.showProjectEmployeeModal = false;
    this.readOnly = false;
    this.renderer.removeClass(this._document.body, 'modal-opened');
  }

  dismissModal(): void {
    this.modalSubject.complete();
    this.showProjectEmployeeModal = false;
    this.readOnly = false;
    this.renderer.removeClass(this._document.body, 'modal-opened');
  }

  private initProjectEmployeeForm(): void {
    this.projectEmployeeForm = this.fb.group({
      hours: new FormControl(undefined, [
        Validators.required,
        Validators.pattern(positiveFloatRegEx),
        Validators.min(0),
      ]),
      year: new FormControl(this.currentYear),
      month: new FormControl(this.currentMonth),
    });
  }

  private patchValueProjectEmployeeForm(month?: any): void {
    if (month) {
      this.projectEmployeeForm.patchValue(this.employee);
      this.projectEmployeeForm.get('hours').patchValue(month.hours);
      this.projectEmployeeForm.get('month').patchValue(month.month);
      this.projectEmployeeForm.get('year').patchValue(month.year);
      if (month.month !== null) {
        this.readOnly = true;
      }
    }
  }

  private initEmployeeTypeAhead(): void {
    let params = new HttpParams();
    params = Helpers.setParam(params, 'status', '0');
    this.employeeSelect = concat(
      of([]), // default items
      this.employeeInput.pipe(
        debounceTime(500),
        distinctUntilChanged(),
        tap(() => {
          this.isEmployeeLoading = true;
        }),
        switchMap(term =>
          this.suggestService.suggestEmployees(term, params).pipe(
            catchError(() => of([])), // empty list on error
            tap(() => {
              this.isEmployeeLoading = false;
            })
          )
        )
      )
    );
  }

  private fillYears(): void {
    this.years = [];
    for (let i = this.currentYear + 1; i >= Number(this.currentYear) - 2; i--) {
      this.years.push(i.toString());
    }
  }
}
