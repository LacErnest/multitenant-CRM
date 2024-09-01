import { EntityModifierDescriptionEnum } from 'src/app/shared/enums/entity-modifier-description.enum';
import { PriceModifierCalculationLogic as AbstractPriceModifierCalculationLogic } from 'src/app/shared/classes/price-modifiers/price-modifier-calculation-logic';
import { EntityPriceModifier } from 'src/app/shared/classes/entity-item/entity-price-modifier';
import { PriceModifierQuantityTypeEnum } from 'src/app/shared/enums/price-modifier-quantity-type.enum';

export class PriceModifierNewCalculationLogic extends AbstractPriceModifierCalculationLogic {
  public orderLogic(
    modifiers: EntityPriceModifier[] = []
  ): EntityPriceModifier[] {
    const modifierOrders = [
      EntityModifierDescriptionEnum.PROJECT_MANAGEMENT,
      EntityModifierDescriptionEnum.DIRECTOR_FEE,
      EntityModifierDescriptionEnum.SPECIAL_DISCOUNT,
      EntityModifierDescriptionEnum.TRANSACTION_FEE,
    ];
    modifiers.sort((a: EntityPriceModifier, b: EntityPriceModifier) => {
      const indexA = modifierOrders.indexOf(
        a.description as EntityModifierDescriptionEnum
      );
      const indexB = modifierOrders.indexOf(
        b.description as EntityModifierDescriptionEnum
      );

      if (indexA !== indexB) {
        return indexA - indexB;
      }
      if (a.created_at && b.created_at && a.created_at !== b.created_at) {
        return (
          new Date(a.created_at).getTime() - new Date(b.created_at).getTime()
        );
      }
      return a.order - b.order;
    });
    return modifiers;
  }

  public calculateSubtotal(
    modifiers: EntityPriceModifier[] = [],
    subtotal = 0,
    subtotalIncludingPriceModifiers = 0
  ): number {
    let subtotalModified = subtotalIncludingPriceModifiers;

    if (modifiers?.length > 0) {
      const subtotalExcludingPriceModifiers = subtotal - subtotalModified;
      const percentageAmount = this.applyPercentage(
        modifiers,
        subtotalModified
      );
      const fixedAmount = this.applyFixedAmount(modifiers);
      subtotalModified += percentageAmount + fixedAmount;
      subtotalModified += this.applyDiscount(modifiers, subtotalModified);
      subtotalModified += subtotalExcludingPriceModifiers;
      subtotalModified += this.applyTransactionFees(
        modifiers,
        subtotalModified
      );
    }

    return subtotalModified;
  }

  public calculateSubtotalWithoutTransactionFees(
    modifiers: EntityPriceModifier[] = [],
    subtotal = 0,
    subtotalIncludingPriceModifiers = 0
  ): number {
    let subtotalModified = subtotalIncludingPriceModifiers;
    if (modifiers?.length > 0) {
      const subtotalExcludingPriceModifiers = subtotal - subtotalModified;
      const percentageAmount = this.applyPercentage(
        modifiers,
        subtotalModified
      );
      const fixedAmount = this.applyFixedAmount(modifiers);
      subtotalModified += percentageAmount + fixedAmount;
      subtotalModified += this.applyDiscount(modifiers, subtotalModified);
      subtotalModified += subtotalExcludingPriceModifiers;
    }

    return subtotalModified;
  }

  /**
   * Apply trnsaction fees to sub total amount
   * @returns
   */
  private applyTransactionFees(
    modifiers: EntityPriceModifier[] = [],
    totalAmount = 0
  ): number {
    const transactionFeesMods = modifiers.filter(
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

  private applyDiscount(
    modifiers: EntityPriceModifier[] = [],
    totalAmount = 0
  ): number {
    const discountMods = modifiers.filter(
      m => m.description === EntityModifierDescriptionEnum.SPECIAL_DISCOUNT
    );
    let discountTotalAmount = totalAmount;
    for (const p of discountMods) {
      let quantity = Number(p.quantity);
      quantity = p.type === 0 ? -quantity : quantity;
      if (p.quantity_type === PriceModifierQuantityTypeEnum.PERCENTAGE) {
        discountTotalAmount += (discountTotalAmount * quantity) / 100;
      } else if (p.quantity_type === PriceModifierQuantityTypeEnum.FIXED) {
        discountTotalAmount += quantity;
      }
    }
    return discountTotalAmount - totalAmount;
  }

  /**
   * Apply fixed amount
   * @returns
   */
  private applyFixedAmount(modifiers: EntityPriceModifier[] = []): number {
    const fixedMods = modifiers.filter(
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
  private applyPercentage(
    modifiers: EntityPriceModifier[] = [],
    totalAmount = 0
  ): number {
    const percentageMods = modifiers.filter(
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
}
