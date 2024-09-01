import { EntityItem } from 'src/app/shared/classes/entity-item/entity.item';

export interface ItemUpdated {
  index: number;
  item: EntityItem;
}

export interface ItemsChangedOrder {
  index: number;
  items: EntityItem[];
}
