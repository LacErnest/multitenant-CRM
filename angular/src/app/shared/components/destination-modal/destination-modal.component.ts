import { Component, Inject, OnInit, Renderer2 } from '@angular/core';
import { concat, Observable, of, Subject } from 'rxjs';
import {
  catchError,
  debounceTime,
  distinctUntilChanged,
  switchMap,
  tap,
} from 'rxjs/operators';
import { HttpParams } from '@angular/common/http';
import { SuggestService } from '../../services/suggest.service';
import { DOCUMENT } from '@angular/common';
import { transition, trigger, useAnimation } from '@angular/animations';
import {
  modalBackdropEnterAnimation,
  modalBackdropLeaveAnimation,
  modalContainerEnterAnimation,
  modalContainerLeaveAnimation,
  modalEnterAnimation,
  modalLeaveAnimation,
} from '../../animations/browser-animations';

@Component({
  selector: 'oz-finance-destination-modal',
  templateUrl: './destination-modal.component.html',
  styleUrls: ['./destination-modal.component.scss'],
  animations: [
    trigger('modalContainerAnimation', [
      transition(':enter', useAnimation(modalContainerEnterAnimation)),
      transition(':leave', useAnimation(modalContainerLeaveAnimation)),
    ]),
    trigger('modalBackdropAnimation', [
      transition(':enter', useAnimation(modalBackdropEnterAnimation)),
      transition(':leave', useAnimation(modalBackdropLeaveAnimation)),
    ]),
    trigger('modalAnimation', [
      transition(':enter', useAnimation(modalEnterAnimation)),
      transition(':leave', useAnimation(modalLeaveAnimation)),
    ]),
  ],
})
export class DestinationModalComponent implements OnInit {
  showDestinationModal = false;
  selectedOrder: any = null;
  selectedOption: string;
  showOrderSelect = false;
  isOrderLoading = false;
  orderSelect: Observable<any[]>;
  orderInput: Subject<string> = new Subject<string>();
  orderDefault: any[];
  cloneOptions: any[];
  private modalSubject: Subject<any>;

  constructor(
    @Inject(DOCUMENT) private _document,
    private suggestService: SuggestService,
    private renderer: Renderer2
  ) {}

  ngOnInit(): void {}

  public openModal(isQuoteList: boolean): Subject<any> {
    this.showDestinationModal = true;
    this.initOrderTypeAhead();
    this.cloneOptions = isQuoteList
      ? ['New order', 'Same order', 'Other order']
      : ['Same order', 'Other order'];
    this.setDefaultOption();
    this.modalSubject = new Subject<any>();
    this.renderer.addClass(this._document.body, 'modal-opened');
    return this.modalSubject;
  }

  optionChanged(event) {
    switch (event) {
      case 'New order':
        this.showOrderSelect = false;
        this.selectedOrder = null;
        break;
      case 'Same order':
        this.showOrderSelect = false;
        this.selectedOrder = 'current';
        break;
      case 'Other order':
        this.showOrderSelect = true;
        this.selectedOrder = undefined;
        break;
    }
  }

  closeModal(value?: any) {
    this.modalSubject.next(value);
    this.modalSubject.complete();
    this.showDestinationModal = false;
    this.setDefaultOption();
    this.renderer.removeClass(this._document.body, 'modal-opened');
  }

  dismissModal() {
    this.modalSubject.complete();
    this.showDestinationModal = false;
    this.setDefaultOption();
    this.renderer.removeClass(this._document.body, 'modal-opened');
  }

  submit() {
    if (this.selectedOrder !== undefined) {
      this.closeModal(this.selectedOrder);
    }
  }

  private setDefaultOption() {
    this.selectedOption = this.cloneOptions[0];
    this.optionChanged(this.selectedOption);
  }

  private initOrderTypeAhead() {
    this.orderSelect = concat(
      of(this.orderDefault), // default items
      this.orderInput.pipe(
        debounceTime(500),
        distinctUntilChanged(),
        tap(() => {
          this.isOrderLoading = true;
        }),
        switchMap(term =>
          this.suggestService.suggestOrder(term, new HttpParams()).pipe(
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
