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
  selector: 'oz-finance-deadline-modal',
  templateUrl: './deadline-modal.component.html',
  styleUrls: ['./deadline-modal.component.scss'],
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
export class DeadlineModalComponent implements OnInit {
  showDeadlineModal = false;
  deadlineForm: FormGroup;
  minDate: any;
  private modalSubject: Subject<any>;

  constructor(
    private fb: FormBuilder,
    @Inject(DOCUMENT) private _document,
    private renderer: Renderer2
  ) {}

  ngOnInit(): void {}

  public openModal(minDate: any): Subject<any> {
    this.renderer.addClass(this._document.body, 'modal-opened');
    this.minDate = minDate;
    this.initReasonForm();
    this.showDeadlineModal = true;
    this.modalSubject = new Subject<any>();
    return this.modalSubject;
  }

  closeModal(value?: any) {
    this.modalSubject.next(value);
    this.modalSubject.complete();
    this.showDeadlineModal = false;
    this.renderer.removeClass(this._document.body, 'modal-opened');
  }

  dismissModal() {
    this.modalSubject.complete();
    this.showDeadlineModal = false;
    this.renderer.removeClass(this._document.body, 'modal-opened');
  }

  submit() {
    if (this.deadlineForm.valid) {
      this.closeModal(this.deadlineForm.getRawValue());
    }
  }

  private initReasonForm() {
    this.deadlineForm = this.fb.group({
      deadline: new FormControl(undefined, Validators.required),
    });
  }
}
