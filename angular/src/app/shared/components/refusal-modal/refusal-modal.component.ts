import { Component, Inject, OnInit, Renderer2 } from '@angular/core';
import {
  FormBuilder,
  FormControl,
  FormGroup,
  Validators,
} from '@angular/forms';
import { Subject } from 'rxjs';
import { transition, trigger, useAnimation } from '@angular/animations';
import {
  errorEnterMessageAnimation,
  errorLeaveMessageAnimation,
  modalBackdropEnterAnimation,
  modalBackdropLeaveAnimation,
  modalContainerEnterAnimation,
  modalContainerLeaveAnimation,
  modalEnterAnimation,
  modalLeaveAnimation,
} from '../../animations/browser-animations';
import { DOCUMENT } from '@angular/common';

@Component({
  selector: 'oz-finance-refusal-modal',
  templateUrl: './refusal-modal.component.html',
  styleUrls: ['./refusal-modal.component.scss'],
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
export class RefusalModalComponent implements OnInit {
  showConfirmModal = false;
  reasonForm: FormGroup;
  private modalSubject: Subject<any>;

  constructor(
    @Inject(DOCUMENT) private _document,
    private fb: FormBuilder,
    private renderer: Renderer2
  ) {}

  ngOnInit(): void {}

  public openModal(): Subject<any> {
    this.initReasonForm();
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
    if (this.reasonForm.valid) {
      this.closeModal(this.reasonForm.getRawValue());
    }
  }

  private initReasonForm() {
    this.reasonForm = this.fb.group({
      reason_of_refusal: new FormControl(undefined, Validators.required),
    });
  }
}
