export interface SalesPersonCommission {
  commission: number;
  commission_percentage: number;
  gross_margin: number;
  order_id: string;
  invoice_id: string;
  order: string;
  invoice: string;
  paid_at?: string;
  paid_value: number;
  status: string;
  total_price: number;
}
