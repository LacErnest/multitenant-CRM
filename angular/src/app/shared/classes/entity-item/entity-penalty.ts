export class EntityPenalty {
  id: string;
  amount: number;
  reason: string;
  showActions: boolean;

  constructor(partial?: Partial<EntityPenalty>) {
    Object.assign(this, partial);
  }
}
