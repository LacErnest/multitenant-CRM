import { GlobalService } from 'src/app/core/services/global.service';
import { EntityPenalty } from 'src/app/shared/classes/entity-item/entity-penalty';
import { EntityPriceModifier } from 'src/app/shared/classes/entity-item/entity-price-modifier';
import { EntityItem } from 'src/app/shared/classes/entity-item/entity.item';
import { EntityModifierDescriptionEnum } from 'src/app/shared/enums/entity-modifier-description.enum';
import { UserRole } from 'src/app/shared/enums/user-role.enum';
import { Invoice, Order, Quote } from 'src/app/shared/interfaces/entities';
import {
  ItemsChangedOrder,
  ItemUpdated,
} from 'src/app/shared/interfaces/items';
import { PriceModifierUpdated } from 'src/app/shared/interfaces/price-modifiers';
import { environment } from 'src/environments/environment';
import { PriceModifierCalculationLogicService } from 'src/app/shared/services/price-modifier-calculatation-logic.service';
import { PriceModifierCalculationLogicValue } from '../../enums/price-modifier-calculation-logic-value.enum';
import { AppStateService } from 'src/app/shared/services/app-state.service';
export abstract class EntityItemContainerBase {
  public currency: number;
  public costCurrency: number;
  public items: EntityItem[] = [];
  public modifiers: EntityPriceModifier[] = [];
  public penalty: EntityPenalty;
  protected updateEnabled = false;
  public priceModifierCalculationLogicValue: PriceModifierCalculationLogicValue =
    PriceModifierCalculationLogicValue.NEW_LOGIC;

  protected constructor(
    protected globalService: GlobalService,
    public priceModifierLogicService: PriceModifierCalculationLogicService
  ) {
    this.currency = this.getDefaultCurrency();
    this.costCurrency = this.getDefaultCurrency();
    this.priceModifierLogicService.init(
      this.priceModifierCalculationLogicValue
    );
  }

  protected get entityModifiersShouldBeDeleted(): boolean {
    return !this.items.length && this.modifiers.length > 0;
  }

  protected entityModifiersShouldBeCreated(
    entity: Quote | Order | Invoice
  ): boolean {
    /**
     * NOTE: if `modifiers` have defaultEntityFee and it's not saved yet - POST request is needed
     */
    const hasDefaultModifierSaved = entity.price_modifiers.find(
      m => m.description === EntityModifierDescriptionEnum.PROJECT_MANAGEMENT
    );

    return entity && !hasDefaultModifierSaved && this.modifiers.length > 0;
  }

  protected getDefaultCurrency(): number {
    return this.globalService.getUserRole() === UserRole.ADMINISTRATOR
      ? environment.currency
      : this.globalService.userCurrency;
  }

  public itemAdded(item: EntityItem): void {
    if (this.updateEnabled) {
      this.createItem(item);
    } else {
      this.items.push(item);
    }
  }

  public itemEdited(event: ItemUpdated): void {
    if (this.updateEnabled) {
      this.updateItem(event.item);
    } else {
      this.items[event.index] = event.item;
    }
  }

  public itemDeleted(index: number): void {
    if (this.updateEnabled) {
      this.deleteItem(this.items[index]);
    } else {
      this.items.splice(index, 1);
      this.checkUnsavedModifiersForDeletion();
    }
  }

  public checkUnsavedModifiersForDeletion(): void {
    if (!this.items.length) {
      this.modifiers = [];
    }
  }

  public itemsOrderChanged(event: ItemsChangedOrder): void {
    if (this.updateEnabled) {
      this.orderItems(event.items, event.index);
    } else {
      this.items = event.items;
    }
  }

  public modifierAdded(modifier: EntityPriceModifier): void {
    modifier.order = this.modifiers.length;
    if (this.updateEnabled) {
      this.createModifier(modifier);
      this.modifiers.push(modifier);
    } else {
      this.modifiers.push(modifier);
    }
    this.sortModifiers();
  }

  protected sortModifiers(): void {
    this.priceModifierLogicService.orderLogic(this.modifiers);
  }

  protected setPriorityOrder(): void {
    this.modifiers.forEach((modifier, index) => {
      modifier.order = this.modifiers.length - index;
    });
  }

  public modifierEdited(event: PriceModifierUpdated): void {
    if (this.updateEnabled) {
      this.updateModifier(event.modifier);
    } else {
      this.modifiers[event.index] = event.modifier;
    }
    this.sortModifiers();
  }

  public modifierDeleted(index: number): void {
    if (this.updateEnabled) {
      this.deleteModifier(this.modifiers[index]);
    } else {
      this.modifiers.splice(index, 1);
    }
    this.sortModifiers();
  }

  public penaltyAdded(penalty): void {
    this.penalty = penalty;
  }

  public penaltyEdited(penalty): void {
    this.penalty = penalty;
  }

  public penaltyDeleted(): void {
    this.penalty = undefined;
  }

  public vatEdited(percentage): void {
    this.editEntityVat(percentage);
  }

  public vatStatusChanged(status): void {
    this.changeEntityVatStatus(status);
  }

  public downPaymentEdited(percentage): void {
    this.editEntityDownPayment(percentage);
  }

  public downPaymentStatusChanged(status): void {
    this.changeEntityDownPaymentStatus(status);
  }

  protected removeItemsFromList(
    idsToRemove: string[],
    isUnsavedEntity = false
  ): void {
    if (idsToRemove.length > 1) {
      this.items = [];
    } else {
      this.items.splice(
        this.items.findIndex(i => i.id === idsToRemove[0]),
        1
      );
    }

    isUnsavedEntity && this.removePriceModifiers();
  }

  protected removePriceModifiers(): void {
    this.modifiers = [];
  }

  protected removePriceModifier(modifierId: string): void {
    this.modifiers.splice(
      this.modifiers.findIndex(i => i.id === modifierId),
      1
    );
  }

  protected replacePriceModifier(modifier: EntityPriceModifier): void {
    const index = this.modifiers.findIndex(i => i.id === modifier.id);
    this.modifiers.splice(index, 1, new EntityPriceModifier(modifier));
  }

  protected updateEntityModifiers(modifier: EntityPriceModifier): void {
    const index = this.modifiers.findIndex(
      m => m.description === modifier.description
    );

    if (index >= 0) {
      this.modifiers[index] = modifier;
    } else {
      this.modifiers.push(new EntityPriceModifier(modifier));
    }
  }

  protected abstract createItem(item: EntityItem): void;

  protected abstract updateItem(item: EntityItem): void;

  protected abstract orderItems(items: EntityItem[], index: number): void;

  protected abstract deleteItem(item: EntityItem): void;

  protected abstract createModifier(modifier: EntityPriceModifier): void;

  protected abstract updateModifier(modifier: EntityPriceModifier): void;

  protected abstract deleteModifier(modifier: EntityPriceModifier): void;

  protected abstract changeEntityVatStatus(status: number): void;

  protected abstract editEntityVat(percentage: number): void;

  protected abstract changeEntityDownPaymentStatus(status: number): void;

  protected abstract editEntityDownPayment(percentage: number): void;
}
