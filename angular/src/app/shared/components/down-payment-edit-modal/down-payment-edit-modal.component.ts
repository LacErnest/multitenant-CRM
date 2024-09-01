import { Component, Inject, OnInit, Renderer2 } from '@angular/core';
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
import {
  FormBuilder,
  FormControl,
  FormGroup,
  Validators,
} from '@angular/forms';
import { DOCUMENT } from '@angular/common';
import { ActivatedRoute } from '@angular/router';

@Component({
  selector: 'oz-finance-down-payment-edit-modal',
  templateUrl: './down-payment-edit-modal.component.html',
  styleUrls: ['./down-payment-edit-modal.component.scss'],
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
export class DownPaymentEditModalComponent implements OnInit {
  showDownPaymentEditModal = false;
  percentageForm: FormGroup;

  private modalSubject: Subject<any>;
  public settings: any;
  public down_payment;

  constructor(
    private fb: FormBuilder,
    protected route: ActivatedRoute,
    @Inject(DOCUMENT) private _document,
    private renderer: Renderer2
  ) {}

  ngOnInit(): void {
    this.getResolvedData();
  }

  private getResolvedData(): void {
    this.settings = this.route.snapshot.data.settings;
  }

  submit() {
    if (this.percentageForm.valid) {
      this.closeModal(this.percentageForm.controls.percentage.value);
    }
  }

  public openModal(down_payment?: number): Subject<any> {
    this.down_payment = down_payment;
    this.initPercentageForm();
    this.showDownPaymentEditModal = true;
    this.modalSubject = new Subject<any>();
    this.renderer.addClass(this._document.body, 'modal-opened');
    return this.modalSubject;
  }

  closeModal(value?: any) {
    this.modalSubject.next(value);
    this.modalSubject.complete();
    this.showDownPaymentEditModal = false;
    this.renderer.removeClass(this._document.body, 'modal-opened');
  }

  dismissModal() {
    this.modalSubject.complete();
    this.showDownPaymentEditModal = false;
    this.renderer.removeClass(this._document.body, 'modal-opened');
  }

  private initPercentageForm() {
    this.percentageForm = this.fb.group({
      percentage: new FormControl(this.down_payment, [
        Validators.min(1),
        Validators.max(100),
        Validators.required,
      ]),
    });
  }
}
