import { Component, Inject, OnInit, Renderer2 } from '@angular/core';
import {
  FormBuilder,
  FormControl,
  FormGroup,
  Validators,
} from '@angular/forms';
import { Subject } from 'rxjs';
import { EntityPenalty } from '../../classes/entity-item/entity-penalty';
import {
  animate,
  animateChild,
  group,
  query,
  style,
  transition,
  trigger,
} from '@angular/animations';
import { DOCUMENT } from '@angular/common';

@Component({
  selector: 'oz-finance-entity-penalty-modal',
  templateUrl: './entity-penalty-modal.component.html',
  styleUrls: ['./entity-penalty-modal.component.scss'],
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
export class EntityPenaltyModalComponent implements OnInit {
  showEntityPenaltyModal = false;
  penaltyForm: FormGroup;
  penalty: EntityPenalty;
  amountOptions = [
    { key: 10, value: '10%' },
    { key: 20, value: '20%' },
    { key: 50, value: '50%' },
  ];
  currencyPrefix: string;
  private modalSubject: Subject<any>;

  constructor(
    @Inject(DOCUMENT) private _document,
    private fb: FormBuilder,
    private renderer: Renderer2
  ) {}

  ngOnInit(): void {}

  submitPenaltyForm() {
    if (this.penaltyForm.valid) {
      this.closeModal(new EntityPenalty(this.penaltyForm.getRawValue()));
    }
  }

  openModal(penalty?: EntityPenalty): Subject<any> {
    this.penalty = penalty;
    this.initPenaltyForm();
    this.patchValuePenaltyForm();
    this.showEntityPenaltyModal = true;
    this.modalSubject = new Subject<any>();
    this.renderer.addClass(this._document.body, 'modal-opened');
    return this.modalSubject;
  }

  closeModal(value?: any) {
    this.modalSubject.next(value);
    this.modalSubject.complete();
    this.showEntityPenaltyModal = false;
    this.renderer.removeClass(this._document.body, 'modal-opened');
  }

  dismissModal() {
    this.modalSubject.complete();
    this.showEntityPenaltyModal = false;
    this.renderer.removeClass(this._document.body, 'modal-opened');
  }

  private initPenaltyForm() {
    this.penaltyForm = this.fb.group({
      amount: new FormControl(10, Validators.required),
      reason: new FormControl(undefined, Validators.required),
    });
  }

  private patchValuePenaltyForm() {
    if (this.penalty) {
      this.penaltyForm.patchValue(this.penalty);
    }
  }
}
