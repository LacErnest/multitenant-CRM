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
import {
  FormBuilder,
  FormControl,
  FormGroup,
  Validators,
} from '@angular/forms';
import { EntityPenalty } from '../../classes/entity-item/entity-penalty';
import { AllOrNothingRequiredValidator } from '../../validators/all-or-nothing-required.validator';
import { ConditionalRequiredValidator } from '../../validators/conditional-required.validator';
import { DOCUMENT, getCurrencySymbol } from '@angular/common';
import { Helpers } from '../../../core/classes/helpers';
import { EntityPenaltyTypeEnum } from 'src/app/shared/enums/entity-penalty-type.enum';
import { CurrencyPrefix } from '../../enums/currency-prefix.enum';
import { UserRole } from '../../enums/user-role.enum';
import { GlobalService } from 'src/app/core/services/global.service';
import { environment } from 'src/environments/environment';
import { EnumService } from 'src/app/core/services/enum.service';

@Component({
  selector: 'oz-finance-rating-modal',
  templateUrl: './rating-modal.component.html',
  styleUrls: ['./rating-modal.component.scss'],
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
export class RatingModalComponent implements OnInit {
  showRatingModal = false;
  rating = 3;
  reasonForm: FormGroup;
  penaltyForm: FormGroup;
  penalty: EntityPenalty;
  penaltyType: EntityPenaltyTypeEnum;
  amountOptions = [
    { key: 10, value: '10%' },
    { key: 20, value: '20%' },
    { key: 50, value: '50%' },
    { key: -1, value: 'Custom Penalty' },
  ];
  currencyPrefix: string;
  @Input() currency: string;
  private modalSubject: Subject<any>;

  constructor(
    private fb: FormBuilder,
    @Inject(DOCUMENT) private _document,
    private renderer: Renderer2,
    private globalService: GlobalService,
    private enumService: EnumService
  ) {}

  ngOnInit(): void {}

  setRating(rating: number): void {
    this.rating = rating;
    this.reasonForm.reset();
  }

  authorize() {
    let value;
    if (this.reasonForm.valid) {
      value = { rating: this.rating };

      if (this.rating === 1 || this.rating === 5) {
        value = { ...value, reason: this.reasonForm.controls.reason.value };
      }
    } else {
      this.reasonForm.controls.reason.markAsDirty();
    }

    let penalty = this.penaltyForm.controls.penalty.value;
    if (penalty === -1) {
      penalty = this.penaltyForm.controls.quantity.value;
    }

    if (this.penaltyForm.valid && penalty) {
      value = { ...value, ...this.penaltyForm.getRawValue(), ...{ penalty } };
    }

    this.closeModal(value);
  }

  public openModal(): Subject<any> {
    this.initReasonForm();
    this.initPenaltyForm();
    this.setCurrencyPrefix();
    this.showRatingModal = true;
    this.modalSubject = new Subject<any>();
    this.renderer.addClass(this._document.body, 'modal-opened');
    return this.modalSubject;
  }

  closeModal(value?: any) {
    this.modalSubject.next(value);
    this.modalSubject.complete();
    this.showRatingModal = false;
    this.renderer.removeClass(this._document.body, 'modal-opened');
  }

  dismissModal() {
    this.modalSubject.complete();
    this.showRatingModal = false;
    this.renderer.removeClass(this._document.body, 'modal-opened');
  }

  private reasonRequiredCondition(): boolean {
    return this.rating === 1 || this.rating === 5;
  }

  private initReasonForm() {
    this.reasonForm = this.fb.group({
      reason: new FormControl('', [
        Validators.maxLength(1000),
        ConditionalRequiredValidator(this.reasonRequiredCondition.bind(this)),
      ]),
    });
  }

  private initPenaltyForm() {
    this.penaltyForm = this.fb.group(
      {
        penalty: new FormControl(undefined),
        penalty_type: new FormControl(undefined),
        quantity: new FormControl(undefined),
        reason_of_penalty: new FormControl(
          undefined,
          Validators.maxLength(256)
        ),
      },
      {
        validators: [
          AllOrNothingRequiredValidator(['penalty', 'reason_of_penalty'], true),
        ],
      }
    );
  }

  public penaltyChanged(): void {
    this.updateValidation();
    this.penaltyForm.controls.penalty_type.patchValue(undefined);
  }

  public penaltyTypeChanged(): void {
    this.updateValidation();
    this.penaltyForm.controls.quantity.patchValue('');
  }

  /**
   * Update quantity validation rules
   * @param description
   */
  private updateValidation(): void {
    const penalty = this.penaltyForm.controls.penalty.value;
    const penaltyType = this.penaltyForm.controls.penalty_type.value;
    this.penaltyForm.controls.quantity.clearValidators();
    if (penalty === -1) {
      if (penaltyType === EntityPenaltyTypeEnum.FIXED) {
        this.penaltyForm.controls.quantity.setValidators([
          Validators.required,
          Validators.min(0),
        ]);
      } else {
        this.penaltyForm.controls.quantity.setValidators([
          Validators.required,
          Validators.min(0),
          Validators.max(100),
        ]);
      }
    }
    this.penaltyForm.controls.quantity.patchValue(undefined);
  }

  private setCurrencyPrefix(): void {
    this.currencyPrefix =
      getCurrencySymbol(
        this.enumService.getEnumMap('currencycode').get(this.currency),
        'wide'
      ) + ' ';
  }
}
