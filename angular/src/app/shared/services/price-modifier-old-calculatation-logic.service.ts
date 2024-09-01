import { EntityPriceModifier } from 'src/app/shared/classes/entity-item/entity-price-modifier';
import { PriceModifierCalculationLogic as AbstractPriceModifierCalculationLogic } from 'src/app/shared/classes/price-modifiers/price-modifier-calculation-logic';
import { PriceModifierQuantityTypeEnum } from 'src/app/shared/enums/price-modifier-quantity-type.enum';
import { EntityModifierDescriptionEnum } from '../enums/entity-modifier-description.enum';

export class PriceModifierOldCalculationLogic extends AbstractPriceModifierCalculationLogic {
  public orderLogic(
    modifiers: EntityPriceModifier[] = []
  ): EntityPriceModifier[] {
    return modifiers;
  }

  public calculateSubtotal(
    modifiers: EntityPriceModifier[] = [],
    subtotal = 0
  ): number {
    if (modifiers?.length > 0) {
      // separate percentage and fixed modifiers
      const percentageMods = modifiers.filter(
        m => m.quantity_type === PriceModifierQuantityTypeEnum.PERCENTAGE
      );
      const fixedMods = modifiers.filter(
        m => m.quantity_type === PriceModifierQuantityTypeEnum.FIXED
      );

      // calculate total percentage modifier
      let percentage = 100;
      for (const p of percentageMods) {
        const quantity = Number(p.quantity);
        percentage += p.type === 0 ? -quantity : quantity;
      }

      // calculate total fixed modifier
      let fixed = 0;
      for (const f of fixedMods) {
        const quantity = Number(f.quantity);
        fixed += f.type === 0 ? -quantity : quantity;
      }

      return subtotal + subtotal * ((percentage - 100) / 100) + fixed;
    } else {
      return subtotal;
    }
  }

  public calculateSubtotalWithoutTransactionFees(
    modifiers: EntityPriceModifier[] = [],
    subtotal = 0
  ): number {
    if (modifiers?.length > 0) {
      // separate percentage and fixed modifiers
      const percentageMods = modifiers.filter(
        m =>
          m.quantity_type === PriceModifierQuantityTypeEnum.PERCENTAGE &&
          m.description !== EntityModifierDescriptionEnum.TRANSACTION_FEE
      );
      const fixedMods = modifiers.filter(
        m =>
          m.quantity_type === PriceModifierQuantityTypeEnum.FIXED &&
          m.description !== EntityModifierDescriptionEnum.TRANSACTION_FEE
      );

      // calculate total percentage modifier
      let percentage = 100;
      for (const p of percentageMods) {
        const quantity = Number(p.quantity);
        percentage += p.type === 0 ? -quantity : quantity;
      }

      // calculate total fixed modifier
      let fixed = 0;
      for (const f of fixedMods) {
        const quantity = Number(f.quantity);
        fixed += f.type === 0 ? -quantity : quantity;
      }

      return subtotal + subtotal * ((percentage - 100) / 100) + fixed;
    } else {
      return subtotal;
    }
  }
}
