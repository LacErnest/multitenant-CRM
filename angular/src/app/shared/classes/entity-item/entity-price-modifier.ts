export class EntityPriceModifier {
  created_at: string;
  description: string;
  entity_id: string;
  entity_type: string;
  id: string;
  quantity: number;
  quantity_type: number;
  type: number;
  updated_at: string;
  xero_id: string;
  order: number;
  // front end props only
  showActions?: boolean;

  constructor(partial?: Partial<EntityPriceModifier>) {
    Object.assign(this, partial);
  }
}
