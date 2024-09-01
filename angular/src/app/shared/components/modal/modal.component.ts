import {
  trigger,
  transition,
  group,
  query,
  animateChild,
  useAnimation,
} from '@angular/animations';
import { DOCUMENT } from '@angular/common';
import {
  Component,
  Inject,
  Input,
  Renderer2,
  TemplateRef,
} from '@angular/core';
import { Subject } from 'rxjs';
import {
  errorEnterMessageAnimation,
  errorLeaveMessageAnimation,
  modalBackdropEnterAnimation,
  modalBackdropLeaveAnimation,
  modalEnterAnimation,
  modalLeaveAnimation,
} from 'src/app/shared/animations/browser-animations';

@Component({
  selector: 'oz-finance-modal',
  templateUrl: './modal.component.html',
  styleUrls: ['./modal.component.scss'],
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
export class ModalComponent {
  @Input() public htmlTemplate: TemplateRef<any>;
  @Input() public isFromDisabled: boolean;
  @Input() public modalClass: string; // TODO: check styles
  @Input() public modalHeading: string;
  @Input() public showCancelBtn = true;
  @Input() public submitBtnText: string;

  public showModal = false;

  private modalSubject: Subject<void>;

  public constructor(
    @Inject(DOCUMENT) private _document: Document,
    private renderer: Renderer2
  ) {}

  public openModal(): Subject<void> {
    this.showModal = true;
    this.modalSubject = new Subject<void>();
    this.renderer.addClass(this._document.body, 'modal-opened');

    return this.modalSubject;
  }

  public dismissModal(): void {
    this.modalSubject.complete();
    this.showModal = false;
    this.renderer.removeClass(this._document.body, 'modal-opened');
  }

  public closeModal(): void {
    this.modalSubject?.next();
    this.modalSubject?.complete();
    this.showModal = false;
    this.renderer.removeClass(this._document.body, 'modal-opened');
  }
}
