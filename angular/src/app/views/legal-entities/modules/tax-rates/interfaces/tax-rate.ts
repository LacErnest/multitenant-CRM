export interface TaxRate {
  id?: string;
  tax_rate: number;
  start_date: string;
  end_date: string;
  xero_sales_tax_type: string;
  xero_purchase_tax_type: string;
}
