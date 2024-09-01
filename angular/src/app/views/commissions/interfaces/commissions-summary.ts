export interface CommissionsSummary {
  companies: {
    showActions: boolean; // todo only for test
    id: string;
    name: string;
    initials: string;
    currency: number;
    total_company_commission: number;
    customers: {
      id: string;
      name: string;
      expanded: boolean;
      total_customer_commission: number;
      quotes: {
        project_id: string;
        number: string;
        id: string;
        commissions: {
          base_commission: number;
          commission_percentage: number;
          commission: number;
          paid_value: number;
          gross_margin: number;
          total: number;
          sales_person: string;
          paid_at: string;
        }[];
      }[];
    }[];
  }[];
  base_commission: number;
  total_all_companies_commission: number;
  count_company_commissions: number;
}
