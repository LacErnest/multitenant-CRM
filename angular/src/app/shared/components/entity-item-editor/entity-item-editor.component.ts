import { transition, trigger, useAnimation } from '@angular/animations';
import { CdkDragDrop, moveItemInArray } from '@angular/cdk/drag-drop';
import {
  Component,
  DoCheck,
  EventEmitter,
  Input,
  IterableDiffer,
  IterableDiffers,
  OnChanges,
  OnInit,
  Output,
  SimpleChanges,
  ViewChild,
} from '@angular/core';
import { EntityModifierDescriptionEnum } from 'src/app/shared/enums/entity-modifier-description.enum';
import { PriceModifierQuantityTypeEnum } from 'src/app/shared/enums/price-modifier-quantity-type.enum';
import { TablePreferenceType } from '../../enums/table-preference-type.enum';
import { PriceModifierTypeEnum } from 'src/app/shared/enums/price-modifier-type.enum';
import {
  menuEnterAnimation,
  menuLeaveAnimation,
} from 'src/app/shared/animations/browser-animations';
import { EntityPenalty } from 'src/app/shared/classes/entity-item/entity-penalty';
import { EntityPriceModifier } from 'src/app/shared/classes/entity-item/entity-price-modifier';
import { EntityItem } from 'src/app/shared/classes/entity-item/entity.item';
import { ConfirmModalComponent } from 'src/app/shared/components/confirm-modal/confirm-modal.component';
import { EntityItemModalComponent } from 'src/app/shared/components/entity-item-modal/entity-item-modal.component';
import { EntityModifierModalComponent } from 'src/app/shared/components/entity-modifier-modal/entity-modifier-modal.component';
import { EntityPenaltyModalComponent } from 'src/app/shared/components/entity-penalty-modal/entity-penalty-modal.component';
import {
  ItemsChangedOrder,
  ItemUpdated,
} from 'src/app/shared/interfaces/items';
import { PriceModifierUpdated } from 'src/app/shared/interfaces/price-modifiers';
import { EntityItemPricePipe } from 'src/app/shared/pipes/entity-item-price.pipe';
import { ceilNumberToTwoDecimals } from 'src/app/core/classes/helpers';
import { VatStatus } from '../../enums/vat-status.enum';
import { DownPaymentStatus } from '../../enums/down-payment-status.enum';
import { VatEditModalComponent } from '../vat-edit-modal/vat-edit-modal.component';
import { DownPaymentEditModalComponent } from '../down-payment-edit-modal/down-payment-edit-modal.component';
import { ActivatedRoute } from '@angular/router';
import { PriceModifierCalculationLogicService } from '../../services/price-modifier-calculatation-logic.service';
import { PriceModifierCalculationLogicValue } from '../../enums/price-modifier-calculation-logic-value.enum';
import { EntityPenaltyTypeEnum } from 'src/app/shared/enums/entity-penalty-type.enum';

@Component({
  selector: 'oz-finance-entity-line-editor',
  templateUrl: './entity-item-editor.component.html',
  styleUrls: ['./entity-item-editor.component.scss'],
  providers: [EntityItemPricePipe],
  animations: [
    trigger('menuAnimation', [
      transition(':enter', useAnimation(menuEnterAnimation)),
      transition(':leave', useAnimation(menuLeaveAnimation)),
    ]),
  ],
})
export class EntityItemEditorComponent implements OnInit, DoCheck, OnChanges {
  @Input() public currency: number;
  @Input() public items: EntityItem[];
  @Input() public modifiers: EntityPriceModifier[];
  @Input() public penalty: number;
  @Input() public penalty_type: EntityPenaltyTypeEnum;
  @Input() public penaltyReason: string;
  @Input() public isLoading: boolean;
  @Input() public legalEntityCountry: number;
  @Input() public countryForComparison: number;
  @Input() public isManualInput: boolean;
  @Input() public readOnly = false;
  @Input() public showPenalty = false;
  @Input() public resourceId: string;
  @Input() public addDefaultEntityFee = true;
  @Input() public taxRate: number;
  @Input() public down_payment = 10;
  @Input() public nonVatLiable = false;
  @Input() public isMasterInput = false;
  @Input() public isShadow = false;
  @Input() public vatStatus: number = VatStatus.DEFAULT;
  @Input() public downPaymentStatus: number = DownPaymentStatus.NEVER;
  @Input() public priceModifierService: PriceModifierCalculationLogicService;
  @Input() public entity: number;

