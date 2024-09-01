import { SalesPersonCommission } from './sales-person-commissions';

export interface ProjectCommission {
  base_commission: number;
  nb_commissions: number;
  sales_person: string;
  sales_person_id: string;
  current_commission_model: string;
  total_commission: number;
  commissions: SalesPersonCommission[];
}
