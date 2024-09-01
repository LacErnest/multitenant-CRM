import { Pipe, PipeTransform } from '@angular/core';
import { EntityItem } from '../classes/entity-item/entity.item';

@Pipe({
  name: 'entityItemPrice',
})
export class EntityItemPricePipe implements PipeTransform {
  transform(item: EntityItem): number {
    const base = item.quantity * item.unit_price;

    if (item.price_modifiers?.length > 0) {
      // seperate percentage and fixed modifiers
      const percentageMods = item.price_modifiers.filter(
        m => m.quantity_type === 0
      );
      const fixedMods = item.price_modifiers.filter(m => m.quantity_type === 1);

      // calculate total percentage modifier
      let percentage = 100;
      for (const p of percentageMods) {
        percentage =
          p.type === 0 ? percentage - p.quantity : percentage + p.quantity;
      }

      // calculate total fixed modifier
      let fixed = 0;
      for (const f of fixedMods) {
        fixed = f.type === 0 ? fixed - f.quantity : fixed + f.quantity;
      }
      return base * (percentage / 100) + fixed;
    } else {
      return base;
    }
  }
}