  @Output() public itemAdded = new EventEmitter<EntityItem>();
  @Output() public itemUpdated = new EventEmitter<ItemUpdated>();
  @Output() public itemDeleted = new EventEmitter<number>();
  @Output() public itemsOrderChanged = new EventEmitter<ItemsChangedOrder>();
  @Output() public modifierAdded = new EventEmitter<EntityPriceModifier>();
  @Output() public modifierUpdated = new EventEmitter<PriceModifierUpdated>();
  @Output() public modifierDeleted = new EventEmitter<number>();
  @Output() public penaltyAdded = new EventEmitter<EntityPenalty>();
  @Output() public penaltyUpdated = new EventEmitter<any>(); // TODO: check
  @Output() public penaltyDeleted = new EventEmitter<void>();
  @Output() public vatStatusChanged = new EventEmitter<number>();
  @Output() public downPaymentStatusChanged = new EventEmitter<number>();
  @Output() public vatEdited = new EventEmitter<number>();
  @Output() public downPaymentEdited = new EventEmitter<number>();

  @ViewChild('entityItemModal')
  private entityItemModal: EntityItemModalComponent;
  @ViewChild('entityModifierModal')
  private entityModifierModal: EntityModifierModalComponent;
  @ViewChild('confirmModal') private confirmModal: ConfirmModalComponent;
  @ViewChild('penaltyModal') private penaltyModal: EntityPenaltyModalComponent;
  @ViewChild('vatEditModal') private vatEditModal: VatEditModalComponent;
  @ViewChild('downPaymentEditModal')
  private downPaymentEditModal: DownPaymentEditModalComponent;

  public subtotal: number;
  public subtotalIncludingPriceModifiers: number;
  public subtotalModified: number;
  public vat: number;
  public downPayment: number;
  public total: number;
  public vatActions = false;
  public downPaymentActions = false;
  public modActions = false;
  public transactionFeeModifier: EntityPriceModifier;
  public subtotalWithoutTransactionFees: number;
  public transactionFeesAmount: number;
  public isInvoice = false;

  private itemsIterableDiffer: IterableDiffer<any>;
  private modifiersIterableDiffer: IterableDiffer<any>;
  private itemModifiersIterableDiffers: IterableDiffer<any>[] = [];
  private settings: any;
  public constructor(
    private entityItemPricePipe: EntityItemPricePipe,
    protected route: ActivatedRoute,
    private iterableDiffers: IterableDiffers
  ) {}

  private static assignItemOrders(items: EntityItem[]): EntityItem[] {
    return items.map((item, i) => {
      item.order = i;
      return item;
    });
  }

  public get showVAT(): boolean {
    if (this.vatStatus === VatStatus.NEVER) {
      return false;
    }

    if (this.nonVatLiable || this.vatStatus === VatStatus.ALWAYS) {
      return true;
    }

    return this.countryForComparison === this.legalEntityCountry;
  }

  public get showTransactionFee(): boolean {
    if (this.vatStatus === VatStatus.NEVER) {
      return false;
    }

    if (this.nonVatLiable || this.vatStatus === VatStatus.ALWAYS) {
      return true;
    }

    return this.countryForComparison === this.legalEntityCountry;
  }

  public get showDownPayment(): boolean {
    if (this.downPaymentStatus === DownPaymentStatus.NEVER) {
      return false;
    }

    if (this.downPaymentStatus === DownPaymentStatus.ALWAYS) {
      return true;
    }
  }

  public ngOnInit(): void {
    this.itemsIterableDiffer = this.iterableDiffers
      .find(this.items)
      .create(null);
    this.modifiersIterableDiffer = this.iterableDiffers
      .find(this.modifiers)
      .create(null);
    this.getResolvedData();
    this.initializeItemModifiersIterableDiffersArray();
    this.calculateTotals();
  }

