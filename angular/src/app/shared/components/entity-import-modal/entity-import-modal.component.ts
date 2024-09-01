import {
  Component,
  EventEmitter,
  Inject,
  Input,
  OnDestroy,
  OnInit,
  Output,
  Renderer2,
} from '@angular/core';
import { Subject } from 'rxjs';
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
  displayAnimation,
  errorEnterMessageAnimation,
  errorLeaveMessageAnimation,
} from '../../animations/browser-animations';
import { DOCUMENT } from '@angular/common';

@Component({
  selector: 'oz-finance-entity-import-modal',
  templateUrl: './entity-import-modal.component.html',
  styleUrls: ['./entity-import-modal.component.scss'],
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
export class EntityImportModalComponent implements OnInit, OnDestroy {
  @Input() isLoading = false;
  @Input() columns = [];
  @Input() properties = [];
  @Output() finalizeImportClicked: EventEmitter<any> = new EventEmitter<any>();

  matches = [];
  modalSubject: Subject<any>;
  showEntityImportModal = false;
  title: string;

  constructor(
    @Inject(DOCUMENT) private _document,
    private renderer: Renderer2
  ) {}

  ngOnInit(): void {}

  ngOnDestroy() {
    if (this.showEntityImportModal) {
      this.closeModal();
    }
  }

  public openModal(title?: string): Subject<any> {
    this.title = title;
    this.renderer.addClass(this._document.body, 'modal-opened');
    this.showEntityImportModal = true;
    this.modalSubject = new Subject<any>();
    return this.modalSubject;
  }

  dismissModal() {
    this.modalSubject.complete();
    this.showEntityImportModal = false;
    this.matches = [];
    this.renderer.removeClass(this._document.body, 'modal-opened');
    (<HTMLInputElement>this._document.getElementById('upload_file')).value = '';
  }

  closeModal(value?: any) {
    this.modalSubject.next(value);
    this.modalSubject.complete();
    this.showEntityImportModal = false;
    this.matches = [];
    this.renderer.removeClass(this._document.body, 'modal-opened');
    (<HTMLInputElement>this._document.getElementById('upload_file')).value = '';
  }

  finalizeImport(): void {
    if (!this.matches.length) {
      return;
    }
    this.finalizeImportClicked.emit(this.matches);
    this.matches = [];
  }

  onChange(chosenProperty: string, column: string): void {
    const index = this.matches.findIndex(
      obj => obj['property'] === chosenProperty
    );
    if (index >= 0) {
      this.matches[index] = { property: chosenProperty, column };
      return;
    }

    this.matches.push({ property: chosenProperty, column });
  }
}
