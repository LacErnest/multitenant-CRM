import {
  animate,
  animateChild,
  group,
  query,
  style,
  transition,
  trigger,
} from '@angular/animations';
import { DOCUMENT, getCurrencySymbol } from '@angular/common';
import { Component, Inject, OnInit, Renderer2 } from '@angular/core';
import {
  FormBuilder,
  FormControl,
  FormGroup,
  Validators,
} from '@angular/forms';
import { Subject } from 'rxjs';
import { EnumService } from 'src/app/core/services/enum.service';
import { GlobalService } from 'src/app/core/services/global.service';
import { EntityPriceModifier } from 'src/app/shared/classes/entity-item/entity-price-modifier';
import {
  DiscountOptionEnum,
  getDiscountOptions,
} from 'src/app/shared/enums/discount-option.enum';
import { EntityModifierDescriptionEnum } from 'src/app/shared/enums/entity-modifier-description.enum';
import { UserRole } from 'src/app/shared/enums/user-role.enum';
import { DiscountOption } from 'src/app/shared/interfaces/discount-option';
import { PriceModifierAvailable } from 'src/app/shared/interfaces/price-modifiers';
import { SharedService } from 'src/app/shared/services/shared.service';
import { environment } from 'src/environments/environment';
import { CurrencyPrefix } from '../../enums/currency-prefix.enum';
import { ActivatedRoute } from '@angular/router';