  public ngOnChanges(changes: SimpleChanges): void {
    if (changes.penalty) {
      this.calculateTotals();
    }

    if (
      changes.legalEntityCountry ||
      changes.countryForComparison ||
      changes.taxRate ||
      changes.down_payment
    ) {
      this.calculateTotals();
    }

    if (changes.modifiers) {
      this.calculateTotals();
    }
  }

  public ngDoCheck(): void {
    if (this.itemsIterableDiffer.diff(this.items)) {
      this.calculateTotals();
      this.initializeItemModifiersIterableDiffersArray();
    } else {
      for (let i = 0; i < this.itemModifiersIterableDiffers.length; i++) {
        if (
          this.itemModifiersIterableDiffers[i]?.diff(
            this.items[i]?.price_modifiers
          )
        ) {
          this.calculateTotals();
          break;
        }
      }
    }

    if (this.modifiersIterableDiffer.diff(this.modifiers)) {
      this.calculateTotals();
    }
  }

  private getResolvedData(): void {
    this.settings = this.route.snapshot.data;
    this.taxRate = this.taxRate || this.settings?.vat_default_value;
    this.down_payment = this.down_payment || 10;
    this.isInvoice = this.getEntityValue() === 'invoices';
  }

  public itemDropped(event: CdkDragDrop<EntityItem>): void {
    if (event.currentIndex !== event.previousIndex && !this.readOnly) {
      let reorderedItems = JSON.parse(JSON.stringify(this.items));

      reorderedItems = reorderedItems.map(i => {
        return new EntityItem(i);
      });

      moveItemInArray(reorderedItems, event.previousIndex, event.currentIndex);
      reorderedItems =
        EntityItemEditorComponent.assignItemOrders(reorderedItems);
      this.itemsOrderChanged.emit({
        index: event.currentIndex,
        items: reorderedItems,
      });
    }
  }

  public addItem(): void {
    this.entityItemModal
      .openModal(
        [],
        undefined,
        this.isManualInput,
        this.isMasterInput,
        this.resourceId,
        this.currency
      )
      .subscribe(value => {
        value.order = this.items.length;

        if (!this.items.length && this.addDefaultEntityFee) {
          const defaultEntityFee = new EntityPriceModifier({
            description: EntityModifierDescriptionEnum.PROJECT_MANAGEMENT,
            quantity: 10,
            quantity_type: PriceModifierQuantityTypeEnum.PERCENTAGE,
            type: PriceModifierTypeEnum.CHARGE,
          });

          this.modifiers.push(defaultEntityFee);
        }

        this.itemAdded.emit(value);
      });
  }

  public editItem(index: number, item: EntityItem): void {
    this.entityItemModal
      .openModal(
        [],
        item,
        this.isManualInput,
        this.isMasterInput,
        this.resourceId,
        this.currency
      )
      .subscribe(value => {
        this.itemUpdated.emit({ index, item: value });
      });
  }

  public deleteItem(index: number): void {
    this.confirmModal
      .openModal('Confirm', 'Are you sure you want to delete this item?')
      .subscribe(result => {
        if (result) {
          this.itemDeleted.emit(index);

          if (!this.items.length) {
            this.modifiers = [];
          }
        }
      });
  }

  public addEntityModifier(): void {
    this.entityModifierModal
      .openModal(this.modifiers, this.isMasterInput)
      .subscribe(value => {
        this.modifierAdded.emit(value);
        this.calculateTotals();
      });
  }

  public editEntityModifier(
    index: number,
    modifier: EntityPriceModifier
  ): void {
    this.entityModifierModal
      .openModal(this.modifiers, this.isMasterInput, modifier)
      .subscribe(value => {
        this.modifierUpdated.emit({ index, modifier: value });
        this.calculateTotals();
      });
  }

  public editTransactionFeesModifier(): void {
    const index = this.modifiers.findIndex(
      m => m.description === EntityModifierDescriptionEnum.TRANSACTION_FEE
    );
    this.entityModifierModal
      .openModal(
        this.modifiers,
        this.isMasterInput,
        this.transactionFeeModifier
      )
      .subscribe(value => {
        this.modifierUpdated.emit({ index, modifier: value });
      });
  }

