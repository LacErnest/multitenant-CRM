import { Component, Inject, OnInit, Renderer2 } from '@angular/core';
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
  errorEnterMessageAnimation,
  errorLeaveMessageAnimation,
} from '../../../../../../shared/animations/browser-animations';
import {
  FormBuilder,
  FormControl,
  FormGroup,
  Validators,
} from '@angular/forms';
import { concat, Observable, of, Subject } from 'rxjs';
import { SuggestService } from '../../../../../../shared/services/suggest.service';
import { numberOnlyRegEx } from '../../../../../../shared/constants/regex';
import { HttpParams } from '@angular/common/http';
import { Helpers } from '../../../../../../core/classes/helpers';
import {
  catchError,
  debounceTime,
  distinctUntilChanged,
  switchMap,
  tap,
} from 'rxjs/operators';
import { DOCUMENT } from '@angular/common';

@Component({
  selector: 'oz-finance-quote-clone-modal',
  templateUrl: './quote-clone-modal.component.html',
  styleUrls: ['./quote-clone-modal.component.scss'],
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
export class QuoteCloneModalComponent implements OnInit {
  isOrderLoading = false;

  showCloneModal = false;
  cloneForm: FormGroup;
  order: any;

  selectedOrder: string;
  orderSelect: Observable<any[]> = new Observable<any[]>();
  orderInput: Subject<string> = new Subject<string>();

  private modalSubject: Subject<any>;

  constructor(
    @Inject(DOCUMENT) private _document,
    private fb: FormBuilder,
    private suggestService: SuggestService,
    private renderer: Renderer2
  ) {}

  ngOnInit(): void {
    this.initEmployeeTypeAhead();
  }

  public openModal(): Subject<any> {
    this.selectedOrder = undefined;
    this.showCloneModal = true;
    this.modalSubject = new Subject<any>();
    this.renderer.addClass(this._document.body, 'modal-opened');
    return this.modalSubject;
  }

  submit() {
    if (this.selectedOrder) {
      this.closeModal(this.selectedOrder);
    }
  }

  closeModal(value?: any) {
    this.modalSubject.next(value);
    this.modalSubject.complete();
    this.showCloneModal = false;
    this.renderer.removeClass(this._document.body, 'modal-opened');
  }

  dismissModal() {
    this.modalSubject.complete();
    this.showCloneModal = false;
    this.renderer.removeClass(this._document.body, 'modal-opened');
  }

  private initEmployeeTypeAhead() {
    let params = new HttpParams();
    params = Helpers.setParam(params, 'status', '[0, 1]');
    this.orderSelect = concat(
      of([]), // default items
      this.orderInput.pipe(
        debounceTime(500),
        distinctUntilChanged(),
        tap(() => {
          this.isOrderLoading = true;
        }),
        switchMap(term =>
          this.suggestService.suggestEmployees(term, params).pipe(
            catchError(() => of([])), // empty list on error
            tap(() => {
              this.isOrderLoading = false;
            })
          )
        )
      )
    );
  }
}
