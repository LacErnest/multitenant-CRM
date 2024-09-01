import { Injectable } from '@angular/core';
import { EntityPriceModifier } from 'src/app/shared/classes/entity-item/entity-price-modifier';
import { PriceModifierCalculationLogicValue } from '../enums/price-modifier-calculation-logic-value.enum';
import { PriceModifierNewCalculationLogic } from './price-modifier-new-calculatation-logic.service';
import { PriceModifierOldCalculationLogic } from './price-modifier-old-calculatation-logic.service';
import { PriceModifierCalculationLogic as AbstractPriceModifierCalculationLogic } from '../classes/price-modifiers/price-modifier-calculation-logic';

@Injectable({
  providedIn: 'root',
})
export class PriceModifierCalculationLogicService {
  private calculationLogics: {
    [x in PriceModifierCalculationLogicValue]: typeof AbstractPriceModifierCalculationLogic;
  } = {
    [PriceModifierCalculationLogicValue.NEW_LOGIC]:
      PriceModifierNewCalculationLogic,
    [PriceModifierCalculationLogicValue.OLD_LOGIC]:
      PriceModifierOldCalculationLogic,
  };

  private logic: PriceModifierCalculationLogicValue;

  private calculationLogic: any;

  constructor() {
    //
  }

  /**
   * Initialize service provider and select the appropriate service
   * @param logic
   * @param priceModifiers
   * @param subtotal
   * @param subtotalIncludingPriceModifiers
   * @returns
   */
  public init(
    logic?: PriceModifierCalculationLogicValue
  ): PriceModifierCalculationLogicService {
    let FistCalculationLogic: any = null;
    if (logic === null || logic === undefined) {
      logic = PriceModifierCalculationLogicValue.NEW_LOGIC;
    }
    if (this.calculationLogics[logic]) {
      FistCalculationLogic = this.calculationLogics[logic];
    } else {
      FistCalculationLogic = Object.values(this.calculationLogics)[0];
    }
    this.calculationLogic = new FistCalculationLogic([], 0, 0);
    this.logic = logic;
    return this;
  }

  /**
   * Calculate subtotal
   * @returns
   */
  public calculateSubtotal(
    modifiers: EntityPriceModifier[] = [],
    subtotal = 0,
    subtotalIncludingPriceModifiers = 0
  ): number {
    if (this.logic === PriceModifierCalculationLogicValue.NEW_LOGIC) {
      return this.calculationLogic.calculateSubtotalWithoutTransactionFees(
        modifiers,
        subtotal,
        subtotalIncludingPriceModifiers
      );
    } else {
      return this.calculationLogic.calculateSubtotal(
        modifiers,
        subtotal,
        subtotalIncludingPriceModifiers
      );
    }
  }

  /**
   * Calculate subtotal
   * @returns
   */
  public calculateSubtotalWithoutTransactionFees(
    modifiers: EntityPriceModifier[] = [],
    subtotal = 0,
    subtotalIncludingPriceModifiers = 0
  ): number {
    return this.calculationLogic.calculateSubtotalWithoutTransactionFees(
      modifiers,
      subtotal,
      subtotalIncludingPriceModifiers
    );
  }

  /**
   * Ordering price modifiers
   * @returns
   */
  public orderLogic(modifiers: EntityPriceModifier[]): EntityPriceModifier[] {
    return this.calculationLogic.orderLogic(modifiers);
  }

  public getPriceModifierCalculationLogic(): PriceModifierCalculationLogicValue {
    return this.logic;
  }
}
