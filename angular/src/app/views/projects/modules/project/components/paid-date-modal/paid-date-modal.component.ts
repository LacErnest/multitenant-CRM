import { Component, Inject, OnInit, Renderer2 } from '@angular/core';
import {
  FormBuilder,
  FormControl,
  FormGroup,
  Validators,
} from '@angular/forms';
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
import { Subject } from 'rxjs';
import {
  errorEnterMessageAnimation,
  errorLeaveMessageAnimation,
  modalBackdropEnterAnimation,
  modalBackdropLeaveAnimation,
  modalContainerEnterAnimation,
  modalContainerLeaveAnimation,
  modalEnterAnimation,
  modalLeaveAnimation,
} from '../../../../../../shared/animations/browser-animations';
import { DOCUMENT } from '@angular/common';

@Component({
  selector: 'oz-finance-paid-date-modal',
  templateUrl: './paid-date-modal.component.html',
  styleUrls: ['./paid-date-modal.component.scss'],
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
    trigger('errorMessageAnimation', [
      transition(':enter', useAnimation(errorEnterMessageAnimation)),
      transition(':leave', useAnimation(errorLeaveMessageAnimation)),
    ]),
  ],
})
export class PaidDateModalComponent implements OnInit {
  showConfirmModal = false;
  dateForm: FormGroup;
  maxDate: any;
  private modalSubject: Subject<any>;

  constructor(
    @Inject(DOCUMENT) private _document,
    private fb: FormBuilder,
    private renderer: Renderer2
  ) {}

  ngOnInit(): void {}

  public openModal(maxDate): Subject<any> {
    this.maxDate = maxDate;
    this.initDateForm();
    this.showConfirmModal = true;
    this.modalSubject = new Subject<any>();
    this.renderer.addClass(this._document.body, 'modal-opened');
    return this.modalSubject;
  }

  closeModal(value?: any) {
    this.modalSubject.next(value);
    this.modalSubject.complete();
    this.showConfirmModal = false;
    this.renderer.removeClass(this._document.body, 'modal-opened');
  }

  dismissModal() {
    this.modalSubject.complete();
    this.showConfirmModal = false;
    this.renderer.removeClass(this._document.body, 'modal-opened');
  }

  submit() {
    if (this.dateForm.valid) {
      this.closeModal(this.dateForm.getRawValue());
    }
  }

  private initDateForm() {
    this.dateForm = this.fb.group({
      pay_date: new FormControl(undefined, Validators.required),
    });
  }
}
