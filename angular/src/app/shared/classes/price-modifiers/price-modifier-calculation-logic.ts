import { EntityPriceModifier } from '../../classes/entity-item/entity-price-modifier';

export abstract class PriceModifierCalculationLogic {
  public abstract orderLogic(
    modifiers: EntityPriceModifier[]
  ): EntityPriceModifier[];

  public abstract calculateSubtotal(
    modifiers: EntityPriceModifier[],
    subTotal: number,
    exludingSubtotal: number
  ): number;

  public abstract calculateSubtotalWithoutTransactionFees(
    modifiers: EntityPriceModifier[],
    subTotal: number,
    exludingSubtotal: number
  ): number;
}
