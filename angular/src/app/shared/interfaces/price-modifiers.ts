import { EntityPriceModifier } from 'src/app/shared/classes/entity-item/entity-price-modifier';
import { EntityModifierDescriptionEnum } from 'src/app/shared/enums/entity-modifier-description.enum';
import { PriceModifierQuantityTypeEnum } from 'src/app/shared/enums/price-modifier-quantity-type.enum';
import { PriceModifierTypeEnum } from 'src/app/shared/enums/price-modifier-type.enum';

export interface PriceModifierUpdated {
  index: number;
  modifier: EntityPriceModifier;
}

export interface PriceModifierAvailable {
  description: EntityModifierDescriptionEnum;
  quantity_type: PriceModifierQuantityTypeEnum;
  type: PriceModifierTypeEnum;
}
