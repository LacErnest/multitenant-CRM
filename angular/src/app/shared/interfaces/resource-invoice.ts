export interface ResourceInvoice {
  contact?: string;
  contact_id?: string;
  customer?: string;
  customer_id?: string;
  date: string;
  download?: boolean;
  due_date: string;
  id: string;
  number: string;
  order: string;
  order_id: string;
  project_id?: string;
  purchase_order: string;
  purchase_order_id: string;
  resource: string;
  resource_id: string;
  status: number;
  type?: number;
  resource_not_vat_liable: boolean;
}