  public deleteTransactionFeesModifier(): void {
    const index = this.modifiers.findIndex(
      m => m.description === EntityModifierDescriptionEnum.TRANSACTION_FEE
    );
    this.confirmModal
      .openModal(
        'Confirm',
        'Are you sure you want to delete this discount/charge?'
      )
      .subscribe(result => {
        if (result) {
          this.modifierDeleted.emit(index);
        }
      });
  }

  public deleteEntityModifier(index: number): void {
    this.confirmModal
      .openModal(
        'Confirm',
        'Are you sure you want to delete this discount/charge?'
      )
      .subscribe(result => {
        if (result) {
          this.modifierDeleted.emit(index);
        }
      });
  }

  public addItemModifier(item: EntityItem, index: number): void {
    this.entityModifierModal
      .openModal(item.price_modifiers, this.isMasterInput)
      .subscribe(value => {
        if (!item.price_modifiers) {
          item.price_modifiers = [];
        }

        item.price_modifiers.push(value);
        this.itemUpdated.emit({ index, item });
      });
  }

  public editItemModifier(
    item: EntityItem,
    index: number,
    modifier: EntityPriceModifier,
    modifierIndex: number
  ): void {
    this.entityModifierModal
      .openModal(item.price_modifiers, this.isMasterInput, modifier)
      .subscribe(value => {
        item.price_modifiers[modifierIndex] = value;
        this.itemUpdated.emit({ index, item });
      });
  }

  public deleteItemModifier(
    item: EntityItem,
    index: number,
    modifierIndex: number
  ): void {
    this.confirmModal
      .openModal(
        'Confirm',
        'Are you sure you want to delete this discount/charge?'
      )
      .subscribe(result => {
        if (result) {
          item.price_modifiers.splice(modifierIndex, 1);
          this.itemUpdated.emit({ index, item });
        }
      });
  }

  public addPenalty(): void {
    this.penaltyModal.openModal().subscribe(value => {
      this.penaltyAdded.emit(value);
    });
  }

  public editPenalty(penalty: EntityPenalty): void {
    this.penaltyModal.openModal(penalty).subscribe(value => {
      this.penaltyAdded.emit(value);
    });
  }

  public deletePenalty(): void {
    this.confirmModal
      .openModal('Confirm', 'Are you sure you want to delete this penalty?')
      .subscribe(result => {
        if (result) {
          this.penaltyDeleted.emit();
        }
      });
  }

  public addVat(): void {
    this.confirmModal
      .openModal('Confirm', 'Are you sure you want to add vat?')
      .subscribe(result => {
        if (result) {
          this.vatStatus = VatStatus.ALWAYS;
          this.vatEdited.emit(
            parseFloat(this.taxRate || this.settings?.vat_default_value)
          );
          this.calculateTotals();
          this.vatStatusChanged.emit(this.vatStatus);
        }
      });
  }

  public editVat(): void {
    this.vatEditModal.openModal(this.taxRate).subscribe(value => {
      this.taxRate = value;
      this.calculateTotals();
      this.vatEdited.emit(parseFloat(value));
    });
  }

  public deleteVat(): void {
    this.confirmModal
      .openModal('Confirm', 'Are you sure you want to remove the vat?')
      .subscribe(result => {
        if (result) {
          this.vatStatus = VatStatus.NEVER;
          this.calculateTotals();
          this.vatStatusChanged.emit(this.vatStatus);
        }
      });
  }

  public defaultVat(): void {
    this.confirmModal
      .openModal(
        'Confirm',
        'Are you sure you want set the vat back to default?'
      )
      .subscribe(result => {
        if (result) {
          this.vatStatus = VatStatus.DEFAULT;
          this.calculateTotals();
          this.vatStatusChanged.emit(this.vatStatus);
        }
      });
  }

  public addDownPayment(): void {
    this.confirmModal
      .openModal('Confirm', 'Are you sure you want to add down payment?')
      .subscribe(result => {
        if (result) {
          this.downPaymentStatus = DownPaymentStatus.ALWAYS;
          this.downPaymentEdited.emit(this.down_payment);
          this.calculateTotals();
          this.downPaymentStatusChanged.emit(this.downPaymentStatus);
        }
      });
  }

