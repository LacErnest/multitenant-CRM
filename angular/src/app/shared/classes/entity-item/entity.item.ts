import { EntityPriceModifier } from './entity-price-modifier';
import { EntityModifierDescriptionEnum } from 'src/app/shared/enums/entity-modifier-description.enum';
import { PriceModifierQuantityTypeEnum } from 'src/app/shared/enums/price-modifier-quantity-type.enum';
export class EntityItem {
  created_at: string;
  description: string;
  entity_id: string;
  entity_type: string;
  id: string;
  order: number;
  price_modifiers: EntityPriceModifier[] = [];
  quantity: number;
  service_id: string;
  service_name: string;
  unit: string;
  unit_price: number;
  updated_at: string;
  xero_id: string;
  company_id: string;
  company_name: string;

  // front end props only
  showActions?: boolean;
  price?: number;

  exclude_from_price_modifiers?: boolean;

  constructor(partial?: Partial<EntityItem>) {
    Object.assign(this, partial);
  }

  calculatePrice() {
    const base = this.quantity * this.unit_price;

    if (this.price_modifiers?.length > 0) {
      // separate percentage and fixed modifiers
      const percentageMods = this.price_modifiers.filter(
        m =>
          m.quantity_type === PriceModifierQuantityTypeEnum.PERCENTAGE &&
          m.description !== EntityModifierDescriptionEnum.SPECIAL_DISCOUNT
      );
      const discountMods = this.price_modifiers.filter(
        m =>
          m.quantity_type === PriceModifierQuantityTypeEnum.PERCENTAGE &&
          m.description === EntityModifierDescriptionEnum.SPECIAL_DISCOUNT
      );
      const fixedMods = this.price_modifiers.filter(
        m => m.quantity_type === PriceModifierQuantityTypeEnum.FIXED
      );

      // calculate discrount amount
      // It depend on the discount order
      this.price = base;
      for (const p of discountMods) {
        const quantity = Number(p.quantity);
        const percentage = p.type === 0 ? 100 - quantity : 100 + quantity;
        this.price = this.price + this.price * ((percentage - 100) / 100);
      }

      // calculate total fixed modifier
      let fixed = 0;
      for (const f of fixedMods) {
        const quantity = Number(f.quantity);
        fixed += f.type === 0 ? -quantity : quantity;
      }
      this.price += fixed;

      // calculate total percentage modifier
      let percentage = 100;
      for (const p of percentageMods) {
        percentage += Number(p.quantity);
      }

      // Number() conversion required due to currency mask needing input type="text" to function

      this.price = this.price + this.price * ((percentage - 100) / 100);
    } else {
      this.price = base;
    }
  }
}
