import { Component, Inject, Input, OnInit, Renderer2 } from '@angular/core';
import {
  animate,
  animateChild,
  group,
  query,
  style,
  transition,
  trigger,
} from '@angular/animations';
import { Subject } from 'rxjs';
import { DOCUMENT } from '@angular/common';

@Component({
  selector: 'oz-finance-confirm-modal',
  templateUrl: './confirm-modal.component.html',
  styleUrls: ['./confirm-modal.component.scss'],
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
  ],
})
export class ConfirmModalComponent implements OnInit {
  showConfirmModal = false;
  title = 'Confirm';
  message = 'Are you sure?';
  showContinue = false;
  @Input() yesLabel = 'Yes';
  @Input() noLabel = 'No';
  @Input() cancelLabel = 'Cancel';

  private modalSubject: Subject<any>;

  constructor(
    @Inject(DOCUMENT) private _document,
    private renderer: Renderer2
  ) {}

  ngOnInit(): void {}

  public openModal(
    title?: string,
    message?: string,
    showContinue?: boolean
  ): Subject<any> {
    this.renderer.addClass(this._document.body, 'modal-opened');
    this.title = title;
    this.message = message;
    this.showConfirmModal = true;
    this.showContinue = showContinue;
    this.modalSubject = new Subject<any>();
    return this.modalSubject;
  }

  public closeModal(value: any): void {
    this.modalSubject.next(value);
    this.modalSubject.complete();
    this.showConfirmModal = false;
    this.renderer.removeClass(this._document.body, 'modal-opened');
  }
}
