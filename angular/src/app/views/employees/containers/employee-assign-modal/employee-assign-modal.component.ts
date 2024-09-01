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
import { HttpParams } from '@angular/common/http';
import {
  catchError,
  debounceTime,
  distinctUntilChanged,
  switchMap,
  tap,
} from 'rxjs/operators';
import { DOCUMENT } from '@angular/common';
import {
  errorEnterMessageAnimation,
  errorLeaveMessageAnimation,
} from '../../../../shared/animations/browser-animations';
import { positiveFloatRegEx } from '../../../../shared/constants/regex';
import { Helpers } from '../../../../core/classes/helpers';
import { SuggestService } from '../../../../shared/services/suggest.service';

@Component({
  selector: 'oz-finance-employee-assign-modal',
  templateUrl: './employee-assign-modal.component.html',
  styleUrls: ['./employee-assign-modal.component.scss'],
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
export class EmployeeAssignModalComponent implements OnInit {
  public isEmployeeLoading = false;
  public showEmployeeAssignModal = false;
  public employee: any;
  public order: any;
  public employeeAssignForm: FormGroup;

  public selectedEmployee: string;
  public selectedOrder: string;
  public orderSelect: Observable<any[]> = new Observable<any[]>();
  public orderInput: Subject<string> = new Subject<string>();

  private modalSubject: Subject<any>;

  constructor(
    private fb: FormBuilder,
    private renderer: Renderer2,
    private suggestService: SuggestService,
    @Inject(DOCUMENT) private _document
  ) {}

  ngOnInit(): void {
    this.initOrderTypeAhead();
  }

  public openModal(employee: any, order?: any): Subject<any> {
    this.employee = employee;
    this.selectedEmployee = employee.id;
    this.order = order;
    this.selectedOrder = order?.order_id;
    this.initEmployeeAssignForm();
    this.patchValueEmployeeAssignForm();
    this.showEmployeeAssignModal = true;
    this.modalSubject = new Subject<any>();
    this.renderer.addClass(this._document.body, 'modal-opened');
    return this.modalSubject;
  }

  closeModal(value?: any) {
    this.modalSubject.next(value);
    this.modalSubject.complete();
    this.showEmployeeAssignModal = false;
    this.renderer.removeClass(this._document.body, 'modal-opened');
  }

  dismissModal() {
    this.modalSubject.complete();
    this.showEmployeeAssignModal = false;
    this.renderer.removeClass(this._document.body, 'modal-opened');
  }

  public submit() {
    if (this.employeeAssignForm.valid) {
      this.closeModal({
        hours: this.employeeAssignForm.get('hours').value,
        order_id: this.selectedOrder,
        employee_id: this.selectedEmployee,
      });
    }
  }

  public cannotSubmit(): boolean {
    return (
      this.employeeAssignForm.invalid ||
      !this.employeeAssignForm.dirty ||
      !this.selectedOrder
    );
  }

  private initOrderTypeAhead() {
    let params = new HttpParams();
    params = Helpers.setParam(params, 'status', 'all');
    this.orderSelect = concat(
      of([]), // default items
      this.orderInput.pipe(
        debounceTime(500),
        distinctUntilChanged(),
        tap(() => {
          this.isEmployeeLoading = true;
        }),
        switchMap(term =>
          this.suggestService.suggestOrder(term, params).pipe(
            catchError(() => of([])), // empty list on error
            tap(() => {
              this.isEmployeeLoading = false;
            })
          )
        )
      )
    );
  }

  private initEmployeeAssignForm(): void {
    this.employeeAssignForm = this.fb.group({
      hours: new FormControl(undefined, [
        Validators.required,
        Validators.pattern(positiveFloatRegEx),
        Validators.min(1),
      ]),
    });
  }

  private patchValueEmployeeAssignForm() {
    if (this.order) {
      this.employeeAssignForm.patchValue(this.order);
    }
  }
}