  public editDownPayment(): void {
    this.downPaymentEditModal.openModal(this.down_payment).subscribe(value => {
      this.down_payment = value;
      this.calculateTotals();
      this.downPaymentEdited.emit(parseFloat(value));
    });
  }

  public deleteDownPayment(): void {
    this.confirmModal
      .openModal('Confirm', 'Are you sure you want to remove the down payment?')
      .subscribe(result => {
        if (result) {
          this.downPaymentStatus = DownPaymentStatus.NEVER;
          this.calculateTotals();
          this.downPaymentStatusChanged.emit(this.downPaymentStatus);
        }
      });
  }

  public hasTransactionFeesModifier(): boolean {
    if (
      this.priceModifierService.getPriceModifierCalculationLogic() ===
      PriceModifierCalculationLogicValue.OLD_LOGIC
    ) {
      return false;
    }
    this.transactionFeeModifier = this.modifiers.find(
      m => m.description === EntityModifierDescriptionEnum.TRANSACTION_FEE
    );
    return !!this.transactionFeeModifier;
  }

  public getTransactionFeesModifierAmount(): number {
    const subtotalWithoutTransactionFees =
      this.priceModifierService.calculateSubtotalWithoutTransactionFees(
        this.modifiers,
        this.subtotal,
        this.subtotalIncludingPriceModifiers
      );
    return (
      this.transactionFeeModifier.quantity * subtotalWithoutTransactionFees
    );
  }

  private calculateTotals(): void {
    this.isLoading = true;
    this.calculateItemPrices();
    this.calculateSubTotal();
    this.calculateTotal();
    this.calculateSubtotalWithoutTransactionFees();
    this.calculateTransactionFeesAmount();
    if (!this.isManualInput) {
      this.ceilTotals();
    }

    this.isLoading = false;
  }

  public getPriceModifiers(): EntityPriceModifier[] {
    if (this.hasTransactionFeesModifier()) {
      return this.modifiers.filter(
        m => m.description !== EntityModifierDescriptionEnum.TRANSACTION_FEE
      );
    }
    return this.modifiers;
  }

  private calculateSubtotalWithoutTransactionFees(): void {
    this.subtotalWithoutTransactionFees =
      this.priceModifierService.calculateSubtotalWithoutTransactionFees(
        this.modifiers,
        this.subtotal,
        this.subtotalIncludingPriceModifiers
      );
  }

  private calculateTransactionFeesAmount(): void {
    if (this.hasTransactionFeesModifier()) {
      this.transactionFeesAmount =
        (this.subtotalWithoutTransactionFees *
          this.transactionFeeModifier.quantity) /
        100;
    } else {
      this.transactionFeesAmount = 0;
    }
  }

  private ceilTotals(): void {
    this.subtotal = ceilNumberToTwoDecimals(this.subtotal);
    this.subtotalIncludingPriceModifiers = ceilNumberToTwoDecimals(
      this.subtotalIncludingPriceModifiers
    );
    // this.subtotalModified = ceilNumberToTwoDecimals(this.subtotalModified);
    this.downPayment = ceilNumberToTwoDecimals(this.downPayment);
    this.vat = ceilNumberToTwoDecimals(this.vat);
    this.total = ceilNumberToTwoDecimals(this.total);
  }

  private calculateTotal(): void {
    const newTotal = this.transactionFeesAmount + this.subtotalModified;
    this.total = this.showVAT ? newTotal + this.vat : newTotal;
    this.total = this.showDownPayment
      ? this.total - this.downPayment
      : this.total;
  }