@Component({
  selector: 'oz-finance-entity-modifier-modal',
  templateUrl: './entity-modifier-modal.component.html',
  styleUrls: ['./entity-modifier-modal.component.scss'],
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
export class EntityModifierModalComponent implements OnInit {
  public currencyPrefix: string = CurrencyPrefix.USD;
  public showEntityModifierModal = false;
  public modifierForm: FormGroup;
  public modifier: EntityPriceModifier;

  public availableModifierOptions: PriceModifierAvailable[] = [];
  public selectedModifier: EntityPriceModifier;

  public showDiscountOptions = false;
  public showPercentageOptions = false;
  public discountOptions: DiscountOption[] = getDiscountOptions();
  public quantityTypeOptions = [
    { key: 0, value: 'Percentage' },
    { key: 1, value: 'Fixed' },
  ];
  public showQuantityTypeOptions = false;
  public isFixed = false;
  public isMasterInput = false;

  private modalSubject: Subject<EntityPriceModifier>;
  private modifierOptions = [];
  public priceModifierSetting: any = {};
  public defaultMaxValues: {
    [x in EntityModifierDescriptionEnum]: { default: number; max: number };
  };

  constructor(
    @Inject(DOCUMENT) private _document,
    protected route: ActivatedRoute,
    private fb: FormBuilder,
    private globalService: GlobalService,
    private sharedService: SharedService,
    private enumService: EnumService,
    private renderer: Renderer2
  ) {}

  ngOnInit(): void {
    this.getPriceModifiers();
    this.getResolvedData();
  }

  public submitModifierForm(): void {
    if (this.modifierForm.valid) {
      this.closeModal(
        new EntityPriceModifier({
          ...this.modifier,
          ...this.modifierForm.getRawValue(),
        })
      );
    }
  }

  private getResolvedData(): void {
    this.priceModifierSetting = this.route.snapshot.data.settings;
    this.defaultMaxValues = {
      [EntityModifierDescriptionEnum.SPECIAL_DISCOUNT]: {
        default: this.priceModifierSetting?.special_discount_default_value,
        max: this.priceModifierSetting?.special_discount_max_value,
      },
      [EntityModifierDescriptionEnum.DIRECTOR_FEE]: {
        default: this.priceModifierSetting?.director_fee_default_value,
        max: this.priceModifierSetting?.director_fee_max_value,
      },
      [EntityModifierDescriptionEnum.PROJECT_MANAGEMENT]: {
        default: this.priceModifierSetting?.project_management_default_value,
        max: this.priceModifierSetting?.project_management_max_value,
      },
      [EntityModifierDescriptionEnum.TRANSACTION_FEE]: {
        default: this.priceModifierSetting?.transaction_fee_default_value,
        max: this.priceModifierSetting?.transaction_fee_max_value,
      },
    };
  }

  public modifierChanged({
    quantity_type,
    type,
    description,
  }: EntityPriceModifier): void {
    const {
      quantity_type: quantity_type_control,
      type: type_control,
      description: description_control,
    } = this.modifierForm.controls;

    quantity_type_control.patchValue(quantity_type);
    type_control.patchValue(type);
    description_control.patchValue(description);
    switch (description) {
      case EntityModifierDescriptionEnum.SPECIAL_DISCOUNT:
        if (this.isMasterInput) {
          this.showQuantityTypeOptions = false;
          this.isFixed = false;
          this.modifierForm.controls.quantity_type.patchValue(0);
        } else {
          this.showQuantityTypeOptions = true;
        }
        this.showDiscountOptions = true;
        this.showPercentageOptions = false;

        break;
      case EntityModifierDescriptionEnum.PROJECT_MANAGEMENT:
      case EntityModifierDescriptionEnum.DIRECTOR_FEE:
        this.showDiscountOptions = false;
        this.showQuantityTypeOptions = false;
        this.showPercentageOptions = true;
        this.isFixed = false;
        this.modifierForm.controls.quantity_type.patchValue(0);
        break;
      case EntityModifierDescriptionEnum.TRANSACTION_FEE:
        this.showDiscountOptions = false;
        this.showQuantityTypeOptions = false;
        this.showPercentageOptions = true;
        this.isFixed = false;
        this.modifierForm.controls.quantity_type.patchValue(0);

        break;
    }
    this.updateValidation();
    if (
      !this.modifier ||
      (this.modifier && this.modifier.description !== description_control.value)
    ) {
      if (this.showPercentageOptions) {
        this.modifierForm.controls.quantity.patchValue(
          this.defaultMaxValues[description].default
        );
      } else {
        this.modifierForm.controls.quantity.patchValue(0);
      }
    }
  }

  public quantityTypeChanged(event): void {
    this.isFixed = event.key === 1;
    this.modifierForm.controls.quantity_type.patchValue(event.key);
    if (!this.isFixed) {
      this.modifierForm.controls.quantity.patchValue(
        this.defaultMaxValues[this.modifierForm.controls.description.value]
          .default
      );
    } else {
      this.modifierForm.controls.quantity.patchValue(0);
    }
    this.updateValidation();
  }

  public openModal(
    existingModifiers: EntityPriceModifier[],
    isMasterInput: boolean,
    modifier?: EntityPriceModifier
  ): Subject<EntityPriceModifier> {
    this.modifier = modifier;
    this.isMasterInput = isMasterInput;
    this.initModifierForm();
    this.patchValueModifierForm();
    this.setCurrencyPrefix();
    this.setAvailableModifierOptions(existingModifiers, modifier);
    this.selectedModifier = this.modifier;

    if (this.modifier) this.modifierChanged(this.modifier);

    this.renderer.addClass(this._document.body, 'modal-opened');
    this.showEntityModifierModal = true;
    this.modalSubject = new Subject<EntityPriceModifier>();
    return this.modalSubject;
  }

  public closeModal(value?: EntityPriceModifier): void {
    this.modalSubject.next(value);
    this.modalSubject.complete();
    this.showEntityModifierModal = false;
    this.renderer.removeClass(this._document.body, 'modal-opened');
  }

  public dismissModal(): void {
    this.modalSubject.complete();
    this.showEntityModifierModal = false;
    this.renderer.removeClass(this._document.body, 'modal-opened');
  }

  // TODO: check filter statement
  private setAvailableModifierOptions(
    existingModifiers: EntityPriceModifier[],
    modifier?: EntityPriceModifier
  ): void {
    this.availableModifierOptions = this.modifierOptions;
    for (const mod of existingModifiers) {
      this.availableModifierOptions = this.availableModifierOptions.filter(
        m => {
          if (
            mod.description === EntityModifierDescriptionEnum.TRANSACTION_FEE &&
            m.description === mod.description &&
            modifier?.description !==
              EntityModifierDescriptionEnum.TRANSACTION_FEE
          ) {
            return false;
          }
          return true;
        }
      );
    }
  }

  private initModifierForm(): void {
    this.modifierForm = this.fb.group({
      type: new FormControl(0, Validators.required),
      quantity: new FormControl(undefined, [
        Validators.required,
        Validators.min(0),
      ]),
      quantity_type: new FormControl(undefined, Validators.required),
      description: new FormControl(undefined, Validators.required),
    });
  }

  private patchValueModifierForm(): void {
    if (this.modifier) {
      this.modifierForm.patchValue(this.modifier);
      this.isFixed = this.modifierForm.controls.quantity_type.value === 1;
    }
  }

  private setCurrencyPrefix(): void {
    const isAdmin = this.globalService.getUserRole() === UserRole.ADMINISTRATOR;
    const currencyCode = isAdmin
      ? environment.currency
      : this.globalService.userCurrency;

    this.currencyPrefix =
      getCurrencySymbol(
        this.enumService.getEnumMap('currencycode').get(currencyCode),
        'wide'
      ) + ' ';
  }

  private getPriceModifiers(): void {
    this.sharedService.getPriceModifiers().subscribe(response => {
      this.modifierOptions = response;
      this.availableModifierOptions = response;
    });
  }

  /**
   * Update quantity validation rules
   * @param description
   */
  private updateValidation(): void {
    const description = this.modifierForm.get('description').value;
    switch (description) {
      case EntityModifierDescriptionEnum.SPECIAL_DISCOUNT:
        if (this.isFixed) {
          this.modifierForm
            .get('quantity')
            .setValidators([Validators.required, Validators.min(0)]);
        } else {
          this.modifierForm
            .get('quantity')
            .setValidators([
              Validators.required,
              Validators.min(0),
              Validators.max(this.defaultMaxValues[description].max),
            ]);
        }
        break;
      case EntityModifierDescriptionEnum.PROJECT_MANAGEMENT:
      case EntityModifierDescriptionEnum.DIRECTOR_FEE:
      case EntityModifierDescriptionEnum.TRANSACTION_FEE:
        this.modifierForm
          .get('quantity')
          .setValidators([
            Validators.required,
            Validators.min(0),
            Validators.max(this.defaultMaxValues[description].max),
          ]);
        break;
    }
  }
}
