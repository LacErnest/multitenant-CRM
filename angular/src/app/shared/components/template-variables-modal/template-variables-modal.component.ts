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
import { DOCUMENT } from '@angular/common';
import { Component, Inject, Input, OnInit, Renderer2 } from '@angular/core';
import { Subject } from 'rxjs';
import {
  displayAnimation,
  errorEnterMessageAnimation,
  errorLeaveMessageAnimation,
} from 'src/app/shared/animations/browser-animations';
import { TemplateField } from 'src/app/shared/interfaces/template';

@Component({
  selector: 'oz-finance-template-variables-modal',
  templateUrl: './template-variables-modal.component.html',
  styleUrls: ['./template-variables-modal.component.scss'],
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
export class TemplateVariablesModalComponent implements OnInit {
  // TODO: refactor
  @Input() fields: TemplateField[] = [];

  showTemplateVariablesModal = false;
  title: string;

  private modalSubject: Subject<any>;

  constructor(
    @Inject(DOCUMENT) private _document,
    private renderer: Renderer2
  ) {}

  ngOnInit(): void {}

  ngOnDestroy() {
    if (this.showTemplateVariablesModal) {
      this.closeModal();
    }
  }

  public openModal(title?: string): Subject<any> {
    this.title = title;
    this.renderer.addClass(this._document.body, 'modal-opened');
    this.showTemplateVariablesModal = true;
    this.modalSubject = new Subject<any>();
    return this.modalSubject;
  }

  dismissModal() {
    this.modalSubject.complete();
    this.showTemplateVariablesModal = false;
    this.renderer.removeClass(this._document.body, 'modal-opened');
  }

  closeModal(value?: any) {
    this.modalSubject.next(value);
    this.modalSubject.complete();
    this.showTemplateVariablesModal = false;
    this.renderer.removeClass(this._document.body, 'modal-opened');
  }
}