  private calculateSubTotal(): void {
    this.subtotal = 0;
    this.subtotalIncludingPriceModifiers = 0;
    this.subtotalModified = 0;
    this.vat = 0;
    this.downPayment = 0;

    if (this.items?.length > 0) {
      for (const item of this.items) {
        this.subtotal += item.price;
        if (!item.exclude_from_price_modifiers) {
          this.subtotalIncludingPriceModifiers += item.price;
        }
      }

      this.subtotalModified = this.priceModifierService.calculateSubtotal(
        this.modifiers,
        this.subtotal,
        this.subtotalIncludingPriceModifiers
      );

      if (this.penalty) {
        if (this.penalty_type === EntityPenaltyTypeEnum.FIXED) {
          this.subtotalModified = this.subtotalModified - this.penalty;
        } else {
          this.subtotalModified =
            this.subtotalModified * ((100 - this.penalty) / 100);
        }
      }

      if (this.showDownPayment) {
        this.downPayment = this.subtotalModified * (this.down_payment / 100);
      }

      if (this.showVAT) {
        this.vat =
          (this.subtotalModified +
            this.transactionFeesAmount +
            this.downPayment) *
          (this.taxRate / 100);
      }
    }
  }

  /**
   * Apply trnsaction fees to sub total amount
   * @returns
   */
  private applyTransactionFees(totalAmount = 0): number {
    const transactionFeesMods = this.modifiers.filter(
      m =>
        m.quantity_type === PriceModifierQuantityTypeEnum.PERCENTAGE &&
        m.description === EntityModifierDescriptionEnum.TRANSACTION_FEE
    );
    let percentage = 0;
    // calculate transaction fees
    for (const p of transactionFeesMods) {
      percentage += Number(p.quantity);
    }
    return (totalAmount * percentage) / 100;
  }

  private applyDiscount(totalAmount = 0): number {
    const discountMods = this.modifiers.filter(
      m => m.description === EntityModifierDescriptionEnum.SPECIAL_DISCOUNT
    );
    let subtotalModified = totalAmount;
    // calculate discrount amount
    // It depend on the discount order
    for (const p of discountMods) {
      if (p.quantity_type === PriceModifierQuantityTypeEnum.PERCENTAGE) {
        const quantity = Number(p.quantity);
        const percentage = p.type === 0 ? -quantity : quantity;
        subtotalModified += subtotalModified * (percentage / 100);
      } else if (p.quantity_type === PriceModifierQuantityTypeEnum.FIXED) {
        const quantity = Number(p.quantity);
        const fixed = p.type === 0 ? -quantity : quantity;
        subtotalModified += fixed;
      }
    }
    return subtotalModified;
  }

  /**
   * Apply fixed amount
   * @returns
   */
  private applyFixedAmount(): number {
    const fixedMods = this.modifiers.filter(
      m =>
        m.quantity_type === PriceModifierQuantityTypeEnum.FIXED &&
        !(
          m.description === EntityModifierDescriptionEnum.SPECIAL_DISCOUNT ||
          m.description === EntityModifierDescriptionEnum.TRANSACTION_FEE
        )
    );
    let fixed = 0;
    for (const f of fixedMods) {
      const quantity = Number(f.quantity);
      fixed += f.type === 0 ? -quantity : quantity;
    }
    return fixed;
  }

  /**
   * calculate percentage amount
   * @returns
   */
  private applyPercentage(totalAmount = 0): number {
    const percentageMods = this.modifiers.filter(
      m =>
        m.quantity_type === PriceModifierQuantityTypeEnum.PERCENTAGE &&
        m.description !== EntityModifierDescriptionEnum.SPECIAL_DISCOUNT &&
        m.description !== EntityModifierDescriptionEnum.TRANSACTION_FEE
    );
    let percentage = 0;
    for (const p of percentageMods) {
      percentage += Number(p.quantity);
    }
    return (totalAmount * percentage) / 100;
  }

  private calculateItemPrices(): void {
    for (const item of this.items) {
      item.calculatePrice();
    }
  }

  private initializeItemModifiersIterableDiffersArray(): void {
    this.items.forEach((item, index) => {
      this.itemModifiersIterableDiffers[index] = this.iterableDiffers
        .find(item?.price_modifiers)
        .create(null);
    });
  }

  private getEntityValue(): string {
    return TablePreferenceType[this.entity].toLowerCase();
  }
  public isFixedPenalty(): boolean {
    return this.penalty_type === EntityPenaltyTypeEnum.FIXED;
  }
}
